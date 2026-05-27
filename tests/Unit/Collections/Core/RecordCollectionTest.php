<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Unit\Collections\Core;

use AndyDefer\DomainStructures\Abstracts\AbstractRecord;
use AndyDefer\DomainStructures\Collections\Core\RecordCollection;
use AndyDefer\DomainStructures\Collections\Core\TypedCollection;
use AndyDefer\DomainStructures\Enums\NormalizeMode;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestUserGrade;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestUserRole;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestUserStatus;
use AndyDefer\DomainStructures\Tests\Fixtures\Records\TestProductRecord;
use AndyDefer\DomainStructures\Tests\Fixtures\Records\TestUserRecord;
use AndyDefer\DomainStructures\Tests\Fixtures\ValueObjects\TestEmailAddress;
use AndyDefer\DomainStructures\Tests\Fixtures\ValueObjects\TestIso8601DateTime;
use AndyDefer\DomainStructures\Tests\TestCase;
use InvalidArgumentException;

/**
 * Unit tests for RecordCollection class.
 *
 * This test suite validates the RecordCollection which is a specialized
 * collection that can ONLY contain AbstractRecord objects.
 *
 * The tests focus on:
 * - Constructor ensures only AbstractRecord type
 * - Type validation when adding items
 * - Normalization for database operations
 * - Collection operations (map, filter, etc.)
 *
 * The tests follow the AAA pattern (Arrange, Act, Assert).
 */
final class RecordCollectionTest extends TestCase
{
    private TestIso8601DateTime $now;

    private TestEmailAddress $testEmail;

    protected function setUp(): void
    {
        parent::setUp();

        $this->now = TestIso8601DateTime::now();
        $this->testEmail = TestEmailAddress::from('test@example.com');
    }

    private function createTestUserRecord(int $id, string $name): TestUserRecord
    {
        return new TestUserRecord(
            id: $id,
            name: $name,
            email: $this->testEmail,
            status: TestUserStatus::ACTIVE,
            role: TestUserRole::USER,
            grade: TestUserGrade::BRONZE,
            createdAt: $this->now
        );
    }

    private function createTestProductRecord(int $id, string $name, float $price): TestProductRecord
    {
        return new TestProductRecord(
            id: $id,
            name: $name,
            price: $price
        );
    }

    // ==================== CONSTRUCTOR TESTS ====================

    /**
     * Test that RecordCollection constructor sets AbstractRecord as allowed type.
     */
    public function test_constructor_sets_abstract_record_as_allowed_type(): void
    {
        // Arrange & Act
        $collection = new RecordCollection;

        // Assert
        $this->assertSame([AbstractRecord::class], $collection->getAllowedTypes());
    }

    /**
     * Test that RecordCollection only accepts AbstractRecord type.
     */
    public function test_collection_only_accepts_abstract_record_type(): void
    {
        // Arrange
        $collection = new RecordCollection;
        $userRecord = $this->createTestUserRecord(1, 'John Doe');

        // Act
        $collection->add($userRecord);

        // Assert
        $this->assertCount(1, $collection);
        $this->assertSame($userRecord, $collection->toArray()[0]);
    }

    /**
     * Test that RecordCollection rejects non-AbstractRecord objects.
     */
    public function test_collection_rejects_non_abstract_record_objects(): void
    {
        // Arrange
        $collection = new RecordCollection;

        // Act & Assert
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected type(s) ' . AbstractRecord::class);

        $collection->add('not a record');
    }

    /**
     * Test that RecordCollection rejects scalar values.
     */
    public function test_collection_rejects_scalar_values(): void
    {
        // Arrange
        $collection = new RecordCollection;

        // Act & Assert
        $this->expectException(InvalidArgumentException::class);

        $collection->add(123);
    }

    // ==================== ADD METHOD TESTS ====================

    /**
     * Test that add method accepts multiple Record objects.
     */
    public function test_add_accepts_multiple_record_objects(): void
    {
        // Arrange
        $collection = new RecordCollection;
        $record1 = $this->createTestUserRecord(1, 'User 1');
        $record2 = $this->createTestUserRecord(2, 'User 2');
        $record3 = $this->createTestUserRecord(3, 'User 3');

        // Act
        $collection->add($record1, $record2, $record3);

        // Assert
        $this->assertCount(3, $collection);
    }

    /**
     * Test that add returns self for chaining.
     */
    public function test_add_returns_self_for_chaining(): void
    {
        // Arrange
        $collection = new RecordCollection;
        $record = $this->createTestUserRecord(1, 'User 1');

        // Act
        $result = $collection->add($record);

        // Assert
        $this->assertSame($collection, $result);
    }

