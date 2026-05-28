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

    /** @var array<string, array<string>> */
    private static array $cachedAllowedTypes = [];

    // ==================== CONSTRUCTOR & VALIDATION ====================
    final protected static function getAllowedTypesList(): array
    {
        return static::$allowedTypesList ??= PhpType::getAllowedTypesList();
    }

    protected function __construct(string ...$types)
    {
        if (empty($types)) {
            throw new InvalidArgumentException(
                'At least one allowed type must be provided.'
            );
        }

        $this->validateTypes($types);
        $this->allowedTypes = $types;

        self::$cachedAllowedTypes[static::class] = $types;
    }

    final protected static function getStoredAllowedTypes(): array
    {
        $class = static::class;

        if (isset(self::$cachedAllowedTypes[$class])) {
            return self::$cachedAllowedTypes[$class];
        }

        try {
            $reflection = new \ReflectionClass($class);
            $tempInstance = $reflection->newInstance();
            $types = $tempInstance->getAllowedTypes();
            self::$cachedAllowedTypes[$class] = $types;

            return $types;
        } catch (\ArgumentCountError $e) {
            throw new InvalidArgumentException(sprintf(
                'Cannot determine allowed types for %s. '.
                    'Please create an instance first before calling from(): new %s(...)',
                $class,
                $class
            ));
        }
    }

    private function validateTypes(array $types): void
    {
        foreach ($types as $type) {
            if (class_exists($type) && (new \ReflectionClass($type))->isAbstract()) {
                throw new InvalidArgumentException(sprintf(
                    'Type "%s" is abstract. Collections cannot be created with abstract types. Use concrete classes instead.',
                    $type
                ));
            }

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

    // ==================== TRANSFORMATION METHODS ====================

    /**
     * @template TReturn
     *
     * @param  Closure(TValue): TReturn  $callback
     * @return TypedCollection<TReturn>
     */
    final public function map(Closure $callback): TypedCollection
    {
        if (empty($this->items)) {
            return new TypedCollection(...$this->allowedTypes);
        }

        /** @var array<TReturn> $mappedItems */
        $mappedItems = array_map($callback, $this->items);

        if (empty($mappedItems)) {
            return new TypedCollection(...$this->allowedTypes);
        }

        $uniqueTypes = [];
        foreach ($mappedItems as $item) {
            $type = PhpType::fromValue($item)->getClassString($item);
            $uniqueTypes[$type] = $type;
        }
        $uniqueTypes = array_values($uniqueTypes);

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

    final public function sortBy(Closure|string $callback, int $flags = SORT_REGULAR, bool $descending = false): static
    {
        $items = $this->items;

        if (is_string($callback)) {
            $property = $callback;
            $callback = fn ($item) => is_object($item) ? ($item->$property ?? null) : null;
        }

        $values = array_map($callback, $items);

        if ($descending) {
            arsort($values, $flags);
        } else {
            asort($values, $flags);
        }

        $sortedItems = [];
        foreach (array_keys($values) as $key) {
            $sortedItems[] = $items[$key];
        }

        $result = new static(...$this->allowedTypes);
        $result->items = $sortedItems;

        return $result;
    }

    final public function usort(Closure $callback): static
    {
        $items = $this->items;
        usort($items, $callback);

        $result = new static(...$this->allowedTypes);
        $result->items = $items;

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
        return json_encode(NormalizerChain::get()->normalize($this), JSON_THROW_ON_ERROR);
    }

    final public function __clone()
    {
        $this->items = array_map(
            fn ($item) => is_object($item) ? clone $item : $item,
            $this->items
        );
    }

    // ==================== HYDRATION HELPERS (EXTRACTED LOGIC) ====================

    /**
     * Validates that allowed types are not empty.
     *
     * @param  array<string>  $allowedTypes
     *
     * @throws InvalidArgumentException
     */
    private static function validateAllowedTypes(array $allowedTypes): void
    {
        if (empty($allowedTypes)) {
            throw new InvalidArgumentException('Cannot determine type to hydrate');
        }
    }

    /**
     * Extracts explicit type from item if present.
     *
     * @param  array<string>  $allowedTypes
     * @return string|null The explicit type or null if not found
     *
     * @throws InvalidArgumentException
     */
    private static function getExplicitType(mixed $item, array $allowedTypes): ?string
    {
        $itemArray = is_object($item) ? (array) $item : $item;

        if (is_array($itemArray) && isset($itemArray['_type'])) {
            $explicitType = $itemArray['_type'];
            if (! in_array($explicitType, $allowedTypes, true)) {
                throw new InvalidArgumentException(sprintf(
                    'Type "%s" specified in "_type" is not allowed. Allowed: %s',
                    $explicitType,
                    implode('|', $allowedTypes)
                ));
            }

            return $explicitType;
        }

        return null;
    }

    /**
     * Detects the appropriate type for an item when multiple types are allowed.
     *
     * @param  array<string>  $allowedTypes
     * @param  int|null  $itemIndex  Optional item index for better error messages
     * @return string The detected type
     *
     * @throws InvalidArgumentException
     */
    private static function detectTypeForItem(mixed $item, array $allowedTypes, ?int $itemIndex = null): string
    {
        $matchedTypes = [];

        foreach ($allowedTypes as $allowedType) {
            try {
                self::convertItem($item, $allowedType);
                $matchedTypes[] = $allowedType;
            } catch (\Exception $e) {
                continue;
            }
        }

        $prefix = $itemIndex !== null ? "item #{$itemIndex}: " : '';

        if (count($matchedTypes) > 1) {
            throw new InvalidArgumentException(sprintf(
                'Ambiguous %sdata can be hydrated by multiple types [%s]. '.
                    'Please specify the type using a "_type" key in the source data.',
                $prefix,
                implode('|', $matchedTypes)
            ));
        }

        if (empty($matchedTypes)) {
            throw new InvalidArgumentException(sprintf(
                'Cannot hydrate %sdata: could not be converted to any allowed type [%s]',
                $prefix,
                implode('|', $allowedTypes)
            ));
        }

        return $matchedTypes[0];
    }

    /**
     * Processes a single item for hydration.
     *
     * @param  array<string>  $allowedTypes
     * @param  int|null  $itemIndex  Optional item index for better error messages
     * @return mixed The converted item
     *
     * @throws InvalidArgumentException
     */
    private static function processItemForHydration(mixed $item, array $allowedTypes, ?int $itemIndex = null): mixed
    {
        // Check for explicit type first
        $explicitType = self::getExplicitType($item, $allowedTypes);

        if ($explicitType !== null) {
            return self::convertItem($item, $explicitType);
        }

        // Single type case
        if (count($allowedTypes) === 1) {
            return self::convertItem($item, $allowedTypes[0]);
        }

        // Multiple types - auto detection
        $detectedType = self::detectTypeForItem($item, $allowedTypes, $itemIndex);

        return self::convertItem($item, $detectedType);
    }

    // ==================== TRANSFORMABLE IMPLEMENTATION ====================

    protected static function convertItem(mixed $item, string $targetType): mixed
    {
        if ($item instanceof $targetType) {
            return $item;
        }

        if (in_array($targetType, PhpType::getScalarTypeNames(), true)) {
            return $item;
        }

        if (is_subclass_of($targetType, Transformable::class)) {
            return $targetType::from($item);
        }

        throw new \RuntimeException(sprintf('Cannot convert to type %s', $targetType));
    }

    final public static function from(mixed $source): static
    {
        if ($source instanceof static) {
            return $source;
        }

        $allowedTypes = static::getStoredAllowedTypes();
        self::validateAllowedTypes($allowedTypes);

        if (! is_iterable($source)) {
            throw new InvalidArgumentException(sprintf(
                'Cannot hydrate %s from non-iterable source',
                static::class
            ));
        }

        $collection = new static(...$allowedTypes);
        $itemIndex = 0;

        foreach ($source as $item) {
            $itemIndex++;
            $convertedItem = self::processItemForHydration($item, $allowedTypes, $itemIndex);
            $collection->add($convertedItem);
        }

        return $collection;
    }

    /**
     * Creates a collection instance from a JSON string.
     *
     * @param  string  $json  JSON string representing an array of items
     *
     * @throws InvalidArgumentException If JSON is invalid or source cannot be hydrated
     */
    final public static function fromJson(string $json): static
    {
        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidArgumentException(sprintf(
                'Invalid JSON: %s',
                json_last_error_msg()
            ));
        }

        // Vérifier que c'est un tableau (pour une collection)
        if (! is_array($data)) {
            throw new InvalidArgumentException(sprintf(
                'JSON must decode to an array for collection hydration. Got %s.',
                gettype($data)
            ));
        }

        return static::from($data);
    }

    /**
     * Hydrates a collection of sources into a typed collection.
     *
     * @template TCollection of AbstractTypedCollection
     *
     * @param  iterable<mixed>  $sources
     * @param  class-string<TCollection>  $collectionClass
     * @return TCollection
     *
     * @throws InvalidArgumentException
     */
    public static function collect(iterable $sources, string $collectionClass = TypedCollection::class): AbstractTypedCollection
    {
        if (! is_subclass_of($collectionClass, AbstractTypedCollection::class)) {
            throw new InvalidArgumentException(sprintf(
                'Collection class "%s" must extend %s',
                $collectionClass,
                AbstractTypedCollection::class
            ));
        }

        $allowedTypes = static::getStoredAllowedTypes();
        self::validateAllowedTypes($allowedTypes);

        $collection = new $collectionClass(...$allowedTypes);

        foreach ($sources as $item) {
            $convertedItem = self::processItemForHydration($item, $allowedTypes);
            $collection->add($convertedItem);
        }

        return $collection;
    }
}
