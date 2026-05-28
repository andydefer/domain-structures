<?php

// FILE: tests/Fixtures/Data/TestFullUserData.php (version mise à jour)

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Fixtures\Data;

use AndyDefer\DomainStructures\Abstracts\AbstractData;
use AndyDefer\DomainStructures\Collections\Utility\StringTypedCollection;
use AndyDefer\DomainStructures\Tests\Fixtures\Collections\TestProductDataCollection;
use AndyDefer\DomainStructures\Tests\Fixtures\Collections\TestUserRoleCollection;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestUserGrade;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestUserStatus;

/**
 * Test Full User Data DTO for unit tests.
 *
 * Used to test the transformation of complete User records into API responses.
 * This Data contains nested collections and various field types.
 */
final class TestFullUserData extends AbstractData
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $email,
        public readonly TestUserStatus $status,
        public readonly ?TestUserRoleCollection $roles,
        public readonly TestUserGrade $grade,
        public readonly ?string $emailVerifiedAt,
        public readonly StringTypedCollection $tags,
        public readonly TestProductDataCollection $products,
        public readonly ?TestProductData $featuredProduct,
        public readonly ?string $createdAt,
    ) {}
}
