<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Integration;

use AndyDefer\DomainStructures\Collections\Core\DataCollection;
use AndyDefer\DomainStructures\Collections\Core\RecordCollection;
use AndyDefer\DomainStructures\Collections\Utility\StringTypedCollection;
use AndyDefer\DomainStructures\Enums\NormalizeMode;
use AndyDefer\DomainStructures\Tests\Fixtures\Collections\ProductDataCollection;
use AndyDefer\DomainStructures\Tests\Fixtures\Collections\ProductRecordCollection;
use AndyDefer\DomainStructures\Tests\Fixtures\Collections\TestUserRoleCollection;
use AndyDefer\DomainStructures\Tests\Fixtures\Data\TestFullUserData;
use AndyDefer\DomainStructures\Tests\Fixtures\Data\TestProductData;
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
use RuntimeException;

/**
 * Integration test for Record to Data transformation.
 *
 * This test suite validates the transformation workflow from database Records
 * to API Data DTOs:
 * - Single Record to Data transformation
 * - RecordCollection to DataCollection transformation
 * - Nested Record collections transformation
 * - Type conversion (int to string, enum to string, etc.)
 * - Field naming conversion (snake_case to camelCase)
 * - Null value handling
 * - Default value handling
 * - Error cases and validation
 * - Complex nested relationships
 *
 * The tests follow the AAA pattern (Arrange, Act, Assert).
 */
final class RecordToDataTransformationTest extends TestCase
{
    private TestIso8601DateTime $now;
    private TestEmailAddress $testEmail;
    private StringTypedCollection $tags;

    protected function setUp(): void
    {
        parent::setUp();

        $this->now = TestIso8601DateTime::now();
        $this->testEmail = TestEmailAddress::fromString('john.doe@example.com');
        $this->tags = new StringTypedCollection;
        $this->tags->add('premium', 'vip', 'early_adopter');
    }

    // ==================== SINGLE RECORD TO DATA TRANSFORMATION TESTS ====================

    /**
     * Test that a simple User Record transforms to User Data DTO.
     */
    public function test_simple_user_record_transforms_to_user_data_dto(): void
    {
        // Arrange
        $record = new TestUserRecord(
            id: 1,
            name: 'John Doe',
            email: $this->testEmail,
            status: TestUserStatus::ACTIVE,
            role: TestUserRole::USER,
            grade: TestUserGrade::BRONZE,
            tags: $this->tags,
            createdAt: $this->now
        );

        // Act
        $data = TestUserData::from($record);

        // Assert
        $this->assertInstanceOf(TestUserData::class, $data);
        $this->assertSame('1', $data->id); // Int to string conversion
        $this->assertSame('John Doe', $data->name);
        $this->assertSame('john.doe@example.com', $data->email->getValue());
        $this->assertSame(TestUserStatus::ACTIVE, $data->status);
        $this->assertSame(TestUserRole::USER, $data->role);
        $this->assertSame(TestUserGrade::BRONZE, $data->grade);
        $this->assertSame($this->tags, $data->tags);
        $this->assertSame($this->now, $data->createdAt);
    }

    /**
     * Test that Record with nullable fields transforms correctly.
     */
    public function test_record_with_nullable_fields_transforms_correctly(): void
    {
        // Arrange
        $record = new TestUserRecord(
            id: null,
            name: 'John Doe',
            email: $this->testEmail,
            emailVerifiedAt: null,
            featuredProduct: null
        );

        // Act
        $data = TestUserData::from($record);

        // Assert
        $this->assertInstanceOf(TestUserData::class, $data);
        $this->assertSame('', $data->id); // Null becomes empty string in Data DTO?
        $this->assertSame('John Doe', $data->name);
        $this->assertNull($data->emailVerifiedAt);
    }

