<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Collections\Utility;

use AndyDefer\DomainStructures\Abstracts\AbstractTypedCollection;

/**
 * Type-safe collection for boolean values.
 *
 * Provides specialized methods for boolean operations including filtering,
 * counting, and validation of boolean states across the collection.
 *
 * @extends TypedCollection<bool>
 */
final class BoolTypedCollection extends AbstractTypedCollection
{
    /**
     * Create a new boolean collection.
     *
     * The constructor enforces that all items added to this collection must be
     * of type boolean.
     */
    public function __construct()
    {
        parent::__construct('bool');
    }

    /**
     * Filter the collection to keep only true values.
     *
     * @return self New collection containing only true values
     */
    public function trueOnly(): self
    {
        return $this->filter(fn ($item): bool => $item === true);
    }

    /**
     * Filter the collection to keep only false values.
     *
     * @return self New collection containing only false values
     */
    public function falseOnly(): self
    {
        return $this->filter(fn ($item): bool => $item === false);
    }

    /**
     * Count how many true values are in the collection.
     *
     * @return int Number of true values
     */
    public function countTrue(): int
    {
        return $this->trueOnly()->count();
    }

    /**
     * Count how many false values are in the collection.
     *
     * @return int Number of false values
     */
    public function countFalse(): int
    {
        return $this->falseOnly()->count();
    }

    /**
     * Check if all values in the collection are true.
     *
     * For an empty collection, returns true (vacuously true).
     *
     * @return bool True if every item is true, false otherwise
     */
    public function allTrue(): bool
    {
        return $this->countTrue() === $this->count();
    }

    /**
     * Check if all values in the collection are false.
     *
     * For an empty collection, returns true (vacuously true).
     *
     * @return bool True if every item is false, false otherwise
     */
    public function allFalse(): bool
    {
        return $this->countFalse() === $this->count();
    }

    /**
     * Check if at least one value in the collection is true.
     *
     * @return bool True if at least one true value exists, false otherwise
     */
    public function anyTrue(): bool
    {
        return $this->countTrue() > 0;
    }

    /**
     * Check if at least one value in the collection is false.
     *
     * @return bool True if at least one false value exists, false otherwise
     */
    public function anyFalse(): bool
    {
        return $this->countFalse() > 0;
    }
}