    // ==================== ALL METHOD TESTS ====================

    /**
     * Test that all returns new RecordCollection with same items.
     */
    public function test_all_returns_new_collection_with_same_items(): void
    {
        // Arrange
        $original = new RecordCollection;
        $original->add(
            $this->createTestUserRecord(1, 'User 1'),
            $this->createTestUserRecord(2, 'User 2')
        );

        // Act
        $newCollection = $original->all();

        // Assert
        $this->assertNotSame($original, $newCollection);
        $this->assertInstanceOf(RecordCollection::class, $newCollection);
        $this->assertCount(2, $newCollection);
        $this->assertEquals($original->toArray(), $newCollection->toArray());
    }

    // ==================== FILTER METHOD TESTS ====================

    /**
     * Test that filter returns RecordCollection with filtered items.
     */
    public function test_filter_returns_record_collection_with_filtered_items(): void
    {
        // Arrange
        $collection = new RecordCollection;
        $collection->add(
            $this->createTestUserRecord(1, 'Alice'),
            $this->createTestUserRecord(2, 'Bob'),
            $this->createTestUserRecord(3, 'Alice')
        );

        // Act
        $filtered = $collection->filter(fn($item) => $item->name === 'Alice');

        // Assert
        $this->assertInstanceOf(RecordCollection::class, $filtered);
        $this->assertCount(2, $filtered);
        $this->assertSame('Alice', $filtered->toArray()[0]->name);
        $this->assertSame('Alice', $filtered->toArray()[1]->name);
    }

    // ==================== MAP METHOD TESTS ====================

    /**
     * Test that map can transform Record objects.
     */
    public function test_map_can_transform_record_objects(): void
    {
        // Arrange
        $collection = new RecordCollection;
        $collection->add(
            $this->createTestUserRecord(1, 'John'),
            $this->createTestUserRecord(2, 'Jane')
        );

        // Act
        /** @var TypedCollection<TestUserRecord> $collection */
        $names = $collection->map(fn($item) => $item->name);

        // Assert - Map returns a TypedCollection
        $this->assertInstanceOf(TypedCollection::class, $names);
        $this->assertSame(['John', 'Jane'], $names->toArray());
    }

    /**
     * Test that map can produce new RecordCollection.
     */
    public function test_map_can_produce_new_record_collection(): void
    {
        // Arrange
        /** @var TypedCollection<TestProductRecord> $collection */
        $collection = new RecordCollection;
        $collection->add(
            $this->createTestProductRecord(1, 'Product A', 100),
            $this->createTestProductRecord(2, 'Product B', 200)
        );

        // Act
        $prices = $collection->map(fn($item) => $item->price * 1.2);

        // Assert
        $this->assertSame([120.0, 240.0], $prices->toArray());
    }

    // ==================== NORMALIZATION TESTS ====================

    /**
     * Test that RecordCollection normalizes to array (snake_case).
     */
    public function test_collection_normalizes_to_array_with_snake_case(): void
    {
        // Arrange
        $collection = new RecordCollection;
        $collection->add(
            $this->createTestUserRecord(1, 'John'),
            $this->createTestUserRecord(2, 'Jane')
        );

        // Act
        $normalized = $collection->normalize();

        // Assert
        $this->assertIsArray($normalized);
        $this->assertCount(2, $normalized);
        $this->assertSame(1, $normalized[0]['id']);
        $this->assertSame('John', $normalized[0]['name']);
        $this->assertArrayHasKey('created_at', $normalized[0]);
        $this->assertSame(2, $normalized[1]['id']);
        $this->assertSame('Jane', $normalized[1]['name']);
    }

    /**
     * Test that RecordCollection normalizes to JSON.
     */
    public function test_collection_normalizes_to_json(): void
    {
        // Arrange
        $collection = new RecordCollection;
        $collection->add($this->createTestUserRecord(1, 'John'));

        // Act
        $json = $collection->normalize(NormalizeMode::JSON);

        // Assert
        $this->assertIsString($json);
        $this->assertJson($json);

        $decoded = json_decode($json, true);
        $this->assertCount(1, $decoded);
        $this->assertSame(1, $decoded[0]['id']);
        $this->assertSame('John', $decoded[0]['name']);
    }

    /**
     * Test that RecordCollection excludes nulls when specified.
     */
    public function test_collection_excludes_nulls_when_specified(): void
    {
        // Arrange
        $collection = new RecordCollection;
        $record = new TestUserRecord(
            name: 'John',
            email: $this->testEmail,
            id: null,
            emailVerifiedAt: null
        );
        $collection->add($record);

        // Act
        $normalized = $collection->normalize(includeNulls: false);

        // Assert
        $this->assertIsArray($normalized);
        $this->assertArrayNotHasKey('id', $normalized[0]);
        $this->assertArrayNotHasKey('email_verified_at', $normalized[0]);
        $this->assertArrayHasKey('name', $normalized[0]);
    }

