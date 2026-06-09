<?php
// tests/Unit/Services/CollectionHydrationServiceTest.php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Unit\Services;

use AndyDefer\DomainStructures\Services\CollectionHydrationService;
use AndyDefer\DomainStructures\Collections\Utility\StringTypedCollection;
use AndyDefer\DomainStructures\Collections\Utility\IntTypedCollection;
use AndyDefer\DomainStructures\Collections\Utility\BoolTypedCollection;
use AndyDefer\DomainStructures\Collections\Utility\FloatTypedCollection;
use AndyDefer\DomainStructures\Collections\Utility\DataObjectCollection;
use AndyDefer\DomainStructures\Tests\TestCase;
use AndyDefer\DomainStructures\Tests\Fixtures\Collections\EnumCollection;
use AndyDefer\DomainStructures\Tests\Fixtures\Collections\ValueObjectCollection;
use AndyDefer\DomainStructures\Tests\Fixtures\Collections\MixedValueObjectCollection;
use AndyDefer\DomainStructures\Tests\Fixtures\Collections\MixedScalarCollection;
use AndyDefer\DomainStructures\Tests\Fixtures\Collections\MixedDataRecordCollection;
use AndyDefer\DomainStructures\Tests\Fixtures\Collections\EmptyTypesCollection;
use AndyDefer\DomainStructures\Tests\Fixtures\Collections\MixedDataCollection;
use AndyDefer\DomainStructures\Tests\Fixtures\Collections\MixedRecordCollection;
use AndyDefer\DomainStructures\Tests\Fixtures\Collections\MixedScalarDataCollection;
use AndyDefer\DomainStructures\Tests\Fixtures\Records\TestUserRecord;
use AndyDefer\DomainStructures\Tests\Fixtures\Records\TestProductRecord;
use AndyDefer\DomainStructures\Tests\Fixtures\Data\TestUserData;
use AndyDefer\DomainStructures\Tests\Fixtures\Data\TestProductData;
use AndyDefer\DomainStructures\Tests\Fixtures\Collections\TestUserRoleCollection;
use AndyDefer\DomainStructures\Tests\Fixtures\Collections\TestProductRecordCollection;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestUserRole;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestUserStatus;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestUserGrade;
use AndyDefer\DomainStructures\Tests\Fixtures\ValueObjects\TestEmailAddress;
use AndyDefer\DomainStructures\Tests\Fixtures\ValueObjects\TestMoney;
use AndyDefer\DomainStructures\Tests\Fixtures\ValueObjects\TestIso8601DateTime;
use AndyDefer\DomainStructures\Utils\DataObject;
use InvalidArgumentException;
use RuntimeException;

