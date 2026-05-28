<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Collections\Core;

use AndyDefer\DomainStructures\Abstracts\AbstractTypedCollection;

/**
 * Type-safe collection that accepts explicitly specified types.
 * 
 * @template TValue of object|string|int|float|bool
 */
class TypedCollection extends AbstractTypedCollection
{
    /**
     * Constructeur public pour permettre la création dynamique
     * 
     * @param  string  ...$types  Allowed types for this collection
     */
    public function __construct(string ...$types)
    {
        parent::__construct(...$types);
    }
}
