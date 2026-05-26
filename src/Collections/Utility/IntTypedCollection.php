<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Collections\Utility;

/**
 * Type-safe collection for integer numbers.
 *
 * Provides specialized mathematical operations and filtering methods for
 * integer values including parity checking (even/odd), zero detection,
 * non-negative filtering, and median calculation.
 *
 * @extends AbstractNumberTypedCollection<int>
 */
final class IntTypedCollection extends AbstractNumberTypedCollection
{
    /**
     * Create a new integer collection.
     *
     * The constructor enforces that all items added to this collection must be
     * of type int.
     */
    public function __construct()
    {
        parent::__construct('int');
    }

    /**
     * Filter the collection to keep only zero values.
     *
     * @return self New collection containing only zero values
     */
    public function zero(): self
    {
        return $this->filter(fn ($item): bool => $item === 0);
    }

    /**
     * Filter the collection to keep only non-negative values (>= 0).
     *
     * This includes zero and all positive integers.
     *
     * @return self New collection containing only non-negative values
     */
    public function nonNegative(): self
    {
        return $this->filter(fn ($item): bool => $item >= 0);
    }

    /**
     * Filter the collection to keep only even numbers.
     *
     * Even numbers are integers divisible by 2 without remainder.
     *
     * @return self New collection containing only even numbers
     */
    public function even(): self
    {
        return $this->filter(fn ($item): bool => $item % 2 === 0);
    }

    /**
     * Filter the collection to keep only odd numbers.
     *
     * Odd numbers are integers not divisible by 2 (remainder of 1 or -1).
     *
     * @return self New collection containing only odd numbers
     */
    public function odd(): self
    {
        return $this->filter(fn ($item): bool => $item % 2 !== 0);
    }

    /**
     * Calculate the median value of the collection.
     *
     * For an odd number of items, returns the middle value.
     * For an even number of items, returns the average of the two middle values.
     * Returns 0.0 for empty collections.
     *
     * @return float The median value as a float (may be .5 for even-sized collections)
     */
    public function median(): float
    {
        $count = $this->count();

        if ($count === 0) {
            return 0.0;
        }

        $sorted = $this->sort()->toArray();
        $middleIndex = intdiv($count, 2);

        // Even count: average of two middle values
        if ($count % 2 === 0) {
            return ($sorted[$middleIndex - 1] + $sorted[$middleIndex]) / 2;
        }

        // Odd count: middle value
        return (float) $sorted[$middleIndex];
    }
}
