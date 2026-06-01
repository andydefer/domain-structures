<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Interfaces;

use AndyDefer\DomainStructures\Abstracts\AbstractData;
use AndyDefer\DomainStructures\Abstracts\AbstractDataObject;
use AndyDefer\DomainStructures\Abstracts\AbstractRecord;
use AndyDefer\DomainStructures\Abstracts\AbstractValueObject;
use AndyDefer\DomainStructures\Collections\Core\TypedCollection;
use Closure;
use Countable;
use IteratorAggregate;
use JsonSerializable;
use Stringable;
use ArrayAccess;
use UnitEnum;

/**
 * Interface for type-safe collections.
 *
 * @template TValue of object|string|int|float|bool
 *
 * @extends Countable
 * @extends IteratorAggregate<array-key, TValue>
 * @extends JsonSerializable
 * @extends Stringable
 * @extends ArrayAccess<array-key, TValue>
 * @extends Transformable<static>
 */
interface TypedCollectionInterface extends Countable, IteratorAggregate, JsonSerializable, Stringable, ArrayAccess, Transformable
{
    /**
     * Adds one or more items to the collection.
     *
     * @return static
     */
    public function add(AbstractDataObject|UnitEnum|AbstractRecord|AbstractValueObject|AbstractData|TypedCollectionInterface|int|string|float|bool|null ...$items): static;

    /**
     * Returns a shallow copy of the entire collection.
     *
     * @return static
     */
    public function all(): static;

    /**
     * Returns the underlying array of items.
     *
     * @return array<TValue>
     */
    public function toArray(): array;

    /**
     * Returns the allowed types for this collection.
     *
     * @return array<string>
     */
    public function getAllowedTypes(): array;

    /**
     * Checks if the collection is empty.
     *
     * @return bool
     */
    public function isEmpty(): bool;

    /**
     * Checks if the collection is not empty.
     *
     * @return bool
     */
    public function isNotEmpty(): bool;

    /**
     * Returns the first item in the collection.
     *
     * @return TValue|null The first item, or null if the collection is empty
     */
    public function first(): mixed;

    /**
     * Returns the last item in the collection.
     *
     * @return TValue|null The last item, or null if the collection is empty
     */
    public function last(): mixed;

    /**
     * Applies a callback to each item and returns a new collection.
     *
     * @template TReturn
     *
     * @param  Closure(TValue): TReturn  $callback
     * @return TypedCollection<TReturn>
     */
    public function map(Closure $callback): TypedCollectionInterface;

    /**
     * Maps items and preserves the same collection type.
     * 
     * @template TReturn of TValue
     * @param Closure(TValue): TReturn $callback
     * @return static
     */
    public function mapPreserveType(Closure $callback): static;

    /**
     * Maps items to a specific target type.
     * 
     * @template TReturn
     * @param Closure(TValue): TReturn $callback
     * @param class-string $targetCollectionClass
     * @return TypedCollection<TReturn>
     */
    public function mapToType(Closure $callback, string $targetCollectionClass, mixed ...$args): TypedCollectionInterface;

    /**
     * Filters items using a callback and returns a new collection.
     *
     * @param  Closure(TValue): bool  $callback
     * @return static
     */
    public function filter(Closure $callback): static;

    /**
     * Sorts the collection by values.
     *
     * @param  int  $flags  PHP sort flags (SORT_REGULAR, SORT_NUMERIC, SORT_STRING, etc.)
     * @return static
     */
    public function sort(int $flags = SORT_REGULAR): static;

    /**
     * Reverses the order of items in the collection.
     *
     * @return static
     */
    public function reverse(): static;

    /**
     * Sorts the collection by a callback or property.
     *
     * @param  Closure(TValue): mixed|string  $callback  Closure or property name
     * @param  int  $flags  PHP sort flags
     * @param  bool  $descending  Sort in descending order
     * @return static
     */
    public function sortBy(Closure|string $callback, int $flags = SORT_REGULAR, bool $descending = false): static;

    /**
     * Sorts the collection using a custom comparison function.
     *
     * @param  Closure(TValue, TValue): int  $callback
     * @return static
     */
    public function usort(Closure $callback): static;

    /**
     * Checks if the collection contains a specific value.
     *
     * @param  AbstractDataObject|UnitEnum|AbstractRecord|AbstractValueObject|AbstractData|self|int|string|float|bool|null  $value
     * @return bool
     */
    public function contains(AbstractDataObject|UnitEnum|AbstractRecord|AbstractValueObject|AbstractData|self|int|string|float|bool|null $value): bool;

    /**
     * Finds the first item that satisfies the callback.
     *
     * @param  Closure(TValue): bool  $callback
     * @return TValue|null
     */
    public function find(Closure $callback): mixed;

    /**
     * Checks if all items satisfy the callback.
     *
     * @param  Closure(TValue): bool  $callback
     * @return bool
     */
    public function every(Closure $callback): bool;

    /**
     * Checks if at least one item satisfies the callback.
     *
     * @param  Closure(TValue): bool  $callback
     * @return bool
     */
    public function some(Closure $callback): bool;

    /**
     * Reduces the collection to a single value using a callback.
     *
     * @param  Closure(mixed, TValue): mixed  $callback
     * @param  mixed  $initial
     * @return mixed
     */
    public function reduce(Closure $callback, mixed $initial = null): mixed;

    /**
     * Executes a callback for each item without modifying the collection.
     *
     * @param  Closure(TValue): void  $callback
     * @return static
     */
    public function each(Closure $callback): static;

    /**
     * Merges another collection into this one.
     *
     * @param  TypedCollectionInterface<TValue>  $collection
     * @return static
     */
    public function merge(TypedCollectionInterface $collection): static;
}
