<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Integration;

use AndyDefer\DomainStructures\Collections\Core\DataCollection;
use AndyDefer\DomainStructures\Collections\Core\RecordCollection;
use AndyDefer\DomainStructures\Collections\Core\TypedCollection;
use AndyDefer\DomainStructures\Collections\Utility\StringTypedCollection;
use AndyDefer\DomainStructures\Normalizers\NormalizerChain;
use AndyDefer\DomainStructures\Tests\Fixtures\Collections\TestProductDataCollection;
use AndyDefer\DomainStructures\Tests\Fixtures\Collections\TestProductRecordCollection;
use AndyDefer\DomainStructures\Tests\Fixtures\Collections\TestUserRoleCollection;
use AndyDefer\DomainStructures\Tests\Fixtures\Data\TestFullUserData;
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
use InvalidArgumentException;
use RuntimeException;

final class RecordToDataTransformationTest extends TestCase
{
    private TestIso8601DateTime $now;

    private TestEmailAddress $testEmail;

    /** @var StringTypedCollection<int, string> */
    private StringTypedCollection $tags;

    protected function setUp(): void
    {
        parent::setUp();

        $this->now = TestIso8601DateTime::from('2024-01-01T12:00:00+00:00');
        $this->testEmail = TestEmailAddress::from('john.doe@example.com');
        $this->tags = new StringTypedCollection;
        $this->tags->add('premium', 'vip', 'early_adopter');
    }

    // ==================== SINGLE RECORD TO DATA TRANSFORMATION TESTS ====================

    public function test_simple_user_record_transforms_to_user_data_dto(): void
    {
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

        $data = TestUserData::from($record);

        $this->assertInstanceOf(TestUserData::class, $data);
        $this->assertSame(1, $data->id);
        $this->assertSame('John Doe', $data->name);
        $this->assertSame('john.doe@example.com', $data->email->getValue());
        $this->assertSame(TestUserStatus::ACTIVE, $data->status);
        $this->assertSame(TestUserRole::USER, $data->role);
        $this->assertSame(TestUserGrade::BRONZE, $data->grade);

        // Utiliser assertEquals pour comparer les valeurs, pas les références
        $this->assertEquals($this->now, $data->createdAt);
    }

    public function test_record_with_nullable_fields_transforms_correctly(): void
    {
        $record = new TestUserRecord(
            id: null,
            name: 'John Doe',
            email: $this->testEmail,
            emailVerifiedAt: null,
            featuredProduct: null
        );

        $data = TestUserData::from($record);

        $this->assertInstanceOf(TestUserData::class, $data);
        $this->assertNull($data->id);
        $this->assertSame('John Doe', $data->name);
        $this->assertNull($data->emailVerifiedAt);
    }

    public function test_complete_user_record_transforms_to_complete_data_dto(): void
    {
        $record = new TestUserRecord(
            id: 42,
            name: 'Jane Smith',
            email: TestEmailAddress::from('jane.smith@example.com'),
            status: TestUserStatus::ACTIVE,
            role: TestUserRole::ADMIN,
            grade: TestUserGrade::PLATINUM,
            emailVerifiedAt: $this->now,
            tags: $this->tags,
            createdAt: $this->now
        );

        $data = TestUserData::from($record);

        $this->assertInstanceOf(TestUserData::class, $data);
        $this->assertSame(42, $data->id);
        $this->assertSame('Jane Smith', $data->name);
        $this->assertSame('jane.smith@example.com', $data->email->getValue());
        $this->assertSame(TestUserStatus::ACTIVE, $data->status);
        $this->assertSame(TestUserRole::ADMIN, $data->role);
        $this->assertSame(TestUserGrade::PLATINUM, $data->grade);

        // Utiliser assertEquals pour les Value Objects
        $this->assertEquals($this->now, $data->emailVerifiedAt);
        $this->assertEquals($this->tags, $data->tags);
        $this->assertEquals($this->now, $data->createdAt);
    }

    // ==================== RECORD COLLECTION TO DATA COLLECTION TESTS ====================

