<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Unit;

use AndyDefer\DomainStructures\Tests\Fixtures\Collections\TestUserRoleCollection;
use AndyDefer\DomainStructures\Tests\Fixtures\Data\TestFullUserData;
use AndyDefer\DomainStructures\Tests\Fixtures\Data\TestProductData;
use AndyDefer\DomainStructures\Tests\Fixtures\Data\TestUserData;
use AndyDefer\DomainStructures\Tests\Fixtures\Data\TestUserWithRolesData;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestUserGrade;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestUserRole;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestUserStatus;
use AndyDefer\DomainStructures\Tests\TestCase;
use Carbon\Carbon;

final class AbstractDataTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::create(2024, 1, 15, 10, 30, 0, 'UTC'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    // ============================================================================
    // toArray() Tests
    // ============================================================================

    public function test_to_array_preserves_camel_case_keys(): void
    {
        $data = new TestUserData(
            id: '1',
            name: 'John Doe',
            email: 'john@example.com',
            status: TestUserStatus::ACTIVE,
            role: TestUserRole::USER,
            grade: TestUserGrade::BRONZE,
            emailVerifiedAt: null,
            tags: [],
            createdAt: Carbon::now()->toIso8601ZuluString(),
        );

        $array = $data->toArray();

        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('name', $array);
        $this->assertArrayHasKey('email', $array);
        $this->assertArrayHasKey('status', $array);
        $this->assertArrayHasKey('role', $array);
        $this->assertArrayHasKey('grade', $array);
        $this->assertArrayHasKey('emailVerifiedAt', $array);
        $this->assertArrayHasKey('createdAt', $array);
        $this->assertArrayNotHasKey('email_verified_at', $array);
        $this->assertArrayNotHasKey('created_at', $array);
    }

    public function test_to_array_converts_backed_enum_to_string_value(): void
    {
        $data = new TestUserData(
            id: '1',
            name: 'John Doe',
            email: 'john@example.com',
            status: TestUserStatus::ACTIVE,
            role: TestUserRole::ADMIN,
            grade: TestUserGrade::BRONZE,
            emailVerifiedAt: null,
            tags: [],
            createdAt: Carbon::now()->toIso8601ZuluString(),
        );

        $array = $data->toArray();

        $this->assertSame('admin', $array['role']);
    }

    public function test_to_array_converts_int_backed_enum_to_int_value(): void
    {
        $data = new TestUserData(
            id: '1',
            name: 'John Doe',
            email: 'john@example.com',
            status: TestUserStatus::ACTIVE,
            role: TestUserRole::USER,
            grade: TestUserGrade::GOLD,
            emailVerifiedAt: null,
            tags: [],
            createdAt: Carbon::now()->toIso8601ZuluString(),
        );

        $array = $data->toArray();

        $this->assertSame(3, $array['grade']);
    }

    public function test_to_array_converts_pure_enum_to_enum_name(): void
    {
        // Créer la collection de rôles avec ADMIN et USER
        $roles = new TestUserRoleCollection;
        $roles->add(TestUserRole::ADMIN, TestUserRole::USER);

        // Créer l'instance Data directement
        $userData = new TestUserWithRolesData(
            id: '1',
            name: 'John Doe',
            email: 'john@example.com',
            status: TestUserStatus::ACTIVE,
            roles: $roles,
            grade: TestUserGrade::GOLD,
            emailVerifiedAt: '2024-01-15T10:30:00Z',
            tags: ['vip', 'premium'],
            createdAt: '2024-01-15T10:30:00Z',
        );

        $array = $data->toArray();

        $this->assertSame(['admin', 'user'], $array['roles']);
    }

    public function test_to_array_converts_datetime_to_iso_8601_format(): void
    {
        $createdAt = Carbon::create(2024, 1, 15, 14, 30, 0, 'UTC');

        $data = new TestUserData(
            id: '1',
            name: 'John Doe',
            email: 'john@example.com',
            status: TestUserStatus::ACTIVE,
            role: TestUserRole::USER,
            grade: TestUserGrade::BRONZE,
            emailVerifiedAt: null,
            tags: [],
            createdAt: $createdAt->toIso8601ZuluString(),
        );

        $array = $data->toArray();

        $this->assertSame('2024-01-15T14:30:00Z', $array['createdAt']);
    }

    public function test_to_array_keeps_null_values(): void
    {
        $data = new TestUserData(
            id: '1',
            name: 'John Doe',
            email: 'john@example.com',
            status: TestUserStatus::ACTIVE,
            role: TestUserRole::USER,
            grade: TestUserGrade::BRONZE,
            emailVerifiedAt: null,
            tags: [],
            createdAt: Carbon::now()->toIso8601ZuluString(),
        );

        $array = $data->toArray();

        $this->assertNull($array['emailVerifiedAt']);
        $this->assertArrayHasKey('emailVerifiedAt', $array);
    }

    public function test_to_array_recursively_converts_nested_data_objects(): void
    {
        $childProduct = new TestProductData(
            id: '2',
            name: 'Child Product',
            price: 100,
            isFeatured: true,
            createdAt: Carbon::now()->toIso8601ZuluString(),
        );

        $child = new TestFullUserData(
            id: '2',
            name: 'Jane Doe',
            email: 'jane@example.com',
            status: TestUserStatus::INACTIVE,
            role: TestUserRole::USER,
            grade: TestUserGrade::SILVER,
            emailVerifiedAt: '2024-01-10T12:00:00Z',
            createdAt: Carbon::now()->toIso8601ZuluString(),
            tags: [],
            products: [new TestProductData(id: '3', name: 'Jane Product', price: 50, createdAt: Carbon::now()->toIso8601ZuluString())],
            featuredProduct: $childProduct,
        );

        $parentProduct = new TestProductData(
            id: '4',
            name: 'Parent Product',
            price: 500,
            isFeatured: true,
            createdAt: Carbon::now()->toIso8601ZuluString(),
        );

        $parent = new TestFullUserData(
            id: '1',
            name: 'John Doe',
            email: 'john@example.com',
            status: TestUserStatus::ACTIVE,
            role: TestUserRole::ADMIN,
            grade: TestUserGrade::GOLD,
            emailVerifiedAt: null,
            createdAt: Carbon::now()->toIso8601ZuluString(),
            tags: [],
            products: [new TestProductData(id: '5', name: 'Parent Product 1', price: 200, createdAt: Carbon::now()->toIso8601ZuluString())],
            featuredProduct: $parentProduct,
            child: $child,
        );

        $array = $parent->toArray();

        $this->assertSame('1', $array['id']);
        $this->assertSame('John Doe', $array['name']);
        $this->assertSame('admin', $array['role']);
        $this->assertSame(3, $array['grade']);

        $this->assertIsArray($array['products']);
        $this->assertCount(1, $array['products']);
        $this->assertSame('Parent Product 1', $array['products'][0]['name']);
        $this->assertSame(200, $array['products'][0]['price']);

        $this->assertIsArray($array['featuredProduct']);
        $this->assertSame('Parent Product', $array['featuredProduct']['name']);
        $this->assertSame(500, $array['featuredProduct']['price']);
        $this->assertTrue($array['featuredProduct']['isFeatured']);

        $this->assertIsArray($array['child']);
        $this->assertSame('2', $array['child']['id']);
        $this->assertSame('user', $array['child']['role']);

        $this->assertIsArray($array['child']['products']);
        $this->assertCount(1, $array['child']['products']);
        $this->assertSame('Jane Product', $array['child']['products'][0]['name']);
    }

    public function test_to_array_converts_arrays_of_data_objects(): void
    {
        $product1 = new TestProductData(
            id: '1',
            name: 'Laptop',
            price: 999,
            isFeatured: true,
            createdAt: Carbon::now()->toIso8601ZuluString(),
        );
        $product2 = new TestProductData(
            id: '2',
            name: 'Mouse',
            price: 29,
            isFeatured: false,
            createdAt: Carbon::now()->toIso8601ZuluString(),
        );
        $product3 = new TestProductData(
            id: '3',
            name: 'Keyboard',
            price: 89,
            isFeatured: false,
            createdAt: Carbon::now()->toIso8601ZuluString(),
        );

        $data = new TestFullUserData(
            id: '1',
            name: 'John Doe',
            email: 'john@example.com',
            status: TestUserStatus::ACTIVE,
            role: TestUserRole::USER,
            grade: TestUserGrade::BRONZE,
            emailVerifiedAt: null,
            createdAt: Carbon::now()->toIso8601ZuluString(),
            tags: ['developer', 'laravel'],
            products: [$product1, $product2, $product3],
            featuredProduct: $product1,
        );

        $array = $data->toArray();

        $this->assertIsArray($array['tags']);
        $this->assertCount(2, $array['tags']);
        $this->assertSame(['developer', 'laravel'], $array['tags']);

        $this->assertIsArray($array['products']);
        $this->assertCount(3, $array['products']);
        $this->assertSame('Laptop', $array['products'][0]['name']);
        $this->assertSame(999, $array['products'][0]['price']);
        $this->assertTrue($array['products'][0]['isFeatured']);
        $this->assertSame('Mouse', $array['products'][1]['name']);
        $this->assertSame(29, $array['products'][1]['price']);
        $this->assertFalse($array['products'][1]['isFeatured']);
        $this->assertSame('Keyboard', $array['products'][2]['name']);
        $this->assertSame(89, $array['products'][2]['price']);
        $this->assertFalse($array['products'][2]['isFeatured']);
    }

    // ============================================================================
    // collect() Tests
    // ============================================================================

    public function test_collect_creates_data_objects_from_arrays(): void
    {
        $users = [
            [
                'id' => '1',
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'status' => TestUserStatus::ACTIVE,
                'role' => TestUserRole::USER,
                'grade' => TestUserGrade::BRONZE,
                'emailVerifiedAt' => null,
                'createdAt' => Carbon::now()->toIso8601ZuluString(),
                'tags' => [],
            ],
            [
                'id' => '2',
                'name' => 'Jane Doe',
                'email' => 'jane@example.com',
                'status' => TestUserStatus::INACTIVE,
                'role' => TestUserRole::USER,
                'grade' => TestUserGrade::BRONZE,
                'emailVerifiedAt' => '2024-01-10T12:00:00Z',
                'createdAt' => Carbon::now()->toIso8601ZuluString(),
                'tags' => [],
            ],
        ];

        $result = TestUserData::collect($users);

        $this->assertCount(2, $result);
        $this->assertInstanceOf(TestUserData::class, $result[0]);
        $this->assertSame('1', $result[0]->id);
        $this->assertSame('John Doe', $result[0]->name);
    }

    public function test_collect_returns_empty_array_when_input_is_empty(): void
    {
        $result = TestUserData::collect([]);

        $this->assertSame([], $result);
    }

    public function test_collect_throws_exception_when_item_is_not_object_or_array(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Item must be an object or array, string given');

        TestUserData::collect(['invalid']);
    }
}
