<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Fixtures\Records;

use AndyDefer\DomainStructures\Abstracts\AbstractRecord;
use AndyDefer\DomainStructures\Collections\Utility\StringTypedCollection;
use AndyDefer\DomainStructures\Tests\Fixtures\Collections\ProductRecordCollection;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestUserGrade;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestUserRole;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestUserStatus;
use AndyDefer\DomainStructures\Tests\Fixtures\ValueObjects\TestEmailAddress;
use AndyDefer\DomainStructures\Tests\Fixtures\ValueObjects\TestIso8601DateTime;

/**
 * Test record for unit tests.
 *
 * PURE RECORD - No logic, just data structure.
 * Used for create/update operations in TestUserRepository.
 */
final class TestUserRecord extends AbstractRecord
{
    public function __construct(
        public readonly ?int $id = null,
        public readonly ?string $name = null,
        public readonly ?TestEmailAddress $email = null,
        public readonly ?TestUserStatus $status = TestUserStatus::ACTIVE,
        public readonly ?TestUserRole $role = TestUserRole::USER,
        public readonly ?TestUserGrade $grade = TestUserGrade::BRONZE,
        public readonly ?TestIso8601DateTime $emailVerifiedAt = null,
        public readonly StringTypedCollection $tags = new StringTypedCollection,
        public readonly ProductRecordCollection $products = new ProductRecordCollection,
        public readonly ?TestProductRecord $featuredProduct = null,
    ) {}
}
