<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Utils;

use AndyDefer\DomainStructures\Abstracts\AbstractAssociative;

/**
 * Associative - Normalise les clés en camelCase.
 *
 * Une fois construit, on ne peut pas le modifier.
 * Pour "modifier", on crée une nouvelle instance avec with(), merge() ou without().
 * Supporte l'accès par propriété (->) et par tableau ([]).
 * Supporte camelCase et snake_case (convertis en camelCase).
 *
 * @template T
 */
class Associative extends AbstractAssociative
{
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
