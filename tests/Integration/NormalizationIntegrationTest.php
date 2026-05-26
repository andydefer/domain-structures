<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Integration;

use AndyDefer\DomainStructures\Collections\Core\DataCollection;
use AndyDefer\DomainStructures\Collections\Core\RecordCollection;
use AndyDefer\DomainStructures\Collections\Core\TypedCollection;
use AndyDefer\DomainStructures\Collections\Utility\IntTypedCollection;
use AndyDefer\DomainStructures\Collections\Utility\StringTypedCollection;
use AndyDefer\DomainStructures\Enums\NormalizeMode;
use AndyDefer\DomainStructures\Tests\Fixtures\Collections\NestedCollection;
use AndyDefer\DomainStructures\Tests\Fixtures\Collections\ProductRecordCollection;
use AndyDefer\DomainStructures\Tests\Fixtures\Collections\TestUserRoleCollection;
use AndyDefer\DomainStructures\Tests\Fixtures\Data\TestProductData;
use AndyDefer\DomainStructures\Tests\Fixtures\Data\TestUserData;
use AndyDefer\DomainStructures\Tests\Fixtures\Data\TestUserWithRolesData;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestBackedIntEnum;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestBackedStringEnum;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestPureEnum;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestUserGrade;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestUserRole;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestUserStatus;
use AndyDefer\DomainStructures\Tests\Fixtures\Records\TestProductRecord;
use AndyDefer\DomainStructures\Tests\Fixtures\Records\TestUserRecord;
use AndyDefer\DomainStructures\Tests\Fixtures\ValueObjects\TestEmailAddress;
use AndyDefer\DomainStructures\Tests\Fixtures\ValueObjects\TestIso8601DateTime;
use AndyDefer\DomainStructures\Tests\TestCase;
use stdClass;

/**
 * Integration test for the normalization system.
 *
 * This test suite validates the complete normalization pipeline:
 * - Normalization of Records (snake_case for database)
 * - Normalization of Data DTOs (camelCase for API)
 * - Normalization of Value Objects
 * - Normalization of Enums (backed and pure)
 * - Normalization of Collections
 * - Normalization of nested structures
 * - Normalization of stdClass objects
 * - Array normalization
 * - Null value handling
 * - Recursive normalization
 *
 * The tests follow the AAA pattern (Arrange, Act, Assert).
 */
final class NormalizationIntegrationTest extends TestCase
{
    private TestIso8601DateTime $now;
    private TestEmailAddress $testEmail;
    private StringTypedCollection $tags;

    protected function setUp(): void
    {
        parent::setUp();

        $this->now = TestIso8601DateTime::now();
        $this->testEmail = TestEmailAddress::fromString('john@example.com');
        $this->tags = new StringTypedCollection;
        $this->tags->add('premium', 'vip', 'early_adopter');
    }

    // ==================== RECORD NORMALIZATION TESTS ====================

    /**
     * Test that a simple Record normalizes to snake_case array.
     */
    public function test_simple_record_normalizes_to_snake_case_array(): void
    {
        // Arrange
        $record = new TestUserRecord(
            id: 1,
            name: 'John Doe',
            email: $this->testEmail,
            status: TestUserStatus::ACTIVE,
            role: TestUserRole::USER,
            grade: TestUserGrade::BRONZE
        );

        // Act
        $normalized = $record->normalize(mode: NormalizeMode::ARRAY);

        // Assert
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

        $this->assertSame(1, $normalized['id']);
        $this->assertSame('John Doe', $normalized['name']);
        $this->assertSame('john@example.com', $normalized['email']);
        $this->assertSame('active', $normalized['status']);
        $this->assertSame('user', $normalized['role']);
        $this->assertSame(1, $normalized['grade']);
    }

