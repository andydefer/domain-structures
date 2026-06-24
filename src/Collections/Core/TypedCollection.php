<?php

// src/Collections/Core/TypedCollection.php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Collections\Core;

use AndyDefer\DomainStructures\Abstracts\AbstractTypedCollection;

class TypedCollection extends AbstractTypedCollection
{
    public function __construct(string ...$types)
    {

        parent::__construct(...$types);
    }
}
