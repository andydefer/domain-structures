<?php
// tests/Fixtures/Collections/ValueObjectCollection.php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Fixtures\Collections;

use AndyDefer\DomainStructures\Abstracts\AbstractTypedCollection;
use AndyDefer\DomainStructures\Tests\Fixtures\ValueObjects\TestEmailAddress;
use AndyDefer\DomainStructures\Tests\Fixtures\ValueObjects\TestMoney;
use AndyDefer\DomainStructures\Tests\Fixtures\ValueObjects\TestIso8601DateTime;

final class ValueObjectCollection extends AbstractTypedCollection
{
    public function __construct()
    {
        parent::__construct(TestEmailAddress::class, TestMoney::class, TestIso8601DateTime::class);
    }
}
