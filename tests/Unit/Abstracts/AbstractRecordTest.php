<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Unit\Abstracts;

use AndyDefer\DomainStructures\Collections\Core\RecordCollection;
use AndyDefer\DomainStructures\Collections\Core\TypedCollection;
use AndyDefer\DomainStructures\Collections\Utility\StringTypedCollection;
use AndyDefer\DomainStructures\Normalizers\NormalizerChain;
use AndyDefer\DomainStructures\Tests\Fixtures\Collections\TestProductRecordCollection;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestUserGrade;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestUserRole;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestUserStatus;
use AndyDefer\DomainStructures\Tests\Fixtures\Records\TestProductRecord;
use AndyDefer\DomainStructures\Tests\Fixtures\Records\TestRequiredRecord;
use AndyDefer\DomainStructures\Tests\Fixtures\Records\TestUserRecord;
use AndyDefer\DomainStructures\Tests\Fixtures\Records\TestUserUpdateRecord;
use AndyDefer\DomainStructures\Tests\Fixtures\ValueObjects\TestEmailAddress;
use AndyDefer\DomainStructures\Tests\Fixtures\ValueObjects\TestIso8601DateTime;
use AndyDefer\DomainStructures\Tests\TestCase;
use AndyDefer\DomainStructures\Utils\DataObject;
use RuntimeException;

final class AbstractRecordTest extends TestCase
{
    private TestIso8601DateTime $now;

    private TestEmailAddress $testEmail;

    private StringTypedCollection $tags;

    protected function setUp(): void
    {
        parent::setUp();

        $this->now = TestIso8601DateTime::from('2024-01-01T12:00:00+00:00');
        $this->testEmail = TestEmailAddress::from('john.doe@example.com');
        $this->tags = new StringTypedCollection;
        $this->tags->add('premium', 'vip');
    }

    // ==================== NORMALIZATION TO ARRAY TESTS ====================

    public function test_record_normalizes_to_array_with_snake_case_keys(): void
    {
        $record = new TestUserRecord(
            id: 1,
            name: 'John Doe',
            email: $this->testEmail,
            status: TestUserStatus::ACTIVE,
            role: TestUserRole::USER,
            grade: TestUserGrade::BRONZE,
            emailVerifiedAt: $this->now,
            tags: $this->tags,
            createdAt: $this->now
        );

        $normalized = NormalizerChain::get()->normalize($record);

        $this->assertIsArray($normalized);
        $this->assertArrayHasKey('id', $normalized);
        $this->assertArrayHasKey('name', $normalized);
        $this->assertArrayHasKey('email', $normalized);
        $this->assertArrayHasKey('status', $normalized);
        $this->assertArrayHasKey('role', $normalized);
        $this->assertArrayHasKey('grade', $normalized);
        $this->assertArrayHasKey('email_verified_at', $normalized);
        $this->assertArrayHasKey('tags', $normalized);
        $this->assertArrayHasKey('products', $normalized);
        $this->assertArrayHasKey('featured_product', $normalized);
        $this->assertArrayHasKey('created_at', $normalized);

        $this->assertEquals(1, $normalized['id']);
        $this->assertSame('John Doe', $normalized['name']);
        $this->assertSame('john.doe@example.com', $normalized['email']);
        $this->assertSame('active', $normalized['status']);
        $this->assertSame('user', $normalized['role']);
        $this->assertEquals(1, $normalized['grade']);
    }

    public function test_record_normalizes_nested_objects_recursively(): void
    {
        $record = new TestUserRecord(
            id: 1,
            name: 'John Doe',
            email: $this->testEmail,
            status: TestUserStatus::ACTIVE,
            role: TestUserRole::USER,
            grade: TestUserGrade::BRONZE,
            emailVerifiedAt: $this->now,
            tags: $this->tags,
            createdAt: $this->now
        );

        $normalized = NormalizerChain::get()->normalize($record);

        $this->assertIsString($normalized['email']);
        $this->assertIsString($normalized['status']);
        $this->assertIsString($normalized['role']);
        $this->assertIsInt($normalized['grade']);
        $this->assertIsString($normalized['email_verified_at']);
        $this->assertIsArray($normalized['tags']);
        $this->assertIsString($normalized['created_at']);
    }