    public function test_record_collection_transforms_to_data_collection(): void
    {
        /** @var RecordCollection<TestUserRecord> $recordCollection */
        $recordCollection = new RecordCollection(TestUserRecord::class);
        $recordCollection->add(
            new TestUserRecord(id: 1, name: 'User 1', email: TestEmailAddress::from('user1@example.com')),
            new TestUserRecord(id: 2, name: 'User 2', email: TestEmailAddress::from('user2@example.com')),
            new TestUserRecord(id: 3, name: 'User 3', email: TestEmailAddress::from('user3@example.com'))
        );

        /** @var DataCollection<TestUserData> $dataCollection */
        $dataCollection = new DataCollection(TestUserData::class);
        foreach ($recordCollection->all() as $record) {
            $dataCollection->add(TestUserData::from($record));
        }

        $this->assertCount(3, $dataCollection);

        /** @var array<int, TestUserData> $dataArray */
        $dataArray = $dataCollection->toArray();
        $this->assertEquals(1, $dataArray[0]->id);
        $this->assertSame('User 1', $dataArray[0]->name);
        $this->assertEquals(2, $dataArray[1]->id);
        $this->assertSame('User 2', $dataArray[1]->name);
        $this->assertEquals(3, $dataArray[2]->id);
        $this->assertSame('User 3', $dataArray[2]->name);
    }

    public function test_empty_record_collection_transforms_to_empty_data_collection(): void
    {
        /** @var RecordCollection<TestUserRecord> $recordCollection */
        $recordCollection = new RecordCollection(TestUserRecord::class);

        /** @var DataCollection<TestUserData> $dataCollection */
        $dataCollection = new DataCollection(TestUserData::class);
        foreach ($recordCollection->all() as $record) {
            $dataCollection->add(TestUserData::from($record));
        }

        $this->assertCount(0, $dataCollection);
        $this->assertTrue($dataCollection->isEmpty());
    }

    public function test_transformation_with_collect_helper_method(): void
    {
        /** @var RecordCollection<TestUserRecord> $recordCollection */
        $recordCollection = new RecordCollection(TestUserRecord::class);
        $recordCollection->add(
            new TestUserRecord(id: 1, name: 'User 1', email: TestEmailAddress::from('user1@example.com')),
            new TestUserRecord(id: 2, name: 'User 2', email: TestEmailAddress::from('user2@example.com'))
        );

        /** @var TypedCollection<TestUserData> $dataCollection */
        $dataCollection = TestUserData::collect($recordCollection);

        $this->assertInstanceOf(TypedCollection::class, $dataCollection);
        $this->assertCount(2, $dataCollection);
        $this->assertInstanceOf(TestUserData::class, $dataCollection[0]);
        $this->assertInstanceOf(TestUserData::class, $dataCollection[1]);
        $this->assertEquals(1, $dataCollection[0]->id);
        $this->assertEquals(2, $dataCollection[1]->id);
    }

    // ==================== NESTED COLLECTION TRANSFORMATION TESTS ====================

    public function test_record_with_nested_product_collection_transforms_correctly(): void
    {
        /** @var TestProductRecordCollection $productRecords */
        $productRecords = new TestProductRecordCollection;
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

        $fullUserData = TestFullUserData::from($userRecord);

        $this->assertInstanceOf(TestFullUserData::class, $fullUserData);
        $this->assertInstanceOf(TestProductDataCollection::class, $fullUserData->products);
        $this->assertCount(3, $fullUserData->products);

        /** @var array<int, TestProductRecord> $products */
        $products = $fullUserData->products->toArray();
        $this->assertSame(1, $products[0]->id);
        $this->assertSame('Laptop', $products[0]->name);
        $this->assertEquals(999.0, $products[0]->price);
        $this->assertTrue($products[0]->isFeatured);
        $this->assertSame(2, $products[1]->id);
        $this->assertSame('Mouse', $products[1]->name);
        $this->assertEquals(29.0, $products[1]->price);
        $this->assertFalse($products[1]->isFeatured);
    }

    public function test_record_with_roles_collection_transforms_correctly(): void
    {
        /** @var TestUserRoleCollection $rolesCollection */
        $rolesCollection = new TestUserRoleCollection;
        $rolesCollection->add(
            TestUserRole::ADMIN,
            TestUserRole::USER,
            TestUserRole::GUEST
        );

        $userData = TestUserWithRolesData::from([
            'id' => 1,
            'name' => 'John Doe',
            'email' => $this->testEmail->getValue(),
            'status' => 'active',
            'roles' => $rolesCollection,
            'grade' => 1,
            'emailVerifiedAt' => null,
            'tags' => [],
            'createdAt' => $this->now->getValue(),
        ]);

        $this->assertInstanceOf(TestUserWithRolesData::class, $userData);
        $this->assertInstanceOf(TestUserRoleCollection::class, $userData->roles);
        $this->assertCount(3, $userData->roles);
        $this->assertTrue($userData->roles->contains(TestUserRole::ADMIN));
        $this->assertTrue($userData->roles->contains(TestUserRole::USER));
        $this->assertTrue($userData->roles->contains(TestUserRole::GUEST));
    }

