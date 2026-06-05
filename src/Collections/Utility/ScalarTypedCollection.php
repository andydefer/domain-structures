<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Collections\Utility;

use AndyDefer\DomainStructures\Abstracts\AbstractTypedCollection;

/**
 * Typed collection for scalar values (string, int, bool, null).
 *
 * @extends AbstractTypedCollection<string|int|bool|null>
 */
final class ScalarTypedCollection extends AbstractTypedCollection
{
    public function __construct()
    {
        parent::__construct('string', 'int', 'bool', 'null');
    }

    /**
     * Returns a new collection containing only string values.
     */
    public function getStrings(): self
    {
        $result = new self;
        $result->items = array_values(array_filter($this->items, fn ($item) => is_string($item)));

        return $result;
    }

    /**
     * Returns a new collection containing only integer values.
     */
    public function getIntegers(): self
    {
        $result = new self;
        $result->items = array_values(array_filter($this->items, fn ($item) => is_int($item)));

        return $result;
    }

    /**
     * Returns a new collection containing only boolean values.
     */
    public function getBooleans(): self
    {
        $result = new self;
        $result->items = array_values(array_filter($this->items, fn ($item) => is_bool($item)));

        return $result;
    }

    /**
     * Returns positions of null values.
     *
     * @return array<int>
     */
    public function getNullPositions(): array
    {
        $positions = [];

        foreach ($this->items as $index => $item) {
            if ($item === null) {
                $positions[] = $index;
            }
        }

        return $positions;
    }

    /**
     * Checks if the collection contains any null values.
     */
    public function containsNull(): bool
    {
        return ! empty($this->getNullPositions());
    }
}
