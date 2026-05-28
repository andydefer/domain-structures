<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Fixtures\Records;

use AndyDefer\DomainStructures\Abstracts\AbstractRecord;
use AndyDefer\DomainStructures\Collections\Core\TypedCollection;
use AndyDefer\DomainStructures\Collections\Utility\StringTypedCollection;
use AndyDefer\DomainStructures\Utils\DataObject;

/**
 * Fixture record for testing validation rules.
 *
 * This record is specifically designed to test the validation rules of AbstractRecord:
 * - TypedCollection cannot be null
 * - TypedCollection cannot have union types
 * - Arrays are not allowed
 */
final class TestValidationRecord extends AbstractRecord
{
    public function __construct(
        public readonly string $validString = 'default',
        public readonly int $validInt = 0,
        public readonly StringTypedCollection $validCollection = new StringTypedCollection,
        public readonly ?StringTypedCollection $invalidNullableCollection = new StringTypedCollection,
        public readonly TypedCollection|DataObject $invalidUnionCollection = new TypedCollection,
        public readonly array $invalidArray = [],
    ) {}
}
