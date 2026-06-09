<?php
// tests/Fixtures/Collections/MixedRecordCollection.php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Fixtures\Collections;

use AndyDefer\DomainStructures\Abstracts\AbstractTypedCollection;
use AndyDefer\DomainStructures\Tests\Fixtures\Records\TestProductRecord;
use AndyDefer\DomainStructures\Tests\Fixtures\Records\TestUserRecord;

/**
 * Collection fixture qui peut contenir TestUserRecord et TestProductRecord.
 */
final class MixedRecordCollection extends AbstractTypedCollection
{
    public function __construct()
    {
        parent::__construct(TestUserRecord::class, TestProductRecord::class);
    }
}
