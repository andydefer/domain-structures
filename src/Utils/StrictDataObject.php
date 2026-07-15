<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Utils;

use AndyDefer\DomainStructures\Abstracts\AbstractDataObject;

class StrictDataObject extends AbstractDataObject
{
    // Alias de StrictAssociative pour rétrocompatibilité
    public function normalizeKey(string $key): string
    {
        return $key;
    }
}
