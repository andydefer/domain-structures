<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Integration;

use AndyDefer\DomainStructures\Collections\Core\DataCollection;
use AndyDefer\DomainStructures\Collections\Core\RecordCollection;
use AndyDefer\DomainStructures\Collections\Utility\StringTypedCollection;
use AndyDefer\DomainStructures\Normalizers\NormalizerChain;
use AndyDefer\DomainStructures\Services\HydrationService;
use AndyDefer\DomainStructures\Tests\Fixtures\Collections\TestProductDataCollection;
use AndyDefer\DomainStructures\Tests\Fixtures\Collections\TestProductRecordCollection;
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

final class FullApiResponseTest extends TestCase
{
    private HydrationService $hydration;
    private TestIso8601DateTime $now;
    private TestEmailAddress $testEmail;
    private StringTypedCollection $tags;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hydration = new HydrationService();
        $this->now = new TestIso8601DateTime('2024-01-01T12:00:00+00:00');
        $this->testEmail = new TestEmailAddress('john.doe@example.com');
        $this->tags = new StringTypedCollection;
        $this->tags->add('premium', 'vip', 'early_adopter');
    }

    // ==================== RECORD TO API RESPONSE TESTS ====================

    public function test_user_record_normalizes_to_snake_case_for_database(): void
    {
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

        $normalized = NormalizerChain::get()->normalize($userRecord);

        $this->assertIsArray($normalized);
        $this->assertArrayHasKey('id', $normalized);
        $this->assertArrayHasKey('name', $normalized);
        $this->assertArrayHasKey('email', $normalized);
        $this->assertArrayHasKey('status', $normalized);
        $this->assertArrayHasKey('role', $normalized);
        $this->assertArrayHasKey('grade', $normalized);
        $this->assertArrayHasKey('tags', $normalized);
        $this->assertArrayHasKey('created_at', $normalized);

        $this->assertEquals(1, $normalized['id']);
        $this->assertSame('John Doe', $normalized['name']);
        $this->assertSame('john.doe@example.com', $normalized['email']);
        $this->assertSame('active', $normalized['status']);
        $this->assertSame('user', $normalized['role']);
        $this->assertEquals(1, $normalized['grade']);
        $this->assertSame(['premium', 'vip', 'early_adopter'], $normalized['tags']);
    }

    public function test_user_record_transforms_to_data_dto_for_api_response(): void
    {
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

        $userData = $this->hydration->hydrate(TestUserData::class, [
            'id' => $userRecord->id,
            'name' => $userRecord->name,
            'email' => $userRecord->email->getValue(),
            'status' => $userRecord->status->value,
            'role' => $userRecord->role->value,
            'grade' => $userRecord->grade->value,
            'tags' => $userRecord->tags->toArray(),
            'createdAt' => $userRecord->createdAt->getValue()
        ]);

        $apiResponse = NormalizerChain::get()->normalize($userData);

        $this->assertInstanceOf(TestUserData::class, $userData);
        $this->assertIsArray($apiResponse);
        $this->assertArrayHasKey('id', $apiResponse);
        $this->assertArrayHasKey('name', $apiResponse);
        $this->assertArrayHasKey('email', $apiResponse);
        $this->assertArrayHasKey('status', $apiResponse);
        $this->assertArrayHasKey('role', $apiResponse);
        $this->assertArrayHasKey('grade', $apiResponse);
        $this->assertArrayHasKey('tags', $apiResponse);
        $this->assertArrayHasKey('createdAt', $apiResponse);

        $this->assertSame(1, $apiResponse['id']);
        $this->assertSame('John Doe', $apiResponse['name']);
        $this->assertSame('john.doe@example.com', $apiResponse['email']);
        $this->assertSame('active', $apiResponse['status']);
        $this->assertSame('admin', $apiResponse['role']);
        $this->assertSame(3, $apiResponse['grade']);
    }

    public function test_user_record_excludes_null_values_for_database_updates(): void
    {
        $updateData = new TestUserUpdateRecord(
            name: 'Jane Doe',
            email: null,
            lifeStage: null
        );

        $normalized = NormalizerChain::get()->normalize($updateData);

        $this->assertIsArray($normalized);
        $this->assertArrayHasKey('name', $normalized);
        $this->assertArrayHasKey('email', $normalized);
        $this->assertArrayHasKey('life_stage', $normalized);
        $this->assertSame('Jane Doe', $normalized['name']);
        $this->assertNull($normalized['email']);
        $this->assertNull($normalized['life_stage']);
        $this->assertCount(3, $normalized);
    }

    // ==================== COLLECTION API RESPONSE TESTS ====================

    public function test_record_collection_transforms_to_data_collection_for_api(): void
    {
        $recordCollection = new RecordCollection(TestUserRecord::class);
        $recordCollection->add(
            new TestUserRecord(id: 1, name: 'John Doe', email: new TestEmailAddress('john@example.com')),
            new TestUserRecord(id: 2, name: 'Jane Doe', email: new TestEmailAddress('jane@example.com')),
            new TestUserRecord(id: 3, name: 'Bob Smith', email: new TestEmailAddress('bob@example.com'))
        );

        $dataCollection = new DataCollection(TestUserData::class);
        foreach ($recordCollection->all() as $record) {
            $userData = $this->hydration->hydrate(TestUserData::class, [
                'id' => $record->id,
                'name' => $record->name,
                'email' => $record->email->getValue(),
                'status' => $record->status?->value ?? TestUserStatus::ACTIVE->value,
                'role' => $record->role?->value ?? TestUserRole::USER->value,
                'grade' => $record->grade?->value ?? TestUserGrade::BRONZE->value,
                'tags' => $record->tags?->toArray(),
                'createdAt' => $record->createdAt?->getValue()
            ]);
            $dataCollection->add($userData);
        }

        $apiResponse = NormalizerChain::get()->normalize($dataCollection);

        $this->assertCount(3, $apiResponse);
        $this->assertEquals(1, $apiResponse[0]['id']);
        $this->assertSame('John Doe', $apiResponse[0]['name']);
        $this->assertEquals(2, $apiResponse[1]['id']);
        $this->assertSame('Jane Doe', $apiResponse[1]['name']);
        $this->assertEquals(3, $apiResponse[2]['id']);
        $this->assertSame('Bob Smith', $apiResponse[2]['name']);
    }

    // ==================== NESTED RELATIONSHIPS TESTS ====================

    public function test_user_with_nested_product_collection_normalizes_correctly(): void
    {
        $productCollection = new TestProductRecordCollection;
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

        $normalized = NormalizerChain::get()->normalize($userRecord);

        $this->assertIsArray($normalized);
        $this->assertArrayHasKey('products', $normalized);
        $this->assertCount(2, $normalized['products']);
        $this->assertSame('Laptop', $normalized['products'][0]['name']);
        $this->assertEquals(999, $normalized['products'][0]['price']);
        $this->assertTrue($normalized['products'][0]['is_featured']);
        $this->assertSame('Mouse', $normalized['products'][1]['name']);
        $this->assertEquals(29, $normalized['products'][1]['price']);
    }

    public function test_user_with_roles_collection_normalizes_correctly(): void
    {
        $rolesCollection = new TestUserRoleCollection;
        $rolesCollection->add(TestUserRole::ADMIN, TestUserRole::USER);

        $userData = new TestUserWithRolesData(
            id: 1,
            name: 'John Doe',
            email: $this->testEmail,
            status: TestUserStatus::ACTIVE,
            roles: $rolesCollection,
            grade: TestUserGrade::PLATINUM,
            emailVerifiedAt: null,
            tags: $this->tags,
            createdAt: $this->now
        );

        $apiResponse = NormalizerChain::get()->normalize($userData);

        $this->assertIsArray($apiResponse);
        $this->assertArrayHasKey('roles', $apiResponse);
        $this->assertCount(2, $apiResponse['roles']);
        $this->assertSame('admin', $apiResponse['roles'][0]);
        $this->assertSame('user', $apiResponse['roles'][1]);
    }

    public function test_deeply_nested_collections_normalize_correctly(): void
    {
        $innerProducts = new TestProductRecordCollection;
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

        $normalized = NormalizerChain::get()->normalize($userRecord);

        $this->assertIsArray($normalized);
        $this->assertArrayHasKey('products', $normalized);
        $this->assertCount(2, $normalized['products']);
        $this->assertSame('Product A', $normalized['products'][0]['name']);
        $this->assertSame('Product B', $normalized['products'][1]['name']);
    }

    // ==================== ENUM SERIALIZATION TESTS ====================

    public function test_backed_string_enum_serializes_to_value_in_api(): void
    {
        $userRecord = new TestUserRecord(
            name: 'John Doe',
            email: $this->testEmail,
            role: TestUserRole::ADMIN
        );

        $normalized = NormalizerChain::get()->normalize($userRecord);

        $this->assertSame('admin', $normalized['role']);
    }

    public function test_backed_int_enum_serializes_to_int_value(): void
    {
        $userRecord = new TestUserRecord(
            name: 'John Doe',
            email: $this->testEmail,
            grade: TestUserGrade::GOLD
        );

        $normalized = NormalizerChain::get()->normalize($userRecord);

        $this->assertEquals(3, $normalized['grade']);
    }

    public function test_pure_enum_in_data_dto_serializes_to_name(): void
    {
        $collection = new TestUserRoleCollection;
        $collection->add(TestUserRole::ADMIN, TestUserRole::USER);

        $userData = new TestUserWithRolesData(
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

        $apiResponse = NormalizerChain::get()->normalize($userData);

        $this->assertSame('active', $apiResponse['status']);
        $this->assertSame(['admin', 'user'], $apiResponse['roles']);
    }

    // ==================== VALUE OBJECT SERIALIZATION TESTS ====================

    public function test_email_value_object_serializes_to_string(): void
    {
        $userRecord = new TestUserRecord(
            name: 'John Doe',
            email: $this->testEmail
        );

        $normalized = NormalizerChain::get()->normalize($userRecord);

        $this->assertSame('john.doe@example.com', $normalized['email']);
        $this->assertIsString($normalized['email']);
    }

    public function test_datetime_value_object_serializes_to_string(): void
    {
        $userRecord = new TestUserRecord(
            name: 'John Doe',
            email: $this->testEmail,
            createdAt: $this->now
        );

        $normalized = NormalizerChain::get()->normalize($userRecord);

        $this->assertIsString($normalized['created_at']);
        $this->assertMatchesRegularExpression('/\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}/', $normalized['created_at']);
    }

    // ==================== PAGINATION AND FILTERING TESTS ====================

    public function test_api_response_can_be_paginated_using_collection_methods(): void
    {
        $allUsers = new RecordCollection(TestUserRecord::class);
        for ($i = 1; $i <= 50; $i++) {
            $allUsers->add(
                new TestUserRecord(
                    id: $i,
                    name: "User {$i}",
                    email: new TestEmailAddress("user{$i}@example.com")
                )
            );
        }

        $page = 2;
        $perPage = 10;
        $offset = ($page - 1) * $perPage;

        $paginated = $allUsers
            ->all()
            ->toArray();
        $paginated = array_slice($paginated, $offset, $perPage);

        $userDataCollection = new DataCollection(TestUserData::class);
        foreach ($paginated as $userRecord) {
            $userData = $this->hydration->hydrate(TestUserData::class, [
                'id' => $userRecord->id,
                'name' => $userRecord->name,
                'email' => $userRecord->email->getValue(),
                'status' => TestUserStatus::ACTIVE->value,
                'role' => TestUserRole::USER->value,
                'grade' => TestUserGrade::BRONZE->value
            ]);
            $userDataCollection->add($userData);
        }

        $apiResponse = NormalizerChain::get()->normalize($userDataCollection);

        $this->assertCount(10, $apiResponse);
        $this->assertEquals(11, $apiResponse[0]['id']);
        $this->assertSame('User 11', $apiResponse[0]['name']);
        $this->assertEquals(20, $apiResponse[9]['id']);
        $this->assertSame('User 20', $apiResponse[9]['name']);
    }

    public function test_api_response_can_be_filtered_using_collection_methods(): void
    {
        $allProducts = new TestProductRecordCollection;
        $allProducts->add(
            new TestProductRecord(id: 1, name: 'Laptop', price: 999, isFeatured: true),
            new TestProductRecord(id: 2, name: 'Mouse', price: 29, isFeatured: false),
            new TestProductRecord(id: 3, name: 'Keyboard', price: 89, isFeatured: true),
            new TestProductRecord(id: 4, name: 'Monitor', price: 299, isFeatured: false),
            new TestProductRecord(id: 5, name: 'Desk', price: 499, isFeatured: true)
        );

        $featuredProducts = $allProducts->filter(fn($product) => $product->isFeatured === true);

        $productDataCollection = new TestProductDataCollection;
        foreach ($featuredProducts->all() as $product) {
            $productData = $this->hydration->hydrate(TestProductData::class, [
                'id' => $product->id,
                'name' => $product->name,
                'price' => $product->price,
                'isFeatured' => $product->isFeatured
            ]);
            $productDataCollection->add($productData);
        }

        $apiResponse = NormalizerChain::get()->normalize($productDataCollection);

        $this->assertCount(3, $apiResponse);
        $this->assertSame('Laptop', $apiResponse[0]['name']);
        $this->assertSame('Keyboard', $apiResponse[1]['name']);
        $this->assertSame('Desk', $apiResponse[2]['name']);
        $this->assertTrue($apiResponse[0]['isFeatured']);
        $this->assertTrue($apiResponse[1]['isFeatured']);
        $this->assertTrue($apiResponse[2]['isFeatured']);
    }

    public function test_api_response_can_be_sorted_using_collection_methods(): void
    {
        $allProducts = new TestProductRecordCollection;
        $allProducts->add(
            new TestProductRecord(id: 1, name: 'Laptop', price: 999),
            new TestProductRecord(id: 2, name: 'Mouse', price: 29),
            new TestProductRecord(id: 3, name: 'Desk', price: 499),
            new TestProductRecord(id: 4, name: 'Keyboard', price: 89)
        );

        $sortedProducts = $allProducts
            ->all()
            ->sortBy('name')
            ->toArray();

        $this->assertSame('Desk', $sortedProducts[0]->name);
        $this->assertSame('Keyboard', $sortedProducts[1]->name);
        $this->assertSame('Laptop', $sortedProducts[2]->name);
        $this->assertSame('Mouse', $sortedProducts[3]->name);

        $sortedByPrice = $allProducts
            ->all()
            ->usort(fn($a, $b) => $a->price <=> $b->price)
            ->toArray();

        $this->assertSame('Mouse', $sortedByPrice[0]->name);
        $this->assertEquals(29, $sortedByPrice[0]->price);
    }

    // ==================== COMPLETE API WORKFLOW TESTS ====================

    public function test_complete_api_workflow_from_database_to_json_response(): void
    {
        $dbRecords = new RecordCollection(TestUserRecord::class);
        $dbRecords->add(
            new TestUserRecord(id: 1, name: 'Alice', email: new TestEmailAddress('alice@example.com'), status: TestUserStatus::ACTIVE),
            new TestUserRecord(id: 2, name: 'Bob', email: new TestEmailAddress('bob@example.com'), status: TestUserStatus::ACTIVE),
            new TestUserRecord(id: 3, name: 'Charlie', email: new TestEmailAddress('charlie@example.com'), status: TestUserStatus::INACTIVE)
        );

        $activeUsers = $dbRecords->filter(fn(TestUserRecord $record) => $record->status === TestUserStatus::ACTIVE);

        $apiData = new DataCollection(TestUserData::class);
        foreach ($activeUsers->all() as $record) {
            $userData = $this->hydration->hydrate(TestUserData::class, [
                'id' => $record->id,
                'name' => $record->name,
                'email' => $record->email->getValue(),
                'status' => $record->status->value,
                'role' => TestUserRole::USER->value,
                'grade' => TestUserGrade::BRONZE->value
            ]);
            $apiData->add($userData);
        }

        $jsonResponse = json_encode(NormalizerChain::get()->normalize($apiData));

        $this->assertIsString($jsonResponse);
        $this->assertJson($jsonResponse);

        $decoded = json_decode($jsonResponse, true);
        $this->assertCount(2, $decoded);
        $this->assertEquals(1, $decoded[0]['id']);
        $this->assertSame('Alice', $decoded[0]['name']);
        $this->assertSame('active', $decoded[0]['status']);
        $this->assertEquals(2, $decoded[1]['id']);
        $this->assertSame('Bob', $decoded[1]['name']);
        $this->assertSame('active', $decoded[1]['status']);
    }

    public function test_complete_api_workflow_with_nested_relationships(): void
    {
        $aliceProducts = new TestProductRecordCollection;
        $aliceProducts->add(
            new TestProductRecord(id: 1, name: 'Laptop', price: 999),
            new TestProductRecord(id: 2, name: 'Mouse', price: 29)
        );

        $bobProducts = new TestProductRecordCollection;
        $bobProducts->add(
            new TestProductRecord(id: 3, name: 'Keyboard', price: 89)
        );

        $userRecords = new RecordCollection(TestUserRecord::class);
        $userRecords->add(
            new TestUserRecord(id: 1, name: 'Alice', email: new TestEmailAddress('alice@example.com'), products: $aliceProducts),
            new TestUserRecord(id: 2, name: 'Bob', email: new TestEmailAddress('bob@example.com'), products: $bobProducts)
        );

        $fullUserData = new DataCollection(TestFullUserData::class);
        foreach ($userRecords->all() as $record) {
            $userData = $this->hydration->hydrate(TestFullUserData::class, [
                'id' => $record->id,
                'name' => $record->name,
                'email' => $record->email->getValue(),
                'status' => TestUserStatus::ACTIVE->value,
                'role' => TestUserRole::USER->value,
                'grade' => TestUserGrade::BRONZE->value,
                'tags' => [], // Ajout du paramètre tags requis
                'products' => $record->products->all()->toArray()
            ]);
            $fullUserData->add($userData);
        }

        $apiResponse = NormalizerChain::get()->normalize($fullUserData);

        $this->assertCount(2, $apiResponse);
        $this->assertCount(2, $apiResponse[0]['products']);
        $this->assertSame('Laptop', $apiResponse[0]['products'][0]['name']);
        $this->assertSame('Mouse', $apiResponse[0]['products'][1]['name']);
        $this->assertCount(1, $apiResponse[1]['products']);
        $this->assertSame('Keyboard', $apiResponse[1]['products'][0]['name']);
    }

    public function test_error_response_handling_with_empty_data(): void
    {
        $emptyResult = new RecordCollection(TestUserRecord::class);

        $apiResponse = [
            'success' => false,
            'message' => 'No records found',
            'data' => NormalizerChain::get()->normalize($emptyResult),
            'count' => $emptyResult->count(),
        ];

        $this->assertFalse($apiResponse['success']);
        $this->assertSame('No records found', $apiResponse['message']);
        $this->assertEmpty($apiResponse['data']);
        $this->assertSame(0, $apiResponse['count']);
    }

    public function test_single_resource_api_response(): void
    {
        $userRecord = new TestUserRecord(
            id: 1,
            name: 'John Doe',
            email: $this->testEmail,
            status: TestUserStatus::ACTIVE
        );

        $userData = $this->hydration->hydrate(TestUserData::class, [
            'id' => $userRecord->id,
            'name' => $userRecord->name,
            'email' => $userRecord->email->getValue(),
            'status' => $userRecord->status->value,
            'role' => TestUserRole::USER->value,
            'grade' => TestUserGrade::BRONZE->value
        ]);

        $apiResponse = [
            'success' => true,
            'data' => NormalizerChain::get()->normalize($userData),
        ];

        $this->assertTrue($apiResponse['success']);
        $this->assertEquals(1, $apiResponse['data']['id']);
        $this->assertSame('John Doe', $apiResponse['data']['name']);
        $this->assertSame('john.doe@example.com', $apiResponse['data']['email']);
        $this->assertSame('active', $apiResponse['data']['status']);
    }

    public function test_create_resource_api_response(): void
    {
        $createdRecord = new TestUserRecord(
            id: 42,
            name: 'New User',
            email: new TestEmailAddress('new@example.com'),
            status: TestUserStatus::ACTIVE
        );

        $createdData = $this->hydration->hydrate(TestUserData::class, [
            'id' => $createdRecord->id,
            'name' => $createdRecord->name,
            'email' => $createdRecord->email->getValue(),
            'status' => $createdRecord->status->value,
            'role' => TestUserRole::USER->value,
            'grade' => TestUserGrade::BRONZE->value
        ]);

        $apiResponse = [
            'success' => true,
            'message' => 'Resource created successfully',
            'data' => NormalizerChain::get()->normalize($createdData),
        ];

        $this->assertTrue($apiResponse['success']);
        $this->assertSame('Resource created successfully', $apiResponse['message']);
        $this->assertEquals(42, $apiResponse['data']['id']);
        $this->assertSame('New User', $apiResponse['data']['name']);
    }

    public function test_update_resource_api_response(): void
    {
        $updatedRecord = new TestUserRecord(
            id: 1,
            name: 'Updated Name',
            email: $this->testEmail,
            status: TestUserStatus::ACTIVE
        );

        $updatedData = $this->hydration->hydrate(TestUserData::class, [
            'id' => $updatedRecord->id,
            'name' => $updatedRecord->name,
            'email' => $updatedRecord->email->getValue(),
            'status' => $updatedRecord->status->value,
            'role' => TestUserRole::USER->value,
            'grade' => TestUserGrade::BRONZE->value
        ]);

        $apiResponse = [
            'success' => true,
            'message' => 'Resource updated successfully',
            'data' => NormalizerChain::get()->normalize($updatedData),
        ];

        $this->assertTrue($apiResponse['success']);
        $this->assertSame('Resource updated successfully', $apiResponse['message']);
        $this->assertSame('Updated Name', $apiResponse['data']['name']);
    }

    public function test_delete_resource_api_response(): void
    {
        $apiResponse = [
            'success' => true,
            'message' => 'Resource deleted successfully',
        ];

        $this->assertTrue($apiResponse['success']);
        $this->assertSame('Resource deleted successfully', $apiResponse['message']);
        $this->assertArrayNotHasKey('data', $apiResponse);
    }

    public function test_validation_error_api_response(): void
    {
        $apiResponse = [
            'success' => false,
            'message' => 'Validation failed',
            'errors' => [
                'email' => ['The email field is required.'],
                'name' => ['The name must be at least 3 characters.'],
            ],
        ];

        $this->assertFalse($apiResponse['success']);
        $this->assertSame('Validation failed', $apiResponse['message']);
        $this->assertArrayHasKey('errors', $apiResponse);
        $this->assertCount(2, $apiResponse['errors']);
        $this->assertStringContainsString('required', $apiResponse['errors']['email'][0]);
    }

    public function test_not_found_error_api_response(): void
    {
        $apiResponse = [
            'success' => false,
            'message' => 'Resource not found',
            'code' => 404,
        ];

        $this->assertFalse($apiResponse['success']);
        $this->assertSame('Resource not found', $apiResponse['message']);
        $this->assertSame(404, $apiResponse['code']);
    }

    // ==================== JSON RESPONSE FORMAT TESTS ====================

    public function test_api_response_can_be_returned_as_json_string(): void
    {
        $userRecord = new TestUserRecord(
            id: 1,
            name: 'John Doe',
            email: $this->testEmail
        );

        $userData = $this->hydration->hydrate(TestUserData::class, [
            'id' => $userRecord->id,
            'name' => $userRecord->name,
            'email' => $userRecord->email->getValue(),
            'status' => TestUserStatus::ACTIVE->value,
            'role' => TestUserRole::USER->value,
            'grade' => TestUserGrade::BRONZE->value
        ]);

        $response = [
            'success' => true,
            'data' => NormalizerChain::get()->normalize($userData),
        ];
        $jsonResponse = json_encode($response, JSON_THROW_ON_ERROR);

        $this->assertIsString($jsonResponse);
        $this->assertJson($jsonResponse);

        $decoded = json_decode($jsonResponse, true);
        $this->assertTrue($decoded['success']);
        $this->assertEquals(1, $decoded['data']['id']);
        $this->assertSame('John Doe', $decoded['data']['name']);
    }

    public function test_collection_api_response_can_be_returned_as_json_string(): void
    {
        $collection = new RecordCollection(TestUserRecord::class);
        $collection->add(
            new TestUserRecord(id: 1, name: 'User 1', email: new TestEmailAddress('user1@example.com')),
            new TestUserRecord(id: 2, name: 'User 2', email: new TestEmailAddress('user2@example.com'))
        );

        $dataCollection = new DataCollection(TestUserData::class);
        foreach ($collection->all() as $record) {
            $userData = $this->hydration->hydrate(TestUserData::class, [
                'id' => $record->id,
                'name' => $record->name,
                'email' => $record->email->getValue(),
                'status' => TestUserStatus::ACTIVE->value,
                'role' => TestUserRole::USER->value,
                'grade' => TestUserGrade::BRONZE->value
            ]);
            $dataCollection->add($userData);
        }

        $response = [
            'success' => true,
            'data' => NormalizerChain::get()->normalize($dataCollection),
            'total' => $collection->count(),
        ];
        $jsonResponse = json_encode($response, JSON_THROW_ON_ERROR);

        $this->assertIsString($jsonResponse);
        $this->assertJson($jsonResponse);

        $decoded = json_decode($jsonResponse, true);
        $this->assertTrue($decoded['success']);
        $this->assertCount(2, $decoded['data']);
        $this->assertEquals(2, $decoded['total']);
    }

    public function test_api_response_preserves_data_types_correctly(): void
    {
        $userRecord = new TestUserRecord(
            id: 123,
            name: 'John Doe',
            email: $this->testEmail,
            grade: TestUserGrade::PLATINUM
        );

        $userData = $this->hydration->hydrate(TestUserData::class, [
            'id' => $userRecord->id,
            'name' => $userRecord->name,
            'email' => $userRecord->email->getValue(),
            'status' => TestUserStatus::ACTIVE->value,
            'role' => TestUserRole::USER->value,
            'grade' => $userRecord->grade->value
        ]);

        $apiResponse = NormalizerChain::get()->normalize($userData);

        $this->assertIsInt($apiResponse['id']);
        $this->assertIsString($apiResponse['name']);
        $this->assertIsString($apiResponse['email']);
        $this->assertIsInt($apiResponse['grade']);
        $this->assertIsArray($apiResponse['tags']);
    }
}