final class CollectionHydrationServiceTest extends TestCase
{
    private CollectionHydrationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CollectionHydrationService();
    }

    // ==================== SCALAR COLLECTION TESTS ====================

    public function test_collect_strings(): void
    {
        $sources = ['php', 'laravel', 'testing'];

        $collection = $this->service->collect($sources, StringTypedCollection::class);

        $this->assertInstanceOf(StringTypedCollection::class, $collection);
        $this->assertCount(3, $collection);
        $this->assertSame(['php', 'laravel', 'testing'], $collection->toArray());
    }

    public function test_collect_integers(): void
    {
        $sources = [1, 2, 3, 4, 5];

        $collection = $this->service->collect($sources, IntTypedCollection::class);

        $this->assertInstanceOf(IntTypedCollection::class, $collection);
        $this->assertCount(5, $collection);
        $this->assertSame([1, 2, 3, 4, 5], $collection->toArray());
    }

    public function test_collect_floats(): void
    {
        $sources = [1.1, 2.2, 3.3];

        $collection = $this->service->collect($sources, FloatTypedCollection::class);

        $this->assertInstanceOf(FloatTypedCollection::class, $collection);
        $this->assertCount(3, $collection);
        $this->assertSame([1.1, 2.2, 3.3], $collection->toArray());
    }

    public function test_collect_booleans(): void
    {
        $sources = [true, false, true];
        /** @var BoolTypedCollection  */
        $collection = $this->service->collect($sources, BoolTypedCollection::class);

        $this->assertInstanceOf(BoolTypedCollection::class, $collection);
        $this->assertCount(3, $collection);
        $this->assertSame(2, $collection->countTrue());
        $this->assertSame(1, $collection->countFalse());
    }

    public function test_collect_mixed_scalars_throws_exception(): void
    {
        $sources = [1, 'string', 3.14, true];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Inconsistent families in collection');

        $this->service->collect($sources, MixedScalarCollection::class);
    }

    // ==================== ENUM COLLECTION TESTS ====================

    public function test_collect_enums_from_strings(): void
    {
        $sources = ['admin', 'user', 'guest'];

        $collection = $this->service->collect($sources, TestUserRoleCollection::class);

        $this->assertInstanceOf(TestUserRoleCollection::class, $collection);
        $this->assertCount(3, $collection);
        $this->assertTrue($collection->contains(TestUserRole::ADMIN));
        $this->assertTrue($collection->contains(TestUserRole::USER));
        $this->assertTrue($collection->contains(TestUserRole::GUEST));
    }

    public function test_collect_mixed_enums_with_explicit_type(): void
    {
        $sources = [
            ['_type' => TestUserRole::class, 'value' => 'admin'],
            ['_type' => TestUserStatus::class, 'value' => 'active'],
            ['_type' => TestUserGrade::class, 'value' => 1],
        ];

        $collection = $this->service->collect($sources, EnumCollection::class);

        $this->assertCount(3, $collection);
        $this->assertInstanceOf(TestUserRole::class, $collection[0]);
        $this->assertInstanceOf(TestUserStatus::class, $collection[1]);
        $this->assertInstanceOf(TestUserGrade::class, $collection[2]);
    }

    public function test_collect_enums_from_json(): void
    {
        $array = ['admin', 'user', 'guest'];
        $json = json_encode($array);

        $collection = $this->service->collectFromJson($json, TestUserRoleCollection::class);

        $this->assertCount(3, $collection);
        $this->assertTrue($collection->contains(TestUserRole::ADMIN));
    }

    // ==================== VALUE OBJECT COLLECTION TESTS ====================

    public function test_collect_value_objects_from_arrays(): void
    {
        $sources = [
            ['value' => 'user1@example.com'],
            ['value' => 'user2@example.com'],
            ['value' => 'user3@example.com'],
        ];

        $collection = $this->service->collect($sources, ValueObjectCollection::class);

        $this->assertCount(3, $collection);
        $this->assertInstanceOf(TestEmailAddress::class, $collection[0]);
        $this->assertSame('user1@example.com', $collection[0]->getValue());
    }

    public function test_collect_mixed_value_objects_with_explicit_type(): void
    {
        $sources = [
            ['_type' => TestEmailAddress::class, 'value' => 'user@example.com'],
            ['_type' => TestMoney::class, 'amount' => 99.99, 'currency' => 'EUR'],
            ['_type' => TestIso8601DateTime::class, 'value' => '2024-01-01T12:00:00+00:00'],
        ];

        $collection = $this->service->collect($sources, MixedValueObjectCollection::class);

        $this->assertCount(3, $collection);
        $this->assertInstanceOf(TestEmailAddress::class, $collection[0]);
        $this->assertInstanceOf(TestMoney::class, $collection[1]);
        $this->assertInstanceOf(TestIso8601DateTime::class, $collection[2]);
    }

    public function test_collect_value_objects_from_json(): void
    {
        $array = [
            ['value' => 'user1@example.com'],
            ['value' => 'user2@example.com'],
        ];
        $json = json_encode($array);

        $collection = $this->service->collectFromJson($json, ValueObjectCollection::class);

        $this->assertCount(2, $collection);
        $this->assertInstanceOf(TestEmailAddress::class, $collection[0]);
    }

    // ==================== RECORD COLLECTION TESTS ====================

    public function test_collect_records_from_arrays(): void
    {
        $sources = [
            ['id' => 1, 'name' => 'User One', 'email' => 'user1@example.com', 'status' => 'active', 'role' => 'user', 'grade' => 1],
            ['id' => 2, 'name' => 'User Two', 'email' => 'user2@example.com', 'status' => 'active', 'role' => 'user', 'grade' => 1],
        ];

        $collection = $this->service->collect($sources, TestProductRecordCollection::class);

        $this->assertCount(2, $collection);
        $this->assertInstanceOf(TestProductRecord::class, $collection[0]);
    }

    public function test_collect_mixed_records_with_explicit_type(): void
    {
        $sources = [
            ['_type' => TestUserRecord::class, 'id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com', 'status' => 'active', 'role' => 'user', 'grade' => 1],
            ['_type' => TestProductRecord::class, 'id' => 2, 'name' => 'Laptop', 'price' => 999.99],
        ];

        $collection = $this->service->collect($sources, MixedRecordCollection::class);

        $this->assertCount(2, $collection);
        $this->assertInstanceOf(TestUserRecord::class, $collection[0]);
        $this->assertInstanceOf(TestProductRecord::class, $collection[1]);
    }

    // ==================== DATA COLLECTION TESTS ====================

    public function test_collect_data_objects(): void
    {
        $sources = [
            ['user_id' => 1, 'user_name' => 'User One'],
            ['user_id' => 2, 'user_name' => 'User Two'],
        ];

        $collection = $this->service->collect($sources, DataObjectCollection::class);

        $this->assertCount(2, $collection);
        $this->assertInstanceOf(DataObject::class, $collection[0]);
        $this->assertSame(1, $collection[0]->userId);
        $this->assertSame('User One', $collection[0]->userName);
    }

    public function test_collect_mixed_data_types_with_explicit_type(): void
    {
        $sources = [
            ['_type' => TestUserData::class, 'id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com', 'status' => 'active', 'role' => 'user', 'grade' => 1],
            ['_type' => TestProductData::class, 'id' => 2, 'name' => 'Laptop', 'price' => 999.99],
        ];

        $collection = $this->service->collect($sources, MixedDataCollection::class);

        $this->assertCount(2, $collection);
        $this->assertInstanceOf(TestUserData::class, $collection[0]);
        $this->assertInstanceOf(TestProductData::class, $collection[1]);
    }

    // ==================== INCONSISTENT FAMILY TESTS ====================

    public function test_collect_mixed_data_and_records_throws_exception(): void
    {
        $sources = [
            ['_type' => TestUserData::class, 'id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com', 'status' => 'active', 'role' => 'user', 'grade' => 1],
            ['_type' => TestUserRecord::class, 'id' => 2, 'name' => 'Jane Doe', 'email' => 'jane@example.com', 'status' => 'active', 'role' => 'user', 'grade' => 1],
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Inconsistent families in collection');

        $this->service->collect($sources, MixedDataRecordCollection::class);
    }

    public function test_collect_mixed_scalar_and_object_throws_exception(): void
    {
        $sources = [
            ['_type' => TestUserData::class, 'id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com', 'status' => 'active', 'role' => 'user', 'grade' => 1],
            'simple string',
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Inconsistent families in collection');

        $this->service->collect($sources, MixedScalarDataCollection::class);
    }

    // ==================== JSON COLLECTION TESTS ====================

    public function test_collect_from_json_strings(): void
    {
        $json = json_encode(['php', 'laravel', 'testing']);

        $collection = $this->service->collectFromJson($json, StringTypedCollection::class);

        $this->assertCount(3, $collection);
        $this->assertSame(['php', 'laravel', 'testing'], $collection->toArray());
    }

    // ==================== AMBIGUOUS TYPE TESTS ====================

    public function test_ambiguous_types_throws_exception(): void
    {
        $sources = [
            ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com'],
            ['id' => 2, 'name' => 'Laptop', 'price' => 999.99],
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->service->collect($sources, MixedDataCollection::class);
    }

    public function test_ambiguous_types_with_explicit_type_works(): void
    {
        $sources = [
            ['_type' => TestUserData::class, 'id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com', 'status' => 'active', 'role' => 'user', 'grade' => 1],
            ['_type' => TestProductData::class, 'id' => 2, 'name' => 'Laptop', 'price' => 999.99],
        ];

        $collection = $this->service->collect($sources, MixedDataCollection::class);

        $this->assertCount(2, $collection);
    }

    // ==================== EMPTY COLLECTION TESTS ====================

    public function test_collect_empty_sources_returns_empty_collection(): void
    {
        $sources = [];

        $collection = $this->service->collect($sources, StringTypedCollection::class);

        $this->assertCount(0, $collection);
        $this->assertTrue($collection->isEmpty());
    }

    public function test_collect_from_json_empty_array_returns_empty_collection(): void
    {
        $json = '[]';

        $collection = $this->service->collectFromJson($json, StringTypedCollection::class);

        $this->assertCount(0, $collection);
        $this->assertTrue($collection->isEmpty());
    }

    // ==================== RETURN SAME INSTANCE TESTS ====================

    public function test_collect_returns_same_instance_if_source_is_already_collection(): void
    {
        $original = new StringTypedCollection();
        $original->add('a', 'b', 'c');

        $result = $this->service->collect($original, StringTypedCollection::class);

        $this->assertSame($original, $result);
    }

    // ==================== ORDER PRESERVATION TESTS ====================

    public function test_collect_preserves_order(): void
    {
        $sources = [5, 3, 1, 4, 2];

        $collection = $this->service->collect($sources, IntTypedCollection::class);

        $this->assertSame([5, 3, 1, 4, 2], $collection->toArray());
    }

    public function test_collect_from_json_preserves_order(): void
    {
        $array = [5, 3, 1, 4, 2];
        $json = json_encode($array);

        $collection = $this->service->collectFromJson($json, IntTypedCollection::class);

        $this->assertSame([5, 3, 1, 4, 2], $collection->toArray());
    }

    // ==================== ERROR HANDLING TESTS ====================

    public function test_collect_invalid_collection_class_throws_exception(): void
    {
        $sources = [1, 2, 3];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('must extend');

        $this->service->collect($sources, 'InvalidClass');
    }

    public function test_collect_from_json_invalid_json_throws_exception(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid JSON');

        $this->service->collectFromJson('{invalid}', StringTypedCollection::class);
    }

    public function test_collect_from_json_non_array_json_throws_exception(): void
    {
        $json = '"just a string"';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('JSON must decode to an array or object for collection hydration');

        $this->service->collectFromJson($json, StringTypedCollection::class);
    }

    public function test_collect_with_collection_having_no_allowed_types_throws_exception(): void
    {
        $sources = [1, 2, 3];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('At least one allowed type must be provided');

        $this->service->collect($sources, EmptyTypesCollection::class);
    }
}
