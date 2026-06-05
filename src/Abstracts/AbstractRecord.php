<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Abstracts;

use AndyDefer\DomainStructures\Interfaces\Transformable;
use AndyDefer\DomainStructures\Normalizers\NormalizerChain;
use AndyDefer\DomainStructures\Traits\Hydratable;

abstract class AbstractRecord implements Transformable
{
    use Hydratable;

    /**
     * Converts the record to a normalized array.
     *
     * This method uses the NormalizerChain to recursively normalize the entire
     * record structure, including nested records, value objects, and collections.
     *
     * @return array<string, mixed> The normalized array representation
     */
    public function toArray(): array
    {
        return NormalizerChain::get()->normalize($this);
    }

    /**
     * Returns the normalized array without null values.
     * Useful for database inserts/updates and API responses.
     *
     * @param  bool  $recursive  Whether to remove nulls recursively (default: true)
     * @return array<string, mixed>
     */
    public function toArrayWithoutNulls(bool $recursive = true): array
    {
        $normalized = $this->toArray();

        if (! $recursive) {
            return array_filter($normalized, fn ($value) => $value !== null);
        }

        return $this->removeNullsRecursively($normalized);
    }

    /**
     * Removes null values from an array recursively.
     * Preserves empty arrays (they represent empty collections).
     *
     * @param  array<mixed>  $data
     * @return array<mixed>
     */
    private function removeNullsRecursively(array $data): array
    {
        $result = [];

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                // Always add the array, even if empty (empty collection has meaning)
                $result[$key] = $this->removeNullsRecursively($value);

                continue;
            }

            if ($value === null) {
                continue;
            }

            $result[$key] = $value;
        }

        return $result;
    }

    public function __toString(): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR);
    }
}
