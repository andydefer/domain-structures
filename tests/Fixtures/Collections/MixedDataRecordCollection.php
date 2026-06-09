<?php
// tests/Fixtures/Collections/MixedDataRecordCollection.php
declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Fixtures\Collections;

use AndyDefer\DomainStructures\Abstracts\AbstractTypedCollection;
use AndyDefer\DomainStructures\Tests\Fixtures\Data\TestUserData;
use AndyDefer\DomainStructures\Tests\Fixtures\Records\TestUserRecord;

/**
 * Collection qui mélange Data et Record (TEST D'ERREUR)
 */
final class MixedDataRecordCollection extends AbstractTypedCollection
{
    public function __construct()
    {
        parent::__construct(
            TestUserData::class,
            TestUserRecord::class
        );
    }
}
