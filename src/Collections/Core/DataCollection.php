<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Collections\Core;

use AndyDefer\DomainStructures\Abstracts\AbstractData;
use AndyDefer\DomainStructures\Abstracts\AbstractTypedCollection;

/**
 * Type-safe collection that can ONLY contain AbstractData objects.
 *
 * @extends TypedCollection<AbstractData>
 */
final class DataCollection extends AbstractTypedCollection
{
    public function __construct()
    {
        parent::__construct(AbstractData::class);
    }
}
