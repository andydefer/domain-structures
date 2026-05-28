<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Unit\Collections\Core;

use AndyDefer\DomainStructures\Collections\Core\DataCollection;
use AndyDefer\DomainStructures\Collections\Core\TypedCollection;
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
use InvalidArgumentException;

final class DataCollectionTest extends TestCase
{
    private TestIso8601DateTime $now;
    private TestEmailAddress $testEmail;

    protected function setUp(): void
    {
        parent::setUp();

        $this->now = TestIso8601DateTime::from('2024-01-01T12:00:00+00:00');
        $this->testEmail = TestEmailAddress::from('test@example.com');
    }

    private function createTestUserData(int $id, string $name): TestUserData
    {
        return new TestUserData(
            id: $id,
            name: $name,
            email: $this->testEmail,
            status: TestUserStatus::ACTIVE,
            role: TestUserRole::USER,
            grade: TestUserGrade::BRONZE,
            emailVerifiedAt: null,
            tags: new StringTypedCollection,
            createdAt: $this->now
        );
    }

    private function createTestProductData(int $id, string $name, float $price): TestProductData
    {
        return new TestProductData(
            id: $id,
            name: $name,
            price: $price,
            isFeatured: false
        );
    }

    // ==================== CONSTRUCTOR TESTS ====================

    public function test_constructor_sets_abstract_data_as_allowed_type(): void
    {
        $collection = new DataCollection(TestUserData::class);

        $this->assertSame([TestUserData::class], $collection->getAllowedTypes());
    }

    public function test_collection_only_accepts_abstract_data_type(): void
    {
        $collection = new DataCollection(TestUserData::class);
        $userData = $this->createTestUserData(1, 'John Doe');

        $collection->add($userData);

        $this->assertCount(1, $collection);
        $this->assertSame($userData, $collection[0]);
    }

    public function test_collection_rejects_non_abstract_data_objects(): void
    {
        $collection = new DataCollection(TestUserData::class);
        $record = new TestUserRecord(name: 'John', email: $this->testEmail);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected type(s) ' . TestUserData::class);

        $collection->add($record);
    }

    public function test_collection_rejects_scalar_values(): void
    {
        $collection = new DataCollection(TestUserData::class);

        $this->expectException(InvalidArgumentException::class);

        $collection->add('not a data object');
    }

    // ==================== ADD METHOD TESTS ====================

    public function test_add_accepts_multiple_data_objects(): void
    {
        $collection = new DataCollection(TestUserData::class);
        $data1 = $this->createTestUserData(1, 'User 1');
        $data2 = $this->createTestUserData(2, 'User 2');
        $data3 = $this->createTestUserData(3, 'User 3');

        $collection->add($data1, $data2, $data3);

        $this->assertCount(3, $collection);
        $this->assertSame($data1, $collection[0]);
        $this->assertSame($data2, $collection[1]);
        $this->assertSame($data3, $collection[2]);
    }

    public function test_add_returns_self_for_chaining(): void
    {
        $collection = new DataCollection(TestUserData::class);
        $data = $this->createTestUserData(1, 'User 1');

        $result = $collection->add($data);

        $this->assertSame($collection, $result);
    }

    // ==================== ALL METHOD TESTS ====================

    public function test_all_returns_new_collection_with_same_items(): void
    {
        $original = new DataCollection(TestUserData::class);
        $original->add(
            $this->createTestUserData(1, 'User 1'),
            $this->createTestUserData(2, 'User 2')
        );

        $newCollection = $original->all();

        $this->assertNotSame($original, $newCollection);
        $this->assertInstanceOf(DataCollection::class, $newCollection);
        $this->assertCount(2, $newCollection);
        $this->assertEquals($original[0], $newCollection[0]);
        $this->assertEquals($original[1], $newCollection[1]);
    }

    // ==================== FILTER METHOD TESTS ====================

    public function test_filter_returns_data_collection_with_filtered_items(): void
    {
        /** @var DataCollection<TestUserData> $collection */
        $collection = new DataCollection(TestUserData::class);
        $collection->add(
            $this->createTestUserData(1, 'Alice'),
            $this->createTestUserData(2, 'Bob'),
            $this->createTestUserData(3, 'Alice')
        );


        $filtered = $collection->filter(fn(TestUserData $item) => $item->name === 'Alice');

        $this->assertInstanceOf(DataCollection::class, $filtered);
        $this->assertCount(2, $filtered);
        $this->assertSame('Alice', $filtered[0]->name);
        $this->assertSame('Alice', $filtered[1]->name);
    }