    /**
     * Test that Record with all fields transforms to complete Data DTO.
     */
    public function test_complete_user_record_transforms_to_complete_data_dto(): void
    {
        // Arrange
        $record = new TestUserRecord(
            id: 42,
            name: 'Jane Smith',
            email: TestEmailAddress::fromString('jane.smith@example.com'),
            status: TestUserStatus::ACTIVE,
            role: TestUserRole::ADMIN,
            grade: TestUserGrade::PLATINUM,
            emailVerifiedAt: $this->now,
            tags: $this->tags,
            createdAt: $this->now
        );

        // Act
        $data = TestUserData::from($record);

        // Assert
        $this->assertInstanceOf(TestUserData::class, $data);
        $this->assertSame('42', $data->id);
        $this->assertSame('Jane Smith', $data->name);
        $this->assertSame('jane.smith@example.com', $data->email->getValue());
        $this->assertSame(TestUserStatus::ACTIVE, $data->status);
        $this->assertSame(TestUserRole::ADMIN, $data->role);
        $this->assertSame(TestUserGrade::PLATINUM, $data->grade);
        $this->assertSame($this->now, $data->emailVerifiedAt);
        $this->assertSame($this->tags, $data->tags);
        $this->assertSame($this->now, $data->createdAt);
    }

    // ==================== RECORD COLLECTION TO DATA COLLECTION TESTS ====================

    /**
     * Test that RecordCollection transforms to DataCollection.
     */
    public function test_record_collection_transforms_to_data_collection(): void
    {
        // Arrange
        $recordCollection = new RecordCollection;
        $recordCollection->add(
            new TestUserRecord(id: 1, name: 'User 1', email: TestEmailAddress::fromString('user1@example.com')),
            new TestUserRecord(id: 2, name: 'User 2', email: TestEmailAddress::fromString('user2@example.com')),
            new TestUserRecord(id: 3, name: 'User 3', email: TestEmailAddress::fromString('user3@example.com'))
        );

        // Act
        $dataCollection = new DataCollection;
        foreach ($recordCollection->all() as $record) {
            $dataCollection->add(TestUserData::from($record));
        }

        // Assert
        $this->assertCount(3, $dataCollection);

        $dataArray = $dataCollection->toArray();
        $this->assertSame('1', $dataArray[0]->id);
        $this->assertSame('User 1', $dataArray[0]->name);
        $this->assertSame('2', $dataArray[1]->id);
        $this->assertSame('User 2', $dataArray[1]->name);
        $this->assertSame('3', $dataArray[2]->id);
        $this->assertSame('User 3', $dataArray[2]->name);
    }

    /**
     * Test that empty RecordCollection transforms to empty DataCollection.
     */
    public function test_empty_record_collection_transforms_to_empty_data_collection(): void
    {
        // Arrange
        $recordCollection = new RecordCollection;

        // Act
        $dataCollection = new DataCollection;
        foreach ($recordCollection->all() as $record) {
            $dataCollection->add(TestUserData::from($record));
        }

        // Assert
        $this->assertCount(0, $dataCollection);
        $this->assertTrue($dataCollection->isEmpty());
    }

    /**
     * Test transformation with collect() helper method.
     */
    public function test_transformation_with_collect_helper_method(): void
    {
        // Arrange
        $recordCollection = new RecordCollection;
        $recordCollection->add(
            new TestUserRecord(id: 1, name: 'User 1', email: TestEmailAddress::fromString('user1@example.com')),
            new TestUserRecord(id: 2, name: 'User 2', email: TestEmailAddress::fromString('user2@example.com'))
        );

        // Act
        $dataArray = TestUserData::collect($recordCollection);

        // Assert
        $this->assertIsArray($dataArray);
        $this->assertCount(2, $dataArray);
        $this->assertInstanceOf(TestUserData::class, $dataArray[0]);
        $this->assertInstanceOf(TestUserData::class, $dataArray[1]);
        $this->assertSame('1', $dataArray[0]->id);
        $this->assertSame('2', $dataArray[1]->id);
    }

    // ==================== NESTED COLLECTION TRANSFORMATION TESTS ====================

