<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Unit\Collections\Core;

use AndyDefer\DomainStructures\Abstracts\AbstractTypedCollection;
use AndyDefer\DomainStructures\Collections\Core\TypedCollection;
use AndyDefer\DomainStructures\Normalizers\NormalizerChain;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestUserStatus;
use AndyDefer\DomainStructures\Tests\Fixtures\Records\TestUserRecord;
use AndyDefer\DomainStructures\Tests\Fixtures\ValueObjects\TestEmailAddress;
use AndyDefer\DomainStructures\Tests\TestCase;
use AndyDefer\DomainStructures\Utils\DataObject;

/**
 * Unit tests for TypedCollection class.
 *
 * This test suite validates the TypedCollection which extends AbstractTypedCollection.
 * Most functionality is already tested in AbstractTypedCollectionTest.
 * This class only tests TypedCollection-specific behavior:
 * - Constructor with specific types
 *
 * The tests follow the AAA pattern (Arrange, Act, Assert).
 */
final class TypedCollectionTest extends TestCase
{
    // ==================== CONSTRUCTOR TESTS ====================

    /**
     * Test that TypedCollection with specific types restricts to those types.
     */
    public function test_constructor_with_specific_types_restricts_to_those_types(): void
    {
        // Arrange & Act
        $collection = new TypedCollection('int', 'string');

        // Assert
        $this->assertSame(['int', 'string'], $collection->getAllowedTypes());
    }

    /**
     * Test that TypedCollection with specific types validates added items.
     */
    public function test_constructor_with_specific_types_validates_added_items(): void
    {
        // Arrange
        $collection = new TypedCollection('int', 'string');

        // Act
        $collection->add(42, 'hello');

        // Assert
        $this->assertCount(2, $collection);
        $this->assertSame(42, $collection[0]);
        $this->assertSame('hello', $collection[1]);
    }

    /**
     * Test that TypedCollection with single type works correctly.
     */
    public function test_constructor_with_single_type_works_correctly(): void
    {
        // Arrange & Act
        $collection = new TypedCollection('int');

        // Assert
        $this->assertSame(['int'], $collection->getAllowedTypes());

        $collection->add(1, 2, 3);
        $this->assertCount(3, $collection);
    }

    /**
     * Test that TypedCollection extends AbstractTypedCollection.
     */
    public function test_typed_collection_extends_abstract_typed_collection(): void
    {
        // Arrange & Act
        $collection = new TypedCollection('int');

        // Assert
        $this->assertInstanceOf(AbstractTypedCollection::class, $collection);
    }

    /**
     * Test that TypedCollection cannot be created without types.
     */
    public function test_constructor_throws_exception_when_no_types_provided(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('At least one allowed type must be provided');

        new TypedCollection;
    }

    // ==================== INHERITED FUNCTIONALITY TESTS ====================
    // These tests verify that inherited methods work correctly with TypedCollection.
    // Full coverage is in AbstractTypedCollectionTest.

    /**
     * Test that map works with TypedCollection.
     */
    public function test_map_works_with_typed_collection(): void
    {
        // Arrange
        /** @var TypedCollection<int> $collection */
        $collection = new TypedCollection('int');
        $collection->add(1, 2, 3);

        // Act
        /** @var TypedCollection<int> $doubled */
        $doubled = $collection->map(fn (int $item) => $item * 2);

        // Assert
        $this->assertInstanceOf(TypedCollection::class, $doubled);
        $this->assertSame([2, 4, 6], $doubled->toArray());
    }

    /**
     * Test that filter works with TypedCollection.
     */
    public function test_filter_works_with_typed_collection(): void
    {
        // Arrange
        /** @var TypedCollection<int> $collection */
        $collection = new TypedCollection('int');
        $collection->add(1, 2, 3, 4, 5);

        // Act
        /** @var TypedCollection<int> $evens */
        $evens = $collection->filter(fn (int $item) => $item % 2 === 0);

        // Assert
        $this->assertInstanceOf(TypedCollection::class, $evens);
        $this->assertSame([2, 4], $evens->toArray());
    }

    /**
     * Test that sort works with TypedCollection.
     */
    public function test_sort_works_with_typed_collection(): void
    {
        // Arrange
        /** @var TypedCollection<int> $collection */
        $collection = new TypedCollection('int');
        $collection->add(5, 2, 8, 1, 9);

        // Act
        /** @var TypedCollection<int> $sorted */
        $sorted = $collection->sort();

        // Assert
        $this->assertInstanceOf(TypedCollection::class, $sorted);
        $this->assertSame([1, 2, 5, 8, 9], $sorted->toArray());
    }

    /**
     * Test that reverse works with TypedCollection.
     */
    public function test_reverse_works_with_typed_collection(): void
    {
        // Arrange
        /** @var TypedCollection<int> $collection */
        $collection = new TypedCollection('int');
        $collection->add(1, 2, 3);

        // Act
        /** @var TypedCollection<int> $reversed */
        $reversed = $collection->reverse();

        // Assert
        $this->assertInstanceOf(TypedCollection::class, $reversed);
        $this->assertSame([3, 2, 1], $reversed->toArray());
    }