    // ==================== MAP METHOD TESTS ====================

    public function test_map_can_transform_data_objects(): void
    {
        $collection = new DataCollection(TestUserData::class);
        $collection->add(
            $this->createTestUserData(1, 'John'),
            $this->createTestUserData(2, 'Jane')
        );

        $names = $collection->map(fn($item) => $item->name);

        $this->assertInstanceOf(TypedCollection::class, $names);
        $this->assertNotInstanceOf(DataCollection::class, $names);
        $this->assertSame(['John', 'Jane'], $names->toArray());
    }

    public function test_map_can_produce_new_data_collection(): void
    {
        $collection = new DataCollection(TestProductData::class);
        $collection->add(
            $this->createTestProductData(1, 'Product A', 100),
            $this->createTestProductData(2, 'Product B', 200)
        );

        $prices = $collection->map(fn($item) => $item->price * 1.2);

        $this->assertInstanceOf(TypedCollection::class, $prices);
        $this->assertNotInstanceOf(DataCollection::class, $prices);
        $this->assertSame([120.0, 240.0], $prices->toArray());
    }

    // ==================== NORMALIZATION TESTS ====================

    public function test_collection_normalizes_to_array(): void
    {
        $collection = new DataCollection(TestUserData::class);
        $collection->add(
            $this->createTestUserData(1, 'John'),
            $this->createTestUserData(2, 'Jane')
        );

        $normalized = $collection->normalize();

        $this->assertIsArray($normalized);
        $this->assertCount(2, $normalized);
        $this->assertEquals(1, $normalized[0]['id']);
        $this->assertSame('John', $normalized[0]['name']);
        $this->assertEquals(2, $normalized[1]['id']);
        $this->assertSame('Jane', $normalized[1]['name']);
    }

    public function test_collection_normalizes_to_json(): void
    {
        $collection = new DataCollection(TestUserData::class);
        $collection->add($this->createTestUserData(1, 'John'));

        $normalized = $collection->normalize();
        $json = json_encode($normalized);

        $this->assertIsString($json);
        $this->assertJson($json);

        $decoded = json_decode($json, true);
        $this->assertCount(1, $decoded);
        $this->assertEquals(1, $decoded[0]['id']);
        $this->assertSame('John', $decoded[0]['name']);
    }

    // ==================== COLLECTION OPERATIONS TESTS ====================

    public function test_collection_supports_count(): void
    {
        $collection = new DataCollection(TestUserData::class);
        $collection->add(
            $this->createTestUserData(1, 'User 1'),
            $this->createTestUserData(2, 'User 2')
        );

        $this->assertCount(2, $collection);
        $this->assertSame(2, $collection->count());
    }

    public function test_collection_supports_is_empty(): void
    {
        $emptyCollection = new DataCollection(TestUserData::class);
        $nonEmptyCollection = new DataCollection(TestUserData::class);
        $nonEmptyCollection->add($this->createTestUserData(1, 'User'));

        $this->assertTrue($emptyCollection->isEmpty());
        $this->assertFalse($nonEmptyCollection->isEmpty());
        $this->assertFalse($emptyCollection->isNotEmpty());
        $this->assertTrue($nonEmptyCollection->isNotEmpty());
    }

    public function test_collection_supports_contains(): void
    {
        $collection = new DataCollection(TestUserData::class);
        $user1 = $this->createTestUserData(1, 'User 1');
        $user2 = $this->createTestUserData(2, 'User 2');
        $collection->add($user1, $user2);

        $this->assertTrue($collection->contains($user1));
        $this->assertTrue($collection->contains($user2));

        $user3 = $this->createTestUserData(3, 'User 3');
        $this->assertFalse($collection->contains($user3));
    }

    public function test_collection_supports_each(): void
    {
        $collection = new DataCollection(TestUserData::class);
        $collection->add(
            $this->createTestUserData(1, 'User 1'),
            $this->createTestUserData(2, 'User 2')
        );
        $names = [];

        $collection->each(function ($item) use (&$names) {
            $names[] = $item->name;
        });

        $this->assertSame(['User 1', 'User 2'], $names);
    }

    public function test_collection_supports_reduce(): void
    {
        $collection = new DataCollection(TestProductData::class);
        $collection->add(
            $this->createTestProductData(1, 'Product A', 100),
            $this->createTestProductData(2, 'Product B', 200),
            $this->createTestProductData(3, 'Product C', 300)
        );

        $total = $collection->reduce(fn($carry, $item) => $carry + $item->price, 0);

        $this->assertSame(600.0, $total);
    }

