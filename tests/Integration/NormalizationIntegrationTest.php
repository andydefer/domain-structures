<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Integration;

use AndyDefer\DomainStructures\Collections\Core\RecordCollection;
use AndyDefer\DomainStructures\Collections\Core\TypedCollection;
use AndyDefer\DomainStructures\Collections\Utility\IntTypedCollection;
use AndyDefer\DomainStructures\Collections\Utility\StringTypedCollection;
use AndyDefer\DomainStructures\Normalizers\RootNormalizer;
use AndyDefer\DomainStructures\Tests\Fixtures\Collections\ProductRecordCollection;
use AndyDefer\DomainStructures\Tests\Fixtures\Collections\TestUserRoleCollection;
use AndyDefer\DomainStructures\Tests\Fixtures\Data\TestUserData;
use AndyDefer\DomainStructures\Tests\Fixtures\Data\TestUserWithRolesData;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestUserGrade;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestUserRole;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestUserStatus;
use AndyDefer\DomainStructures\Tests\Fixtures\Records\TestProductRecord;
use AndyDefer\DomainStructures\Tests\Fixtures\Records\TestUserRecord;
use AndyDefer\DomainStructures\Tests\Fixtures\ValueObjects\TestEmailAddress;
use AndyDefer\DomainStructures\Tests\Fixtures\ValueObjects\TestIso8601DateTime;
use AndyDefer\DomainStructures\Tests\TestCase;
use AndyDefer\DomainStructures\Utils\DataObject;

final class NormalizationIntegrationTest extends TestCase
{
    private TestIso8601DateTime $now;

    private TestEmailAddress $testEmail;

    private StringTypedCollection $tags;

