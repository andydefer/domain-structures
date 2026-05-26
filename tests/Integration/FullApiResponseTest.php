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
use AndyDefer\DomainStructures\Tests\Fixtures\Records\TestUserUpdateRecord;
use AndyDefer\DomainStructures\Tests\Fixtures\ValueObjects\TestEmailAddress;
use AndyDefer\DomainStructures\Tests\Fixtures\ValueObjects\TestIso8601DateTime;
use AndyDefer\DomainStructures\Tests\TestCase;

/**
 * Integration test for complete API response workflow.
 *
 * This test suite validates the entire flow from database records to API responses:
 * - Database Record creation and normalization (snake_case for DB)
 * - Record to Data DTO transformation
 * - Data DTO normalization (camelCase for API)
 * - Nested collections and relationships handling
 * - Enum serialization (values for DB, labels/values for API)
 * - Null value handling for partial updates
 * - Collection pagination and transformation
 * - Error responses and edge cases
 *
 * The tests follow the AAA pattern (Arrange, Act, Assert).
 */
final class FullApiResponseTest extends TestCase
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

    // ==================== RECORD TO API RESPONSE TESTS ====================

    /**
     * Test that a simple User Record is properly normalized for database storage.
     */
    public function test_user_record_normalizes_to_snake_case_for_database(): void
    {
        // Arrange
        $userRecord = new TestUserRecord(
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
        $normalized = $userRecord->normalize(includeNulls: true, mode: NormalizeMode::ARRAY);

        // Assert
        $this->assertIsArray($normalized);
        $this->assertArrayHasKey('id', $normalized);
        $this->assertArrayHasKey('name', $normalized);
        $this->assertArrayHasKey('email', $normalized);
        $this->assertArrayHasKey('status', $normalized);
        $this->assertArrayHasKey('role', $normalized);
        $this->assertArrayHasKey('grade', $normalized);
        $this->assertArrayHasKey('tags', $normalized);
        $this->assertArrayHasKey('created_at', $normalized); // Snake case for DB

        $this->assertSame(1, $normalized['id']);
        $this->assertSame('John Doe', $normalized['name']);
        $this->assertSame('john.doe@example.com', $normalized['email']);
        $this->assertSame('active', $normalized['status']);
        $this->assertSame('user', $normalized['role']);
        $this->assertSame(1, $normalized['grade']);
        $this->assertSame(['premium', 'vip', 'early_adopter'], $normalized['tags']);
    }

    /**
     * Test that a User Record is properly transformed to Data DTO for API response.
     */
    public function test_user_record_transforms_to_data_dto_for_api_response(): void
    {
        // Arrange
        $userRecord = new TestUserRecord(
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
        $userData = TestUserData::from($userRecord);
        $apiResponse = $userData->normalize(NormalizeMode::ARRAY);

        // Assert
        $this->assertInstanceOf(TestUserData::class, $userData);
        $this->assertIsArray($apiResponse);
        $this->assertArrayHasKey('id', $apiResponse);
        $this->assertArrayHasKey('name', $apiResponse);
        $this->assertArrayHasKey('email', $apiResponse);
        $this->assertArrayHasKey('status', $apiResponse);
        $this->assertArrayHasKey('role', $apiResponse);
        $this->assertArrayHasKey('grade', $apiResponse);
        $this->assertArrayHasKey('tags', $apiResponse);
        $this->assertArrayHasKey('createdAt', $apiResponse); // Camel case for API

        $this->assertSame('1', $apiResponse['id']); // Data DTO uses string for ID
        $this->assertSame('John Doe', $apiResponse['name']);
        $this->assertSame('john.doe@example.com', $apiResponse['email']);
        $this->assertSame('active', $apiResponse['status']);
        $this->assertSame('admin', $apiResponse['role']);
        $this->assertSame('gold', $apiResponse['grade']); // String representation for API
    }

    /**
     * Test that User Record with null values handles includeNulls flag correctly.
     */
    public function test_user_record_excludes_null_values_for_database_updates(): void
    {
        // Arrange
        $updateData = new TestUserUpdateRecord(
            name: 'Jane Doe',
            email: null,
            lifeStage: null
        );

        // Act - Partial update should exclude nulls
        $normalized = $updateData->normalize(includeNulls: false, mode: NormalizeMode::ARRAY);

        // Assert
        $this->assertIsArray($normalized);
        $this->assertArrayHasKey('name', $normalized);
        $this->assertArrayNotHasKey('email', $normalized);
        $this->assertArrayNotHasKey('life_stage', $normalized);
        $this->assertSame('Jane Doe', $normalized['name']);
        $this->assertCount(1, $normalized);
    }

    // ==================== COLLECTION API RESPONSE TESTS ====================

    /**
     * Test that a collection of Records is transformed to Data collection for API.
     */
    public function test_record_collection_transforms_to_data_collection_for_api(): void
    {
        // Arrange
        $recordCollection = new RecordCollection;
        $recordCollection->add(
            new TestUserRecord(id: 1, name: 'John Doe', email: TestEmailAddress::fromString('john@example.com')),
            new TestUserRecord(id: 2, name: 'Jane Doe', email: TestEmailAddress::fromString('jane@example.com')),
            new TestUserRecord(id: 3, name: 'Bob Smith', email: TestEmailAddress::fromString('bob@example.com'))
        );

        // Act - Transform each record to Data DTO
        $dataCollection = new DataCollection;
        foreach ($recordCollection->all() as $record) {
            $dataCollection->add(TestUserData::from($record));
        }

        $apiResponse = $dataCollection->normalize(NormalizeMode::ARRAY);

        // Assert
        $this->assertCount(3, $apiResponse);
        $this->assertSame('1', $apiResponse[0]['id']);
        $this->assertSame('John Doe', $apiResponse[0]['name']);
        $this->assertSame('2', $apiResponse[1]['id']);
        $this->assertSame('Jane Doe', $apiResponse[1]['name']);
        $this->assertSame('3', $apiResponse[2]['id']);
        $this->assertSame('Bob Smith', $apiResponse[2]['name']);
    }

    /**
     * Test that RecordCollection normalizes to JSON for API response.
     */
    public function test_record_collection_normalizes_to_json_for_api(): void
    {
        // Arrange
        $recordCollection = new RecordCollection;
        $recordCollection->add(
            new TestUserRecord(id: 1, name: 'John Doe', email: TestEmailAddress::fromString('john@example.com'))
        );

        // Act
        $jsonResponse = $recordCollection->normalize(NormalizeMode::JSON);

        // Assert
        $this->assertIsString($jsonResponse);
        $this->assertJson($jsonResponse);

        $decoded = json_decode($jsonResponse, true);
        $this->assertIsArray($decoded);
        $this->assertCount(1, $decoded);
        $this->assertSame('John Doe', $decoded[0]['name']);
    }

    // ==================== NESTED RELATIONSHIPS TESTS ====================

    /**
     * Test that User with nested Product collection is properly normalized.
     */
    public function test_user_with_nested_product_collection_normalizes_correctly(): void
    {
        // Arrange
        $productCollection = new ProductRecordCollection;
        $productCollection->add(
            new TestProductRecord(id: 1, name: 'Laptop', price: 999, isFeatured: true),
            new TestProductRecord(id: 2, name: 'Mouse', price: 29, isFeatured: false)
        );

        $userRecord = new TestUserRecord(
            id: 1,
            name: 'John Doe',
            email: $this->testEmail,
            products: $productCollection
        );

        // Act
        $normalized = $userRecord->normalize(includeNulls: false, mode: NormalizeMode::ARRAY);

        // Assert
        $this->assertIsArray($normalized);
        $this->assertArrayHasKey('products', $normalized);
        $this->assertCount(2, $normalized['products']);
        $this->assertSame('Laptop', $normalized['products'][0]['name']);
        $this->assertSame(999, $normalized['products'][0]['price']);
        $this->assertSame(true, $normalized['products'][0]['is_featured']);
        $this->assertSame('Mouse', $normalized['products'][1]['name']);
        $this->assertSame(29, $normalized['products'][1]['price']);
    }

    /**
     * Test that User with roles collection normalizes correctly.
     */
    public function test_user_with_roles_collection_normalizes_correctly(): void
    {
        // Arrange
        $rolesCollection = new TestUserRoleCollection;
        $rolesCollection->add(TestUserRole::ADMIN, TestUserRole::USER);

        $userData = new TestUserWithRolesData(
            id: '1',
            name: 'John Doe',
            email: $this->testEmail,
            status: TestUserStatus::ACTIVE,
            roles: $rolesCollection,
            grade: TestUserGrade::PLATINUM,
            emailVerifiedAt: null,
            tags: $this->tags,
            createdAt: $this->now
        );

        // Act
        $apiResponse = $userData->normalize(NormalizeMode::ARRAY);

        // Assert
        $this->assertIsArray($apiResponse);
        $this->assertArrayHasKey('roles', $apiResponse);
        $this->assertCount(2, $apiResponse['roles']);
        $this->assertSame('admin', $apiResponse['roles'][0]);
        $this->assertSame('user', $apiResponse['roles'][1]);
    }

    /**
     * Test that deeply nested structures (collection of collections) normalize correctly.
     */
    public function test_deeply_nested_collections_normalize_correctly(): void
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

        // Act
        $normalized = $userRecord->normalize(NormalizeMode::ARRAY);

        // Assert
        $this->assertIsArray($normalized);
        $this->assertArrayHasKey('products', $normalized);
        $this->assertCount(2, $normalized['products']);
        $this->assertSame('Product A', $normalized['products'][0]['name']);
        $this->assertSame('Product B', $normalized['products'][1]['name']);
    }

    // ==================== ENUM SERIALIZATION TESTS ====================

    /**
     * Test that backed string enum serializes to its value in API response.
     */
    public function test_backed_string_enum_serializes_to_value_in_api(): void
    {
        // Arrange
        $userRecord = new TestUserRecord(
            name: 'John Doe',
            email: $this->testEmail,
            role: TestUserRole::ADMIN
        );

        // Act
        $normalized = $userRecord->normalize(
            mode: NormalizeMode::ARRAY
        );

        // Assert
        $this->assertSame('admin', $normalized['role']);
    }

    /**
     * Test that backed int enum serializes to int value.
     */
    public function test_backed_int_enum_serializes_to_int_value(): void
    {
        // Arrange
        $userRecord = new TestUserRecord(
            name: 'John Doe',
            email: $this->testEmail,
            grade: TestUserGrade::GOLD
        );

        // Act
        $normalized = $userRecord->normalize(
            mode: NormalizeMode::ARRAY
        );

        // Assert
        $this->assertSame(3, $normalized['grade']); // GOLD = 3
    }

    /**
     * Test that pure enum (no backing) in Data DTO serializes to name.
     */
    public function test_pure_enum_in_data_dto_serializes_to_name(): void
    {
        // Arrange
        $collection = new TestUserRoleCollection;
        $collection->add(TestUserRole::ADMIN, TestUserRole::USER);

        $userData = new TestUserWithRolesData(
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
        $apiResponse = $userData->normalize(NormalizeMode::ARRAY);

        // Assert
        $this->assertSame('active', $apiResponse['status']); // Backed enum -> value
        $this->assertSame(['admin', 'user'], $apiResponse['roles']); // Backed enum values
    }

    // ==================== VALUE OBJECT SERIALIZATION TESTS ====================

    /**
     * Test that EmailAddress Value Object serializes to string.
     */
    public function test_email_value_object_serializes_to_string(): void
    {
        // Arrange
        $userRecord = new TestUserRecord(
            name: 'John Doe',
            email: $this->testEmail
        );

        // Act
        $normalized = $userRecord->normalize(NormalizeMode::ARRAY);

        // Assert
        $this->assertSame('john.doe@example.com', $normalized['email']);
        $this->assertIsString($normalized['email']);
    }

    /**
     * Test that Iso8601DateTime Value Object serializes to string.
     */
    public function test_datetime_value_object_serializes_to_string(): void
    {
        // Arrange
        $userRecord = new TestUserRecord(
            name: 'John Doe',
            email: $this->testEmail,
            createdAt: $this->now
        );

        // Act
        $normalized = $userRecord->normalize(NormalizeMode::ARRAY);

        // Assert
        $this->assertIsString($normalized['created_at']);
        $this->assertMatchesRegularExpression('/\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}/', $normalized['created_at']);
    }

    // ==================== PAGINATION AND FILTERING TESTS ====================

    /**
     * Test that API response can be paginated using collection methods.
     */
    public function test_api_response_can_be_paginated_using_collection_methods(): void
    {
        // Arrange
        $allUsers = new RecordCollection;
        for ($i = 1; $i <= 50; $i++) {
            $allUsers->add(
                new TestUserRecord(
                    id: $i,
                    name: "User {$i}",
                    email: TestEmailAddress::fromString("user{$i}@example.com")
                )
            );
        }

        $page = 2;
        $perPage = 10;
        $offset = ($page - 1) * $perPage;

        // Act
        $paginated = $allUsers
            ->all()
            ->toArray();

        $paginated = array_slice($paginated, $offset, $perPage);

        $userDataCollection = new DataCollection;
        foreach ($paginated as $userRecord) {
            $userDataCollection->add(TestUserData::from($userRecord));
        }

        $apiResponse = $userDataCollection->normalize(NormalizeMode::ARRAY);

        // Assert
        $this->assertCount(10, $apiResponse);
        $this->assertSame('11', $apiResponse[0]['id']);
        $this->assertSame('User 11', $apiResponse[0]['name']);
        $this->assertSame('20', $apiResponse[9]['id']);
        $this->assertSame('User 20', $apiResponse[9]['name']);
    }

    /**
     * Test that API response can be filtered using collection methods.
     */
    public function test_api_response_can_be_filtered_using_collection_methods(): void
    {
        // Arrange
        $allProducts = new ProductRecordCollection;
        $allProducts->add(
            new TestProductRecord(id: 1, name: 'Laptop', price: 999, isFeatured: true),
            new TestProductRecord(id: 2, name: 'Mouse', price: 29, isFeatured: false),
            new TestProductRecord(id: 3, name: 'Keyboard', price: 89, isFeatured: true),
            new TestProductRecord(id: 4, name: 'Monitor', price: 299, isFeatured: false),
            new TestProductRecord(id: 5, name: 'Desk', price: 499, isFeatured: true)
        );

        // Act - Filter featured products only
        $featuredProducts = $allProducts->filter(fn($product) => $product->isFeatured === true);

        $productDataCollection = new ProductDataCollection;
        foreach ($featuredProducts->all() as $product) {
            $productDataCollection->add(TestProductData::from($product));
        }

        $apiResponse = $productDataCollection->normalize(NormalizeMode::ARRAY);

        // Assert
        $this->assertCount(3, $apiResponse);
        $this->assertSame('Laptop', $apiResponse[0]['name']);
        $this->assertSame('Keyboard', $apiResponse[1]['name']);
        $this->assertSame('Desk', $apiResponse[2]['name']);
        $this->assertTrue($apiResponse[0]['isFeatured']);
        $this->assertTrue($apiResponse[1]['isFeatured']);
        $this->assertTrue($apiResponse[2]['isFeatured']);
    }

    /**
     * Test that API response can be sorted using collection methods.
     */
    public function test_api_response_can_be_sorted_using_collection_methods(): void
    {
        // Arrange
        $allProducts = new ProductRecordCollection;
        $allProducts->add(
            new TestProductRecord(id: 1, name: 'Laptop', price: 999),
            new TestProductRecord(id: 2, name: 'Mouse', price: 29),
            new TestProductRecord(id: 3, name: 'Desk', price: 499),
            new TestProductRecord(id: 4, name: 'Keyboard', price: 89)
        );

        // Act - Sort by price ascending
        $sortedProducts = $allProducts
            ->all()
            ->sort()
            ->toArray();

        // Assert
        $this->assertSame('Mouse', $sortedProducts[0]->name);
        $this->assertSame(29, $sortedProducts[0]->price);
        $this->assertSame('Keyboard', $sortedProducts[1]->name);
        $this->assertSame(89, $sortedProducts[1]->price);
        $this->assertSame('Desk', $sortedProducts[2]->name);
        $this->assertSame(499, $sortedProducts[2]->price);
        $this->assertSame('Laptop', $sortedProducts[3]->name);
        $this->assertSame(999, $sortedProducts[3]->price);
    }

    // ==================== COMPLETE API WORKFLOW TESTS ====================

    /**
     * Test complete API workflow from database records to JSON response.
     */
    public function test_complete_api_workflow_from_database_to_json_response(): void
    {
        // Arrange - Simulate database query results
        $dbRecords = new RecordCollection;
        $dbRecords->add(
            new TestUserRecord(id: 1, name: 'Alice', email: TestEmailAddress::fromString('alice@example.com'), status: TestUserStatus::ACTIVE),
            new TestUserRecord(id: 2, name: 'Bob', email: TestEmailAddress::fromString('bob@example.com'), status: TestUserStatus::ACTIVE),
            new TestUserRecord(id: 3, name: 'Charlie', email: TestEmailAddress::fromString('charlie@example.com'), status: TestUserStatus::INACTIVE)
        );

        // Act - Transform to API response
        $activeUsers = $dbRecords->filter(fn($record) => $record->status === TestUserStatus::ACTIVE);

        $apiData = new DataCollection;
        foreach ($activeUsers->all() as $record) {
            $apiData->add(TestUserData::from($record));
        }

        $jsonResponse = $apiData->normalize(NormalizeMode::JSON);

        // Assert
        $this->assertIsString($jsonResponse);
        $this->assertJson($jsonResponse);

        $decoded = json_decode($jsonResponse, true);
        $this->assertCount(2, $decoded);
        $this->assertSame('1', $decoded[0]['id']);
        $this->assertSame('Alice', $decoded[0]['name']);
        $this->assertSame('active', $decoded[0]['status']);
        $this->assertSame('2', $decoded[1]['id']);
        $this->assertSame('Bob', $decoded[1]['name']);
        $this->assertSame('active', $decoded[1]['status']);
    }

    /**
     * Test complete API workflow with nested relationships.
     */
    public function test_complete_api_workflow_with_nested_relationships(): void
    {
        // Arrange - Simulate database records with relationships
        $aliceProducts = new ProductRecordCollection;
        $aliceProducts->add(
            new TestProductRecord(id: 1, name: 'Laptop', price: 999),
            new TestProductRecord(id: 2, name: 'Mouse', price: 29)
        );

        $bobProducts = new ProductRecordCollection;
        $bobProducts->add(
            new TestProductRecord(id: 3, name: 'Keyboard', price: 89)
        );

        $userRecords = new RecordCollection;
        $userRecords->add(
            new TestUserRecord(id: 1, name: 'Alice', email: TestEmailAddress::fromString('alice@example.com'), products: $aliceProducts),
            new TestUserRecord(id: 2, name: 'Bob', email: TestEmailAddress::fromString('bob@example.com'), products: $bobProducts)
        );

        // Act
        $fullUserData = new DataCollection;
        foreach ($userRecords->all() as $record) {
            $fullUserData->add(TestFullUserData::from($record));
        }

        $apiResponse = $fullUserData->normalize(NormalizeMode::ARRAY);

        // Assert
        $this->assertCount(2, $apiResponse);

        // Check Alice's products
        $this->assertCount(2, $apiResponse[0]['products']);
        $this->assertSame('Laptop', $apiResponse[0]['products'][0]['name']);
        $this->assertSame('Mouse', $apiResponse[0]['products'][1]['name']);

        // Check Bob's products
        $this->assertCount(1, $apiResponse[1]['products']);
        $this->assertSame('Keyboard', $apiResponse[1]['products'][0]['name']);
    }

    /**
     * Test error response handling with empty data.
     */
    public function test_error_response_handling_with_empty_data(): void
    {
        // Arrange
        $emptyResult = new RecordCollection;

        // Act
        $apiResponse = [
            'success' => false,
            'message' => 'No records found',
            'data' => $emptyResult->normalize(NormalizeMode::ARRAY),
            'count' => $emptyResult->count(),
        ];

        // Assert
        $this->assertFalse($apiResponse['success']);
        $this->assertSame('No records found', $apiResponse['message']);
        $this->assertEmpty($apiResponse['data']);
        $this->assertSame(0, $apiResponse['count']);
    }

    /**
     * Test single resource API response.
     */
    public function test_single_resource_api_response(): void
    {
        // Arrange
        $userRecord = new TestUserRecord(
            id: 1,
            name: 'John Doe',
            email: $this->testEmail,
            status: TestUserStatus::ACTIVE
        );

        // Act
        $userData = TestUserData::from($userRecord);
        $apiResponse = [
            'success' => true,
            'data' => $userData->normalize(NormalizeMode::ARRAY),
        ];

        // Assert
        $this->assertTrue($apiResponse['success']);
        $this->assertSame('1', $apiResponse['data']['id']);
        $this->assertSame('John Doe', $apiResponse['data']['name']);
        $this->assertSame('john.doe@example.com', $apiResponse['data']['email']);
        $this->assertSame('active', $apiResponse['data']['status']);
    }

    /**
     * Test create resource API response (201 Created).
     */
    public function test_create_resource_api_response(): void
    {
        // Arrange
        $createdRecord = new TestUserRecord(
            id: 42,
            name: 'New User',
            email: TestEmailAddress::fromString('new@example.com'),
            status: TestUserStatus::ACTIVE
        );

        // Act
        $createdData = TestUserData::from($createdRecord);
        $apiResponse = [
            'success' => true,
            'message' => 'Resource created successfully',
            'data' => $createdData->normalize(NormalizeMode::ARRAY),
        ];

        // Assert
        $this->assertTrue($apiResponse['success']);
        $this->assertSame('Resource created successfully', $apiResponse['message']);
        $this->assertSame('42', $apiResponse['data']['id']);
        $this->assertSame('New User', $apiResponse['data']['name']);
    }

    /**
     * Test update resource API response.
     */
    public function test_update_resource_api_response(): void
    {
        // Arrange
        $updatedRecord = new TestUserRecord(
            id: 1,
            name: 'Updated Name',
            email: $this->testEmail,
            status: TestUserStatus::ACTIVE
        );

        // Act
        $updatedData = TestUserData::from($updatedRecord);
        $apiResponse = [
            'success' => true,
            'message' => 'Resource updated successfully',
            'data' => $updatedData->normalize(NormalizeMode::ARRAY),
        ];

        // Assert
        $this->assertTrue($apiResponse['success']);
        $this->assertSame('Resource updated successfully', $apiResponse['message']);
        $this->assertSame('Updated Name', $apiResponse['data']['name']);
    }

    /**
     * Test delete resource API response.
     */
    public function test_delete_resource_api_response(): void
    {
        // Arrange & Act
        $apiResponse = [
            'success' => true,
            'message' => 'Resource deleted successfully',
        ];

        // Assert
        $this->assertTrue($apiResponse['success']);
        $this->assertSame('Resource deleted successfully', $apiResponse['message']);
        $this->assertArrayNotHasKey('data', $apiResponse);
    }

    /**
     * Test validation error API response.
     */
    public function test_validation_error_api_response(): void
    {
        // Arrange & Act
        $apiResponse = [
            'success' => false,
            'message' => 'Validation failed',
            'errors' => [
                'email' => ['The email field is required.'],
                'name' => ['The name must be at least 3 characters.'],
            ],
        ];

        // Assert
        $this->assertFalse($apiResponse['success']);
        $this->assertSame('Validation failed', $apiResponse['message']);
        $this->assertArrayHasKey('errors', $apiResponse);
        $this->assertCount(2, $apiResponse['errors']);
        $this->assertStringContainsString('required', $apiResponse['errors']['email'][0]);
    }

    /**
     * Test not found error API response.
     */
    public function test_not_found_error_api_response(): void
    {
        // Arrange & Act
        $apiResponse = [
            'success' => false,
            'message' => 'Resource not found',
            'code' => 404,
        ];

        // Assert
        $this->assertFalse($apiResponse['success']);
        $this->assertSame('Resource not found', $apiResponse['message']);
        $this->assertSame(404, $apiResponse['code']);
    }

    // ==================== JSON RESPONSE FORMAT TESTS ====================

    /**
     * Test that API response can be returned as JSON string.
     */
    public function test_api_response_can_be_returned_as_json_string(): void
    {
        // Arrange
        $userRecord = new TestUserRecord(
            id: 1,
            name: 'John Doe',
            email: $this->testEmail
        );

        // Act
        $userData = TestUserData::from($userRecord);
        $response = [
            'success' => true,
            'data' => $userData->normalize(NormalizeMode::ARRAY),
        ];
        $jsonResponse = json_encode($response, JSON_THROW_ON_ERROR);

        // Assert
        $this->assertIsString($jsonResponse);
        $this->assertJson($jsonResponse);

        $decoded = json_decode($jsonResponse, true);
        $this->assertTrue($decoded['success']);
        $this->assertSame('1', $decoded['data']['id']);
        $this->assertSame('John Doe', $decoded['data']['name']);
    }

    /**
     * Test that collection API response can be returned as JSON string.
     */
    public function test_collection_api_response_can_be_returned_as_json_string(): void
    {
        // Arrange
        $collection = new RecordCollection;
        $collection->add(
            new TestUserRecord(id: 1, name: 'User 1', email: TestEmailAddress::fromString('user1@example.com')),
            new TestUserRecord(id: 2, name: 'User 2', email: TestEmailAddress::fromString('user2@example.com'))
        );

        // Act
        $response = [
            'success' => true,
            'data' => $collection->normalize(NormalizeMode::ARRAY),
            'total' => $collection->count(),
        ];
        $jsonResponse = json_encode($response, JSON_THROW_ON_ERROR);

        // Assert
        $this->assertIsString($jsonResponse);
        $this->assertJson($jsonResponse);

        $decoded = json_decode($jsonResponse, true);
        $this->assertTrue($decoded['success']);
        $this->assertCount(2, $decoded['data']);
        $this->assertSame(2, $decoded['total']);
    }

    /**
     * Test that API response preserves data types correctly.
     */
    public function test_api_response_preserves_data_types_correctly(): void
    {
        // Arrange
        $userRecord = new TestUserRecord(
            id: 123,
            name: 'John Doe',
            email: $this->testEmail,
            grade: TestUserGrade::PLATINUM
        );

        // Act
        $userData = TestUserData::from($userRecord);
        $apiResponse = $userData->normalize(NormalizeMode::ARRAY);

        // Assert
        $this->assertIsString($apiResponse['id']);      // ID becomes string in Data DTO
        $this->assertIsString($apiResponse['name']);    // String
        $this->assertIsString($apiResponse['email']);   // Email as string
        $this->assertIsString($apiResponse['grade']);   // Enum as string in API
        $this->assertIsArray($apiResponse['tags']);     // Collection as array
    }
}