    public function test_deeply_nested_collections_transform_recursively(): void
    {
        /** @var TestProductRecordCollection $innerProducts */
        $innerProducts = new TestProductRecordCollection;
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

        $fullUserData = TestFullUserData::from($userRecord);

        $this->assertInstanceOf(TestProductDataCollection::class, $fullUserData->products);
        $this->assertCount(2, $fullUserData->products);

        /** @var array<int, TestProductRecord> $products */
        $products = $fullUserData->products->toArray();
        $this->assertSame('Product A', $products[0]->name);
        $this->assertSame('Product B', $products[1]->name);
    }

    // ==================== FIELD NAME CONVERSION TESTS ====================

    public function test_snake_case_record_fields_become_camel_case_in_data_dto(): void
    {
        $record = new TestUserRecord(
            id: 1,
            name: 'John Doe',
            email: $this->testEmail,
            emailVerifiedAt: $this->now,
            createdAt: $this->now
        );

        $data = TestUserData::from($record);

        $this->assertObjectHasProperty('emailVerifiedAt', $data);
        $this->assertObjectHasProperty('createdAt', $data);
    }

    // ==================== TYPE CONVERSION TESTS ====================

    public function test_record_int_id_converts_to_int_id_in_data_dto(): void
    {
        $record = new TestUserRecord(id: 123, name: 'John Doe', email: $this->testEmail);
        $data = TestUserData::from($record);

        $this->assertIsInt($record->id);
        $this->assertIsInt($data->id);
        $this->assertSame(123, $data->id);
    }

    public function test_record_null_id_becomes_null_in_data_dto(): void
    {
        $record = new TestUserRecord(id: null, name: 'John Doe', email: $this->testEmail);
        $data = TestUserData::from($record);

        $this->assertNull($record->id);
        $this->assertNull($data->id);
    }

    public function test_record_backed_enum_stays_as_enum_in_data_dto(): void
    {
        $record = new TestUserRecord(
            name: 'John Doe',
            email: $this->testEmail,
            status: TestUserStatus::ACTIVE,
            role: TestUserRole::ADMIN,
            grade: TestUserGrade::GOLD
        );

        $data = TestUserData::from($record);

        $this->assertInstanceOf(TestUserStatus::class, $data->status);
        $this->assertInstanceOf(TestUserRole::class, $data->role);
        $this->assertInstanceOf(TestUserGrade::class, $data->grade);
        $this->assertSame(TestUserStatus::ACTIVE, $data->status);
        $this->assertSame(TestUserRole::ADMIN, $data->role);
        $this->assertSame(TestUserGrade::GOLD, $data->grade);
    }

    public function test_record_value_object_stays_as_value_object_in_data_dto(): void
    {
        $record = new TestUserRecord(
            name: 'John Doe',
            email: $this->testEmail,
            createdAt: $this->now
        );

        $data = TestUserData::from($record);

        $this->assertInstanceOf(TestEmailAddress::class, $data->email);
        $this->assertInstanceOf(TestIso8601DateTime::class, $data->createdAt);
        $this->assertSame('john.doe@example.com', $data->email->getValue());
        $this->assertSame($this->now->getValue(), $data->createdAt->getValue());
    }

    public function test_record_collection_stays_as_collection_in_data_dto(): void
    {
        $tags = new StringTypedCollection;
        $tags->add('tag1', 'tag2');

        $record = new TestUserRecord(
            name: 'John Doe',
            email: $this->testEmail,
            tags: $tags
        );

        $data = TestUserData::from($record);

        $this->assertInstanceOf(StringTypedCollection::class, $data->tags);
        $this->assertCount(2, $data->tags);
        $this->assertSame(['tag1', 'tag2'], $data->tags->toArray());
    }

    // ==================== DEFAULT VALUE TESTS ====================

    public function test_record_default_values_are_preserved_in_data_dto(): void
    {
        $record = new TestUserRecord(
            name: 'John Doe',
            email: $this->testEmail
        );

        $data = TestUserData::from($record);

        $this->assertSame(TestUserStatus::ACTIVE, $data->status);
        $this->assertSame(TestUserRole::USER, $data->role);
        $this->assertSame(TestUserGrade::BRONZE, $data->grade);
    }

