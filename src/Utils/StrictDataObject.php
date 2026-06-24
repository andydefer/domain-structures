<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Utils;

use AndyDefer\DomainStructures\Abstracts\AbstractDataObject;

/**
 * StrictDataObject - Préserve la casse originale des clés.
 *
 * Contrairement à DataObject qui normalise en camelCase,
 * cette classe garde les clés exactement comme fournies.
 *
 * @deprecated Utilisez StrictAssociative à la place. Cette classe sera supprimée dans la version 3.0.0.
 */
class StrictDataObject extends AbstractDataObject
{
    // Alias de StrictAssociative pour rétrocompatibilité
    public function normalizeKey(string $key): string
    {
        return $key;
    }
}
