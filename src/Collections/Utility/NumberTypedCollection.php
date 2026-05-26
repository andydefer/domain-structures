<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Collections\Utility;

/**
 * Type-safe collection for mixed numeric values (integers or floats).
 *
 * Unlike IntTypedCollection and FloatTypedCollection which enforce a single
 * numeric type, this collection accepts both integers and floats. This is
 * useful when working with mixed numeric data or when type flexibility is needed.
 *
 * Provides filtering methods specifically for numeric values including zero
 * detection and non-negative filtering across both integer and float types.
 *
 * @extends AbstractNumberTypedCollection<int|float>
 */
final class NumberTypedCollection extends AbstractNumberTypedCollection
{
    /**
     * Create a new mixed numeric collection.
     *
     * The constructor allows both integer and float values to be added to
     * this collection. All items will be validated against both types.
     */
    public function __construct()
    {
        parent::__construct('int', 'float');
    }

    /**
     * Filter the collection to keep only zero values.
     *
     * This method considers both integer 0 and float 0.0 as zero values.
     * Uses strict comparison to ensure only actual numeric zeros are matched.
     *
     * @return self New collection containing only zero values
     */
    public function zero(): self
    {
        return $this->filter(fn ($item): bool => $item === 0 || $item === 0.0);
    }

    /**
     * Filter the collection to keep only non-negative values (>= 0).
     *
     * This includes zero and all positive numbers, regardless of whether they
     * are integers or floats.
     *
     * @return self New collection containing only non-negative values
     */
    public function nonNegative(): self
    {
        return $this->filter(fn ($item): bool => $item >= 0);
    }

    /**
     * Check if all values in the collection are integers.
     *
     * @return bool True if every item is an integer, false otherwise
     */
    public function areAllIntegers(): bool
    {
        return $this->every(fn ($item): bool => is_int($item));
    }

    /**
     * Check if any value in the collection is a float.
     *
     * @return bool True if at least one float exists, false otherwise
     */
    public function hasAnyFloat(): bool
    {
        return $this->some(fn ($item): bool => is_float($item));
    }

    /**
     * Convert all values to floats.
     *
     * Integers are cast to floats, floats remain unchanged.
     *
     * @return FloatTypedCollection New collection with all values as floats
     */
    public function toFloats(): FloatTypedCollection
    {
        $collection = new FloatTypedCollection;

        foreach ($this->toArray() as $item) {
            $collection->add((float) $item);
        }

        return $collection;
    }

    /**
     * Convert all values to integers (truncates decimal part).
     *
     * Floats are truncated toward zero (same as (int) cast).
     * Note: This loses decimal precision.
     *
     * @return IntTypedCollection New collection with all values as integers
     */
    public function toIntegers(): IntTypedCollection
    {
        $collection = new IntTypedCollection;

        foreach ($this->toArray() as $item) {
            $collection->add((int) $item);
        }

        return $collection;
    }

    /**
     * Separate integers and floats into two distinct collections.
     *
     * @return array{integers: IntTypedCollection, floats: FloatTypedCollection}
     */
    public function separateTypes(): array
    {
        $integers = new IntTypedCollection;
        $floats = new FloatTypedCollection;

        foreach ($this->toArray() as $item) {
            if (is_int($item)) {
                $integers->add($item);
            } else {
                $floats->add($item);
            }
        }

        return ['integers' => $integers, 'floats' => $floats];
    }
}
