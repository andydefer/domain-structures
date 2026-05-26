<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Collections\Core;

use AndyDefer\DomainStructures\Abstracts\AbstractData;

/**
 * Type-safe collection that can ONLY contain AbstractData objects.
 *
 * @extends TypedCollection<AbstractData>
 */
final class DataCollection extends TypedCollection
{
    public function __construct()
    {
        parent::__construct(AbstractData::class);
    }
}
