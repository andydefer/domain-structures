<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Utils;

use AndyDefer\DomainStructures\Abstracts\AbstractDataObject;

class DataObject extends AbstractDataObject
{
    // Alias de Associative pour rétrocompatibilité
    /**
     * Normalise une clé string en camelCase.
     *
     * @param  string  $key  La clé à normaliser
     * @return string La clé normalisée en camelCase
     */
    protected function normalizeKey(string $key): string
    {
        return $this->snakeToCamel($key);
    }
}
