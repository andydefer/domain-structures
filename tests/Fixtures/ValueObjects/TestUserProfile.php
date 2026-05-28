<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Fixtures\ValueObjects;

use AndyDefer\DomainStructures\Abstracts\AbstractValueObject;
use AndyDefer\DomainStructures\Collections\Utility\StringTypedCollection;
use AndyDefer\DomainStructures\Tests\Fixtures\Collections\TestUserRoleCollection;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestUserGrade;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestUserStatus;
use AndyDefer\DomainStructures\Tests\Fixtures\Records\TestUserProfileRecord;
use AndyDefer\DomainStructures\Utils\DataObject;
use InvalidArgumentException;

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

    public static function from(mixed $source): static
    {
        if ($source instanceof self) {
            return $source;
        }

        $data = DataObject::from($source);

        // Gérer emailVerifiedAt qui peut être null
        $emailVerifiedAt = null;
        if (property_exists($data, 'emailVerifiedAt') && $data->emailVerifiedAt !== null) {
            $emailVerifiedAt = TestIso8601DateTime::from($data->emailVerifiedAt);
        }

        return new self(
            id: $data->id ?? throw new InvalidArgumentException('Missing id'),
            name: $data->name ?? throw new InvalidArgumentException('Missing name'),
            email: TestEmailAddress::from($data->email ?? throw new InvalidArgumentException('Missing email')),
            status: TestUserStatus::from($data->status ?? throw new InvalidArgumentException('Missing status')),
            roles: TestUserRoleCollection::from($data->roles ?? throw new InvalidArgumentException('Missing roles')),
            grade: TestUserGrade::from($data->grade ?? throw new InvalidArgumentException('Missing grade')),
            emailVerifiedAt: $emailVerifiedAt,
            tags: StringTypedCollection::from($data->tags ?? throw new InvalidArgumentException('Missing tags')),
            createdAt: TestIso8601DateTime::from($data->createdAt ?? throw new InvalidArgumentException('Missing createdAt')),
        );
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