    public function test_record_normalizes_with_null_values_included(): void
    {
        $record = new TestUserRecord(
            name: 'John Doe',
            email: $this->testEmail,
            id: null,
            emailVerifiedAt: null,
            featuredProduct: null,
            grade: null
        );

        $normalized = NormalizerChain::get()->normalize($record);

        // Tous les champs sont inclus, même les null
        $this->assertArrayHasKey('id', $normalized);
        $this->assertArrayHasKey('email_verified_at', $normalized);
        $this->assertArrayHasKey('featured_product', $normalized);
        $this->assertArrayHasKey('grade', $normalized);
        $this->assertArrayHasKey('name', $normalized);
        $this->assertArrayHasKey('email', $normalized);
        $this->assertArrayHasKey('status', $normalized);
        $this->assertArrayHasKey('role', $normalized);
        $this->assertArrayHasKey('tags', $normalized);
        $this->assertArrayHasKey('products', $normalized);

        $this->assertNull($normalized['id']);
        $this->assertNull($normalized['email_verified_at']);
        $this->assertNull($normalized['featured_product']);
        $this->assertNull($normalized['grade']);
    }

    public function test_record_includes_null_values_by_default(): void
    {
        $record = new TestUserRecord(
            name: 'John Doe',
            email: $this->testEmail,
            id: null,
            emailVerifiedAt: null
        );

        $normalized = NormalizerChain::get()->normalize($record);

        $this->assertArrayHasKey('id', $normalized);
        $this->assertArrayHasKey('email_verified_at', $normalized);
        $this->assertNull($normalized['id']);
        $this->assertNull($normalized['email_verified_at']);
    }

    public function test_record_converts_camel_case_to_snake_case(): void
    {
        $record = new TestUserRecord(
            emailVerifiedAt: $this->now,
            createdAt: $this->now
        );

        $normalized = NormalizerChain::get()->normalize($record);

        $this->assertArrayHasKey('email_verified_at', $normalized);
        $this->assertArrayNotHasKey('emailVerifiedAt', $normalized);
        $this->assertArrayHasKey('created_at', $normalized);
        $this->assertArrayNotHasKey('createdAt', $normalized);
    }

    // ==================== MAGIC TO_STRING TESTS ====================

    public function test_magic_to_string_returns_json_representation(): void
    {
        $record = new TestUserRecord(
            id: 1,
            name: 'John Doe',
            email: $this->testEmail
        );

        $string = (string) $record;

        $this->assertIsString($string);
        $this->assertJson($string);

        $decoded = json_decode($string, true);
        $this->assertEquals(1, $decoded['id']);
        $this->assertSame('John Doe', $decoded['name']);
    }

    // ==================== HYDRATION FROM SOURCE TESTS ====================

    public function test_record_hydrates_from_data_object(): void
    {
        $source = DataObject::from([
            'id' => 1,
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'status' => 'active',
            'role' => 'admin',
            'grade' => 4,
        ]);

        $record = TestUserRecord::from($source);

        $this->assertInstanceOf(TestUserRecord::class, $record);
        $this->assertSame(1, $record->id);
        $this->assertSame('John Doe', $record->name);
        $this->assertSame('john.doe@example.com', $record->email->getValue());
        $this->assertSame(TestUserStatus::ACTIVE, $record->status);
        $this->assertSame(TestUserRole::ADMIN, $record->role);
        $this->assertSame(TestUserGrade::PLATINUM, $record->grade);
    }

    public function test_record_hydrates_from_another_record(): void
    {
        $sourceRecord = new TestUserRecord(
            id: 42,
            name: 'Jane Smith',
            email: TestEmailAddress::from('jane@example.com'),
            status: TestUserStatus::ACTIVE,
            role: TestUserRole::ADMIN,
            grade: TestUserGrade::GOLD,
            tags: $this->tags,
            createdAt: $this->now
        );

        $newRecord = TestUserRecord::from($sourceRecord);

        $this->assertInstanceOf(TestUserRecord::class, $newRecord);
        $this->assertNotSame($sourceRecord, $newRecord);
        $this->assertSame(42, $newRecord->id);
        $this->assertSame('Jane Smith', $newRecord->name);
        $this->assertSame('jane@example.com', $newRecord->email->getValue());
        $this->assertSame(TestUserStatus::ACTIVE, $newRecord->status);
        $this->assertSame(TestUserRole::ADMIN, $newRecord->role);
        $this->assertSame(TestUserGrade::GOLD, $newRecord->grade);
    }