    /**
     * Test that Record normalizes to JSON string.
     */
    public function test_record_normalizes_to_json_string(): void
    {
        // Arrange
        $record = new TestUserRecord(
            id: 1,
            name: 'John Doe',
            email: $this->testEmail
        );

        // Act
        $json = $record->normalize(mode: NormalizeMode::JSON);

        // Assert
        $this->assertIsString($json);
        $this->assertJson($json);

        $decoded = json_decode($json, true);
        $this->assertSame(1, $decoded['id']);
        $this->assertSame('John Doe', $decoded['name']);
        $this->assertSame('john@example.com', $decoded['email']);
    }

    /**
     * Test that Record excludes null values when includeNulls is false.
     */
    public function test_record_excludes_null_values_when_include_nulls_false(): void
    {
        // Arrange
        $record = new TestUserRecord(
            name: 'John Doe',
            email: $this->testEmail,
            id: null,
            emailVerifiedAt: null,
            featuredProduct: null
        );

        // Act
        $normalized = $record->normalize(includeNulls: false, mode: NormalizeMode::ARRAY);

        // Assert
        $this->assertArrayNotHasKey('id', $normalized);
        $this->assertArrayNotHasKey('email_verified_at', $normalized);
        $this->assertArrayNotHasKey('featured_product', $normalized);
        $this->assertArrayHasKey('name', $normalized);
        $this->assertArrayHasKey('email', $normalized);
        $this->assertArrayHasKey('status', $normalized);
    }

    /**
     * Test that Record includes null values when includeNulls is true.
     */
    public function test_record_includes_null_values_when_include_nulls_true(): void
    {
        // Arrange
        $record = new TestUserRecord(
            name: 'John Doe',
            email: $this->testEmail,
            id: null,
            emailVerifiedAt: null
        );

        // Act
        $normalized = $record->normalize(includeNulls: true, mode: NormalizeMode::ARRAY);

        // Assert
        $this->assertArrayHasKey('id', $normalized);
        $this->assertArrayHasKey('email_verified_at', $normalized);
        $this->assertNull($normalized['id']);
        $this->assertNull($normalized['email_verified_at']);
    }

    // ==================== DATA DTO NORMALIZATION TESTS ====================

    /**
     * Test that Data DTO normalizes to camelCase array for API.
     */
    public function test_data_dto_normalizes_to_camel_case_array_for_api(): void
    {
        // Arrange
        $data = new TestUserData(
            id: '1',
            name: 'John Doe',
            email: $this->testEmail,
            status: TestUserStatus::ACTIVE,
            role: TestUserRole::ADMIN,
            grade: TestUserGrade::GOLD,
            emailVerifiedAt: $this->now,
            tags: $this->tags,
            createdAt: $this->now
        );

        // Act
        $normalized = $data->normalize(mode: NormalizeMode::ARRAY);

        // Assert
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

        $this->assertSame('1', $normalized['id']);
        $this->assertSame('John Doe', $normalized['name']);
        $this->assertSame('john@example.com', $normalized['email']);
        $this->assertSame('active', $normalized['status']);
        $this->assertSame('admin', $normalized['role']);
        $this->assertSame('gold', $normalized['grade']);
    }

    /**
     * Test that Data DTO normalizes to JSON string.
     */
    public function test_data_dto_normalizes_to_json_string(): void
    {
        // Arrange
        $data = new TestUserData(
            id: '1',
            name: 'John Doe',
            email: $this->testEmail,
            status: TestUserStatus::ACTIVE,
            role: TestUserRole::USER,
            grade: TestUserGrade::BRONZE,
            emailVerifiedAt: null,
            tags: $this->tags,
            createdAt: $this->now
        );

        // Act
        $json = $data->normalize(mode: NormalizeMode::JSON);

        // Assert
        $this->assertIsString($json);
        $this->assertJson($json);

        $decoded = json_decode($json, true);
        $this->assertSame('1', $decoded['id']);
        $this->assertSame('John Doe', $decoded['name']);
    }

    // ==================== ENUM NORMALIZATION TESTS ====================

