<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Fixtures\Data;

use AndyDefer\DomainStructures\Abstracts\AbstractData;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestUserGrade;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestUserRole;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestUserStatus;
use AndyDefer\DomainStructures\Tests\Fixtures\ValueObjects\TestEmailAddress;

/**
 * Test Simple User Data DTO for unit tests.
 *
 * Used for testing basic data transformation scenarios.
 */
final class TestSimpleUserData extends AbstractData
{
    public function __construct(
        // REQUIRED PARAMETERS FIRST
        public readonly string $firstName,
        public readonly string $lastName,
        public readonly TestEmailAddress $email,
        public readonly ?TestUserStatus $status = TestUserStatus::ACTIVE,
        public readonly ?TestUserRole $role = TestUserRole::GUEST,
        public readonly TestUserGrade $grade = TestUserGrade::BRONZE,
        // OPTIONAL PARAMETERS AFTER
        public readonly ?int $id = null,
    ) {}
}