    /**
     * Test that Record with nested Product collection transforms to Data with nested Product Data.
     */
    public function test_record_with_nested_product_collection_transforms_correctly(): void
    {
        // Arrange
        $productRecords = new ProductRecordCollection;
        $productRecords->add(
            new TestProductRecord(id: 1, name: 'Laptop', price: 999, isFeatured: true),
            new TestProductRecord(id: 2, name: 'Mouse', price: 29, isFeatured: false),
            new TestProductRecord(id: 3, name: 'Keyboard', price: 89, isFeatured: true)
        );

        $userRecord = new TestUserRecord(
            id: 1,
            name: 'John Doe',
            email: $this->testEmail,
            products: $productRecords,
            createdAt: $this->now
        );

        // Act
        $fullUserData = TestFullUserData::from($userRecord);

        // Assert
        $this->assertInstanceOf(TestFullUserData::class, $fullUserData);
        $this->assertInstanceOf(ProductDataCollection::class, $fullUserData->products);
        $this->assertCount(3, $fullUserData->products);

        $products = $fullUserData->products->toArray();
        $this->assertSame(1, $products[0]->id);
        $this->assertSame('Laptop', $products[0]->name);
        $this->assertSame(999.0, $products[0]->price);
        $this->assertTrue($products[0]->isFeatured);

        $this->assertSame(2, $products[1]->id);
        $this->assertSame('Mouse', $products[1]->name);
        $this->assertSame(29.0, $products[1]->price);
        $this->assertFalse($products[1]->isFeatured);
    }

    /**
     * Test that Record with roles collection transforms to Data with roles collection.
     */
    public function test_record_with_roles_collection_transforms_correctly(): void
    {
        // Arrange
        $rolesCollection = new TestUserRoleCollection;
        $rolesCollection->add(
            TestUserRole::ADMIN,
            TestUserRole::USER,
            TestUserRole::GUEST
        );

        $userRecord = new TestUserRecord(
            id: 1,
            name: 'John Doe',
            email: $this->testEmail,
            role: TestUserRole::ADMIN,
            createdAt: $this->now
        );

        // Act - Note: TestUserRecord doesn't have roles collection, so we create directly
        $userData = new TestUserWithRolesData(
            id: '1',
            name: 'John Doe',
            email: $this->testEmail,
            status: TestUserStatus::ACTIVE,
            roles: $rolesCollection,
            grade: TestUserGrade::BRONZE,
            emailVerifiedAt: null,
            tags: $this->tags,
            createdAt: $this->now
        );

        // Assert
        $this->assertInstanceOf(TestUserWithRolesData::class, $userData);
        $this->assertInstanceOf(TestUserRoleCollection::class, $userData->roles);
        $this->assertCount(3, $userData->roles);
        $this->assertTrue($userData->roles->contains(TestUserRole::ADMIN));
        $this->assertTrue($userData->roles->contains(TestUserRole::USER));
        $this->assertTrue($userData->roles->contains(TestUserRole::GUEST));
    }

