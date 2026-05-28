<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Collections\Utility;

use AndyDefer\DomainStructures\Abstracts\AbstractTypedCollection;
use InvalidArgumentException;

/**
 * Base class for type-safe collections of numeric values (int|float).
 *
 * Provides common mathematical operations and filtering methods specifically
 * designed for collections containing only integers or floats. All items are
 * validated against the numeric type at construction time.
 *
 * @template TValue of int|float
 *
 * @extends AbstractTypedCollection<TValue>
 */
abstract class AbstractNumberTypedCollection extends AbstractTypedCollection
{
    /**
     * Filter the collection to keep only positive numbers (greater than 0).
     *
     * @return static<TValue> New collection containing only positive numbers
     */
    public function positive(): static
    {
        return $this->filter(fn ($item): bool => $item > 0);
    }

    /**
     * Filter the collection to keep only negative numbers (less than 0).
     *
     * @return static<TValue> New collection containing only negative numbers
     */
    public function negative(): static
    {
        return $this->filter(fn ($item): bool => $item < 0);
    }

    /**
     * Filter the collection to keep numbers within a specified inclusive range.
     *
     * @param  TValue  $min  Minimum value (inclusive)
     * @param  TValue  $max  Maximum value (inclusive)
     * @return static<TValue> New collection containing numbers in the range
     *
     * @throws InvalidArgumentException If min is greater than max
     */
    public function between(int|float $min, int|float $max): static
    {
        if ($min > $max) {
            throw new InvalidArgumentException(
                sprintf('Minimum value (%s) cannot be greater than maximum value (%s)', $min, $max)
            );
        }

        return $this->filter(fn ($item): bool => $item >= $min && $item <= $max);
    }

    /**
     * Calculate the arithmetic mean of all items in the collection.
     *
     * For empty collections, returns 0.0 to avoid division by zero errors.
     *
     * @return float The average value, or 0.0 if the collection is empty
     */
    public function average(): float
    {
        $count = $this->count();

        if ($count === 0) {
            return 0.0;
        }

        return $this->sum() / $count;
    }

    /**
     * Calculate the sum of all items in the collection.
     *
     * For empty collections, returns 0.0 to maintain type consistency.
     *
     * @return int|float The sum of all items, or 0.0 if the collection is empty
     */
    public function sum(): int|float
    {
        $sum = 0;
        foreach ($this->items as $item) {
            $sum += $item;
        }

        return $sum;
    }

    /**
     * Generate a sequence of numbers within a range.
     *
     * Creates a new collection populated with numbers starting from `$start`
     * to `$end`, incrementing by `$step`. The direction of iteration is
     * automatically determined based on the start/end values and step sign.
     *
     * @param  TValue  $start  First value in the sequence
     * @param  TValue  $end  Last value in the sequence (inclusive)
     * @param  TValue  $step  Increment between each value (cannot be zero)
     * @return static<TValue> Collection containing the generated sequence
     *
     * @throws InvalidArgumentException If step is zero
     */
    public static function range(int|float $start, int|float $end, int|float $step = 1): static
    {
        // ✅ Correction: utiliser == au lieu de === pour capturer int 0 et float 0.0
        if ($step == 0) {
            throw new InvalidArgumentException('Step value cannot be zero');
        }

        $collection = new static;

        // Positive step: iterate upward
        if ($start <= $end && $step > 0) {
            for ($current = $start; $current <= $end; $current += $step) {
                $collection->add($current);
            }
        }
        // Negative step: iterate downward
        elseif ($start >= $end && $step < 0) {
            for ($current = $start; $current >= $end; $current += $step) {
                $collection->add($current);
            }
        }

        return $collection;
    }
}