    public function test_explicit_values_override_defaults_during_transformation(): void
    {
        $record = new TestUserRecord(
            name: 'John Doe',
            email: $this->testEmail,
            status: TestUserStatus::SUSPENDED,
            role: TestUserRole::ADMIN,
            grade: TestUserGrade::PLATINUM
        );

        $data = TestUserData::from($record);

        $this->assertSame(TestUserStatus::SUSPENDED, $data->status);
        $this->assertSame(TestUserRole::ADMIN, $data->role);
        $this->assertSame(TestUserGrade::PLATINUM, $data->grade);
    }

    // ==================== NORMALIZATION AFTER TRANSFORMATION TESTS ====================

    public function test_data_dto_normalizes_correctly_after_transformation(): void
    {
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

        $data = TestUserData::from($record);
        $normalized = NormalizerChain::get()->normalize($data);

        $this->assertIsArray($normalized);
        $this->assertArrayHasKey('id', $normalized);
        $this->assertArrayHasKey('name', $normalized);
        $this->assertArrayHasKey('email', $normalized);
        $this->assertArrayHasKey('status', $normalized);
        $this->assertArrayHasKey('role', $normalized);
        $this->assertArrayHasKey('grade', $normalized);
        $this->assertArrayHasKey('tags', $normalized);
        $this->assertArrayHasKey('createdAt', $normalized);

        $this->assertEquals(1, $normalized['id']);
        $this->assertSame('John Doe', $normalized['name']);
        $this->assertSame('john.doe@example.com', $normalized['email']);
        $this->assertSame('active', $normalized['status']);
        $this->assertSame('admin', $normalized['role']);
        $this->assertEquals(3, $normalized['grade']);
    }

    public function test_complete_workflow_record_to_data_to_json_response(): void
    {
        $record = new TestUserRecord(
            id: 42,
            name: 'Jane Doe',
            email: TestEmailAddress::from('jane@example.com'),
            status: TestUserStatus::ACTIVE,
            role: TestUserRole::ADMIN,
            grade: TestUserGrade::PLATINUM,
            tags: $this->tags,
            createdAt: $this->now
        );

        $data = TestUserData::from($record);
        $jsonResponse = json_encode(NormalizerChain::get()->normalize($data));

        $this->assertIsString($jsonResponse);
        $this->assertJson($jsonResponse);

        $decoded = json_decode($jsonResponse, true);
        $this->assertEquals(42, $decoded['id']);
        $this->assertSame('Jane Doe', $decoded['name']);
        $this->assertSame('jane@example.com', $decoded['email']);
        $this->assertSame('active', $decoded['status']);
        $this->assertSame('admin', $decoded['role']);
        $this->assertEquals(4, $decoded['grade']);
        $this->assertIsArray($decoded['tags']);
    }

    // ==================== COLLECTION TRANSFORMATION WITH FILTERING TESTS ====================

    public function test_filtered_record_collection_transforms_correctly(): void
    {
        /** @var RecordCollection<TestUserRecord> $recordCollection */
        $recordCollection = new RecordCollection(TestUserRecord::class);
        $recordCollection->add(
            new TestUserRecord(id: 1, name: 'User Active 1', email: TestEmailAddress::from('active1@example.com'), status: TestUserStatus::ACTIVE),
            new TestUserRecord(id: 2, name: 'User Inactive', email: TestEmailAddress::from('inactive@example.com'), status: TestUserStatus::INACTIVE),
            new TestUserRecord(id: 3, name: 'User Active 2', email: TestEmailAddress::from('active2@example.com'), status: TestUserStatus::ACTIVE),
            new TestUserRecord(id: 4, name: 'User Suspended', email: TestEmailAddress::from('suspended@example.com'), status: TestUserStatus::SUSPENDED)
        );

        /** @var RecordCollection<TestUserRecord> $activeRecords */
        $activeRecords = $recordCollection->filter(fn(TestUserRecord $record) => $record->status === TestUserStatus::ACTIVE);

        /** @var DataCollection<TestUserData> $dataCollection */
        $dataCollection = new DataCollection(TestUserData::class);
        foreach ($activeRecords->all() as $record) {
            $dataCollection->add(TestUserData::from($record));
        }

        $this->assertCount(2, $dataCollection);

        /** @var array<int, TestUserData> $dataArray */
        $dataArray = $dataCollection->toArray();
        $this->assertEquals(1, $dataArray[0]->id);
        $this->assertSame('User Active 1', $dataArray[0]->name);
        $this->assertEquals(3, $dataArray[1]->id);
        $this->assertSame('User Active 2', $dataArray[1]->name);
    }

