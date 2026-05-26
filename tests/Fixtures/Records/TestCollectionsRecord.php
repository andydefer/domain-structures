<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Fixtures\Records;

use AndyDefer\DomainStructures\Abstracts\AbstractRecord;
use AndyDefer\DomainStructures\Collections\Core\TypedCollection;
use AndyDefer\DomainStructures\Collections\Utility\IntTypedCollection;
use AndyDefer\DomainStructures\Collections\Utility\StringTypedCollection;

/**
 * Fixture record for testing collections of collections.
 *
 * Used to test that AbstractRecord can handle properties of type
 * TypedCollection where the collection itself contains collections.
 */
final class TestCollectionsRecord extends AbstractRecord
{
    public function __construct(
        public readonly TypedCollection $stringCollections = new TypedCollection(StringTypedCollection::class),
        public readonly TypedCollection $intCollections = new TypedCollection(IntTypedCollection::class),
    ) {}
}
