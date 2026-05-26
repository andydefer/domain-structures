<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Fixtures\ValueObjects;

use AndyDefer\DomainStructures\Abstracts\AbstractValueObject;
use AndyDefer\DomainStructures\Collections\Utility\StringTypedCollection;
use AndyDefer\DomainStructures\Tests\Fixtures\Collections\TestUserRoleCollection;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestUserGrade;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestUserStatus;
use AndyDefer\DomainStructures\Tests\Fixtures\Records\TestUserProfileRecord;

/**
 * Value Object representing a user profile.
 */
final class TestUserProfile extends AbstractValueObject
{
    private function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly TestEmailAddress $email,
        public readonly TestUserStatus $status,
        public readonly TestUserRoleCollection $roles,
        public readonly TestUserGrade $grade,
        public readonly ?TestIso8601DateTime $emailVerifiedAt,
        public readonly StringTypedCollection $tags,
        public readonly TestIso8601DateTime $createdAt,
    ) {}

    public static function from(...$values): static
    {
        return new self(...$values);
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
