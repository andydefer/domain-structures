<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Unit\Interfaces;

use AndyDefer\DomainStructures\Collections\Core\DataCollection;
use AndyDefer\DomainStructures\Collections\Core\RecordCollection;
use AndyDefer\DomainStructures\Collections\Core\TypedCollection;
use AndyDefer\DomainStructures\Collections\Utility\IntTypedCollection;
use AndyDefer\DomainStructures\Tests\Fixtures\Collections\TestEmailCollection;
use AndyDefer\DomainStructures\Tests\Fixtures\Data\TestProductData;
use AndyDefer\DomainStructures\Tests\Fixtures\Data\TestUserData;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestUserGrade;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestUserRole;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestUserStatus;
use AndyDefer\DomainStructures\Tests\Fixtures\Records\TestProductRecord;
use AndyDefer\DomainStructures\Tests\Fixtures\Records\TestUserRecord;
use AndyDefer\DomainStructures\Tests\Fixtures\ValueObjects\TestEmailAddress;
use AndyDefer\DomainStructures\Tests\Fixtures\ValueObjects\TestIso8601DateTime;
use AndyDefer\DomainStructures\Tests\TestCase;
use AndyDefer\DomainStructures\Utils\DataObject;

final class TransformableTest extends TestCase
{
    private array $userArray;
    private array $usersArray;
    private array $productsArray;
    private array $emailsArray;
    private array $scalarsArray;
    private string $usersJson;
    private string $userJson;
    private string $productsJson;
    private string $emailsJson;
    private string $scalarsJson;

    protected function setUp(): void
    {
        parent::setUp();

        // Données pour les tests
        $this->userArray = [
            'id' => 1,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'status' => 'active',
            'role' => 'admin',
            'grade' => 4,
            'created_at' => '2024-01-01T12:00:00+00:00',
        ];

        $this->usersArray = [
            ['id' => 1, 'name' => 'Alice', 'email' => 'alice@example.com', 'status' => 'active', 'role' => 'admin', 'grade' => 4],
            ['id' => 2, 'name' => 'Bob', 'email' => 'bob@example.com', 'status' => 'active', 'role' => 'user', 'grade' => 2],
            ['id' => 3, 'name' => 'Alice', 'email' => 'alice2@example.com', 'status' => 'inactive', 'role' => 'user', 'grade' => 1],
            ['id' => 4, 'name' => 'Charlie', 'email' => 'charlie@example.com', 'status' => 'active', 'role' => 'admin', 'grade' => 4],
            ['id' => 5, 'name' => 'Alice', 'email' => 'alice3@example.com', 'status' => 'active', 'role' => 'user', 'grade' => 3],
        ];

        $this->productsArray = [
            ['id' => 1, 'name' => 'Laptop', 'price' => 999.99, 'isFeatured' => true],
            ['id' => 2, 'name' => 'Mouse', 'price' => 29.99, 'isFeatured' => false],
            ['id' => 3, 'name' => 'Keyboard', 'price' => 89.99, 'isFeatured' => true],
            ['id' => 4, 'name' => 'Monitor', 'price' => 299.99, 'isFeatured' => false],
            ['id' => 5, 'name' => 'Desk', 'price' => 499.99, 'isFeatured' => true],
        ];

        $this->emailsArray = [
            'user1@example.com',
            'user2@example.com',
            'user3@example.com',
            'user4@example.com',
            'user5@example.com',
        ];

        $this->scalarsArray = [1, 2, 3, 4, 5];

        // Encodage JSON
        $this->usersJson = json_encode($this->usersArray);
        $this->userJson = json_encode($this->userArray);
        $this->productsJson = json_encode($this->productsArray);
        $this->emailsJson = json_encode($this->emailsArray);
        $this->scalarsJson = json_encode($this->scalarsArray);
    }

    // ==================== RECORD TESTS ====================

    public function test_record_from(): void
    {
        $record = TestUserRecord::from($this->userArray);

        $this->assertInstanceOf(TestUserRecord::class, $record);
        $this->assertSame(1, $record->id);
        $this->assertSame('John Doe', $record->name);
        $this->assertSame('john@example.com', $record->email->getValue());
        $this->assertSame(TestUserStatus::ACTIVE, $record->status);
        $this->assertSame(TestUserRole::ADMIN, $record->role);
        $this->assertSame(TestUserGrade::PLATINUM, $record->grade);
    }

    public function test_record_fromJson(): void
    {
        $record = TestUserRecord::fromJson($this->userJson);

        $this->assertInstanceOf(TestUserRecord::class, $record);
        $this->assertSame(1, $record->id);
        $this->assertSame('John Doe', $record->name);
        $this->assertSame('john@example.com', $record->email->getValue());
        $this->assertSame(TestUserStatus::ACTIVE, $record->status);
        $this->assertSame(TestUserRole::ADMIN, $record->role);
        $this->assertSame(TestUserGrade::PLATINUM, $record->grade);
    }

