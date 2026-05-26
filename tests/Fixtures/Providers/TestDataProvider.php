<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Fixtures\Providers;

use AndyDefer\DomainStructures\Collections\Utility\IntTypedCollection;
use AndyDefer\DomainStructures\Collections\Utility\StringTypedCollection;
use AndyDefer\DomainStructures\Tests\Fixtures\Collections\ProductRecordCollection;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestUserGrade;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestUserRole;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestUserStatus;
use AndyDefer\DomainStructures\Tests\Fixtures\Records\TestProductRecord;
use AndyDefer\DomainStructures\Tests\Fixtures\Records\TestUserRecord;
use AndyDefer\DomainStructures\Tests\Fixtures\ValueObjects\TestEmailAddress;
use AndyDefer\DomainStructures\Tests\Fixtures\ValueObjects\TestIso8601DateTime;

final class TestDataProvider
{
    /**
     * Provide valid user records for testing.
     */
    public static function validUserRecords(): array
    {
        $email = TestEmailAddress::fromString('john@example.com');
        $tags = new StringTypedCollection;
        $tags->add('vip', 'premium');

        return [
            'minimal user' => [
                new TestUserRecord(
                    name: 'John Doe',
                    email: $email,
                ),
            ],
            'full user with id' => [
                new TestUserRecord(
                    id: 1,
                    name: 'Jane Doe',
                    email: TestEmailAddress::fromString('jane@example.com'),
                    status: TestUserStatus::ACTIVE,
                    role: TestUserRole::ADMIN,
                    grade: TestUserGrade::GOLD,
                    tags: $tags,
                ),
            ],
            'user with nullable fields' => [
                new TestUserRecord(
                    name: 'Bob Smith',
                    email: TestEmailAddress::fromString('bob@example.com'),
                    id: null,
                    emailVerifiedAt: null,
                    featuredProduct: null,
                ),
            ],
        ];
    }

    /**
     * Provide invalid data for validation testing.
     */
    public static function invalidUserData(): array
    {
        return [
            'missing name' => [
                'data' => ['email' => 'test@example.com'],
                'expectedError' => 'Missing required properties: name',
            ],
            'invalid email' => [
                'data' => ['name' => 'Test', 'email' => 'not-an-email'],
                'expectedError' => 'Invalid email',
            ],
            'wrong enum type' => [
                'data' => ['name' => 'Test', 'email' => 'test@example.com', 'status' => 'invalid_status'],
                'expectedError' => 'Type mismatch',
            ],
        ];
    }

    /**
     * Provide numeric collections for testing.
     */
    public static function numericCollections(): array
    {
        return [
            'positive integers' => [new IntTypedCollection, [1, 2, 3, 4, 5]],
            'negative integers' => [new IntTypedCollection, [-5, -4, -3, -2, -1]],
            'mixed integers' => [new IntTypedCollection, [-3, -2, -1, 0, 1, 2, 3]],
            'empty collection' => [new IntTypedCollection, []],
            'single element' => [new IntTypedCollection, [42]],
        ];
    }

    /**
     * Provide product records for testing collections.
     */
    public static function productRecords(): array
    {
        $collection = new ProductRecordCollection;

        $product1 = new TestProductRecord(id: 1, name: 'Laptop', price: 999, isFeatured: true);
        $product2 = new TestProductRecord(id: 2, name: 'Mouse', price: 29, isFeatured: false);
        $product3 = new TestProductRecord(id: 3, name: 'Keyboard', price: 89, isFeatured: true);
        $product4 = new TestProductRecord(id: 4, name: 'Monitor', price: 299, isFeatured: false);

        $collection->add($product1, $product2, $product3, $product4);

        return [
            'full collection' => [$collection],
            'empty collection' => [new ProductRecordCollection],
            'single product' => [(new ProductRecordCollection)->add($product1)],
        ];
    }

    /**
     * Provide normalization test cases.
     */
    public static function normalizationScenarios(): array
    {
        $now = TestIso8601DateTime::now();

        return [
            'simple record' => [
                'input' => new TestUserRecord(name: 'John', email: TestEmailAddress::fromString('john@test.com')),
                'expected' => [
                    'name' => 'John',
                    'email' => 'john@test.com',
                    'status' => 'active',
                    'role' => 'user',
                    'grade' => 1,
                    'tags' => [],
                ],
            ],
            'record with null values (includeNulls=true)' => [
                'input' => new TestUserRecord(name: 'John', email: TestEmailAddress::fromString('john@test.com'), id: null),
                'includeNulls' => true,
                'expected' => [
                    'id' => null,
                    'name' => 'John',
                    'email' => 'john@test.com',
                    'status' => 'active',
                    'role' => 'user',
                    'grade' => 1,
                    'email_verified_at' => null,
                    'tags' => [],
                    'products' => [],
                    'featured_product' => null,
                ],
            ],
            'record with null values (includeNulls=false)' => [
                'input' => new TestUserRecord(name: 'John', email: TestEmailAddress::fromString('john@test.com'), id: null),
                'includeNulls' => false,
                'expected' => [
                    'name' => 'John',
                    'email' => 'john@test.com',
                    'status' => 'active',
                    'role' => 'user',
                    'grade' => 1,
                    'tags' => [],
                    'products' => [],
                ],
            ],
        ];
    }
}
