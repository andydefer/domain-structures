<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Utils;

/**
 * StrictDataObject - Préserve la casse originale des clés.
 * 
 * Contrairement à DataObject qui normalise en camelCase,
 * cette classe garde les clés exactement comme fournies.
 */
class StrictDataObject extends DataObject
{
    /**
     * Normalise une clé en la laissant inchangée.
     *
     * @param string $key La clé à normaliser
     * @return string La clé inchangée
     */
    protected function normalizeKey(string $key): string
    {
        // Préserve la casse originale
        return $key;
    }
}