    public function test_collection_supports_find(): void
    {
        $collection = new DataCollection(TestUserData::class);
        $collection->add(
            $this->createTestUserData(1, 'Alice'),
            $this->createTestUserData(2, 'Bob'),
            $this->createTestUserData(3, 'Charlie')
        );

        $found = $collection->find(fn($item) => $item->name === 'Bob');

        $this->assertNotNull($found);
        $this->assertEquals(2, $found->id);
        $this->assertSame('Bob', $found->name);
    }

    public function test_collection_supports_every(): void
    {
        $collection = new DataCollection(TestUserData::class);
        $collection->add(
            $this->createTestUserData(1, 'User 1'),
            $this->createTestUserData(2, 'User 2')
        );

        $this->assertTrue($collection->every(fn($item) => strlen($item->name) > 0));
        $this->assertFalse($collection->every(fn($item) => $item->name === 'User 1'));
    }

    public function test_collection_supports_some(): void
    {
        $collection = new DataCollection(TestUserData::class);
        $collection->add(
            $this->createTestUserData(1, 'Alice'),
            $this->createTestUserData(2, 'Bob'),
            $this->createTestUserData(3, 'Charlie')
        );

        $this->assertTrue($collection->some(fn($item) => $item->name === 'Bob'));
        $this->assertFalse($collection->some(fn($item) => $item->name === 'David'));
    }

    public function test_collection_supports_reverse(): void
    {
        $collection = new DataCollection(TestUserData::class);
        $collection->add(
            $this->createTestUserData(1, 'First'),
            $this->createTestUserData(2, 'Second'),
            $this->createTestUserData(3, 'Third')
        );

        $reversed = $collection->reverse();

        $this->assertInstanceOf(DataCollection::class, $reversed);
        $this->assertSame('Third', $reversed[0]->name);
        $this->assertSame('Second', $reversed[1]->name);
        $this->assertSame('First', $reversed[2]->name);
    }

    public function test_collection_supports_merge(): void
    {
        $collection1 = new DataCollection(TestUserData::class);
        $collection2 = new DataCollection(TestUserData::class);
        $collection1->add($this->createTestUserData(1, 'User 1'));
        $collection2->add($this->createTestUserData(2, 'User 2'));

        $merged = $collection1->merge($collection2);

        $this->assertInstanceOf(DataCollection::class, $merged);
        $this->assertCount(2, $merged);
        $this->assertSame('User 1', $merged[0]->name);
        $this->assertSame('User 2', $merged[1]->name);
    }

    // ==================== ARRAY ACCESS TESTS ====================

    public function test_collection_supports_array_access(): void
    {
        $collection = new DataCollection(TestUserData::class);
        $collection->add($this->createTestUserData(1, 'User 1'));

        $this->assertTrue(isset($collection[0]));
        $this->assertInstanceOf(TestUserData::class, $collection[0]);
        $this->assertSame('User 1', $collection[0]->name);
    }

    // ==================== JSON SERIALIZATION TESTS ====================

    public function test_collection_can_be_json_serialized(): void
    {
        $collection = new DataCollection(TestUserData::class);
        $collection->add($this->createTestUserData(1, 'User 1'));

        $json = json_encode($collection);

        $this->assertIsString($json);
        $this->assertJson($json);

        $decoded = json_decode($json, true);
        $this->assertCount(1, $decoded);
        $this->assertEquals(1, $decoded[0]['id']);
    }

    // ==================== EDGE CASES TESTS ====================

    public function test_empty_collection_normalizes_to_empty_array(): void
    {
        $emptyCollection = new DataCollection(TestUserData::class);

        $normalized = $emptyCollection->normalize();

        $this->assertIsArray($normalized);
        $this->assertEmpty($normalized);
    }

    public function test_collection_can_handle_many_items(): void
    {
        $collection = new DataCollection(TestUserData::class);

        for ($i = 1; $i <= 100; $i++) {
            $collection->add($this->createTestUserData($i, "User {$i}"));
        }

        $this->assertCount(100, $collection);
        $this->assertSame('User 50', $collection[49]->name);
    }

    public function test_collection_preserves_item_order(): void
    {
        $collection = new DataCollection(TestUserData::class);

        $collection->add(
            $this->createTestUserData(1, 'First'),
            $this->createTestUserData(2, 'Second'),
            $this->createTestUserData(3, 'Third')
        );

        $this->assertSame('First', $collection[0]->name);
        $this->assertSame('Second', $collection[1]->name);
        $this->assertSame('Third', $collection[2]->name);
    }
}