    public function test_record_collect_default(): void
    {
        $collection = TestUserRecord::collect($this->usersArray);

        $this->assertInstanceOf(TypedCollection::class, $collection);
        $this->assertCount(5, $collection);
        $this->assertContainsOnlyInstancesOf(TestUserRecord::class, $collection);
    }

    public function test_record_collect_into_record_collection(): void
    {
        $collection = TestUserRecord::collect($this->usersArray, RecordCollection::class);

        $this->assertInstanceOf(RecordCollection::class, $collection);
        $this->assertCount(5, $collection);
        $this->assertContainsOnlyInstancesOf(TestUserRecord::class, $collection);
    }

    public function test_record_collect_chaining_map_and_filter(): void
    {
        $adminEmails = TestUserRecord::collect($this->usersArray)
            ->filter(fn($user) => $user->role === TestUserRole::ADMIN)
            ->map(fn($user) => $user->email->getValue())
            ->sort()
            ->toArray();

        $this->assertSame(['alice@example.com', 'charlie@example.com'], $adminEmails);
    }

    public function test_record_collect_chaining_with_multiple_operations(): void
    {
        $result = TestUserRecord::collect($this->usersArray)
            ->filter(fn($user) => $user->status === TestUserStatus::ACTIVE)
            ->filter(fn($user) => $user->grade->value >= 3)  // ✅ Correction: grade est un enum
            ->map(fn($user) => $user->name)
            ->sort()
            ->reverse()
            ->toArray();

        // Alice (grade 4), Charlie (grade 4), Alice (grade 3)
        $this->assertSame(['Charlie', 'Alice', 'Alice'], $result);
    }

    // ==================== DATA DTO TESTS ====================

    public function test_data_from(): void
    {
        $data = TestUserData::from($this->userArray);

        $this->assertInstanceOf(TestUserData::class, $data);
        $this->assertSame(1, $data->id);
        $this->assertSame('John Doe', $data->name);
        $this->assertSame('john@example.com', $data->email->getValue());
        $this->assertSame(TestUserStatus::ACTIVE, $data->status);
        $this->assertSame(TestUserRole::ADMIN, $data->role);
        $this->assertSame(TestUserGrade::PLATINUM, $data->grade);
    }

    public function test_data_fromJson(): void
    {
        $data = TestUserData::fromJson($this->userJson);

        $this->assertInstanceOf(TestUserData::class, $data);
        $this->assertSame(1, $data->id);
        $this->assertSame('John Doe', $data->name);
        $this->assertSame('john@example.com', $data->email->getValue());
    }

    public function test_data_collect_default(): void
    {
        $collection = TestUserData::collect($this->usersArray);

        $this->assertInstanceOf(TypedCollection::class, $collection);
        $this->assertCount(5, $collection);
        $this->assertContainsOnlyInstancesOf(TestUserData::class, $collection);
    }

    public function test_data_collect_into_data_collection(): void
    {
        $collection = TestUserData::collect($this->usersArray, DataCollection::class);

        $this->assertInstanceOf(DataCollection::class, $collection);
        $this->assertCount(5, $collection);
        $this->assertContainsOnlyInstancesOf(TestUserData::class, $collection);
    }

    public function test_data_collect_chaining(): void
    {
        $activeUsers = TestUserData::collect($this->usersArray)
            ->filter(fn($user) => $user->status === TestUserStatus::ACTIVE)
            ->count();

        $this->assertSame(4, $activeUsers);
    }

    // ==================== VALUE OBJECT TESTS ====================

    public function test_vo_from(): void
    {
        $email = TestEmailAddress::from('test@example.com');

        $this->assertInstanceOf(TestEmailAddress::class, $email);
        $this->assertSame('test@example.com', $email->getValue());
    }

    public function test_vo_fromJson(): void
    {
        $json = json_encode('test@example.com');
        $email = TestEmailAddress::fromJson($json);

        $this->assertInstanceOf(TestEmailAddress::class, $email);
        $this->assertSame('test@example.com', $email->getValue());
    }

    public function test_vo_collect_default(): void
    {
        $collection = TestEmailAddress::collect($this->emailsArray);

        $this->assertInstanceOf(TypedCollection::class, $collection);
        $this->assertCount(5, $collection);
        $this->assertContainsOnlyInstancesOf(TestEmailAddress::class, $collection);
    }

    public function test_vo_collect_from_json(): void
    {
        $collection = TestEmailAddress::collect(json_decode($this->emailsJson, true));

        $this->assertInstanceOf(TypedCollection::class, $collection);
        $this->assertCount(5, $collection);
        $this->assertContainsOnlyInstancesOf(TestEmailAddress::class, $collection);
    }

