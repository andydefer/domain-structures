<?php
// tests/Fixtures/Collections/MixedDataCollection.php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Fixtures\Collections;

use AndyDefer\DomainStructures\Abstracts\AbstractTypedCollection;
use AndyDefer\DomainStructures\Tests\Fixtures\Data\TestProductData;
use AndyDefer\DomainStructures\Tests\Fixtures\Data\TestUserData;

/**
 * Collection fixture qui peut contenir TestUserData et TestProductData.
 */
final class MixedDataCollection extends AbstractTypedCollection
{
    public function __construct()
    {
        parent::__construct(TestUserData::class, TestProductData::class);
    }
}
