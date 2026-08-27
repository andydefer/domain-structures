<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Abstracts;

use AndyDefer\DomainStructures\Collections\Core\TypedCollection;
use AndyDefer\DomainStructures\Enums\PhpType;
use AndyDefer\DomainStructures\Hydration\Hydrator;
use AndyDefer\DomainStructures\Interfaces\Transformable;
use AndyDefer\DomainStructures\Interfaces\TypedCollectionInterface;
use AndyDefer\DomainStructures\Normalizers\NormalizerChain;
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
abstract class AbstractTypedCollection implements TypedCollectionInterface
{
    /** @var array<TValue> */
    protected array $items = [];

    /** @var array<string> */
    private array $allowedTypes = [];

    private static ?array $allowedTypesList = null;

    /** @var array<string, array<string>> */
    private static array $cachedAllowedTypes = [];

    // ==================== CONSTRUCTOR & VALIDATION ====================
    protected static function getAllowedTypesList(): array
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

            if ($allowedType === AbstractDataObject::class && $value instanceof AbstractDataObject) {
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
                    'Object of type "%s" is not allowed. Only AbstractDataObject, UnitEnum, AbstractRecord, AbstractValueObject, AbstractData, and TypedCollection are allowed.',
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

    public function add(AbstractDataObject|UnitEnum|AbstractRecord|AbstractValueObject|AbstractData|TypedCollectionInterface|int|string|float|bool|null ...$items): static
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
    final public function map(Closure $callback): TypedCollectionInterface
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

    /**
     * Maps items and preserves the same collection type.
     *
     * This method attempts to keep the same collection class.
     * An exception is thrown if mapped items are not compatible
     * with the original collection's allowed types.
     *
     * @template TReturn of TValue
     *
     * @param  Closure(TValue): TReturn  $callback
     *
     * @throws InvalidArgumentException If mapped items are incompatible
     */
    final public function mapPreserveType(Closure $callback): static
    {
        if (empty($this->items)) {
            $result = new static(...$this->allowedTypes);
            $result->items = [];

            return $result;
        }

        /** @var array<TReturn> $mappedItems */
        $mappedItems = array_map($callback, $this->items);

        $result = new static(...$this->allowedTypes);

        /** @var TReturn $item */
        foreach ($mappedItems as $item) {
            /** @var mixed $item */
            $result->add($item);
        }

        return $result;
    }

    /**
     * Maps items to a specific target collection type.
     *
     * This method creates a new collection of the specified type
     * with the mapped items. The target collection must extend
     * AbstractTypedCollection.
     *
     * @template TReturn
     * @template TCollection of AbstractTypedCollection
     *
     * @param  Closure(TValue): TReturn  $callback
     * @param  class-string<TypedCollectionInterface>  $targetCollectionClass
     * @param  mixed  ...$args  Constructor arguments for the target collection
     */
    final public function mapToType(Closure $callback, string $targetCollectionClass, mixed ...$args): TypedCollectionInterface
    {
        if (empty($this->items)) {
            return new $targetCollectionClass(...$args);
        }

        /** @var array<TReturn> $mappedItems */
        $mappedItems = array_map($callback, $this->items);

        $result = new $targetCollectionClass(...$args);

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

    final public function contains(AbstractDataObject|UnitEnum|AbstractRecord|AbstractValueObject|AbstractData|TypedCollectionInterface|int|string|float|bool|null $value): bool
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

    final public function first(): mixed
    {
        return $this->items[0] ?? null;
    }

    final public function last(): mixed
    {
        if (empty($this->items)) {
            return null;
        }

        return $this->items[count($this->items) - 1] ?? null;
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

    // ==================== HYDRATION HELPERS ====================

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
            // Vérifier si ce sont des types scalaires
            $scalarTypes = PhpType::getScalarTypeNames();
            $hasScalarAmbiguity = ! empty(array_intersect($matchedTypes, $scalarTypes));

            if ($hasScalarAmbiguity) {
                throw new InvalidArgumentException(sprintf(
                    'Ambiguous %svalue could be interpreted as multiple scalar types [%s]. '.
                        'Please ensure the collection is configured with a single scalar type or use distinct types.',
                    $prefix,
                    implode('|', $matchedTypes)
                ));
            }

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
        // Si l'item est déjà du bon type, on le retourne directement
        if ($item instanceof $targetType) {
            return $item;
        }

        // Pour les types scalaires, conversion standard PHP
        if (in_array($targetType, PhpType::getScalarTypeNames(), true)) {
            return match ($targetType) {
                'int' => (int) $item,
                'string' => (string) $item,
                'float' => (float) $item,
                'bool' => (bool) $item,
                'null' => null,
                default => $item,
            };
        }

        // Utiliser l'hydrateur pour les objets
        return Hydrator::hydrate($targetType, $item);
    }

    /**
     * Creates a collection instance from an iterable source.
     *
     * @param  iterable<mixed>  $source  Source data (array, Collection, etc.)
     *
     * @throws InvalidArgumentException
     */
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
            // Normaliser l'item d'abord
            $normalizedItem = NormalizerChain::get()->normalize($item);
            $convertedItem = self::processItemForHydration($normalizedItem, $allowedTypes, $itemIndex);
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
     * @param  class-string<TCollection>|null  $collectionClass
     * @param  string  ...$constructorArgs  Arguments to pass to the target collection's constructor
     * @return ($collectionClass is null ? static : TCollection)
     *
     * @throws InvalidArgumentException
     */
    public static function collect(iterable $sources, ?string $collectionClass = null, string ...$constructorArgs): AbstractTypedCollection
    {
        // Si aucun type de collection n'est spécifié, on utilise la classe courante
        $targetClass = $collectionClass ?? static::class;

        if (! is_subclass_of($targetClass, AbstractTypedCollection::class)) {
            throw new InvalidArgumentException(sprintf(
                'Collection class "%s" must extend %s',
                $targetClass,
                AbstractTypedCollection::class
            ));
        }

        // Si la source est déjà une instance de la classe cible, on la retourne directement
        if ($sources instanceof $targetClass) {
            return $sources;
        }

        // Instancier la collection cible avec les arguments fournis
        $collection = empty($constructorArgs)
            ? new $targetClass
            : new $targetClass(...$constructorArgs);

        // Récupérer les types autorisés de l'instance créée
        $allowedTypes = $collection->getAllowedTypes();
        self::validateAllowedTypes($allowedTypes);

        // Hydratation des items
        foreach ($sources as $item) {
            // Normaliser l'item d'abord
            $normalizedItem = NormalizerChain::get()->normalize($item);

            // Puis traiter l'item normalisé pour l'hydratation
            $convertedItem = self::processItemForHydration($normalizedItem, $allowedTypes);
            $collection->add($convertedItem);
        }

        return $collection;
    }
}
