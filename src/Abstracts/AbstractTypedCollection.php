<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Abstracts;

use AndyDefer\DomainStructures\Enums\NormalizeMode;
use AndyDefer\DomainStructures\Interfaces\TypedCollectionInterface;
use AndyDefer\DomainStructures\Normalizers\NormalizerChain;
use AndyDefer\DomainStructures\Traits\ArrayableTrait;
use AndyDefer\DomainStructures\TypeDetectors\TypeDetectorChain;
use Closure;
use InvalidArgumentException;
use stdClass;
use UnitEnum;

/**
 * Abstract type-safe collection for records, value objects, data DTOs, enums, and scalar values.
 *
 * @template TValue of object|string|int|float|bool
 */
abstract class AbstractTypedCollection implements TypedCollectionInterface
{
    use ArrayableTrait;

    /** @var array<TValue> */
    protected array $items = [];

    /** @var array<class-string<AbstractRecord>|class-string<AbstractValueObject>|class-string<AbstractData>|class-string<UnitEnum>|string> */
    private array $allowedTypes = [];

    private const TYPE_MAPPING = [
        'integer' => 'int',
        'double' => 'float',
        'string' => 'string',
        'boolean' => 'bool',
        'NULL' => 'null',
        'object' => 'object',
    ];

    private static ?array $scalarTypes = null;

    private static ?array $allowedTypesList = null;

    private static function getScalarTypes(): array
    {
        if (self::$scalarTypes === null) {
            self::$scalarTypes = array_values(self::TYPE_MAPPING);
        }

        return self::$scalarTypes;
    }

    /**
     * Get the list of all allowed types that can be stored in a collection.
     *
     * @return array<int, string>
     */
    final protected static function getAllowedTypesList(): array
    {
        if (self::$allowedTypesList === null) {
            self::$allowedTypesList = [
                'int',
                'string',
                'float',
                'bool',
                'null',
                UnitEnum::class,
                AbstractRecord::class,
                AbstractValueObject::class,
                AbstractData::class,
                self::class,
                stdClass::class,
            ];
        }

        return self::$allowedTypesList;
    }

    public function __construct(...$types)
    {
        $this->validateTypes($types);
        $this->allowedTypes = $types;
    }

    private static function normalizeType(string $type): string
    {
        return self::TYPE_MAPPING[$type] ?? $type;
    }

    private function validateTypes(array $types): void
    {
        if (empty($types)) {
            throw new InvalidArgumentException('At least one type must be provided');
        }

        foreach ($types as $type) {
            $this->validateSingleType($type);
        }
    }

    private function validateSingleType(string $type): void
    {
        $isValid = match (true) {
            in_array($type, self::getScalarTypes(), true) => true,
            $type === UnitEnum::class || is_subclass_of($type, UnitEnum::class) => true,
            $type === self::class || (class_exists($type) && is_subclass_of($type, self::class)) => true,
            $type === AbstractRecord::class || (class_exists($type) && is_subclass_of($type, AbstractRecord::class)) => true,
            $type === AbstractValueObject::class || (class_exists($type) && is_subclass_of($type, AbstractValueObject::class)) => true,
            $type === AbstractData::class || (class_exists($type) && is_subclass_of($type, AbstractData::class)) => true,
            $type === stdClass::class => true,
            default => false,
        };

        if ($isValid) {
            return;
        }

        if (! class_exists($type)) {
            throw new InvalidArgumentException(sprintf('Type "%s" is not a valid class', $type));
        }

        throw new InvalidArgumentException(sprintf(
            'Type "%s" is not allowed. Must be a scalar, Enum, Record, ValueObject, Data, TypedCollection, or stdClass',
            $type
        ));
    }

    private function matchesAllowedType(mixed $value): bool
    {
        $valueType = self::normalizeType(gettype($value));

        foreach ($this->allowedTypes as $allowedType) {
            $result = match (true) {
                $valueType === $allowedType => true,
                $allowedType === UnitEnum::class && $value instanceof UnitEnum => true,
                is_subclass_of($allowedType, UnitEnum::class) && $value instanceof $allowedType => true,
                $allowedType === self::class && $value instanceof self => true,
                $allowedType === stdClass::class && $value instanceof stdClass => true,
                $value instanceof $allowedType => true,
                default => false,
            };

            if ($result) {
                return true;
            }
        }

        return false;
    }

    private function getValueTypeName(mixed $value): string
    {
        return TypeDetectorChain::get()->getTypeName($value);
    }

