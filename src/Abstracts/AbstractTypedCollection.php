<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Abstracts;

use AndyDefer\DomainStructures\Collections\Core\TypedCollection;
use AndyDefer\DomainStructures\Enums\PhpType;
use AndyDefer\DomainStructures\Interfaces\Transformable;
use AndyDefer\DomainStructures\Interfaces\TypedCollectionInterface;
use AndyDefer\DomainStructures\Normalizers\NormalizerChain;
use AndyDefer\DomainStructures\Utils\DataObject;
use ArrayIterator;
use Closure;
use InvalidArgumentException;
use Traversable;
use UnitEnum;

/**
 * Abstract type-safe collection for records, value objects, data DTOs, enums, and scalar values.
 *
 * @template TValue of object|string|int|float|bool
 *
 * @implements TypedCollectionInterface<TValue>
 * @implements Transformable<static>
 */
abstract class AbstractTypedCollection implements \ArrayAccess, \JsonSerializable, Transformable, TypedCollectionInterface
{
    /** @var array<TValue> */
    protected array $items = [];

    /** @var array<string> */
    private array $allowedTypes = [];

    private static ?array $allowedTypesList = null;

    // ==================== CONSTRUCTOR & VALIDATION ====================

    final protected static function getAllowedTypesList(): array
    {
        return self::$allowedTypesList ??= PhpType::getAllowedTypesList();
    }

    protected function __construct(string ...$types)
    {
        $this->validateTypes($types);
        $this->allowedTypes = $types;
    }

    private function validateTypes(array $types): void
    {
        if (empty($types)) {
            throw new InvalidArgumentException('At least one type must be provided');
        }

        foreach ($types as $type) {
            if (PhpType::isValidType($type)) {
                continue;
            }

            throw new InvalidArgumentException(sprintf(
                'Type "%s" is not allowed. Must be %s',
                $type,
                PhpType::getAllowedTypeDescription()
            ));
        }
    }

    // ==================== TYPE MATCHING HELPERS ====================

    private function matchesAllowedType(mixed $value): bool
    {
        $valueType = PhpType::fromValue($value);
        $valueTypeName = $valueType->getNormalizedName();

        foreach ($this->allowedTypes as $allowedType) {
            if (in_array($allowedType, PhpType::getScalarTypeNames(), true) && $valueTypeName === $allowedType) {
                return true;
            }

            if ($allowedType === DataObject::class && $value instanceof DataObject) {
                return true;
            }

            if ($valueType->isEnum()) {
                if ($allowedType === UnitEnum::class || $value instanceof $allowedType) {
                    return true;
                }

                continue;
            }

            if (class_exists($allowedType) && $value instanceof $allowedType) {
                return true;
            }
        }

        return false;
    }

    private function isAllowedObjectType(PhpType $type): bool
    {
        return $type->isEnum() || $type->isRecord() || $type->isValueObject() ||
            $type->isData() || $type->isCollection() || $type->isDataObject();
    }

    private function validateItem(mixed $item): void
    {
        $itemType = PhpType::fromValue($item);

        if (! $this->matchesAllowedType($item)) {
            if ($itemType->isObject() && ! $this->isAllowedObjectType($itemType)) {
                throw new InvalidArgumentException(sprintf(
                    'Object of type "%s" is not allowed. Only DataObject, UnitEnum, AbstractRecord, AbstractValueObject, AbstractData, and TypedCollection are allowed.',
                    $item::class
                ));
            }

            throw new InvalidArgumentException(sprintf(
                'Expected type(s) %s, got %s',
                implode('|', $this->allowedTypes),
                $itemType->getDisplayName($item)
            ));
        }
    }

    // ==================== CORE COLLECTION METHODS ====================

    final public function add(DataObject|UnitEnum|AbstractRecord|AbstractValueObject|AbstractData|self|int|string|float|bool|null ...$items): static
    {
        foreach ($items as $item) {
            $this->validateItem($item);
            $this->items[] = $item;
        }

        return $this;
    }

    final public function all(): static
    {
        $result = new static(...$this->allowedTypes);
        $result->items = $this->items;

        return $result;
    }

    final public function toArray(): array
    {
        return $this->items;
    }

    final public function getAllowedTypes(): array
    {
        return $this->allowedTypes;
    }

    final public function count(): int
    {
        return count($this->items);
    }

    final public function isEmpty(): bool
    {
        return empty($this->items);
    }

    final public function isNotEmpty(): bool
    {
        return ! $this->isEmpty();
    }

    // ==================== NORMALIZATION ====================

    /**
     * Normalize the collection to an array.
     *
     * @param  bool  $includeNulls  Whether to include null values
     * @return array<int, mixed>
     */
    public function normalize(bool $includeNulls = true): array
    {
        $normalizer = NormalizerChain::get();
        $result = [];

        foreach ($this->items as $item) {
            $normalized = $normalizer->normalize($item);

            if (! $includeNulls && $normalized === null) {
                continue;
            }

            $result[] = $normalized;
        }

        return $result;
    }

    // ==================== TRANSFORMATION METHODS ====================