    /**
     * Test that backed string enum normalizes to its string value.
     */
    public function test_backed_string_enum_normalizes_to_string_value(): void
    {
        // Arrange
        $record = new TestUserRecord(
            name: 'John Doe',
            email: $this->testEmail,
            role: TestUserRole::ADMIN
        );

        // Act
        $normalized = $record->normalize(mode: NormalizeMode::ARRAY);

        // Assert
        $this->assertSame('admin', $normalized['role']);
        $this->assertIsString($normalized['role']);
    }

    /**
     * Test that backed int enum normalizes to its int value.
     */
    public function test_backed_int_enum_normalizes_to_int_value(): void
    {
        // Arrange
        $record = new TestUserRecord(
            name: 'John Doe',
            email: $this->testEmail,
            grade: TestUserGrade::PLATINUM
        );

        // Act
        $normalized = $record->normalize(mode: NormalizeMode::ARRAY);

        // Assert
        $this->assertSame(4, $normalized['grade']);
        $this->assertIsInt($normalized['grade']);
    }

    /**
     * Test that pure enum normalizes to its name.
     */
    public function test_pure_enum_normalizes_to_name(): void
    {
        // Arrange
        $collection = new TestUserRoleCollection;
        $collection->add(TestUserRole::ADMIN, TestUserRole::USER);

        $data = new TestUserWithRolesData(
            id: '1',
            name: 'John Doe',
            email: $this->testEmail,
            status: TestUserStatus::ACTIVE,
            roles: $collection,
            grade: TestUserGrade::BRONZE,
            emailVerifiedAt: null,
            tags: $this->tags,
            createdAt: $this->now
        );

        // Act
        $normalized = $data->normalize(mode: NormalizeMode::ARRAY);

        // Assert
        $this->assertIsArray($normalized['roles']);
        $this->assertSame(['admin', 'user'], $normalized['roles']);
    }

    // ==================== VALUE OBJECT NORMALIZATION TESTS ====================

    /**
     * Test that EmailAddress Value Object normalizes to string.
     */
    public function test_email_value_object_normalizes_to_string(): void
    {
        // Arrange
        $record = new TestUserRecord(
            name: 'John Doe',
            email: $this->testEmail
        );

        // Act
        $normalized = $record->normalize(mode: NormalizeMode::ARRAY);

        // Assert
        $this->assertSame('john@example.com', $normalized['email']);
        $this->assertIsString($normalized['email']);
    }

    /**
     * Test that Iso8601DateTime Value Object normalizes to string.
     */
    public function test_datetime_value_object_normalizes_to_string(): void
    {
        // Arrange
        $record = new TestUserRecord(
            name: 'John Doe',
            email: $this->testEmail,
            createdAt: $this->now
        );

        // Act
        $normalized = $record->normalize(mode: NormalizeMode::ARRAY);

        // Assert
        $this->assertIsString($normalized['created_at']);
        $this->assertMatchesRegularExpression(
            '/\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}/',
            $normalized['created_at']
        );
    }

    // ==================== COLLECTION NORMALIZATION TESTS ====================

    /**
     * Test that StringTypedCollection normalizes to array.
     */
    public function test_string_typed_collection_normalizes_to_array(): void
    {
        // Arrange
        $collection = new StringTypedCollection;
        $collection->add('apple', 'banana', 'cherry');

        // Act
        $normalized = $collection->normalize(mode: NormalizeMode::ARRAY);

        // Assert
        $this->assertIsArray($normalized);
        $this->assertSame(['apple', 'banana', 'cherry'], $normalized);
    }

    /**
     * Test that IntTypedCollection normalizes to array.
     */
    public function test_int_typed_collection_normalizes_to_array(): void
    {
        // Arrange
        $collection = new IntTypedCollection;
        $collection->add(1, 2, 3, 4, 5);

        // Act
        $normalized = $collection->normalize(mode: NormalizeMode::ARRAY);

        // Assert
        $this->assertIsArray($normalized);
        $this->assertSame([1, 2, 3, 4, 5], $normalized);
    }

