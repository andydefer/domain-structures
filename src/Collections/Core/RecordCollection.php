<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Collections\Core;

use AndyDefer\DomainStructures\Abstracts\AbstractRecord;
use AndyDefer\DomainStructures\Abstracts\AbstractTypedCollection;

/**
 * Type-safe collection that can ONLY contain AbstractRecord objects.
 *
 * @extends AbstractTypedCollection<AbstractRecord>
 */
final class RecordCollection extends AbstractTypedCollection
{
    public function __construct()
    {
        parent::__construct(AbstractRecord::class);
    }
}