    private function validateItem(mixed $item): void
    {
        if ($item instanceof UnitEnum) {
            if (! $this->matchesAllowedType($item)) {
                $allowedTypesStr = implode('|', $this->allowedTypes);
                throw new InvalidArgumentException(sprintf(
                    'Expected type(s) %s, got %s',
                    $allowedTypesStr,
                    $this->getValueTypeName($item)
                ));
            }

            return;
        }

        if (is_object($item) && ! ($item instanceof stdClass) && ! ($item instanceof AbstractRecord) && ! ($item instanceof AbstractValueObject) && ! ($item instanceof AbstractData) && ! ($item instanceof self)) {
            throw new InvalidArgumentException(sprintf(
                'Object of type "%s" is not allowed. Only stdClass, UnitEnum, AbstractRecord, AbstractValueObject, AbstractData, and TypedCollection are allowed.',
                $item::class
            ));
        }

        if (! $this->matchesAllowedType($item)) {
            $allowedTypesStr = implode('|', $this->allowedTypes);
            throw new InvalidArgumentException(sprintf(
                'Expected type(s) %s, got %s',
                $allowedTypesStr,
                $this->getValueTypeName($item)
            ));
        }
    }

    final public function add(int|string|float|bool|null|UnitEnum|AbstractRecord|AbstractValueObject|AbstractData|self|stdClass ...$items): static
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
        $result->add(...$this->items);

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

    public function normalize(NormalizeMode $mode = NormalizeMode::ARRAY, bool $includeNulls = true): array|string
    {
        $result = [];
        foreach ($this->items as $item) {
            $normalized = NormalizerChain::get()->normalize($item, $mode, $includeNulls);
            if (! $includeNulls && $normalized === null) {
                continue;
            }
            $result[] = $normalized;
        }

        return $mode === NormalizeMode::JSON ? json_encode($result, JSON_THROW_ON_ERROR) : $result;
    }

    /**
     * Transform each item in the collection into a new collection.
     *
     * Applies the callback to every item in the collection and returns a new
     * collection containing the transformed values. The new collection's allowed
     * type is automatically determined from the first transformed item.
     *
     * @template TReturn
     *
     * @param  Closure(TValue): TReturn  $callback  The transformation function
     * @return static<TReturn> New collection with transformed items
     *
     * @throws InvalidArgumentException If callback returns an invalid type
     */
    final public function map(Closure $callback): static
    {
        if (empty($this->items)) {
            return new static(...$this->allowedTypes);
        }

        $mappedItems = [];
        foreach ($this->items as $item) {
            $mappedItems[] = $callback($item);
        }

        if (empty($mappedItems)) {
            return new static(...$this->allowedTypes);
        }

        $firstResult = $mappedItems[0];
        $detector = TypeDetectorChain::get()->detect($firstResult);
        $returnType = $detector->getClassString($firstResult);

        /** @var static<TReturn> $result */
        $result = new static($returnType);
        foreach ($mappedItems as $item) {
            /** @var int|string|float|bool|null|UnitEnum|AbstractRecord|AbstractValueObject|AbstractData|AbstractTypedCollection|stdClass $item */
            $result->add($item);
        }

        return $result;
    }

    /**
     * Sort the collection in ascending order.
     *
     * Returns a new collection with items sorted in ascending order.
     * For mixed-type collections, the behavior may be unpredictable.
     * Use sortBy() for specific property sorting on objects.
     *
     * @param  int  $flags  Sorting flags (SORT_REGULAR, SORT_NUMERIC, SORT_STRING, etc.)
     * @return static<TValue> New collection with sorted items
     */
    final public function sort(int $flags = SORT_REGULAR): static
    {
        $items = $this->items;
        sort($items, $flags);

        $result = new static(...$this->allowedTypes);
        $result->items = $items;

        return $result;
    }

    final public function filter(Closure $callback): static
    {
        $result = new static(...$this->allowedTypes);
        $result->items = array_values(array_filter($this->items, $callback));

        return $result;
    }

    final public function contains(int|string|float|bool|null|UnitEnum|AbstractRecord|AbstractValueObject|AbstractData|TypedCollectionInterface|stdClass $value): bool
    {
        return in_array($value, $this->items, true);
    }

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

    final public function reduce(Closure $callback, mixed $initial = null): mixed
    {
        return array_reduce($this->items, $callback, $initial);
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

    final public function reverse(): static
    {
        $result = new static(...$this->allowedTypes);
        $result->items = array_reverse($this->items);

        return $result;
    }

    final public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->items);
    }

    final public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function __toString(): string
    {
        return $this->normalize(NormalizeMode::JSON);
    }

    final public function __clone()
    {
        $newItems = [];
        foreach ($this->items as $item) {
            $newItems[] = $item instanceof self ? clone $item : $item;
        }
        $this->items = $newItems;
    }
}
