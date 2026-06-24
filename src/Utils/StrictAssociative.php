<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Utils;

use AndyDefer\DomainStructures\Abstracts\AbstractAssociative;

/**
 * StrictAssociative - Préserve la casse originale des clés.
 *
 * Contrairement à StrictAssociative qui normalise en camelCase,
 * cette classe garde les clés exactement comme fournies.
 */
class StrictAssociative extends AbstractAssociative
{
    /**
     * Normalise une clé en la laissant inchangée.
     *
     * @param  string  $key  La clé à normaliser
     * @return string La clé inchangée
     */
    protected function normalizeKey(string $key): string
    {
        // Préserve la casse originale
        return $key;
    }
}