    /**
     * Test that RecordCollection normalizes to array of arrays.
     */
    public function test_record_collection_normalizes_to_array_of_arrays(): void
    {
        // Arrange
        $collection = new RecordCollection;
        $collection->add(
            new TestUserRecord(id: 1, name: 'User 1', email: TestEmailAddress::fromString('user1@example.com')),
            new TestUserRecord(id: 2, name: 'User 2', email: TestEmailAddress::fromString('user2@example.com'))
        );

        // Act
        $normalized = $collection->normalize(mode: NormalizeMode::ARRAY);

        // Assert
        $this->assertIsArray($normalized);
        $this->assertCount(2, $normalized);
        $this->assertSame(1, $normalized[0]['id']);
        $this->assertSame('User 1', $normalized[0]['name']);
        $this->assertSame(2, $normalized[1]['id']);
        $this->assertSame('User 2', $normalized[1]['name']);
    }

    /**
     * Test that RecordCollection normalizes to JSON.
     */
    public function test_record_collection_normalizes_to_json(): void
    {
        // Arrange
        $collection = new RecordCollection;
        $collection->add(
            new TestUserRecord(id: 1, name: 'User 1', email: TestEmailAddress::fromString('user1@example.com'))
        );

        // Act
        $json = $collection->normalize(mode: NormalizeMode::JSON);

        // Assert
        $this->assertIsString($json);
        $this->assertJson($json);

        $decoded = json_decode($json, true);
        $this->assertCount(1, $decoded);
        $this->assertSame(1, $decoded[0]['id']);
    }

    /**
     * Test that collection normalizes with null handling.
     */
    public function test_collection_normalizes_with_null_handling(): void
    {
        // Arrange
        $collection = new TypedCollection('int', 'null');
        $collection->add(1, null, 2, null, 3);

        // Act - Exclude nulls
        $withoutNulls = $collection->normalize(mode: NormalizeMode::ARRAY, includeNulls: false);

        // Assert
        $this->assertSame([1, 2, 3], $withoutNulls);
    }

    // ==================== NESTED NORMALIZATION TESTS ====================

    /**
     * Test that Record with nested Product collection normalizes correctly.
     */
    public function test_record_with_nested_product_collection_normalizes_correctly(): void
    {
        // Arrange
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

        // Act
        $normalized = $record->normalize(mode: NormalizeMode::ARRAY);

        // Assert
        $this->assertIsArray($normalized['products']);
        $this->assertCount(2, $normalized['products']);
        $this->assertSame('Laptop', $normalized['products'][0]['name']);
        $this->assertSame(999, $normalized['products'][0]['price']);
        $this->assertTrue($normalized['products'][0]['is_featured']);
        $this->assertSame('Mouse', $normalized['products'][1]['name']);
        $this->assertSame(29, $normalized['products'][1]['price']);
        $this->assertFalse($normalized['products'][1]['is_featured']);
    }

    /**
     * Test that deeply nested structures normalize recursively.
     */
    public function test_deeply_nested_structures_normalize_recursively(): void
    {
        // Arrange
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

        $collection = new RecordCollection;
        $collection->add($userRecord);

        // Act
        $normalized = $collection->normalize(mode: NormalizeMode::ARRAY);

        // Assert
        $this->assertIsArray($normalized);
        $this->assertCount(1, $normalized);
        $this->assertCount(2, $normalized[0]['products']);
        $this->assertSame('Product A', $normalized[0]['products'][0]['name']);
        $this->assertSame('Product B', $normalized[0]['products'][1]['name']);
    }

    /**
     * Test that nested collections within collections normalize correctly.
     */
    public function test_nested_collections_within_collections_normalize_correctly(): void
    {
        // Arrange
        $nestedCollection = new NestedCollection;

        $inner1 = new StringTypedCollection;
        $inner1->add('a', 'b', 'c');

        $inner2 = new StringTypedCollection;
        $inner2->add('d', 'e', 'f');

        $nestedCollection->add($inner1, $inner2);

        // Act
        $normalized = $nestedCollection->normalize(mode: NormalizeMode::ARRAY);

        // Assert
        $this->assertIsArray($normalized);
        $this->assertCount(2, $normalized);
        $this->assertSame(['a', 'b', 'c'], $normalized[0]);
        $this->assertSame(['d', 'e', 'f'], $normalized[1]);
    }