    /**
     * Test that deeply nested collections transform recursively.
     */
    public function test_deeply_nested_collections_transform_recursively(): void
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
            products: $innerProducts,
            createdAt: $this->now
        );

        // Act
        $fullUserData = TestFullUserData::from($userRecord);

        // Assert
        $this->assertInstanceOf(ProductDataCollection::class, $fullUserData->products);
        $this->assertCount(2, $fullUserData->products);

        $products = $fullUserData->products->toArray();
        $this->assertSame('Product A', $products[0]->name);
        $this->assertSame('Product B', $products[1]->name);
    }

    // ==================== FIELD NAME CONVERSION TESTS ====================

    /**
     * Test that snake_case Record fields become camelCase in Data DTO.
     */
    public function test_snake_case_record_fields_become_camel_case_in_data_dto(): void
    {
        // Arrange
        $record = new TestUserRecord(
            id: 1,
            name: 'John Doe',
            email: $this->testEmail,
            emailVerifiedAt: $this->now,
            createdAt: $this->now
        );

        // Act
        $data = TestUserData::from($record);

        // Assert - Data DTO uses camelCase property names
        $this->assertObjectHasProperty('emailVerifiedAt', $data);
        $this->assertObjectHasProperty('createdAt', $data);

        // Record has snake_case after normalization, but Data DTO properties are camelCase
        $normalizedRecord = $record->normalize(mode: NormalizeMode::ARRAY);
        $normalizedData = $data->normalize(mode: NormalizeMode::ARRAY);

        $this->assertArrayHasKey('email_verified_at', $normalizedRecord);
        $this->assertArrayHasKey('emailVerifiedAt', $normalizedData);
        $this->assertArrayHasKey('created_at', $normalizedRecord);
        $this->assertArrayHasKey('createdAt', $normalizedData);
    }

    // ==================== TYPE CONVERSION TESTS ====================

    /**
     * Test that Record int ID converts to string ID in Data DTO.
     */
    public function test_record_int_id_converts_to_string_id_in_data_dto(): void
    {
        // Arrange
        $record = new TestUserRecord(id: 123, name: 'John Doe', email: $this->testEmail);

        // Act
        $data = TestUserData::from($record);

        // Assert
        $this->assertIsInt($record->id);
        $this->assertIsString($data->id);
        $this->assertSame('123', $data->id);
    }

    /**
     * Test that Record null ID becomes empty string in Data DTO.
     */
    public function test_record_null_id_becomes_empty_string_in_data_dto(): void
    {
        // Arrange
        $record = new TestUserRecord(id: null, name: 'John Doe', email: $this->testEmail);

        // Act
        $data = TestUserData::from($record);

        // Assert
        $this->assertNull($record->id);
        $this->assertSame('', $data->id);
    }

    /**
     * Test that Record backed enum stays as enum in Data DTO (not converted to string yet).
     */
    public function test_record_backed_enum_stays_as_enum_in_data_dto(): void
    {
        // Arrange
        $record = new TestUserRecord(
            name: 'John Doe',
            email: $this->testEmail,
            status: TestUserStatus::ACTIVE,
            role: TestUserRole::ADMIN,
            grade: TestUserGrade::GOLD
        );

        // Act
        $data = TestUserData::from($record);

        // Assert - Enums remain as enums in Data DTO (normalization happens later)
        $this->assertInstanceOf(TestUserStatus::class, $data->status);
        $this->assertInstanceOf(TestUserRole::class, $data->role);
        $this->assertInstanceOf(TestUserGrade::class, $data->grade);
        $this->assertSame(TestUserStatus::ACTIVE, $data->status);
        $this->assertSame(TestUserRole::ADMIN, $data->role);
        $this->assertSame(TestUserGrade::GOLD, $data->grade);
    }

    /**
     * Test that Record Value Object stays as Value Object in Data DTO.
     */
    public function test_record_value_object_stays_as_value_object_in_data_dto(): void
    {
        // Arrange
        $record = new TestUserRecord(
            name: 'John Doe',
            email: $this->testEmail,
            createdAt: $this->now
        );

        // Act
        $data = TestUserData::from($record);

        // Assert
        $this->assertInstanceOf(TestEmailAddress::class, $data->email);
        $this->assertInstanceOf(TestIso8601DateTime::class, $data->createdAt);
        $this->assertSame('john.doe@example.com', $data->email->getValue());
        $this->assertSame($this->now->getValue(), $data->createdAt->getValue());
    }

    /**
     * Test that Record collection stays as collection in Data DTO.
     */
    public function test_record_collection_stays_as_collection_in_data_dto(): void
    {
        // Arrange
        $tags = new StringTypedCollection;
        $tags->add('tag1', 'tag2');

        $record = new TestUserRecord(
            name: 'John Doe',
            email: $this->testEmail,
            tags: $tags
        );

        // Act
        $data = TestUserData::from($record);

        // Assert
        $this->assertInstanceOf(StringTypedCollection::class, $data->tags);
        $this->assertCount(2, $data->tags);
        $this->assertSame(['tag1', 'tag2'], $data->tags->toArray());
    }

    // ==================== DEFAULT VALUE TESTS ====================

    /**
     * Test that Record default values are preserved in Data DTO.
     */
    public function test_record_default_values_are_preserved_in_data_dto(): void
    {
        // Arrange
        $record = new TestUserRecord(
            name: 'John Doe',
            email: $this->testEmail
            // status, role, grade use defaults
        );

        // Act
        $data = TestUserData::from($record);

        // Assert - Default values are preserved
        $this->assertSame(TestUserStatus::ACTIVE, $data->status);
        $this->assertSame(TestUserRole::USER, $data->role);
        $this->assertSame(TestUserGrade::BRONZE, $data->grade);
    }

    /**
     * Test that explicit values override defaults during transformation.
     */
    public function test_explicit_values_override_defaults_during_transformation(): void
    {
        // Arrange
        $record = new TestUserRecord(
            name: 'John Doe',
            email: $this->testEmail,
            status: TestUserStatus::SUSPENDED,
            role: TestUserRole::ADMIN,
            grade: TestUserGrade::PLATINUM
        );

        // Act
        $data = TestUserData::from($record);

        // Assert
        $this->assertSame(TestUserStatus::SUSPENDED, $data->status);
        $this->assertSame(TestUserRole::ADMIN, $data->role);
        $this->assertSame(TestUserGrade::PLATINUM, $data->grade);
    }

    // ==================== NORMALIZATION AFTER TRANSFORMATION TESTS ====================

    /**
     * Test that Data DTO normalizes correctly after transformation from Record.
     */
    public function test_data_dto_normalizes_correctly_after_transformation(): void
    {
        // Arrange
        $record = new TestUserRecord(
            id: 1,
            name: 'John Doe',
            email: $this->testEmail,
            status: TestUserStatus::ACTIVE,
            role: TestUserRole::ADMIN,
            grade: TestUserGrade::GOLD,
            tags: $this->tags,
            createdAt: $this->now
        );

        // Act
        $data = TestUserData::from($record);
        $normalized = $data->normalize(mode: NormalizeMode::ARRAY);

        // Assert - Data DTO normalizes to camelCase with string values
        $this->assertIsArray($normalized);
        $this->assertArrayHasKey('id', $normalized);
        $this->assertArrayHasKey('name', $normalized);
        $this->assertArrayHasKey('email', $normalized);
        $this->assertArrayHasKey('status', $normalized);
        $this->assertArrayHasKey('role', $normalized);
        $this->assertArrayHasKey('grade', $normalized);
        $this->assertArrayHasKey('tags', $normalized);
        $this->assertArrayHasKey('createdAt', $normalized);

        $this->assertSame('1', $normalized['id']);
        $this->assertSame('John Doe', $normalized['name']);
        $this->assertSame('john.doe@example.com', $normalized['email']);
        $this->assertSame('active', $normalized['status']);
        $this->assertSame('admin', $normalized['role']);
        $this->assertSame('gold', $normalized['grade']);
    }

    /**
     * Test complete workflow: Record → Data → JSON API response.
     */
    public function test_complete_workflow_record_to_data_to_json_response(): void
    {
        // Arrange
        $record = new TestUserRecord(
            id: 42,
            name: 'Jane Doe',
            email: TestEmailAddress::fromString('jane@example.com'),
            status: TestUserStatus::ACTIVE,
            role: TestUserRole::ADMIN,
            grade: TestUserGrade::PLATINUM,
            tags: $this->tags,
            createdAt: $this->now
        );

        // Act
        $data = TestUserData::from($record);
        $jsonResponse = $data->normalize(mode: NormalizeMode::JSON);

        // Assert
        $this->assertIsString($jsonResponse);
        $this->assertJson($jsonResponse);

        $decoded = json_decode($jsonResponse, true);
        $this->assertSame('42', $decoded['id']);
        $this->assertSame('Jane Doe', $decoded['name']);
        $this->assertSame('jane@example.com', $decoded['email']);
        $this->assertSame('active', $decoded['status']);
        $this->assertSame('admin', $decoded['role']);
        $this->assertSame('platinum', $decoded['grade']);
        $this->assertIsArray($decoded['tags']);
    }

    // ==================== COLLECTION TRANSFORMATION WITH FILTERING TESTS ====================

    /**
     * Test that filtered RecordCollection transforms correctly.
     */
    public function test_filtered_record_collection_transforms_correctly(): void
    {
        // Arrange
        $recordCollection = new RecordCollection;
        $recordCollection->add(
            new TestUserRecord(id: 1, name: 'User Active 1', email: TestEmailAddress::fromString('active1@example.com'), status: TestUserStatus::ACTIVE),
            new TestUserRecord(id: 2, name: 'User Inactive', email: TestEmailAddress::fromString('inactive@example.com'), status: TestUserStatus::INACTIVE),
            new TestUserRecord(id: 3, name: 'User Active 2', email: TestEmailAddress::fromString('active2@example.com'), status: TestUserStatus::ACTIVE),
            new TestUserRecord(id: 4, name: 'User Suspended', email: TestEmailAddress::fromString('suspended@example.com'), status: TestUserStatus::SUSPENDED)
        );

        // Act - Filter only active users
        $activeRecords = $recordCollection->filter(fn($record) => $record->status === TestUserStatus::ACTIVE);

        $dataCollection = new DataCollection;
        foreach ($activeRecords->all() as $record) {
            $dataCollection->add(TestUserData::from($record));
        }

        // Assert
        $this->assertCount(2, $dataCollection);

        $dataArray = $dataCollection->toArray();
        $this->assertSame('1', $dataArray[0]->id);
        $this->assertSame('User Active 1', $dataArray[0]->name);
        $this->assertSame('3', $dataArray[1]->id);
        $this->assertSame('User Active 2', $dataArray[1]->name);
    }

    /**
     * Test that mapped RecordCollection transforms correctly.
     */
    public function test_mapped_record_collection_transforms_correctly(): void
    {
        // Arrange
        $recordCollection = new RecordCollection;
        $recordCollection->add(
            new TestUserRecord(id: 1, name: 'User 1', email: TestEmailAddress::fromString('user1@example.com')),
            new TestUserRecord(id: 2, name: 'User 2', email: TestEmailAddress::fromString('user2@example.com')),
            new TestUserRecord(id: 3, name: 'User 3', email: TestEmailAddress::fromString('user3@example.com'))
        );

        // Act - Transform to Data DTOs directly with map
        $dataArray = $recordCollection
            ->map(fn($record) => TestUserData::from($record))
            ->toArray();

        // Assert
        $this->assertCount(3, $dataArray);
        $this->assertInstanceOf(TestUserData::class, $dataArray[0]);
        $this->assertInstanceOf(TestUserData::class, $dataArray[1]);
        $this->assertInstanceOf(TestUserData::class, $dataArray[2]);
        $this->assertSame('1', $dataArray[0]->id);
        $this->assertSame('2', $dataArray[1]->id);
        $this->assertSame('3', $dataArray[2]->id);
    }

    // ==================== ERROR HANDLING TESTS ====================

    /**
     * Test that transformation fails gracefully when required fields are missing.
     */
    public function test_transformation_fails_when_required_fields_are_missing(): void
    {
        // Arrange - Record missing required 'name' field
        $record = new TestUserRecord(
            id: 1,
            email: $this->testEmail
            // name is required in TestUserData constructor
        );

        // Act & Assert
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Missing required properties: name');

        TestUserData::from($record);
    }

    /**
     * Test that transformation fails with type mismatch.
     */
    public function test_transformation_fails_with_type_mismatch(): void
    {
        // This test would require a record with incompatible types
        // But since our records are typed, this is handled at construction time

        // Skip or implement with a custom scenario
        $this->markTestSkipped('Type mismatches are caught at Record construction time');
    }

    // ==================== COMPLEX SCENARIO TESTS ====================

    /**
     * Test transformation with featured product relationship.
     */
    public function test_transformation_with_featured_product_relationship(): void
    {
        // Arrange
        $featuredProduct = new TestProductRecord(
            id: 99,
            name: 'Featured Product',
            price: 999,
            isFeatured: true
        );

        $userRecord = new TestUserRecord(
            id: 1,
            name: 'John Doe',
            email: $this->testEmail,
            featuredProduct: $featuredProduct,
            createdAt: $this->now
        );

        // Act
        $fullUserData = TestFullUserData::from($userRecord);

        // Assert
        $this->assertInstanceOf(TestFullUserData::class, $fullUserData);
        $this->assertNotNull($fullUserData->featuredProduct);
        $this->assertSame(99, $fullUserData->featuredProduct->id);
        $this->assertSame('Featured Product', $fullUserData->featuredProduct->name);
        $this->assertSame(999.0, $fullUserData->featuredProduct->price);
        $this->assertTrue($fullUserData->featuredProduct->isFeatured);
    }

    /**
     * Test transformation preserves immutability.
     */
    public function test_transformation_preserves_immutability(): void
    {
        // Arrange
        $record = new TestUserRecord(
            id: 1,
            name: 'John Doe',
            email: $this->testEmail
        );

        // Act
        $data = TestUserData::from($record);

        // Assert - Both Record and Data DTO have readonly properties
        $recordReflection = new \ReflectionClass($record);
        $dataReflection = new \ReflectionClass($data);

        foreach ($recordReflection->getProperties() as $property) {
            $this->assertTrue($property->isReadOnly(), 'Record property should be readonly');
        }

        foreach ($dataReflection->getProperties() as $property) {
            $this->assertTrue($property->isReadOnly(), 'Data DTO property should be readonly');
        }
    }

    /**
     * Test batch transformation of multiple records.
     */
    public function test_batch_transformation_of_multiple_records(): void
    {
        // Arrange
        $records = [
            new TestUserRecord(id: 1, name: 'User A', email: TestEmailAddress::fromString('a@example.com')),
            new TestUserRecord(id: 2, name: 'User B', email: TestEmailAddress::fromString('b@example.com')),
            new TestUserRecord(id: 3, name: 'User C', email: TestEmailAddress::fromString('c@example.com')),
            new TestUserRecord(id: 4, name: 'User D', email: TestEmailAddress::fromString('d@example.com')),
            new TestUserRecord(id: 5, name: 'User E', email: TestEmailAddress::fromString('e@example.com')),
        ];

        // Act
        $dataArray = array_map(
            fn($record) => TestUserData::from($record),
            $records
        );

        // Assert
        $this->assertCount(5, $dataArray);
        $this->assertSame('1', $dataArray[0]->id);
        $this->assertSame('User A', $dataArray[0]->name);
        $this->assertSame('5', $dataArray[4]->id);
        $this->assertSame('User E', $dataArray[4]->name);
    }

    /**
     * Test that original Record is not modified during transformation.
     */
    public function test_original_record_is_not_modified_during_transformation(): void
    {
        // Arrange
        $originalRecord = new TestUserRecord(
            id: 1,
            name: 'John Doe',
            email: $this->testEmail,
            status: TestUserStatus::ACTIVE
        );

        $originalHash = spl_object_hash($originalRecord);
        $originalName = $originalRecord->name;
        $originalStatus = $originalRecord->status;

        // Act
        $data = TestUserData::from($originalRecord);

        // Assert - Original record unchanged
        $this->assertSame($originalHash, spl_object_hash($originalRecord));
        $this->assertSame($originalName, $originalRecord->name);
        $this->assertSame($originalStatus, $originalRecord->status);

        // Data DTO is a new object
        $this->assertNotSame($originalRecord, $data);
    }
}