    public function test_vo_collect_with_specialized_collection(): void
    {
        $emailsArray = [
            'user1@example.com',
            'user2@gmail.com',
            'user3@example.com',
            'user4@yahoo.com',
            'user5@example.com',
            'user6@gmail.com',
        ];

        $collection = TestEmailAddress::collect($emailsArray, TestEmailCollection::class);

        $this->assertInstanceOf(TestEmailCollection::class, $collection);
        $this->assertCount(6, $collection);

        // Tous les emails sont uniques (pas de doublons)
        $unique = $collection->unique();
        $this->assertCount(6, $unique);  // ✅ Correction: 6 emails uniques

        $exampleEmails = $collection->fromDomain('example.com');
        $this->assertCount(3, $exampleEmails);
        $this->assertSame('user1@example.com', $exampleEmails[0]->getValue());

        $domains = $collection->getDomains();
        $this->assertSame(['example.com', 'gmail.com', 'example.com', 'yahoo.com', 'example.com', 'gmail.com'], $domains);

        $uniqueDomains = $collection->getUniqueDomains();
        $this->assertSame(['example.com', 'gmail.com', 'yahoo.com'], $uniqueDomains);
    }

    // ==================== TYPED COLLECTION TESTS ====================

    public function test_typed_collection_from_with_scalars(): void
    {
        $collection = new TypedCollection('int');
        $result = $collection::from($this->scalarsArray);

        $this->assertInstanceOf(TypedCollection::class, $result);
        $this->assertCount(5, $result);
        $this->assertSame([1, 2, 3, 4, 5], $result->toArray());
    }

    public function test_typed_collection_from_with_objects(): void
    {
        $collection = new TypedCollection(TestUserData::class);
        $result = $collection::from($this->usersArray);

        $this->assertInstanceOf(TypedCollection::class, $result);
        $this->assertCount(5, $result);
        $this->assertContainsOnlyInstancesOf(TestUserData::class, $result);
    }

    public function test_typed_collection_fromJson_with_scalars(): void
    {
        $collection = new TypedCollection('int');
        $result = $collection::fromJson($this->scalarsJson);

        $this->assertInstanceOf(TypedCollection::class, $result);
        $this->assertCount(5, $result);
        $this->assertSame([1, 2, 3, 4, 5], $result->toArray());
    }

    public function test_typed_collection_fromJson_with_objects(): void
    {
        $collection = new TypedCollection(TestUserData::class);
        $result = $collection::fromJson($this->usersJson);

        $this->assertInstanceOf(TypedCollection::class, $result);
        $this->assertCount(5, $result);
        $this->assertContainsOnlyInstancesOf(TestUserData::class, $result);
    }

    public function test_typed_collection_collect(): void
    {
        $sources = [
            [1, 2, 3],
            [4, 5, 6],
            [7, 8, 9],
        ];

        $collection = new TypedCollection(IntTypedCollection::class);
        $result = $collection::collect($sources);

        $this->assertInstanceOf(TypedCollection::class, $result);
        $this->assertCount(3, $result);
        // ✅ Correction: Les éléments sont des IntTypedCollection, pas TypedCollection
        $this->assertContainsOnlyInstancesOf(IntTypedCollection::class, $result);

        $this->assertSame([1, 2, 3], $result[0]->toArray());
        $this->assertSame([4, 5, 6], $result[1]->toArray());
        $this->assertSame([7, 8, 9], $result[2]->toArray());
    }

    public function test_typed_collection_with_product_data(): void
    {
        $collection = new TypedCollection(TestProductData::class);
        $result = $collection::from($this->productsArray);

        $this->assertInstanceOf(TypedCollection::class, $result);
        $this->assertCount(5, $result);
        $this->assertContainsOnlyInstancesOf(TestProductData::class, $result);

        $featured = $result
            ->filter(fn($product) => $product->isFeatured === true)
            ->map(fn($product) => $product->name)
            ->toArray();

        $this->assertSame(['Laptop', 'Keyboard', 'Desk'], $featured);
    }

    // ==================== DATA OBJECT TESTS ====================

    public function test_data_object_from(): void
    {
        $dataObject = DataObject::from($this->userArray);

        $this->assertInstanceOf(DataObject::class, $dataObject);
        $this->assertSame(1, $dataObject->get('id'));
        $this->assertSame('John Doe', $dataObject->get('name'));
        $this->assertSame('john@example.com', $dataObject->get('email'));
    }

    public function test_data_object_fromJson(): void
    {
        $dataObject = DataObject::fromJson($this->userJson);

        $this->assertInstanceOf(DataObject::class, $dataObject);
        $this->assertSame(1, $dataObject->get('id'));
        $this->assertSame('John Doe', $dataObject->get('name'));
    }