    // ==================== COLLECTION OPERATIONS TESTS ====================

    /**
     * Test that RecordCollection supports count.
     */
    public function test_collection_supports_count(): void
    {
        // Arrange
        $collection = new RecordCollection;
        $collection->add(
            $this->createTestUserRecord(1, 'User 1'),
            $this->createTestUserRecord(2, 'User 2')
        );

        // Act & Assert
        $this->assertCount(2, $collection);
        $this->assertSame(2, $collection->count());
    }

    /**
     * Test that RecordCollection supports isEmpty.
     */
    public function test_collection_supports_is_empty(): void
    {
        // Arrange
        $emptyCollection = new RecordCollection;
        $nonEmptyCollection = new RecordCollection;
        $nonEmptyCollection->add($this->createTestUserRecord(1, 'User'));

        // Act & Assert
        $this->assertTrue($emptyCollection->isEmpty());
        $this->assertFalse($nonEmptyCollection->isEmpty());
    }

    /**
     * Test that RecordCollection supports contains.
     */
    public function test_collection_supports_contains(): void
    {
        // Arrange
        $collection = new RecordCollection;
        $user1 = $this->createTestUserRecord(1, 'User 1');
        $user2 = $this->createTestUserRecord(2, 'User 2');
        $collection->add($user1, $user2);

        // Act & Assert
        $this->assertTrue($collection->contains($user1));
        $this->assertTrue($collection->contains($user2));

        $user3 = $this->createTestUserRecord(3, 'User 3');
        $this->assertFalse($collection->contains($user3));
    }

    /**
     * Test that RecordCollection supports each.
     */
    public function test_collection_supports_each(): void
    {
        // Arrange
        $collection = new RecordCollection;
        $collection->add(
            $this->createTestUserRecord(1, 'User 1'),
            $this->createTestUserRecord(2, 'User 2')
        );
        $names = [];

        // Act
        $collection->each(function ($item) use (&$names) {
            $names[] = $item->name;
        });

        // Assert
        $this->assertSame(['User 1', 'User 2'], $names);
    }

    /**
     * Test that RecordCollection supports reduce.
     */
    public function test_collection_supports_reduce(): void
    {
        // Arrange
        $collection = new RecordCollection;
        $collection->add(
            $this->createTestProductRecord(1, 'Product A', 100),
            $this->createTestProductRecord(2, 'Product B', 200),
            $this->createTestProductRecord(3, 'Product C', 300)
        );

        // Act
        $total = $collection->reduce(fn($carry, $item) => $carry + $item->price, 0);

        // Assert
        $this->assertSame(600.0, $total);
    }

    /**
     * Test that RecordCollection supports find.
     */
    public function test_collection_supports_find(): void
    {
        // Arrange
        $collection = new RecordCollection;
        $collection->add(
            $this->createTestUserRecord(1, 'Alice'),
            $this->createTestUserRecord(2, 'Bob'),
            $this->createTestUserRecord(3, 'Charlie')
        );

        // Act
        $found = $collection->find(fn($item) => $item->name === 'Bob');

        // Assert
        $this->assertNotNull($found);
        $this->assertSame(2, $found->id);
        $this->assertSame('Bob', $found->name);
    }

    /**
     * Test that RecordCollection supports every.
     */
    public function test_collection_supports_every(): void
    {
        // Arrange
        $collection = new RecordCollection;
        $collection->add(
            $this->createTestUserRecord(1, 'User 1'),
            $this->createTestUserRecord(2, 'User 2')
        );

        // Act & Assert
        $this->assertTrue($collection->every(fn($item) => strlen($item->name) > 0));
        $this->assertFalse($collection->every(fn($item) => $item->name === 'User 1'));
    }

    /**
     * Test that RecordCollection supports some.
     */
    public function test_collection_supports_some(): void
    {
        // Arrange
        $collection = new RecordCollection;
        $collection->add(
            $this->createTestUserRecord(1, 'Alice'),
            $this->createTestUserRecord(2, 'Bob'),
            $this->createTestUserRecord(3, 'Charlie')
        );

        // Act & Assert
        $this->assertTrue($collection->some(fn($item) => $item->name === 'Bob'));
        $this->assertFalse($collection->some(fn($item) => $item->name === 'David'));
    }