    /**
     * Test that mapped record collection transforms correctly using collect method.
     */
    public function test_mapped_record_collection_transforms_correctly(): void
    {
        /** @var RecordCollection<TestUserRecord> $recordCollection */
        $recordCollection = new RecordCollection(TestUserRecord::class);
        $recordCollection->add(
            new TestUserRecord(id: 1, name: 'User 1', email: TestEmailAddress::from('user1@example.com')),
            new TestUserRecord(id: 2, name: 'User 2', email: TestEmailAddress::from('user2@example.com')),
            new TestUserRecord(id: 3, name: 'User 3', email: TestEmailAddress::from('user3@example.com'))
        );

        /** @var array<int, TestUserData> $dataArray */
        $dataArray = TestUserData::collect($recordCollection);

        $this->assertCount(3, $dataArray);
        $this->assertInstanceOf(TestUserData::class, $dataArray[0]);
        $this->assertInstanceOf(TestUserData::class, $dataArray[1]);
        $this->assertInstanceOf(TestUserData::class, $dataArray[2]);
        $this->assertEquals(1, $dataArray[0]->id);
        $this->assertEquals(2, $dataArray[1]->id);
        $this->assertEquals(3, $dataArray[2]->id);
    }

    // ==================== ERROR HANDLING TESTS ====================

    public function test_transformation_fails_when_required_fields_are_missing(): void
    {
        $record = new TestUserRecord(
            id: 1,
            email: $this->testEmail
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Parameter "$name" for AndyDefer\DomainStructures\Tests\Fixtures\Data\TestUserData cannot be null');

        TestUserData::from($record);
    }

    // ==================== COMPLEX SCENARIO TESTS ====================

    public function test_transformation_with_featured_product_relationship(): void
    {
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

        $fullUserData = TestFullUserData::from($userRecord);

        $this->assertInstanceOf(TestFullUserData::class, $fullUserData);
        $this->assertNotNull($fullUserData->featuredProduct);
        $this->assertSame(99, $fullUserData->featuredProduct->id);
        $this->assertSame('Featured Product', $fullUserData->featuredProduct->name);
        $this->assertEquals(999.0, $fullUserData->featuredProduct->price);
        $this->assertTrue($fullUserData->featuredProduct->isFeatured);
    }

    public function test_transformation_preserves_immutability(): void
    {
        $record = new TestUserRecord(
            id: 1,
            name: 'John Doe',
            email: $this->testEmail
        );

        $data = TestUserData::from($record);

        $recordReflection = new \ReflectionClass($record);
        $dataReflection = new \ReflectionClass($data);

        foreach ($recordReflection->getProperties() as $property) {
            $this->assertTrue($property->isReadOnly(), 'Record property should be readonly');
        }

        foreach ($dataReflection->getProperties() as $property) {
            $this->assertTrue($property->isReadOnly(), 'Data DTO property should be readonly');
        }
    }

    public function test_batch_transformation_of_multiple_records(): void
    {
        $records = [
            new TestUserRecord(id: 1, name: 'User A', email: TestEmailAddress::from('a@example.com')),
            new TestUserRecord(id: 2, name: 'User B', email: TestEmailAddress::from('b@example.com')),
            new TestUserRecord(id: 3, name: 'User C', email: TestEmailAddress::from('c@example.com')),
            new TestUserRecord(id: 4, name: 'User D', email: TestEmailAddress::from('d@example.com')),
            new TestUserRecord(id: 5, name: 'User E', email: TestEmailAddress::from('e@example.com')),
        ];

        /** @var array<int, TestUserData> $dataArray */
        $dataArray = TestUserData::collect($records);

        $this->assertCount(5, $dataArray);
        $this->assertEquals(1, $dataArray[0]->id);
        $this->assertSame('User A', $dataArray[0]->name);
        $this->assertEquals(5, $dataArray[4]->id);
        $this->assertSame('User E', $dataArray[4]->name);
    }

    public function test_original_record_is_not_modified_during_transformation(): void
    {
        $originalRecord = new TestUserRecord(
            id: 1,
            name: 'John Doe',
            email: $this->testEmail,
            status: TestUserStatus::ACTIVE
        );

        $originalHash = spl_object_hash($originalRecord);
        $originalName = $originalRecord->name;
        $originalStatus = $originalRecord->status;

        $data = TestUserData::from($originalRecord);

        $this->assertSame($originalHash, spl_object_hash($originalRecord));
        $this->assertSame($originalName, $originalRecord->name);
        $this->assertSame($originalStatus, $originalRecord->status);
        $this->assertNotSame($originalRecord, $data);
    }
}