    /**
     * Test that merge works with TypedCollection.
     */
    public function test_merge_works_with_typed_collection(): void
    {
        // Arrange
        /** @var TypedCollection<int> $collection1 */
        $collection1 = new TypedCollection('int');
        /** @var TypedCollection<int> $collection2 */
        $collection2 = new TypedCollection('int');
        $collection1->add(1, 2);
        $collection2->add(3, 4);

        // Act
        /** @var TypedCollection<int> $merged */
        $merged = $collection1->merge($collection2);

        // Assert
        $this->assertInstanceOf(TypedCollection::class, $merged);
        $this->assertSame([1, 2, 3, 4], $merged->toArray());
    }

    /**
     * Test that normalize works with TypedCollection.
     */
    public function test_normalize_works_with_typed_collection(): void
    {
        // Arrange
        /** @var TypedCollection<int> $collection */
        $collection = new TypedCollection('int');
        $collection->add(1, 2, 3);

        // Act
        $normalized = NormalizerChain::get()->normalize($collection);

        // Assert
        $this->assertSame([1, 2, 3], $normalized);
    }

    /**
     * Test that array access works with TypedCollection.
     */
    public function test_array_access_works_with_typed_collection(): void
    {
        // Arrange
        /** @var TypedCollection<string> $collection */
        $collection = new TypedCollection('string');
        $collection->add('a', 'b', 'c');

        // Act & Assert
        $this->assertSame('a', $collection[0]);
        $this->assertSame('b', $collection[1]);
        $this->assertSame('c', $collection[2]);

        $collection[3] = 'd';
        $this->assertSame('d', $collection[3]);
    }

    /**
     * Test that foreach works with TypedCollection.
     */
    public function test_foreach_works_with_typed_collection(): void
    {
        // Arrange
        /** @var TypedCollection<int> $collection */
        $collection = new TypedCollection('int');
        $collection->add(1, 2, 3);
        $items = [];

        // Act
        foreach ($collection as $item) {
            $items[] = $item;
        }

        // Assert
        $this->assertSame([1, 2, 3], $items);
    }

    /**
     * Test that count works with TypedCollection.
     */
    public function test_count_works_with_typed_collection(): void
    {
        // Arrange
        /** @var TypedCollection<int> $collection */
        $collection = new TypedCollection('int');
        $collection->add(1, 2, 3);

        // Act & Assert
        $this->assertCount(3, $collection);
        $this->assertSame(3, $collection->count());
    }

    /**
     * Test that TypedCollection can store DataObject instances.
     */
    public function test_typed_collection_can_store_data_objects(): void
    {
        // Arrange
        /** @var TypedCollection<DataObject> $collection */
        $collection = new TypedCollection(DataObject::class);

        $data1 = new DataObject(['id' => 1, 'name' => 'John']);
        $data2 = new DataObject(['id' => 2, 'name' => 'Jane']);

        // Act
        $collection->add($data1, $data2);

        // Assert
        $this->assertCount(2, $collection);
        $this->assertSame(1, $collection[0]->get('id'));
        $this->assertSame('John', $collection[0]->get('name'));
        $this->assertSame(2, $collection[1]->get('id'));
        $this->assertSame('Jane', $collection[1]->get('name'));
    }

    /**
     * Test that TypedCollection with DataObject type rejects non-DataObject items.
     */
    public function test_typed_collection_with_data_object_type_rejects_other_types(): void
    {
        // Arrange
        /** @var TypedCollection<DataObject> $collection */
        $collection = new TypedCollection(DataObject::class);

        // Expect
        $this->expectException(\InvalidArgumentException::class);

        // Act
        $collection->add('not a data object');
    }

    /**
     * Test that TypedCollection can store multiple types.
     */
    public function test_typed_collection_can_store_multiple_types(): void
    {
        // Arrange
        /** @var TypedCollection<int|string|bool> $collection */
        $collection = new TypedCollection('int', 'string', 'bool');

        // Act
        $collection->add(42, 'hello', true);

        // Assert
        $this->assertCount(3, $collection);
        $this->assertSame(42, $collection[0]);
        $this->assertSame('hello', $collection[1]);
        $this->assertTrue($collection[2]);
    }

    /**
     * Test that TypedCollection can store enums.
     */
    public function test_typed_collection_can_store_enums(): void
    {
        // Arrange
        /** @var TypedCollection<TestUserStatus> $collection */
        $collection = new TypedCollection(TestUserStatus::class);

        // Act
        $collection->add(TestUserStatus::ACTIVE, TestUserStatus::INACTIVE);

        // Assert
        $this->assertCount(2, $collection);
        $this->assertSame(TestUserStatus::ACTIVE, $collection[0]);
        $this->assertSame(TestUserStatus::INACTIVE, $collection[1]);
    }

    /**
     * Test that TypedCollection can store records.
     */
    public function test_typed_collection_can_store_records(): void
    {
        // Arrange
        /** @var TypedCollection<TestUserRecord> $collection */
        $collection = new TypedCollection(TestUserRecord::class);
        $record = new TestUserRecord(name: 'John', email: TestEmailAddress::from('john@example.com'));

        // Act
        $collection->add($record);

        // Assert
        $this->assertCount(1, $collection);
        $this->assertSame($record, $collection[0]);
    }
}
