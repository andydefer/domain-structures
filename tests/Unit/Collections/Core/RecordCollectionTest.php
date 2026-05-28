<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Unit\Collections\Core;

use AndyDefer\DomainStructures\Collections\Core\RecordCollection;
use AndyDefer\DomainStructures\Collections\Core\TypedCollection;
use AndyDefer\DomainStructures\Normalizers\NormalizerChain;
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

        $this->now = TestIso8601DateTime::from('2024-01-01T12:00:00+00:00');
        $this->testEmail = TestEmailAddress::from('test@example.com');
    }

    /**
     * Create a test user record.
     */
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

    /**
     * Create a test product record.
     */
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
     * Test that RecordCollection constructor sets allowed type.
     */
    public function test_constructor_sets_abstract_record_as_allowed_type(): void
    {
        $collection = new RecordCollection(TestUserRecord::class);

        $this->assertSame([TestUserRecord::class], $collection->getAllowedTypes());
    }

    /**
     * Test that RecordCollection only accepts the specified record type.
     */
    public function test_collection_only_accepts_abstract_record_type(): void
    {
        /** @var RecordCollection<TestUserRecord> $collection */
        $collection = new RecordCollection(TestUserRecord::class);
        $userRecord = $this->createTestUserRecord(1, 'John Doe');

        $collection->add($userRecord);

        $this->assertCount(1, $collection);
        $this->assertSame($userRecord, $collection[0]);
    }

    /**
     * Test that RecordCollection rejects non-AbstractRecord objects.
     */
    public function test_collection_rejects_non_abstract_record_objects(): void
    {
        /** @var RecordCollection<TestUserRecord> $collection */
        $collection = new RecordCollection(TestUserRecord::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected type(s) '.TestUserRecord::class);

        $collection->add('not a record');
    }

    /**
     * Test that RecordCollection rejects scalar values.
     */
    public function test_collection_rejects_scalar_values(): void
    {
        /** @var RecordCollection<TestUserRecord> $collection */
        $collection = new RecordCollection(TestUserRecord::class);

        $this->expectException(InvalidArgumentException::class);

        $collection->add(123);
    }

    // ==================== ADD METHOD TESTS ====================

    /**
     * Test that add method accepts multiple Record objects.
     */
    public function test_add_accepts_multiple_record_objects(): void
    {
        /** @var RecordCollection<TestUserRecord> $collection */
        $collection = new RecordCollection(TestUserRecord::class);
        $record1 = $this->createTestUserRecord(1, 'User 1');
        $record2 = $this->createTestUserRecord(2, 'User 2');
        $record3 = $this->createTestUserRecord(3, 'User 3');

        $collection->add($record1, $record2, $record3);

        $this->assertCount(3, $collection);
        $this->assertSame($record1, $collection[0]);
        $this->assertSame($record2, $collection[1]);
        $this->assertSame($record3, $collection[2]);
    }

    /**
     * Test that add returns self for chaining.
     */
    public function test_add_returns_self_for_chaining(): void
    {
        /** @var RecordCollection<TestUserRecord> $collection */
        $collection = new RecordCollection(TestUserRecord::class);
        $record = $this->createTestUserRecord(1, 'User 1');

        $result = $collection->add($record);

        $this->assertSame($collection, $result);
    }

    // ==================== ALL METHOD TESTS ====================

    /**
     * Test that all returns new RecordCollection with same items.
     */
    public function test_all_returns_new_collection_with_same_items(): void
    {
        /** @var RecordCollection<TestUserRecord> $original */
        $original = new RecordCollection(TestUserRecord::class);
        $original->add(
            $this->createTestUserRecord(1, 'User 1'),
            $this->createTestUserRecord(2, 'User 2')
        );

        $newCollection = $original->all();

        $this->assertNotSame($original, $newCollection);
        $this->assertInstanceOf(RecordCollection::class, $newCollection);
        $this->assertCount(2, $newCollection);
        $this->assertEquals($original[0], $newCollection[0]);
        $this->assertEquals($original[1], $newCollection[1]);
    }

    // ==================== FILTER METHOD TESTS ====================

    /**
     * Test that filter returns RecordCollection with filtered items.
     */
    public function test_filter_returns_record_collection_with_filtered_items(): void
    {
        /** @var RecordCollection<TestUserRecord> $collection */
        $collection = new RecordCollection(TestUserRecord::class);
        $collection->add(
            $this->createTestUserRecord(1, 'Alice'),
            $this->createTestUserRecord(2, 'Bob'),
            $this->createTestUserRecord(3, 'Alice')
        );

        /** @var RecordCollection<TestUserRecord> $filtered */
        $filtered = $collection->filter(fn (TestUserRecord $item) => $item->name === 'Alice');

        $this->assertInstanceOf(RecordCollection::class, $filtered);
        $this->assertCount(2, $filtered);
        $this->assertSame('Alice', $filtered[0]->name);
        $this->assertSame('Alice', $filtered[1]->name);
    }

    // ==================== MAP METHOD TESTS ====================

    /**
     * Test that map can transform Record objects.
     */
    public function test_map_can_transform_record_objects(): void
    {
        /** @var RecordCollection<TestUserRecord> $collection */
        $collection = new RecordCollection(TestUserRecord::class);
        $collection->add(
            $this->createTestUserRecord(1, 'John'),
            $this->createTestUserRecord(2, 'Jane')
        );

        /** @var TypedCollection<string> $names */
        $names = $collection->map(fn (TestUserRecord $item) => $item->name);

        $this->assertInstanceOf(TypedCollection::class, $names);
        $this->assertSame(['John', 'Jane'], $names->toArray());
    }

    /**
     * Test that map can produce new collection.
     */
    public function test_map_can_produce_new_record_collection(): void
    {
        /** @var RecordCollection<TestProductRecord> $collection */
        $collection = new RecordCollection(TestProductRecord::class);
        $collection->add(
            $this->createTestProductRecord(1, 'Product A', 100),
            $this->createTestProductRecord(2, 'Product B', 200)
        );

        /** @var TypedCollection<float> $prices */
        $prices = $collection->map(fn (TestProductRecord $item) => $item->price * 1.2);

        $this->assertSame([120.0, 240.0], $prices->toArray());
    }

    // ==================== NORMALIZATION TESTS ====================

    /**
     * Test that RecordCollection normalizes to array (snake_case).
     */
    public function test_collection_normalizes_to_array_with_snake_case(): void
    {
        /** @var RecordCollection<TestUserRecord> $collection */
        $collection = new RecordCollection(TestUserRecord::class);
        $collection->add(
            $this->createTestUserRecord(1, 'John'),
            $this->createTestUserRecord(2, 'Jane')
        );

        $normalized = NormalizerChain::get()->normalize($collection);

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
        /** @var RecordCollection<TestUserRecord> $collection */
        $collection = new RecordCollection(TestUserRecord::class);
        $collection->add($this->createTestUserRecord(1, 'John'));

        $json = json_encode(NormalizerChain::get()->normalize($collection));

        $this->assertIsString($json);
        $this->assertJson($json);

        $decoded = json_decode($json, true);
        $this->assertCount(1, $decoded);
        $this->assertSame(1, $decoded[0]['id']);
        $this->assertSame('John', $decoded[0]['name']);
    }

    /**
     * Test that RecordCollection excludes nulls when specified.
     * Note: With uniform normalization, nulls are ALWAYS included.
     * This test is kept for backward compatibility but modified to expect nulls.
     */
    public function test_collection_includes_nulls_by_default(): void
    {
        /** @var RecordCollection<TestUserRecord> $collection */
        $collection = new RecordCollection(TestUserRecord::class);
        $record = new TestUserRecord(
            name: 'John',
            email: $this->testEmail,
            id: null,
            emailVerifiedAt: null
        );
        $collection->add($record);

        $normalized = NormalizerChain::get()->normalize($collection);

        $this->assertIsArray($normalized);
        $this->assertCount(1, $normalized);
        $this->assertArrayHasKey('id', $normalized[0]);
        $this->assertArrayHasKey('email_verified_at', $normalized[0]);
        $this->assertArrayHasKey('name', $normalized[0]);
        $this->assertNull($normalized[0]['id']);
        $this->assertNull($normalized[0]['email_verified_at']);
        $this->assertSame('John', $normalized[0]['name']);
    }

    // ==================== COLLECTION OPERATIONS TESTS ====================

    /**
     * Test that RecordCollection supports count.
     */
    public function test_collection_supports_count(): void
    {
        /** @var RecordCollection<TestUserRecord> $collection */
        $collection = new RecordCollection(TestUserRecord::class);
        $collection->add(
            $this->createTestUserRecord(1, 'User 1'),
            $this->createTestUserRecord(2, 'User 2')
        );

        $this->assertCount(2, $collection);
        $this->assertSame(2, $collection->count());
    }

    /**
     * Test that RecordCollection supports isEmpty.
     */
    public function test_collection_supports_is_empty(): void
    {
        /** @var RecordCollection<TestUserRecord> $emptyCollection */
        $emptyCollection = new RecordCollection(TestUserRecord::class);
        /** @var RecordCollection<TestUserRecord> $nonEmptyCollection */
        $nonEmptyCollection = new RecordCollection(TestUserRecord::class);
        $nonEmptyCollection->add($this->createTestUserRecord(1, 'User'));

        $this->assertTrue($emptyCollection->isEmpty());
        $this->assertFalse($nonEmptyCollection->isEmpty());
    }

    /**
     * Test that RecordCollection supports contains.
     */
    public function test_collection_supports_contains(): void
    {
        /** @var RecordCollection<TestUserRecord> $collection */
        $collection = new RecordCollection(TestUserRecord::class);
        $user1 = $this->createTestUserRecord(1, 'User 1');
        $user2 = $this->createTestUserRecord(2, 'User 2');
        $collection->add($user1, $user2);

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
        /** @var RecordCollection<TestUserRecord> $collection */
        $collection = new RecordCollection(TestUserRecord::class);
        $collection->add(
            $this->createTestUserRecord(1, 'User 1'),
            $this->createTestUserRecord(2, 'User 2')
        );
        $names = [];

        $collection->each(function (TestUserRecord $item) use (&$names) {
            $names[] = $item->name;
        });

        $this->assertSame(['User 1', 'User 2'], $names);
    }

    /**
     * Test that RecordCollection supports reduce.
     */
    public function test_collection_supports_reduce(): void
    {
        /** @var RecordCollection<TestProductRecord> $collection */
        $collection = new RecordCollection(TestProductRecord::class);
        $collection->add(
            $this->createTestProductRecord(1, 'Product A', 100),
            $this->createTestProductRecord(2, 'Product B', 200),
            $this->createTestProductRecord(3, 'Product C', 300)
        );

        $total = $collection->reduce(fn ($carry, TestProductRecord $item) => $carry + $item->price, 0);

        $this->assertSame(600.0, $total);
    }

    /**
     * Test that RecordCollection supports find.
     */
    public function test_collection_supports_find(): void
    {
        /** @var RecordCollection<TestUserRecord> $collection */
        $collection = new RecordCollection(TestUserRecord::class);
        $collection->add(
            $this->createTestUserRecord(1, 'Alice'),
            $this->createTestUserRecord(2, 'Bob'),
            $this->createTestUserRecord(3, 'Charlie')
        );

        /** @var TestUserRecord|null $found */
        $found = $collection->find(fn (TestUserRecord $item) => $item->name === 'Bob');

        $this->assertNotNull($found);
        $this->assertSame(2, $found->id);
        $this->assertSame('Bob', $found->name);
    }

    /**
     * Test that RecordCollection supports every.
     */
    public function test_collection_supports_every(): void
    {
        /** @var RecordCollection<TestUserRecord> $collection */
        $collection = new RecordCollection(TestUserRecord::class);
        $collection->add(
            $this->createTestUserRecord(1, 'User 1'),
            $this->createTestUserRecord(2, 'User 2')
        );

        $this->assertTrue($collection->every(fn (TestUserRecord $item) => strlen($item->name) > 0));
        $this->assertFalse($collection->every(fn (TestUserRecord $item) => $item->name === 'User 1'));
    }

    /**
     * Test that RecordCollection supports some.
     */
    public function test_collection_supports_some(): void
    {
        /** @var RecordCollection<TestUserRecord> $collection */
        $collection = new RecordCollection(TestUserRecord::class);
        $collection->add(
            $this->createTestUserRecord(1, 'Alice'),
            $this->createTestUserRecord(2, 'Bob'),
            $this->createTestUserRecord(3, 'Charlie')
        );

        $this->assertTrue($collection->some(fn (TestUserRecord $item) => $item->name === 'Bob'));
        $this->assertFalse($collection->some(fn (TestUserRecord $item) => $item->name === 'David'));
    }

    /**
     * Test that RecordCollection supports reverse.
     */
    public function test_collection_supports_reverse(): void
    {
        /** @var RecordCollection<TestUserRecord> $collection */
        $collection = new RecordCollection(TestUserRecord::class);
        $collection->add(
            $this->createTestUserRecord(1, 'First'),
            $this->createTestUserRecord(2, 'Second'),
            $this->createTestUserRecord(3, 'Third')
        );

        /** @var RecordCollection<TestUserRecord> $reversed */
        $reversed = $collection->reverse();

        $this->assertInstanceOf(RecordCollection::class, $reversed);
        $this->assertSame('Third', $reversed[0]->name);
        $this->assertSame('Second', $reversed[1]->name);
        $this->assertSame('First', $reversed[2]->name);
    }

    /**
     * Test that RecordCollection supports merge.
     */
    public function test_collection_supports_merge(): void
    {
        /** @var RecordCollection<TestUserRecord> $collection1 */
        $collection1 = new RecordCollection(TestUserRecord::class);
        /** @var RecordCollection<TestUserRecord> $collection2 */
        $collection2 = new RecordCollection(TestUserRecord::class);
        $collection1->add($this->createTestUserRecord(1, 'User 1'));
        $collection2->add($this->createTestUserRecord(2, 'User 2'));

        /** @var RecordCollection<TestUserRecord> $merged */
        $merged = $collection1->merge($collection2);

        $this->assertInstanceOf(RecordCollection::class, $merged);
        $this->assertCount(2, $merged);
        $this->assertSame('User 1', $merged[0]->name);
        $this->assertSame('User 2', $merged[1]->name);
    }

    /**
     * Test that RecordCollection supports sort.
     */
    public function test_collection_supports_sort(): void
    {
        /** @var RecordCollection<TestProductRecord> $collection */
        $collection = new RecordCollection(TestProductRecord::class);
        $collection->add(
            $this->createTestProductRecord(3, 'Product C', 300),
            $this->createTestProductRecord(1, 'Product A', 100),
            $this->createTestProductRecord(2, 'Product B', 200)
        );

        $sorted = $collection->sort();

        $this->assertInstanceOf(RecordCollection::class, $sorted);
        $this->assertSame('Product A', $sorted[0]->name);
        $this->assertSame('Product B', $sorted[1]->name);
        $this->assertSame('Product C', $sorted[2]->name);
    }

    // ==================== ARRAY ACCESS TESTS ====================

    /**
     * Test that RecordCollection supports array access.
     */
    public function test_collection_supports_array_access(): void
    {
        /** @var RecordCollection<TestUserRecord> $collection */
        $collection = new RecordCollection(TestUserRecord::class);
        $collection->add($this->createTestUserRecord(1, 'User 1'));

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
        /** @var RecordCollection<TestUserRecord> $collection */
        $collection = new RecordCollection(TestUserRecord::class);
        $collection->add($this->createTestUserRecord(1, 'User 1'));

        $json = json_encode($collection);

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
        /** @var RecordCollection<TestUserRecord> $emptyCollection */
        $emptyCollection = new RecordCollection(TestUserRecord::class);

        $normalized = NormalizerChain::get()->normalize($emptyCollection);

        $this->assertIsArray($normalized);
        $this->assertEmpty($normalized);
    }

    /**
     * Test that RecordCollection can handle many items.
     */
    public function test_collection_can_handle_many_items(): void
    {
        /** @var RecordCollection<TestUserRecord> $collection */
        $collection = new RecordCollection(TestUserRecord::class);

        for ($i = 1; $i <= 100; $i++) {
            $collection->add($this->createTestUserRecord($i, "User {$i}"));
        }

        $this->assertCount(100, $collection);
        $this->assertSame('User 50', $collection[49]->name);
    }

    /**
     * Test that RecordCollection preserves item order.
     */
    public function test_collection_preserves_item_order(): void
    {
        /** @var RecordCollection<TestUserRecord> $collection */
        $collection = new RecordCollection(TestUserRecord::class);

        $collection->add(
            $this->createTestUserRecord(1, 'First'),
            $this->createTestUserRecord(2, 'Second'),
            $this->createTestUserRecord(3, 'Third')
        );

        $this->assertSame('First', $collection[0]->name);
        $this->assertSame('Second', $collection[1]->name);
        $this->assertSame('Third', $collection[2]->name);
    }
}