    // ==================== STDCLASS NORMALIZATION TESTS ====================

    /**
     * Test that stdClass normalizes to array.
     */
    public function test_stdclass_normalizes_to_array(): void
    {
        // Arrange
        $object = new stdClass;
        $object->name = 'John Doe';
        $object->email = 'john@example.com';
        $object->age = 30;

        $collection = new TypedCollection(stdClass::class);
        $collection->add($object);

        // Act
        $normalized = $collection->normalize(mode: NormalizeMode::ARRAY);

        // Assert
        $this->assertIsArray($normalized);
        $this->assertCount(1, $normalized);
        $this->assertSame('John Doe', $normalized[0]['name']);
        $this->assertSame('john@example.com', $normalized[0]['email']);
        $this->assertSame(30, $normalized[0]['age']);
    }

    /**
     * Test that stdClass with nested objects normalizes recursively.
     */
    public function test_stdclass_with_nested_objects_normalizes_recursively(): void
    {
        // Arrange
        $nested = new stdClass;
        $nested->value = 'nested value';

        $object = new stdClass;
        $object->name = 'John Doe';
        $object->nested = $nested;

        $collection = new TypedCollection(stdClass::class);
        $collection->add($object);

        // Act
        $normalized = $collection->normalize(mode: NormalizeMode::ARRAY);

        // Assert
        $this->assertIsArray($normalized);
        $this->assertIsArray($normalized[0]['nested']);
        $this->assertSame('nested value', $normalized[0]['nested']['value']);
    }

    // ==================== EDGE CASES TESTS ====================

    /**
     * Test that empty collection normalizes to empty array.
     */
    public function test_empty_collection_normalizes_to_empty_array(): void
    {
        // Arrange
        $emptyCollection = new StringTypedCollection;

        // Act
        $normalized = $emptyCollection->normalize(mode: NormalizeMode::ARRAY);

        // Assert
        $this->assertIsArray($normalized);
        $this->assertEmpty($normalized);
    }

    /**
     * Test that empty collection normalizes to empty JSON array.
     */
    public function test_empty_collection_normalizes_to_empty_json_array(): void
    {
        // Arrange
        $emptyCollection = new StringTypedCollection;

        // Act
        $json = $emptyCollection->normalize(mode: NormalizeMode::JSON);

        // Assert
        $this->assertSame('[]', $json);
    }

    /**
     * Test that Record with all nulls normalizes correctly.
     */
    public function test_record_with_all_nulls_normalizes_correctly(): void
    {
        // Arrange
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

        // Act - Include nulls
        $withNulls = $record->normalize(includeNulls: true, mode: NormalizeMode::ARRAY);

        // Act - Exclude nulls
        $withoutNulls = $record->normalize(includeNulls: false, mode: NormalizeMode::ARRAY);

        // Assert
        $this->assertArrayHasKey('id', $withNulls);
        $this->assertNull($withNulls['id']);
        $this->assertArrayNotHasKey('id', $withoutNulls);
        $this->assertEmpty($withoutNulls);
    }

    /**
     * Test that normalization preserves data integrity.
     */
    public function test_normalization_preserves_data_integrity(): void
    {
        // Arrange
        $originalEmail = 'john@example.com';
        $originalName = 'John Doe';

        $record = new TestUserRecord(
            id: 123,
            name: $originalName,
            email: TestEmailAddress::fromString($originalEmail)
        );

        // Act
        $normalized = $record->normalize(mode: NormalizeMode::ARRAY);

        // Assert - Data is preserved
        $this->assertSame(123, $normalized['id']);
        $this->assertSame($originalName, $normalized['name']);
        $this->assertSame($originalEmail, $normalized['email']);
    }

