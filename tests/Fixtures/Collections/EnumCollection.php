<?php
// tests/Fixtures/Collections/EnumCollection.php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Fixtures\Collections;

use AndyDefer\DomainStructures\Abstracts\AbstractTypedCollection;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestUserRole;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestUserStatus;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestUserGrade;

final class EnumCollection extends AbstractTypedCollection
{
    public function __construct()
    {
        parent::__construct(TestUserRole::class, TestUserStatus::class, TestUserGrade::class);
    }
}