    /**
     * Transform each item in the collection into a new collection.
     *
     * Applies the callback to every item in the collection and returns a new
     * collection containing the transformed values. The new collection's allowed
     * type is automatically determined from the transformed items.
     *
     * @template TReturn
     * @param Closure(TValue): TReturn $callback
     * @return TypedCollection<TReturn>
     */
    final public function map(Closure $callback): TypedCollection
    {
        if (empty($this->items)) {
            // Conserver les types d'origine sur une collection vide
            return new TypedCollection(...$this->allowedTypes);
        }

        /** @var array<TReturn> $mappedItems */
        $mappedItems = array_map($callback, $this->items);

        if (empty($mappedItems)) {
            return new TypedCollection(...$this->allowedTypes);
        }

        // Collect unique types from mapped items
        $uniqueTypes = [];
        foreach ($mappedItems as $item) {
            $type = PhpType::fromValue($item)->getClassString();
            $uniqueTypes[$type] = $type;
        }
        $uniqueTypes = array_values($uniqueTypes);

        // Créer une nouvelle collection TYPEDCOLLECTION (pas static)
        $result = new TypedCollection(...$uniqueTypes);

        foreach ($mappedItems as $item) {
            $result->add($item);
        }

        return $result;
    }

    final public function filter(Closure $callback): static
    {
        $result = new static(...$this->allowedTypes);
        $result->items = array_values(array_filter($this->items, $callback));

        return $result;
    }

    final public function sort(int $flags = SORT_REGULAR): static
    {
        $items = $this->items;
        sort($items, $flags);

        $result = new static(...$this->allowedTypes);
        $result->items = $items;

        return $result;
    }

    final public function reverse(): static
    {
        $result = new static(...$this->allowedTypes);
        $result->items = array_reverse($this->items);

        return $result;
    }

    // ==================== QUERY METHODS ====================

    final public function contains(DataObject|UnitEnum|AbstractRecord|AbstractValueObject|AbstractData|TypedCollectionInterface|int|string|float|bool|null $value): bool
    {
        return in_array($value, $this->items, true);
    }

    final public function find(Closure $callback): mixed
    {
        foreach ($this->items as $item) {
            if ($callback($item)) {
                return $item;
            }
        }

        return null;
    }

    final public function every(Closure $callback): bool
    {
        foreach ($this->items as $item) {
            if (! $callback($item)) {
                return false;
            }
        }

        return true;
    }

    final public function some(Closure $callback): bool
    {
        foreach ($this->items as $item) {
            if ($callback($item)) {
                return true;
            }
        }

        return false;
    }

    final public function reduce(Closure $callback, mixed $initial = null): mixed
    {
        return array_reduce($this->items, $callback, $initial);
    }

    // ==================== ITERATION METHODS ====================

    final public function each(Closure $callback): static
    {
        foreach ($this->items as $item) {
            $callback($item);
        }

        return $this;
    }

    final public function merge(TypedCollectionInterface $collection): static
    {
        $result = new static(...$this->allowedTypes);
        $result->items = array_merge($this->items, $collection->toArray());

        return $result;
    }

    // ==================== ARRAY ACCESS METHODS ====================

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->items[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->items[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->validateItem($value);

        if ($offset === null) {
            $this->items[] = $value;
        } else {
            $this->items[$offset] = $value;
        }
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->items[$offset]);
    }

    // ==================== ITERATOR METHODS ====================

    final public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }

    // ==================== JSON SERIALIZATION ====================

    final public function jsonSerialize(): array
    {
        return $this->items;
    }

    // ==================== MAGIC METHODS ====================

    public function __toString(): string
    {
        return json_encode($this->normalize(false), JSON_THROW_ON_ERROR);
    }

    final public function __clone()
    {
        $this->items = array_map(
            fn($item) => is_object($item) ? clone $item : $item,
            $this->items
        );
    }

    // ==================== TRANSFORMABLE IMPLEMENTATION ====================

    final public static function from(mixed $source): static
    {
        if ($source instanceof static) {
            return $source;
        }

        $allowedType = static::getAllowedTypesList()[0] ?? null;

        if ($allowedType === null) {
            throw new InvalidArgumentException('Cannot determine type to hydrate');
        }

        if (! is_iterable($source)) {
            throw new InvalidArgumentException(sprintf(
                'Cannot hydrate %s from non-iterable source',
                static::class
            ));
        }

        $collection = new static;

        foreach ($source as $item) {
            if ($item instanceof $allowedType) {
                $collection->add($item);

                continue;
            }

            if (in_array($allowedType, PhpType::getScalarTypeNames(), true)) {
                $collection->add($item);

                continue;
            }

            if (is_subclass_of($allowedType, Transformable::class)) {
                $collection->add($allowedType::from($item));

                continue;
            }

            throw new InvalidArgumentException(sprintf(
                'Cannot hydrate %s: item of type %s cannot be converted to %s',
                static::class,
                is_object($item) ? $item::class : gettype($item),
                $allowedType
            ));
        }

        return $collection;
    }
}