    /**
     * Test that RecordCollection supports reverse.
     */
    public function test_collection_supports_reverse(): void
    {
        // Arrange
        $collection = new RecordCollection;
        $collection->add(
            $this->createTestUserRecord(1, 'First'),
            $this->createTestUserRecord(2, 'Second'),
            $this->createTestUserRecord(3, 'Third')
        );

        // Act
        $reversed = $collection->reverse();

        // Assert
        $this->assertInstanceOf(RecordCollection::class, $reversed);
        $this->assertSame('Third', $reversed->toArray()[0]->name);
        $this->assertSame('Second', $reversed->toArray()[1]->name);
        $this->assertSame('First', $reversed->toArray()[2]->name);
    }

    /**
     * Test that RecordCollection supports merge.
     */
    public function test_collection_supports_merge(): void
    {
        // Arrange
        $collection1 = new RecordCollection;
        $collection2 = new RecordCollection;
        $collection1->add($this->createTestUserRecord(1, 'User 1'));
        $collection2->add($this->createTestUserRecord(2, 'User 2'));

        // Act
        $merged = $collection1->merge($collection2);

        // Assert
        $this->assertInstanceOf(RecordCollection::class, $merged);
        $this->assertCount(2, $merged);
        $this->assertSame('User 1', $merged->toArray()[0]->name);
        $this->assertSame('User 2', $merged->toArray()[1]->name);
    }

    /**
     * Test that RecordCollection supports sort.
     */
    public function test_collection_supports_sort(): void
    {
        // Arrange
        $collection = new RecordCollection;
        $collection->add(
            $this->createTestProductRecord(3, 'Product C', 300),
            $this->createTestProductRecord(1, 'Product A', 100),
            $this->createTestProductRecord(2, 'Product B', 200)
        );

        // Act
        $sorted = $collection->sort();

        // Assert
        $this->assertInstanceOf(RecordCollection::class, $sorted);
        $this->assertSame('Product A', $sorted->toArray()[0]->name);
        $this->assertSame('Product B', $sorted->toArray()[1]->name);
        $this->assertSame('Product C', $sorted->toArray()[2]->name);
    }

    // ==================== ARRAY ACCESS TESTS ====================

    /**
     * Test that RecordCollection supports array access.
     */
    public function test_collection_supports_array_access(): void
    {
        // Arrange
        $collection = new RecordCollection;
        $collection->add($this->createTestUserRecord(1, 'User 1'));

        // Act & Assert
        $this->assertTrue(isset($collection[0]));
        $this->assertInstanceOf(TestUserRecord::class, $collection[0]);
        $this->assertSame('User 1', $collection[0]->name);
    }

    // ==================== JSON SERIALIZATION TESTS ====================

    /**
     * Test that RecordCollection can be JSON serialized.
     */
    public function test_collection_can_be_json_serialized(): void
    {
        // Arrange
        $collection = new RecordCollection;
        $collection->add($this->createTestUserRecord(1, 'User 1'));

        // Act
        $json = json_encode($collection);

        // Assert
        $this->assertIsString($json);
        $this->assertJson($json);

        $decoded = json_decode($json, true);
        $this->assertCount(1, $decoded);
        $this->assertSame(1, $decoded[0]['id']);
    }

    // ==================== EDGE CASES TESTS ====================

    /**
     * Test that empty RecordCollection normalizes to empty array.
     */
    public function test_empty_collection_normalizes_to_empty_array(): void
    {
        // Arrange
        $emptyCollection = new RecordCollection;

        // Act
        $normalized = $emptyCollection->normalize();

        // Assert
        $this->assertIsArray($normalized);
        $this->assertEmpty($normalized);
    }

    /**
     * Test that RecordCollection can handle many items.
     */
    public function test_collection_can_handle_many_items(): void
    {
        // Arrange
        $collection = new RecordCollection;

        for ($i = 1; $i <= 100; $i++) {
            $collection->add($this->createTestUserRecord($i, "User {$i}"));
        }

        // Act & Assert
        $this->assertCount(100, $collection);
        $this->assertSame('User 50', $collection->toArray()[49]->name);
    }

    /**
     * Test that RecordCollection preserves item order.
     */
    public function test_collection_preserves_item_order(): void
    {
        // Arrange
        $collection = new RecordCollection;

        // Act
        $collection->add(
            $this->createTestUserRecord(1, 'First'),
            $this->createTestUserRecord(2, 'Second'),
            $this->createTestUserRecord(3, 'Third')
        );

        // Assert
        $items = $collection->toArray();
        $this->assertSame('First', $items[0]->name);
        $this->assertSame('Second', $items[1]->name);
        $this->assertSame('Third', $items[2]->name);
    }
}
