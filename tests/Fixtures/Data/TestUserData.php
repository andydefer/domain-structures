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
 *
 * Used to test the transformation of User records into API responses.
 */
final class TestUserData extends AbstractData
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly TestEmailAddress $email,
        public readonly TestUserStatus $status,
        public readonly TestUserRole $role,
        public readonly TestUserGrade $grade,
        public readonly ?TestIso8601DateTime $emailVerifiedAt,
        public readonly StringTypedCollection $tags,
        public readonly TestIso8601DateTime $createdAt,
    ) {}
}