    private RootNormalizer $rootNormalizer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rootNormalizer = new RootNormalizer;
        $this->now = TestIso8601DateTime::from('2024-01-01T12:00:00+00:00');
        $this->testEmail = TestEmailAddress::from('john@example.com');
        $this->tags = new StringTypedCollection;
        $this->tags->add('premium', 'vip', 'early_adopter');
    }

    // ==================== RECORD NORMALIZATION TESTS ====================

    public function test_simple_record_normalizes_to_snake_case_array(): void
    {
        $record = new TestUserRecord(
            id: 1,
            name: 'John Doe',
            email: $this->testEmail,
            status: TestUserStatus::ACTIVE,
            role: TestUserRole::USER,
            grade: TestUserGrade::BRONZE
        );

        $normalized = $this->rootNormalizer->normalize($record);

        $this->assertIsArray($normalized);
        $this->assertEquals(1, $normalized['id']);
        $this->assertSame('John Doe', $normalized['name']);
        $this->assertSame('john@example.com', $normalized['email']);
        $this->assertSame('active', $normalized['status']);
        $this->assertSame('user', $normalized['role']);
        $this->assertEquals(1, $normalized['grade']);
    }

    public function test_record_includes_all_null_values(): void
    {
        $record = new TestUserRecord(
            name: 'John Doe',
            email: $this->testEmail,
            id: null,
            emailVerifiedAt: null,
            featuredProduct: null
        );

        $normalized = $this->rootNormalizer->normalize($record);

        // Les null sont TOUJOURS inclus
        $this->assertArrayHasKey('id', $normalized);
        $this->assertArrayHasKey('email_verified_at', $normalized);
        $this->assertArrayHasKey('featured_product', $normalized);
        $this->assertArrayHasKey('name', $normalized);
        $this->assertArrayHasKey('email', $normalized);
        $this->assertNull($normalized['id']);
        $this->assertNull($normalized['email_verified_at']);
        $this->assertNull($normalized['featured_product']);
    }

    // ==================== DATA DTO NORMALIZATION TESTS ====================

    public function test_data_dto_normalizes_to_camel_case_array_for_api(): void
    {
        $data = new TestUserData(
            id: 1,
            name: 'John Doe',
            email: $this->testEmail,
            status: TestUserStatus::ACTIVE,
            role: TestUserRole::ADMIN,
            grade: TestUserGrade::GOLD,
            emailVerifiedAt: $this->now,
            tags: $this->tags,
            createdAt: $this->now
        );

        $normalized = $this->rootNormalizer->normalize($data);

        $this->assertEquals(1, $normalized['id']);
        $this->assertSame('John Doe', $normalized['name']);
        $this->assertSame('john@example.com', $normalized['email']);
        $this->assertSame('active', $normalized['status']);
        $this->assertSame('admin', $normalized['role']);
        // TestUserGrade::GOLD a la valeur 3 (int)
        $this->assertEquals(3, $normalized['grade']);
    }

    // ==================== ENUM NORMALIZATION TESTS ====================

    public function test_backed_string_enum_normalizes_to_string_value(): void
    {
        $record = new TestUserRecord(
            name: 'John Doe',
            email: $this->testEmail,
            role: TestUserRole::ADMIN
        );

        $normalized = $this->rootNormalizer->normalize($record);

        $this->assertSame('admin', $normalized['role']);
        $this->assertIsString($normalized['role']);
    }

    public function test_backed_int_enum_normalizes_to_int_value(): void
    {
        $record = new TestUserRecord(
            name: 'John Doe',
            email: $this->testEmail,
            grade: TestUserGrade::PLATINUM
        );

        $normalized = $this->rootNormalizer->normalize($record);

        $this->assertSame(4, $normalized['grade']);
        $this->assertIsInt($normalized['grade']);
    }

    public function test_pure_enum_normalizes_to_name(): void
    {
        $collection = new TestUserRoleCollection;
        $collection->add(TestUserRole::ADMIN, TestUserRole::USER);

        $data = new TestUserWithRolesData(
            id: 1,
            name: 'John Doe',
            email: $this->testEmail,
            status: TestUserStatus::ACTIVE,
            roles: $collection,
            grade: TestUserGrade::BRONZE,
            emailVerifiedAt: null,
            tags: $this->tags,
            createdAt: $this->now
        );

        $normalized = $this->rootNormalizer->normalize($data);

        $this->assertIsArray($normalized['roles']);
        $this->assertSame(['admin', 'user'], $normalized['roles']);
    }

    // ==================== VALUE OBJECT NORMALIZATION TESTS ====================

    public function test_email_value_object_normalizes_to_string(): void
    {
        $record = new TestUserRecord(
            name: 'John Doe',
            email: $this->testEmail
        );

        $normalized = $this->rootNormalizer->normalize($record);

        $this->assertSame('john@example.com', $normalized['email']);
        $this->assertIsString($normalized['email']);
    }

    public function test_datetime_value_object_normalizes_to_string(): void
    {
        $record = new TestUserRecord(
            name: 'John Doe',
            email: $this->testEmail,
            createdAt: $this->now
        );

        $normalized = $this->rootNormalizer->normalize($record);

        $this->assertIsString($normalized['created_at']);
        $this->assertMatchesRegularExpression(
            '/\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}/',
            $normalized['created_at']
        );
    }

    // ==================== COLLECTION NORMALIZATION TESTS ====================

    public function test_string_typed_collection_normalizes_to_array(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('apple', 'banana', 'cherry');

        $normalized = $this->rootNormalizer->normalize($collection);

        $this->assertSame(['apple', 'banana', 'cherry'], $normalized);
    }

    public function test_int_typed_collection_normalizes_to_array(): void
    {
        $collection = new IntTypedCollection;
        $collection->add(1, 2, 3, 4, 5);

        $normalized = $this->rootNormalizer->normalize($collection);

        $this->assertSame([1, 2, 3, 4, 5], $normalized);
    }

    public function test_record_collection_normalizes_to_array_of_arrays(): void
    {
        $collection = new RecordCollection(TestUserRecord::class);
        $collection->add(
            new TestUserRecord(id: 1, name: 'User1', email: TestEmailAddress::from('user1@example.com')),
            new TestUserRecord(id: 2, name: 'User2', email: TestEmailAddress::from('user2@example.com'))
        );

        $normalized = $this->rootNormalizer->normalize($collection);

        $this->assertCount(2, $normalized);
        $this->assertEquals(1, $normalized[0]['id']);
        $this->assertSame('User1', $normalized[0]['name']);
        $this->assertEquals(2, $normalized[1]['id']);
        $this->assertSame('User2', $normalized[1]['name']);
    }

    public function test_collection_always_includes_nulls(): void
    {
        $collection = new TypedCollection('int', 'null');
        $collection->add(1, null, 2, null, 3);

        $normalized = $this->rootNormalizer->normalize($collection);

        // Les null sont TOUJOURS inclus
        $this->assertSame([1, null, 2, null, 3], $normalized);
    }

    // ==================== NESTED NORMALIZATION TESTS ====================

    public function test_record_with_nested_product_collection_normalizes_correctly(): void
    {
        $products = new ProductRecordCollection;
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

        $normalized = $this->rootNormalizer->normalize($record);

        $this->assertEquals(999, $normalized['products'][0]['price']);
        $this->assertSame('Laptop', $normalized['products'][0]['name']);
        $this->assertTrue($normalized['products'][0]['is_featured']);
        $this->assertEquals(29, $normalized['products'][1]['price']);
        $this->assertSame('Mouse', $normalized['products'][1]['name']);
        $this->assertFalse($normalized['products'][1]['is_featured']);
    }

    public function test_deeply_nested_structures_normalize_recursively(): void
    {
        $innerProducts = new ProductRecordCollection;
        $innerProducts->add(
            new TestProductRecord(id: 1, name: 'Product A', price: 100),
            new TestProductRecord(id: 2, name: 'Product B', price: 200)
        );

        $userRecord = new TestUserRecord(
            id: 1,
            name: 'John Doe',
            email: $this->testEmail,
            products: $innerProducts
        );

        $collection = new RecordCollection(TestUserRecord::class);
        $collection->add($userRecord);

        $normalized = $this->rootNormalizer->normalize($collection);

        $this->assertCount(1, $normalized);
        $this->assertCount(2, $normalized[0]['products']);
        $this->assertSame('Product A', $normalized[0]['products'][0]['name']);
        $this->assertSame('Product B', $normalized[0]['products'][1]['name']);
    }

    /**
     * ✅ CORRECTION: Utiliser TypedCollection<StringTypedCollection> au lieu de NestedCollection
     */
    public function test_nested_collections_within_collections_normalize_correctly(): void
    {
        $container = new TypedCollection(StringTypedCollection::class);

        $inner1 = new StringTypedCollection;
        $inner1->add('a', 'b', 'c');

        $inner2 = new StringTypedCollection;
        $inner2->add('d', 'e', 'f');

        $container->add($inner1, $inner2);

        $normalized = $this->rootNormalizer->normalize($container);

        $this->assertCount(2, $normalized);
        $this->assertSame(['a', 'b', 'c'], $normalized[0]);
        $this->assertSame(['d', 'e', 'f'], $normalized[1]);
    }

    // ==================== DATAOBJECT NORMALIZATION TESTS ====================

    public function test_data_object_normalizes_to_array(): void
    {
        $dataObject = DataObject::from([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'age' => 30,
        ]);

        $collection = new TypedCollection(DataObject::class);
        $collection->add($dataObject);

        $normalized = $this->rootNormalizer->normalize($collection);

        $this->assertCount(1, $normalized);
        $this->assertSame('John Doe', $normalized[0]['name']);
        $this->assertSame('john@example.com', $normalized[0]['email']);
        $this->assertSame(30, $normalized[0]['age']);
    }

    public function test_data_object_with_nested_data_objects_normalizes_recursively(): void
    {
        $nested = DataObject::from(['value' => 'nested value']);
        $dataObject = DataObject::from(['name' => 'John Doe', 'nested' => $nested]);

        $collection = new TypedCollection(DataObject::class);
        $collection->add($dataObject);

        $normalized = $this->rootNormalizer->normalize($collection);

        $this->assertIsArray($normalized[0]['nested']);
        $this->assertSame('nested value', $normalized[0]['nested']['value']);
    }

    // ==================== EDGE CASES TESTS ====================

    public function test_empty_collection_normalizes_to_empty_array(): void
    {
        $emptyCollection = new StringTypedCollection;
        $normalized = $this->rootNormalizer->normalize($emptyCollection);

        $this->assertIsArray($normalized);
        $this->assertEmpty($normalized);
    }

    public function test_record_with_all_nulls_normalizes_including_nulls(): void
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

        $normalized = $this->rootNormalizer->normalize($record);

        // Les null sont TOUJOURS inclus
        $this->assertArrayHasKey('id', $normalized);
        $this->assertArrayHasKey('email_verified_at', $normalized);
        $this->assertArrayHasKey('featured_product', $normalized);
        $this->assertArrayHasKey('created_at', $normalized);
        $this->assertNull($normalized['id']);
        $this->assertNull($normalized['email_verified_at']);
        $this->assertNull($normalized['featured_product']);
        $this->assertNull($normalized['created_at']);
    }

    public function test_normalization_preserves_data_integrity(): void
    {
        $originalEmail = 'john@example.com';
        $originalName = 'John Doe';

        $record = new TestUserRecord(
            id: 123,
            name: $originalName,
            email: TestEmailAddress::from($originalEmail)
        );

        $normalized = $this->rootNormalizer->normalize($record);

        $this->assertSame(123, $normalized['id']);
        $this->assertSame($originalName, $normalized['name']);
        $this->assertSame($originalEmail, $normalized['email']);
    }

    public function test_multiple_normalization_calls_produce_same_result(): void
    {
        $record = new TestUserRecord(
            id: 1,
            name: 'John Doe',
            email: $this->testEmail
        );

        $first = $this->rootNormalizer->normalize($record);
        $second = $this->rootNormalizer->normalize($record);

        $this->assertSame($first, $second);
    }

    // ==================== MIXED TYPE NORMALIZATION TESTS ====================

    public function test_collection_with_mixed_types_normalizes_correctly(): void
    {
        $mixedCollection = new TypedCollection('int', 'float', 'string', 'bool', 'null', TestUserStatus::class, TestUserRecord::class);
        $mixedCollection->add(
            42,
            3.14,
            'string',
            true,
            null,
            TestUserStatus::ACTIVE,
            new TestUserRecord(name: 'John', email: $this->testEmail)
        );

        $normalized = $this->rootNormalizer->normalize($mixedCollection);

        $this->assertCount(7, $normalized);
        $this->assertSame(42, $normalized[0]);
        $this->assertSame(3.14, $normalized[1]);
        $this->assertSame('string', $normalized[2]);
        $this->assertTrue($normalized[3]);
        $this->assertNull($normalized[4]);
        $this->assertSame('active', $normalized[5]);
        $this->assertArrayHasKey('name', $normalized[6]);
        $this->assertSame('John', $normalized[6]['name']);
    }

    // ==================== COMPLEX SCENARIOS TESTS ====================

    public function test_complete_normalization_workflow_from_record_to_api(): void
    {
        $dbRecord = new TestUserRecord(
            id: 1,
            name: 'John Doe',
            email: $this->testEmail,
            status: TestUserStatus::ACTIVE,
            role: TestUserRole::ADMIN,
            grade: TestUserGrade::GOLD,
            tags: $this->tags,
            createdAt: $this->now
        );

        $dbNormalized = $this->rootNormalizer->normalize($dbRecord);
        $apiData = TestUserData::from($dbRecord);
        $apiNormalized = $this->rootNormalizer->normalize($apiData);

        $this->assertArrayHasKey('created_at', $dbNormalized);
        $this->assertArrayNotHasKey('createdAt', $dbNormalized);
        $this->assertArrayHasKey('createdAt', $apiNormalized);
        $this->assertArrayNotHasKey('created_at', $apiNormalized);
        $this->assertSame($dbNormalized['name'], $apiNormalized['name']);
        $this->assertSame($dbNormalized['email'], $apiNormalized['email']);
    }

    public function test_to_string_magic_method_returns_json(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('a', 'b', 'c');

        $string = (string) $collection;

        $this->assertIsString($string);
        $this->assertJson($string);

        $decoded = json_decode($string, true);
        $this->assertSame(['a', 'b', 'c'], $decoded);
    }

    public function test_data_dto_to_string_returns_json(): void
    {
        $data = new TestUserData(
            id: 1,
            name: 'John Doe',
            email: $this->testEmail,
            status: TestUserStatus::ACTIVE,
            role: TestUserRole::USER,
            grade: TestUserGrade::BRONZE,
            emailVerifiedAt: null,
            tags: $this->tags,
            createdAt: $this->now
        );

        $this->assertTrue(method_exists($data, '__toString'), '__toString method not found');

        $string = (string) $data;
        $this->assertIsString($string);
        $this->assertJson($string);

        $decoded = json_decode($string, true);
        $this->assertSame('John Doe', $decoded['name']);
    }
}
