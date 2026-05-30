<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Unit\Abstracts;

use AndyDefer\DomainStructures\Collections\Core\RecordCollection;
use AndyDefer\DomainStructures\Collections\Core\TypedCollection;
use AndyDefer\DomainStructures\Collections\Utility\StringTypedCollection;
use AndyDefer\DomainStructures\Enums\NormalizeMode;
use AndyDefer\DomainStructures\Normalizers\NormalizerChain;
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
use InvalidArgumentException;

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

        $normalized = NormalizerChain::get()->normalize($data);

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

        $normalized = NormalizerChain::get()->normalize($data);

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

        $normalized = NormalizerChain::get()->normalize($data);

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
        $this->assertSame(42, $newData->id);
        $this->assertSame('Jane Smith', $newData->name);
        $this->assertSame('jane@example.com', $newData->email->getValue());
    }

    // ==================== COLLECTION HYDRATION TESTS ====================

    public function test_collect_creates_array_of_data_dtos_from_collection(): void
    {
        $recordCollection = new RecordCollection(TestUserRecord::class);
        $recordCollection->add(
            new TestUserRecord(id: 1, name: 'User 1', email: TestEmailAddress::from('user1@example.com')),
            new TestUserRecord(id: 2, name: 'User 2', email: TestEmailAddress::from('user2@example.com'))
        );

        // ✅ collect() retourne maintenant une TypedCollection, pas un array
        $dataCollection = TestUserData::collect($recordCollection);

        $this->assertInstanceOf(TypedCollection::class, $dataCollection);
        $this->assertCount(2, $dataCollection);
        $this->assertInstanceOf(TestUserData::class, $dataCollection[0]);
        $this->assertInstanceOf(TestUserData::class, $dataCollection[1]);
        $this->assertSame(1, $dataCollection[0]->id);
        $this->assertSame('User 1', $dataCollection[0]->name);
        $this->assertSame(2, $dataCollection[1]->id);
        $this->assertSame('User 2', $dataCollection[1]->name);
    }

    public function test_collect_on_empty_collection_returns_empty_collection(): void
    {
        $collection = new TypedCollection(NormalizeMode::class);

        $emptyCollection = new RecordCollection(TestUserRecord::class);
        $result = TestUserData::collect($emptyCollection);

        $this->assertInstanceOf(TypedCollection::class, $result);
        $this->assertCount(0, $result);
        $this->assertTrue($result->isEmpty());
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

        $normalizedProduct = NormalizerChain::get()->normalize($productData);

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

        $normalized = NormalizerChain::get()->normalize($data);

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

        $normalized = NormalizerChain::get()->normalize($data);

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

        $first = NormalizerChain::get()->normalize($data);
        $second = NormalizerChain::get()->normalize($data);

        $this->assertSame($first, $second);
    }

    // ==================== ERROR HANDLING TESTS ====================

    public function test_hydration_throws_exception_when_required_property_missing(): void
    {
        $source = DataObject::from(['name' => 'John Doe']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Missing required parameter "\$email"/');

        $data = TestUserData::from($source);
    }

    public function test_hydration_throws_exception_when_required_property_missing_in_array(): void
    {
        $source = ['name' => 'John Doe'];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Missing required parameter "\$email"/');

        TestUserData::from($source);
    }

    // ==================== TO ARRAY METHOD TESTS ====================

    public function test_to_array_returns_normalized_array(): void
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

        $array = $data->toArray();

        $this->assertIsArray($array);
        $this->assertSame(1, $array['id']);
        $this->assertSame('John Doe', $array['name']);
        $this->assertSame('john.doe@example.com', $array['email']);
        $this->assertSame('active', $array['status']);
        $this->assertSame('user', $array['role']);
        $this->assertSame(1, $array['grade']);
        $this->assertArrayHasKey('emailVerifiedAt', $array);
        $this->assertArrayHasKey('createdAt', $array);
    }

    public function test_to_array_preserves_camel_case_keys(): void
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

        $array = $data->toArray();

        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('name', $array);
        $this->assertArrayHasKey('email', $array);
        $this->assertArrayHasKey('status', $array);
        $this->assertArrayHasKey('role', $array);
        $this->assertArrayHasKey('grade', $array);
        $this->assertArrayHasKey('emailVerifiedAt', $array);
        $this->assertArrayHasKey('tags', $array);
        $this->assertArrayHasKey('createdAt', $array);

        // Vérifier qu'il n'y a pas de clés snake_case
        $this->assertArrayNotHasKey('email_verified_at', $array);
        $this->assertArrayNotHasKey('created_at', $array);
    }

    public function test_to_array_returns_same_as_normalizer_chain(): void
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

        $expected = NormalizerChain::get()->normalize($data);
        $actual = $data->toArray();

        $this->assertSame($expected, $actual);
    }

    public function test_to_array_on_data_with_null_values(): void
    {
        $data = new TestUserData(
            id: null,
            name: 'John Doe',
            email: $this->testEmail,
            status: TestUserStatus::ACTIVE,
            role: TestUserRole::USER,
            grade: TestUserGrade::BRONZE,
            emailVerifiedAt: null,
            tags: new StringTypedCollection,
            createdAt: $this->now
        );

        $array = $data->toArray();

        $this->assertNull($array['id']);
        $this->assertNull($array['emailVerifiedAt']);
        $this->assertSame('John Doe', $array['name']);
    }

    public function test_to_array_on_data_with_empty_collection(): void
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

        $array = $data->toArray();

        $this->assertIsArray($array['tags']);
        $this->assertEmpty($array['tags']);
    }

    public function test_to_array_on_data_with_nested_data_objects(): void
    {
        $tags = new StringTypedCollection;
        $tags->add('premium', 'vip');

        $data = new TestUserData(
            id: 1,
            name: 'John Doe',
            email: $this->testEmail,
            status: TestUserStatus::ACTIVE,
            role: TestUserRole::USER,
            grade: TestUserGrade::BRONZE,
            emailVerifiedAt: $this->now,
            tags: $tags,
            createdAt: $this->now
        );

        $array = $data->toArray();

        $this->assertIsArray($array['tags']);
        $this->assertCount(2, $array['tags']);
        $this->assertSame(['premium', 'vip'], $array['tags']);
    }

    public function test_to_string_uses_to_array_internally(): void
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

        $expectedJson = json_encode($data->toArray(), JSON_THROW_ON_ERROR);
        $actualString = (string) $data;

        $this->assertSame($expectedJson, $actualString);
    }
}
