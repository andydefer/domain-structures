<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Collections\Utility;

use AndyDefer\DomainStructures\Abstracts\AbstractTypedCollection;
use AndyDefer\DomainStructures\Collections\Core\TypedCollection;
use AndyDefer\DomainStructures\Utils\DataObject;

final class DataObjectCollection extends AbstractTypedCollection
{
    public function __construct()
    {
        parent::__construct(DataObject::class);
    }
}