    public function test_record_hydration_respects_default_values(): void
    {
        $source = DataObject::from([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $record = TestUserRecord::from($source);

        $this->assertSame(TestUserStatus::ACTIVE, $record->status);
        $this->assertSame(TestUserRole::USER, $record->role);
        $this->assertSame(TestUserGrade::BRONZE, $record->grade);
    }

    public function test_record_hydration_with_explicit_null_overrides_defaults(): void
    {
        $source = DataObject::from([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'status' => null,
            'role' => null,
            'grade' => null,
        ]);

        $record = TestUserRecord::from($source);

        $this->assertNull($record->status);
        $this->assertNull($record->role);
        $this->assertNull($record->grade);
    }

    public function test_record_hydrates_from_partial_record_for_updates(): void
    {
        $updateData = new TestUserUpdateRecord(
            name: 'Updated Name',
            lifeStage: null
        );

        $targetRecord = TestUserRecord::from($updateData);

        $this->assertSame('Updated Name', $targetRecord->name);
        $this->assertNull($targetRecord->id);
        $this->assertNull($targetRecord->email);
    }

    // ==================== COLLECTION HYDRATION TESTS ====================

    public function test_collect_creates_collection_of_records_from_collection(): void
    {
        $recordCollection = new RecordCollection(TestUserRecord::class);
        $recordCollection->add(
            new TestUserRecord(id: 1, name: 'User 1', email: TestEmailAddress::from('user1@example.com')),
            new TestUserRecord(id: 2, name: 'User 2', email: TestEmailAddress::from('user2@example.com'))
        );

        $resultCollection = TestUserRecord::collect($recordCollection);

        $this->assertInstanceOf(TypedCollection::class, $resultCollection);
        $this->assertCount(2, $resultCollection);
        $this->assertInstanceOf(TestUserRecord::class, $resultCollection[0]);
        $this->assertInstanceOf(TestUserRecord::class, $resultCollection[1]);
        $this->assertSame(1, $resultCollection[0]->id);
        $this->assertSame('User 1', $resultCollection[0]->name);
        $this->assertSame(2, $resultCollection[1]->id);
        $this->assertSame('User 2', $resultCollection[1]->name);
    }

    public function test_collect_on_empty_collection_returns_empty_typed_collection(): void
    {
        $emptyCollection = new RecordCollection(TestUserRecord::class);
        $result = TestUserRecord::collect($emptyCollection);

        $this->assertInstanceOf(\AndyDefer\DomainStructures\Collections\Core\TypedCollection::class, $result);
        $this->assertCount(0, $result);
        $this->assertTrue($result->isEmpty());
    }

    // ==================== NESTED RECORD TESTS ====================

    public function test_record_with_nested_product_collection_normalizes_correctly(): void
    {
        $products = new TestProductRecordCollection;
        $products->add(
            new TestProductRecord(id: 1, name: 'Laptop', price: 999, isFeatured: true),
            new TestProductRecord(id: 2, name: 'Mouse', price: 29, isFeatured: false)
        );

        $record = new TestUserRecord(
            id: 1,
            name: 'John Doe',
            email: $this->testEmail,
            products: $products
        );

        $normalized = NormalizerChain::get()->normalize($record);

        $this->assertIsArray($normalized['products']);
        $this->assertCount(2, $normalized['products']);
        $this->assertSame('Laptop', $normalized['products'][0]['name']);
        $this->assertEquals(999, $normalized['products'][0]['price']);
        $this->assertTrue($normalized['products'][0]['is_featured']);
        $this->assertSame('Mouse', $normalized['products'][1]['name']);
        $this->assertEquals(29, $normalized['products'][1]['price']);
        $this->assertFalse($normalized['products'][1]['is_featured']);
    }

    public function test_record_with_nested_collection_normalizes_recursively(): void
    {
        $tags = new StringTypedCollection;
        $tags->add('tag1', 'tag2', 'tag3');

        $record = new TestUserRecord(
            id: 1,
            name: 'John Doe',
            email: $this->testEmail,
            tags: $tags
        );

        $normalized = NormalizerChain::get()->normalize($record);

        $this->assertIsArray($normalized['tags']);
        $this->assertCount(3, $normalized['tags']);
        $this->assertSame(['tag1', 'tag2', 'tag3'], $normalized['tags']);
    }

    // ==================== TYPE CONVERSION TESTS ====================

    public function test_string_id_becomes_int_id_in_record(): void
    {
        $source = DataObject::from([
            'id' => '123',
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $record = TestUserRecord::from($source);

        $this->assertIsInt($record->id);
        $this->assertSame(123, $record->id);
    }

    public function test_string_enum_value_becomes_proper_enum(): void
    {
        $source = DataObject::from([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'status' => 'suspended',
            'role' => 'admin',
            'grade' => 4,
        ]);

        $record = TestUserRecord::from($source);

        $this->assertInstanceOf(TestUserStatus::class, $record->status);
        $this->assertInstanceOf(TestUserRole::class, $record->role);
        $this->assertInstanceOf(TestUserGrade::class, $record->grade);
        $this->assertSame(TestUserStatus::SUSPENDED, $record->status);
        $this->assertSame(TestUserRole::ADMIN, $record->role);
        $this->assertSame(TestUserGrade::PLATINUM, $record->grade);
    }

    // ==================== EDGE CASES TESTS ====================

    public function test_record_with_all_nulls_normalizes_correctly(): void
    {
        $record = new TestUserRecord(
            id: null,
            name: null,
            email: null,
            status: null,
            role: null,
            grade: null,
            emailVerifiedAt: null,
            featuredProduct: null,
            createdAt: null
        );

        $normalized = NormalizerChain::get()->normalize($record);

        $this->assertArrayHasKey('tags', $normalized);
        $this->assertArrayHasKey('products', $normalized);
        $this->assertNull($normalized['id']);
        $this->assertNull($normalized['email_verified_at']);
    }

    public function test_multiple_normalization_calls_produce_same_result(): void
    {
        $record = new TestUserRecord(
            id: 1,
            name: 'John Doe',
            email: $this->testEmail
        );

        $first = NormalizerChain::get()->normalize($record);
        $second = NormalizerChain::get()->normalize($record);

        $this->assertSame($first, $second);
    }

    // ==================== ERROR HANDLING TESTS ====================

    public function test_hydration_throws_exception_when_required_property_missing(): void
    {
        $source = DataObject::from(['email' => 'john@example.com']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Missing required parameter "$name"');

        TestRequiredRecord::from($source);
    }

    public function test_hydration_throws_exception_for_invalid_enum_value(): void
    {
        $source = DataObject::from([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'status' => 'invalid_status_value',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/Invalid value "invalid_status_value" for enum/');

        TestUserRecord::from($source);
    }

    public function test_hydration_throws_exception_for_invalid_integer_value(): void
    {
        $source = DataObject::from([
            'id' => 'not an integer',
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot convert value to int');

        TestUserRecord::from($source);
    }

    // ==================== PRIVATE METHOD TESTS ====================

    public function test_camel_case_to_snake_case_conversion_works_correctly(): void
    {
        $record = new TestUserRecord(
            emailVerifiedAt: $this->now,
            createdAt: $this->now
        );

        $normalized = NormalizerChain::get()->normalize($record);

        $this->assertArrayHasKey('email_verified_at', $normalized);
        $this->assertArrayHasKey('created_at', $normalized);
        $this->assertArrayNotHasKey('emailVerifiedAt', $normalized);
        $this->assertArrayNotHasKey('createdAt', $normalized);
    }

    public function test_conversion_handles_multiple_uppercase_letters(): void
    {
        $record = new TestUserRecord(
            emailVerifiedAt: $this->now
        );

        $normalized = NormalizerChain::get()->normalize($record);

        $this->assertArrayHasKey('email_verified_at', $normalized);
    }
}
