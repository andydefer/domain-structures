<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Unit\Collections\Core;

use AndyDefer\DomainStructures\Abstracts\AbstractData;
use AndyDefer\DomainStructures\Abstracts\AbstractRecord;
use AndyDefer\DomainStructures\Abstracts\AbstractTypedCollection;
use AndyDefer\DomainStructures\Abstracts\AbstractValueObject;
use AndyDefer\DomainStructures\Collections\Core\TypedCollection;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestUserStatus;
use AndyDefer\DomainStructures\Tests\Fixtures\Records\TestUserRecord;
use AndyDefer\DomainStructures\Tests\Fixtures\ValueObjects\TestEmailAddress;
use AndyDefer\DomainStructures\Tests\TestCase;

/**
 * Unit tests for TypedCollection class.
 *
 * This test suite validates the TypedCollection which extends AbstractTypedCollection.
 * Most functionality is already tested in AbstractTypedCollectionTest.
 * This class only tests TypedCollection-specific behavior:
 * - Constructor with no types (accepts all allowed types)
 * - Constructor with specific types
 *
 * The tests follow the AAA pattern (Arrange, Act, Assert).
 */
final class TypedCollectionTest extends TestCase
{
    // ==================== CONSTRUCTOR TESTS ====================

    /**
     * Test that TypedCollection with no types accepts all allowed types.
     */
    public function test_constructor_with_no_types_accepts_all_allowed_types(): void
    {
        // Arrange & Act
        $collection = new TypedCollection;

        // Assert
        $allowedTypes = $collection->getAllowedTypes();

        $this->assertContains('int', $allowedTypes);
        $this->assertContains('string', $allowedTypes);
        $this->assertContains('float', $allowedTypes);
        $this->assertContains('bool', $allowedTypes);
        $this->assertContains('null', $allowedTypes);
        $this->assertContains(\UnitEnum::class, $allowedTypes);
        $this->assertContains(AbstractRecord::class, $allowedTypes);
        $this->assertContains(AbstractValueObject::class, $allowedTypes);
        $this->assertContains(AbstractData::class, $allowedTypes);
        $this->assertContains(AbstractTypedCollection::class, $allowedTypes);
        $this->assertContains(\stdClass::class, $allowedTypes);
    }

    /**
     * Test that TypedCollection with no types can add any allowed type.
     */
    public function test_constructor_with_no_types_can_add_any_allowed_type(): void
    {
        // Arrange
        $collection = new TypedCollection;

        // Act
        $collection->add(
            42,                    // int
            'string',              // string
            3.14,                  // float
            true,                  // bool
            null,                  // null
            TestUserStatus::ACTIVE, // enum
            new TestUserRecord(    // record
                name: 'User',
                email: TestEmailAddress::from('user@example.com')
            ),
            new \stdClass,         // stdClass
            new TypedCollection    // collection
        );

        // Assert
        $this->assertCount(9, $collection);
    }

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
        $collection = new TypedCollection;

        // Assert
        $this->assertInstanceOf(AbstractTypedCollection::class, $collection);
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
        $collection = new TypedCollection('int');
        $collection->add(1, 2, 3);

        // Act
        $doubled = $collection->map(fn($item) => $item * 2);

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
        $collection = new TypedCollection('int');
        $collection->add(1, 2, 3, 4, 5);

        // Act
        $evens = $collection->filter(fn($item) => $item % 2 === 0);

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
        $collection = new TypedCollection('int');
        $collection->add(5, 2, 8, 1, 9);

        // Act
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
        $collection = new TypedCollection('int');
        $collection->add(1, 2, 3);

        // Act
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
        $collection1 = new TypedCollection('int');
        $collection2 = new TypedCollection('int');
        $collection1->add(1, 2);
        $collection2->add(3, 4);

        // Act
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
        $collection = new TypedCollection('int');
        $collection->add(1, 2, 3);

        // Act
        $normalized = $collection->normalize();

        // Assert
        $this->assertSame([1, 2, 3], $normalized);
    }

    /**
     * Test that array access works with TypedCollection.
     */
    public function test_array_access_works_with_typed_collection(): void
    {
        // Arrange
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
        $collection = new TypedCollection('int');
        $collection->add(1, 2, 3);

        // Act & Assert
        $this->assertCount(3, $collection);
        $this->assertSame(3, $collection->count());
    }
}
