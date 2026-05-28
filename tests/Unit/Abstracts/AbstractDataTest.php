<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Unit\Abstracts;

use AndyDefer\DomainStructures\Collections\Core\RecordCollection;
use AndyDefer\DomainStructures\Collections\Utility\StringTypedCollection;
use AndyDefer\DomainStructures\Tests\Fixtures\Data\TestProductData;
use AndyDefer\DomainStructures\Tests\Fixtures\Data\TestUserData;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestUserGrade;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestUserRole;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestUserStatus;
use AndyDefer\DomainStructures\Tests\Fixtures\Records\TestUserRecord;
use AndyDefer\DomainStructures\Tests\Fixtures\ValueObjects\TestEmailAddress;
use AndyDefer\DomainStructures\Tests\Fixtures\ValueObjects\TestIso8601DateTime;
use AndyDefer\DomainStructures\Tests\TestCase;
use AndyDefer\DomainStructures\Utils\DataObject;
use RuntimeException;

final class AbstractDataTest extends TestCase
{
    private TestIso8601DateTime $now;

    private TestEmailAddress $testEmail;

    protected function setUp(): void
    {
        parent::setUp();

        $this->now = TestIso8601DateTime::from('2024-01-01T12:00:00+00:00');
        $this->testEmail = TestEmailAddress::from('john.doe@example.com');
    }

    // ==================== NORMALIZATION TO ARRAY TESTS ====================

    public function test_data_dto_normalizes_to_array_with_camel_case_keys(): void
    {
        $data = new TestUserData(
            id: 1,
            name: 'John Doe',
            email: $this->testEmail,
            status: TestUserStatus::ACTIVE,
            role: TestUserRole::USER,
            grade: TestUserGrade::BRONZE,
            emailVerifiedAt: $this->now,
            tags: new StringTypedCollection,
            createdAt: $this->now
        );

        $normalized = $data->normalize();

        $this->assertIsArray($normalized);
        $this->assertArrayHasKey('id', $normalized);
        $this->assertArrayHasKey('name', $normalized);
        $this->assertArrayHasKey('email', $normalized);
        $this->assertArrayHasKey('status', $normalized);
        $this->assertArrayHasKey('role', $normalized);
        $this->assertArrayHasKey('grade', $normalized);
        $this->assertArrayHasKey('emailVerifiedAt', $normalized);
        $this->assertArrayHasKey('tags', $normalized);
        $this->assertArrayHasKey('createdAt', $normalized);

        $this->assertSame(1, $normalized['id']);
        $this->assertSame('John Doe', $normalized['name']);
        $this->assertSame('john.doe@example.com', $normalized['email']);
        $this->assertSame('active', $normalized['status']);
        $this->assertSame('user', $normalized['role']);
        $this->assertSame(1, $normalized['grade']);
    }

    public function test_data_dto_normalizes_nested_objects_recursively(): void
    {
        $data = new TestUserData(
            id: 1,
            name: 'John Doe',
            email: $this->testEmail,
            status: TestUserStatus::ACTIVE,
            role: TestUserRole::USER,
            grade: TestUserGrade::BRONZE,
            emailVerifiedAt: $this->now,
            tags: new StringTypedCollection,
            createdAt: $this->now
        );

        $normalized = $data->normalize();

        $this->assertIsString($normalized['email']);
        $this->assertIsString($normalized['status']);
        $this->assertIsString($normalized['role']);
        $this->assertIsInt($normalized['grade']);
        $this->assertIsString($normalized['emailVerifiedAt']);
        $this->assertIsString($normalized['createdAt']);
    }

    public function test_data_dto_normalizes_with_null_values(): void
    {
        $data = new TestUserData(
            id: 1,
            name: 'John Doe',
            email: $this->testEmail,
            status: TestUserStatus::ACTIVE,
            role: TestUserRole::USER,
            grade: TestUserGrade::BRONZE,
            emailVerifiedAt: null,
            tags: new StringTypedCollection,
            createdAt: $this->now
        );

        $normalized = $data->normalize();

        $this->assertArrayHasKey('emailVerifiedAt', $normalized);
        $this->assertNull($normalized['emailVerifiedAt']);
    }

    // ==================== MAGIC TO_STRING TESTS ====================

