<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Collections\Utility;

use AndyDefer\DomainStructures\Abstracts\AbstractTypedCollection;
use AndyDefer\DomainStructures\Utils\StrictDataObject;

final class StrictDataObjectCollection extends AbstractTypedCollection
{
    public function __construct()
    {
        parent::__construct(StrictDataObject::class);
    }
}
