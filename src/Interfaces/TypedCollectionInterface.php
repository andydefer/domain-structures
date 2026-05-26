<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Interfaces;

use AndyDefer\DomainStructures\Abstracts\AbstractData;
use AndyDefer\DomainStructures\Abstracts\AbstractRecord;
use AndyDefer\DomainStructures\Abstracts\AbstractTypedCollection;
use AndyDefer\DomainStructures\Abstracts\AbstractValueObject;
use Closure;
use Countable;
use IteratorAggregate;
use JsonSerializable;
use stdClass;
use Stringable;
use Traversable;
use UnitEnum;

/**
 * Type-safe collection for records, value objects, data DTOs, enums, and scalar values.
 *
 * @template TValue of object|string|int|float|bool
 */
interface TypedCollectionInterface extends Countable, IteratorAggregate, JsonSerializable, Stringable
{
    /**
     * Add one or multiple items.
     *
     * @param  TValue  ...$items
     * @return static<TValue>
     */
    public function add(int|string|float|bool|null|UnitEnum|AbstractRecord|AbstractValueObject|AbstractData|AbstractTypedCollection|stdClass ...$items): static;

    /**
     * Get all items as a new collection.
     *
     * @return static<TValue>
     */
    public function all(): static;

    /**
     * Convert the collection to a plain array.
     *
     * @return array<TValue>
     */
    public function toArray(): array;

    /**
     * Get the allowed types.
     *
     * @return array<string>
     */
    public function getAllowedTypes(): array;

    /**
     * Get the number of items.
     */
    public function count(): int;

    /**
     * Check if empty.
     */
    public function isEmpty(): bool;

    /**
     * Check if not empty.
     */
    public function isNotEmpty(): bool;

    /**
     * Transform each item.
     *
     * @template TReturn
     *
     * @param  Closure(TValue): TReturn  $callback
     * @return static<TReturn>
     */
    public function map(Closure $callback): static;

    /**
     * Filter items.
     *
     * @param  Closure(TValue): bool  $callback
     * @return static<TValue>
     */
    public function filter(Closure $callback): static;

    /**
     * Check if the collection contains a specific value.
     */
    public function contains(int|string|float|bool|null|UnitEnum|AbstractRecord|AbstractValueObject|AbstractData|AbstractTypedCollection|stdClass $value): bool;

    /**
     * Execute callback on each item (for side effects).
     *
     * @param  Closure(TValue): void  $callback
     * @return static<TValue>
     */
    public function each(Closure $callback): static;

    /**
     * Merge with another collection.
     *
     * @param  static<TValue>  $collection
     * @return static<TValue>
     */
    public function merge(AbstractTypedCollection $collection): static;

    /**
     * Reduce the collection to a single value.
     *
     * @template TReturn
     *
     * @param  Closure(TReturn, TValue): TReturn  $callback
     * @param  TReturn  $initial
     * @return TReturn
     */
    public function reduce(Closure $callback, mixed $initial = null): mixed;

    /**
     * Find the first item satisfying the predicate.
     *
     * @param  Closure(TValue): bool  $callback
     * @return TValue|null
     */
    public function find(Closure $callback): mixed;

    /**
     * Check if all items satisfy the predicate.
     *
     * @param  Closure(TValue): bool  $callback
     */
    public function every(Closure $callback): bool;

    /**
     * Check if at least one item satisfies the predicate.
     *
     * @param  Closure(TValue): bool  $callback
     */
    public function some(Closure $callback): bool;

    /**
     * Reverse the order.
     *
     * @return static<TValue>
     */
    public function reverse(): static;

    /**
     * Get an iterator for the collection.
     *
     * @return Traversable<TValue>
     */
    public function getIterator(): Traversable;
}