    public function test_magic_to_string_returns_json(): void
    {
        $data = new TestUserData(
            id: 1,
            name: 'John Doe',
            email: $this->testEmail,
            status: TestUserStatus::ACTIVE,
            role: TestUserRole::USER,
            grade: TestUserGrade::BRONZE,
            emailVerifiedAt: null,
            tags: new StringTypedCollection,
            createdAt: $this->now
        );

        $string = (string) $data;

        $this->assertIsString($string);
        $this->assertJson($string);

        $decoded = json_decode($string, true);
        $this->assertSame('John Doe', $decoded['name']);
    }

    // ==================== HYDRATION FROM SOURCE TESTS ====================

    public function test_data_dto_hydrates_from_record(): void
    {
        $record = new TestUserRecord(
            id: 1,
            name: 'John Doe',
            email: $this->testEmail,
            status: TestUserStatus::ACTIVE,
            role: TestUserRole::ADMIN,
            grade: TestUserGrade::GOLD,
            createdAt: $this->now
        );

        $data = TestUserData::from($record);

        $this->assertInstanceOf(TestUserData::class, $data);
        $this->assertSame(1, $data->id);
        $this->assertSame('John Doe', $data->name);
        $this->assertSame('john.doe@example.com', $data->email->getValue());
        $this->assertSame(TestUserStatus::ACTIVE, $data->status);
        $this->assertSame(TestUserRole::ADMIN, $data->role);
        $this->assertSame(TestUserGrade::GOLD, $data->grade);
    }

    public function test_data_dto_hydrates_from_data_object(): void
    {
        $source = DataObject::from([
            'id' => 1,
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'status' => 'active',
            'role' => 'user',
            'grade' => 1,
        ]);

        $data = TestUserData::from($source);

        $this->assertInstanceOf(TestUserData::class, $data);
        $this->assertSame(1, $data->id);
        $this->assertSame('John Doe', $data->name);
        $this->assertSame('john.doe@example.com', $data->email->getValue());
        $this->assertSame(TestUserStatus::ACTIVE, $data->status);
        $this->assertSame(TestUserRole::USER, $data->role);
        $this->assertSame(TestUserGrade::BRONZE, $data->grade);
    }

    public function test_data_dto_hydrates_from_array(): void
    {
        $source = [
            'id' => 1,
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'status' => 'active',
            'role' => 'user',
            'grade' => 1,
        ];

        $data = TestUserData::from($source);

        $this->assertInstanceOf(TestUserData::class, $data);
        $this->assertSame(1, $data->id);
        $this->assertSame('John Doe', $data->name);
        $this->assertSame('john.doe@example.com', $data->email->getValue());
        $this->assertSame(TestUserStatus::ACTIVE, $data->status);
        $this->assertSame(TestUserRole::USER, $data->role);
        $this->assertSame(TestUserGrade::BRONZE, $data->grade);
    }

    public function test_data_dto_hydrates_from_another_data_dto(): void
    {
        $sourceData = new TestUserData(
            id: 42,
            name: 'Jane Smith',
            email: TestEmailAddress::from('jane@example.com'),
            status: TestUserStatus::ACTIVE,
            role: TestUserRole::ADMIN,
            grade: TestUserGrade::PLATINUM,
            emailVerifiedAt: $this->now,
            tags: new StringTypedCollection,
            createdAt: $this->now
        );

        $newData = TestUserData::from($sourceData);

        $this->assertInstanceOf(TestUserData::class, $newData);
        $this->assertNotSame($sourceData, $newData);
        $this->assertSame(42, $newData->id);
        $this->assertSame('Jane Smith', $newData->name);
        $this->assertSame('jane@example.com', $newData->email->getValue());
    }

    // ==================== COLLECTION HYDRATION TESTS ====================

    public function test_collect_creates_array_of_data_dtos_from_collection(): void
    {
        $recordCollection = new RecordCollection;
        $recordCollection->add(
            new TestUserRecord(id: 1, name: 'User 1', email: TestEmailAddress::from('user1@example.com')),
            new TestUserRecord(id: 2, name: 'User 2', email: TestEmailAddress::from('user2@example.com'))
        );

        $dataArray = TestUserData::collect($recordCollection);

        $this->assertIsArray($dataArray);
        $this->assertCount(2, $dataArray);
        $this->assertInstanceOf(TestUserData::class, $dataArray[0]);
        $this->assertInstanceOf(TestUserData::class, $dataArray[1]);
        $this->assertSame(1, $dataArray[0]->id);
        $this->assertSame('User 1', $dataArray[0]->name);
        $this->assertSame(2, $dataArray[1]->id);
        $this->assertSame('User 2', $dataArray[1]->name);
    }

