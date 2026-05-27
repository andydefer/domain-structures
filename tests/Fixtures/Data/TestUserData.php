<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Fixtures\Data;

use AndyDefer\DomainStructures\Abstracts\AbstractData;
use AndyDefer\DomainStructures\Collections\Utility\StringTypedCollection;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestUserGrade;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestUserRole;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestUserStatus;
use AndyDefer\DomainStructures\Tests\Fixtures\ValueObjects\TestEmailAddress;
use AndyDefer\DomainStructures\Tests\Fixtures\ValueObjects\TestIso8601DateTime;

/**
 * Test User Data DTO for unit tests.
 */
final class TestUserData extends AbstractData
{
    public function __construct(
        // REQUIRED PARAMETERS FIRST
        public readonly string $name,
        public readonly TestEmailAddress $email,
        public readonly TestUserStatus $status,
        public readonly TestUserRole $role,
        public readonly TestUserGrade $grade,
        // OPTIONAL PARAMETERS AFTER
        public readonly ?int $id = null,
        public readonly ?TestIso8601DateTime $emailVerifiedAt = null,
        public readonly StringTypedCollection $tags = new StringTypedCollection,
        public readonly ?TestIso8601DateTime $createdAt = null,
    ) {}
}
