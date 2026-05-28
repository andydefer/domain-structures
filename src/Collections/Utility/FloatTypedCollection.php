<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Collections\Utility;

/**
 * Type-safe collection for floating-point numbers.
 *
 * Provides specialized mathematical operations for float values including
 * rounding operations (round, ceil, floor) and precision formatting.
 *
 * @extends AbstractNumberTypedCollection<float>
 */
final class FloatTypedCollection extends AbstractNumberTypedCollection
{
    /**
     * Create a new float collection.
     *
     * The constructor enforces that all items added to this collection must be
     * of type float.
     */
    public function __construct()
    {
        parent::__construct('float');
    }

    /**
     * Round each float value to a specified precision.
     *
     * @param  int  $precision  Number of decimal digits to round to (0 = integer)
     * @return self New collection with rounded values
     */
    public function round(int $precision = 0): self
    {
        $result = new self;

        foreach ($this->items as $item) {
            $result->add(round($item, $precision));
        }

        return $result;
    }

    /**
     * Round each float value up to the next highest integer.
     *
     * @return self New collection with ceiling values
     */
    public function ceil(): self
    {
        $result = new self;

        foreach ($this->items as $item) {
            $result->add(ceil($item));
        }

        return $result;
    }

    /**
     * Round each float value down to the next lowest integer.
     *
     * @return self New collection with floor values
     */
    public function floor(): self
    {
        $result = new self;

        foreach ($this->items as $item) {
            $result->add(floor($item));
        }

        return $result;
    }

    /**
     * Format each float value with a specific number of decimal places.
     *
     * This method is an alias for round() with the specified precision.
     * Returns float values (not strings) for continued numeric operations.
     *
     * @param  int  $decimals  Number of decimal places (default: 2)
     * @return self New collection with formatted values
     */
    public function format(int $decimals = 2): self
    {
        return $this->round($decimals);
    }

    /**
     * Convert all float values to integers (truncates decimal part).
     *
     * Floats are truncated toward zero (same as (int) cast).
     * Note: This loses decimal precision.
     *
     * @return IntTypedCollection New collection with all values as integers
     */
    public function toIntegers(): IntTypedCollection
    {
        $collection = new IntTypedCollection;

        foreach ($this->items as $item) {
            $collection->add((int) $item);
        }

        return $collection;
    }
}