    public function test_data_object_collect(): void
    {
        $collection = DataObject::collect($this->usersArray);

        $this->assertInstanceOf(TypedCollection::class, $collection);
        $this->assertCount(5, $collection);
        $this->assertContainsOnlyInstancesOf(DataObject::class, $collection);
    }

    public function test_data_object_collect_chaining(): void
    {
        $names = DataObject::collect($this->usersArray)
            ->filter(fn($item) => $item->get('status') === 'active')
            ->map(fn($item) => $item->get('name'))
            ->sort()
            ->toArray();

        // Alice (active), Alice (inactive est exclu), Bob (active), Charlie (active), Alice (active)
        // Les actifs: Alice (id1), Bob, Charlie, Alice (id5) → 4 noms
        $this->assertSame(['Alice', 'Alice', 'Bob', 'Charlie'], $names);
    }

    // ==================== CROSS-TYPE TESTS ====================

    public function test_record_to_data_consistency(): void
    {
        $record = TestUserRecord::from($this->userArray);
        $data = TestUserData::from($record);

        $this->assertSame($record->id, $data->id);
        $this->assertSame($record->name, $data->name);
        $this->assertSame($record->email->getValue(), $data->email->getValue());
        $this->assertSame($record->status, $data->status);
        $this->assertSame($record->role, $data->role);
        $this->assertSame($record->grade, $data->grade);
    }

    public function test_from_and_fromJson_produce_same_result(): void
    {
        $fromArray = TestUserRecord::from($this->userArray);
        $fromJson = TestUserRecord::fromJson($this->userJson);

        $this->assertSame($fromArray->id, $fromJson->id);
        $this->assertSame($fromArray->name, $fromJson->name);
        $this->assertSame($fromArray->email->getValue(), $fromJson->email->getValue());
    }

    public function test_collect_with_empty_sources(): void
    {
        $recordCollection = TestUserRecord::collect([]);
        $dataCollection = TestUserData::collect([]);
        $voCollection = TestEmailAddress::collect([]);
        $dataObjectCollection = DataObject::collect([]);

        $this->assertCount(0, $recordCollection);
        $this->assertCount(0, $dataCollection);
        $this->assertCount(0, $voCollection);
        $this->assertCount(0, $dataObjectCollection);
        $this->assertTrue($recordCollection->isEmpty());
        $this->assertTrue($dataCollection->isEmpty());
        $this->assertTrue($voCollection->isEmpty());
        $this->assertTrue($dataObjectCollection->isEmpty());
    }

    public function test_complex_workflow_across_types(): void
    {
        $records = TestUserRecord::collect($this->usersArray)
            ->filter(fn($record) => $record->status === TestUserStatus::ACTIVE)
            ->filter(fn($record) => $record->grade->value >= 3);  // ✅ Correction: grade est un enum

        $dataCollection = new DataCollection(TestUserData::class);
        foreach ($records as $record) {
            $dataCollection->add(TestUserData::from($record));
        }

        $result = $dataCollection
            ->map(fn(TestUserData $data) => $data->name)
            ->sort()
            ->toArray();

        // Alice (grade 4), Charlie (grade 4), Alice (grade 3)
        $this->assertSame(['Alice', 'Alice', 'Charlie'], $result);
    }

    public function test_collect_with_specific_collection_class_chaining(): void
    {
        $collection = TestUserRecord::collect($this->usersArray, RecordCollection::class);

        $activeAdmins = $collection
            ->filter(fn(TestUserRecord $record) => $record->status === TestUserStatus::ACTIVE)
            ->filter(fn(TestUserRecord $record) => $record->role === TestUserRole::ADMIN)
            ->map(fn(TestUserRecord $record) => $record->name)
            ->toArray();

        $this->assertSame(['Alice', 'Charlie'], $activeAdmins);
    }

    public function test_nested_collect_with_map(): void
    {
        $emailCollections = TestEmailAddress::collect($this->emailsArray)
            ->map(fn($email) => TestEmailAddress::collect([$email->getValue(), $email->getValue()]))
            ->toArray();

        $this->assertCount(5, $emailCollections);
        $this->assertInstanceOf(TypedCollection::class, $emailCollections[0]);
        $this->assertCount(2, $emailCollections[0]);
    }

    public function test_chaining_collect_then_collect_again(): void
    {
        $firstCollect = TestUserRecord::collect($this->usersArray);
        $secondCollect = TestUserRecord::collect($firstCollect->toArray());

        $this->assertCount(5, $secondCollect);
        $this->assertEquals($firstCollect->toArray(), $secondCollect->toArray());
    }
}
