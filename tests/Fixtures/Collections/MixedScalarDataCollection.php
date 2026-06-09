<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Fixtures\Collections;

use AndyDefer\DomainStructures\Abstracts\AbstractTypedCollection;
use AndyDefer\DomainStructures\Tests\Fixtures\Data\TestUserData;

final class MixedScalarDataCollection extends AbstractTypedCollection
{
    public function __construct()
    {
        parent::__construct(
            TestUserData::class,
            'string'
        );
    }
}