    public function test_collect_on_empty_collection_returns_empty_array(): void
    {
        $emptyCollection = new RecordCollection;
        $result = TestUserData::collect($emptyCollection);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    // ==================== NESTED DATA DTO TESTS ====================

    public function test_product_data_normalizes_correctly(): void
    {
        $productData = new TestProductData(
            id: 1,
            name: 'Laptop',
            price: 999.99,
            isFeatured: true
        );

        $normalizedProduct = $productData->normalize();

        $this->assertIsArray($normalizedProduct);
        $this->assertSame(1, $normalizedProduct['id']);
        $this->assertSame('Laptop', $normalizedProduct['name']);
        $this->assertSame(999.99, $normalizedProduct['price']);
        $this->assertTrue($normalizedProduct['isFeatured']);
    }

    public function test_data_dto_with_collection_normalizes_correctly(): void
    {
        $tags = new StringTypedCollection;
        $tags->add('premium', 'vip', 'new');

        $data = new TestUserData(
            id: 1,
            name: 'John Doe',
            email: $this->testEmail,
            status: TestUserStatus::ACTIVE,
            role: TestUserRole::USER,
            grade: TestUserGrade::BRONZE,
            emailVerifiedAt: null,
            tags: $tags,
            createdAt: $this->now
        );

        $normalized = $data->normalize();

        $this->assertIsArray($normalized['tags']);
        $this->assertCount(3, $normalized['tags']);
        $this->assertSame(['premium', 'vip', 'new'], $normalized['tags']);
    }

    // ==================== TYPE CONVERSION TESTS ====================

    public function test_int_id_remains_int_in_data_dto(): void
    {
        $record = new TestUserRecord(id: 123, name: 'John Doe', email: $this->testEmail);
        $data = TestUserData::from($record);

        $this->assertIsInt($data->id);
        $this->assertSame(123, $data->id);
    }

    public function test_enum_values_are_preserved_as_enums_in_data_dto(): void
    {
        $record = new TestUserRecord(
            name: 'John Doe',
            email: $this->testEmail,
            status: TestUserStatus::SUSPENDED,
            role: TestUserRole::ADMIN,
            grade: TestUserGrade::GOLD
        );

        $data = TestUserData::from($record);

        $this->assertInstanceOf(TestUserStatus::class, $data->status);
        $this->assertInstanceOf(TestUserRole::class, $data->role);
        $this->assertInstanceOf(TestUserGrade::class, $data->grade);
        $this->assertSame(TestUserStatus::SUSPENDED, $data->status);
        $this->assertSame(TestUserRole::ADMIN, $data->role);
        $this->assertSame(TestUserGrade::GOLD, $data->grade);
    }

    // ==================== EDGE CASES TESTS ====================

    public function test_data_dto_with_nullable_fields_normalizes_correctly(): void
    {
        $data = new TestUserData(
            id: null,
            name: '',
            email: $this->testEmail,
            status: TestUserStatus::ACTIVE,
            role: TestUserRole::USER,
            grade: TestUserGrade::BRONZE,
            emailVerifiedAt: null,
            tags: new StringTypedCollection,
            createdAt: $this->now
        );

        $normalized = $data->normalize();

        $this->assertIsArray($normalized);
        $this->assertNull($normalized['id']);
        $this->assertNull($normalized['emailVerifiedAt']);
    }

    public function test_multiple_normalization_calls_produce_same_result(): void
    {
        $data = new TestUserData(
            id: 1,
            name: 'John Doe',
            email: $this->testEmail,
            status: TestUserStatus::ACTIVE,
            role: TestUserRole::USER,
            grade: TestUserGrade::BRONZE,
            emailVerifiedAt: null,
            tags: new StringTypedCollection,
            createdAt: $this->now
        );

        $first = $data->normalize();
        $second = $data->normalize();

        $this->assertSame($first, $second);
    }

    // ==================== ERROR HANDLING TESTS ====================

    public function test_hydration_throws_exception_when_required_property_missing(): void
    {
        $source = DataObject::from(['name' => 'John Doe']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Missing required parameter "$email"');

        TestUserData::from($source);
    }

    public function test_hydration_throws_exception_when_required_property_missing_in_array(): void
    {
        $source = ['name' => 'John Doe'];

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Missing required parameter "$email"');

        TestUserData::from($source);
    }
}
