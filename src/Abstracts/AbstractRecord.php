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
     * Returns the normalized array without null values.
     * Useful for database inserts/updates and API responses.
     *
     * @param  bool  $recursive  Whether to remove nulls recursively (default: true)
     * @return array<string, mixed>
     */
    public function toArrayWithoutNulls(bool $recursive = true): array
    {
        $normalized = NormalizerChain::get()->normalize($this);

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
            // Si c'est un tableau, le traiter récursivement
            if (is_array($value)) {
                // Toujours ajouter le tableau, même s'il est vide
                // (une collection vide a du sens)
                $result[$key] = $this->removeNullsRecursively($value);

                continue;
            }

            // Ignorer les valeurs null
            if ($value === null) {
                continue;
            }

            // Garder toutes les autres valeurs
            $result[$key] = $value;
        }

        return $result;
    }

    public function __toString(): string
    {
        return json_encode(NormalizerChain::get()->normalize($this), JSON_THROW_ON_ERROR);
    }
}
