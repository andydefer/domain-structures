<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Fixtures\ValueObjects;

use AndyDefer\DomainStructures\Abstracts\AbstractValueObject;
use AndyDefer\DomainStructures\Collections\Utility\StringTypedCollection;
use AndyDefer\DomainStructures\Tests\Fixtures\Collections\TestUserRoleCollection;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestUserGrade;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestUserStatus;
use AndyDefer\DomainStructures\Tests\Fixtures\Records\TestUserProfileRecord;
use InvalidArgumentException;

/**
 * Value Object representing a user profile.
 *
 * @example
 * $profile = TestUserProfile::from([
 *     'id' => 1,
 *     'name' => 'John Doe',
 *     'email' => 'john@example.com',
 *     'status' => 'active',
 *     'roles' => ['admin', 'editor'],
 *     'grade' => 'gold',
 *     'tags' => ['vip', 'premium'],
 *     'createdAt' => '2024-01-15T14:30:00+01:00',
 *     'emailVerifiedAt' => '2024-01-16T10:00:00+01:00'
 * ]);
 * @example
 * // Without optional fields
 * $profile = TestUserProfile::from([
 *     'id' => 2,
 *     'name' => 'Jane Doe',
 *     'email' => 'jane@example.com',
 *     'status' => 'pending',
 *     'roles' => ['user'],
 *     'grade' => 'silver',
 *     'tags' => ['new'],
 *     'createdAt' => '2024-01-15T14:30:00+01:00'
 * ]);
 */
final class TestUserProfile extends AbstractValueObject
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly TestEmailAddress $email,
        public readonly TestUserStatus $status,
        public readonly TestUserRoleCollection $roles,
        public readonly TestUserGrade $grade,
        public readonly ?TestIso8601DateTime $emailVerifiedAt,
        public readonly StringTypedCollection $tags,
        public readonly TestIso8601DateTime $createdAt,
    ) {
        if ($id <= 0) {
            throw new InvalidArgumentException("User ID must be positive: {$id}");
        }

        if (empty($name)) {
            throw new InvalidArgumentException('User name cannot be empty');
        }

        if (strlen($name) < 2) {
            throw new InvalidArgumentException('User name must be at least 2 characters');
        }
    }

    public function getValue(): TestUserProfileRecord
    {
        return new TestUserProfileRecord(
            id: $this->id,
            name: $this->name,
            email: $this->email,
            status: $this->status,
            roles: $this->roles,
            grade: $this->grade,
            emailVerifiedAt: $this->emailVerifiedAt,
            tags: $this->tags,
            createdAt: $this->createdAt,
        );
    }
}