    /**
     * Test that multiple normalization calls produce same result.
     */
    public function test_multiple_normalization_calls_produce_same_result(): void
    {
        // Arrange
        $record = new TestUserRecord(
            id: 1,
            name: 'John Doe',
            email: $this->testEmail
        );

        // Act
        $first = $record->normalize(mode: NormalizeMode::ARRAY);
        $second = $record->normalize(mode: NormalizeMode::ARRAY);

        // Assert
        $this->assertSame($first, $second);
    }

    // ==================== MIXED TYPE NORMALIZATION TESTS ====================

    /**
     * Test that collection with mixed types normalizes correctly.
     */
    public function test_collection_with_mixed_types_normalizes_correctly(): void
    {
        // Arrange
        $mixedCollection = new TypedCollection;
        $mixedCollection->add(
            42,
            3.14,
            'string',
            true,
            null,
            TestUserStatus::ACTIVE,
            new TestUserRecord(name: 'John', email: $this->testEmail)
        );

        // Act
        $normalized = $mixedCollection->normalize(mode: NormalizeMode::ARRAY);

        // Assert
        $this->assertIsArray($normalized);
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

    /**
     * Test that collection normalizes to JSON with proper types.
     */
    public function test_collection_normalizes_to_json_with_proper_types(): void
    {
        // Arrange
        $collection = new TypedCollection;
        $collection->add(42, 3.14, 'string', true, null);

        // Act
        $json = $collection->normalize(mode: NormalizeMode::JSON);

        // Assert
        $this->assertIsString($json);
        $this->assertJson($json);

        $decoded = json_decode($json, true);
        $this->assertSame([42, 3.14, 'string', true, null], $decoded);
    }

    // ==================== COMPLEX SCENARIOS TESTS ====================

    /**
     * Test complete normalization workflow from Record to API response.
     */
    public function test_complete_normalization_workflow_from_record_to_api(): void
    {
        // Arrange - Database Record
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

        // Act - Normalize for database (snake_case)
        $dbNormalized = $dbRecord->normalize(mode: NormalizeMode::ARRAY);

        // Transform to Data DTO
        $apiData = TestUserData::from($dbRecord);

        // Normalize for API (camelCase)
        $apiNormalized = $apiData->normalize(mode: NormalizeMode::ARRAY);

        // Assert - Database format has snake_case
        $this->assertArrayHasKey('created_at', $dbNormalized);
        $this->assertArrayNotHasKey('createdAt', $dbNormalized);

        // Assert - API format has camelCase
        $this->assertArrayHasKey('createdAt', $apiNormalized);
        $this->assertArrayNotHasKey('created_at', $apiNormalized);

        // Assert - Data integrity preserved
        $this->assertSame($dbNormalized['name'], $apiNormalized['name']);
        $this->assertSame($dbNormalized['email'], $apiNormalized['email']);
    }

    /**
     * Test that __toString magic method returns JSON.
     */
    public function test_to_string_magic_method_returns_json(): void
    {
        // Arrange
        $collection = new StringTypedCollection;
        $collection->add('a', 'b', 'c');

        // Act
        $string = (string) $collection;

        // Assert
        $this->assertIsString($string);
        $this->assertJson($string);
        $this->assertSame('["a","b","c"]', $string);
    }

    /**
     * Test that Data DTO __toString returns JSON.
     */
    public function test_data_dto_to_string_returns_json(): void
    {
        // Arrange
        $data = new TestUserData(
            id: '1',
            name: 'John Doe',
            email: $this->testEmail,
            status: TestUserStatus::ACTIVE,
            role: TestUserRole::USER,
            grade: TestUserGrade::BRONZE,
            emailVerifiedAt: null,
            tags: $this->tags,
            createdAt: $this->now
        );

        // Act
        $string = (string) $data;

        // Assert
        $this->assertIsString($string);
        $this->assertJson($string);

        $decoded = json_decode($string, true);
        $this->assertSame('John Doe', $decoded['name']);
    }
}
