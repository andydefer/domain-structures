<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Integration;

use AndyDefer\DomainStructures\Collections\Core\TypedCollection;
use AndyDefer\DomainStructures\Collections\Utility\IntTypedCollection;
use AndyDefer\DomainStructures\Collections\Utility\StringTypedCollection;
use AndyDefer\DomainStructures\Enums\NormalizeMode;
use AndyDefer\DomainStructures\Tests\Fixtures\Collections\ProductRecordCollection;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestBackedIntEnum;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestBackedStringEnum;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestUserStatus;
use AndyDefer\DomainStructures\Tests\Fixtures\Records\TestProductRecord;
use AndyDefer\DomainStructures\Tests\Fixtures\Records\TestUserRecord;
use AndyDefer\DomainStructures\Tests\Fixtures\ValueObjects\TestEmailAddress;
use AndyDefer\DomainStructures\Tests\TestCase;
use InvalidArgumentException;

/**
 * Integration test for collection workflows.
 *
 * This test suite validates the complete workflow of typed collections including:
 * - Creation and type validation
 * - Adding items with type safety
 * - Transformation operations (map, filter, reduce)
 * - Sorting and ordering
 * - Merging and cloning
 * - Normalization to array/JSON
 * - Iteration and array access
 * - Complex nested scenarios
 *
 * The tests follow the AAA pattern (Arrange, Act, Assert).
 */
final class CollectionWorkflowTest extends TestCase
{
    private TypedCollection $mixedCollection;

    private IntTypedCollection $intCollection;

    private StringTypedCollection $stringCollection;

    private ProductRecordCollection $productCollection;

    protected function setUp(): void
    {
        parent::setUp();

        // Create mixed collection accepting all types
        $this->mixedCollection = new TypedCollection;

        // Create integer collection
        $this->intCollection = new IntTypedCollection;

        // Create string collection
        $this->stringCollection = new StringTypedCollection;

        // Create product collection
        $this->productCollection = new ProductRecordCollection;
    }

    // ==================== CREATION AND TYPE VALIDATION TESTS ====================

    /**
     * Test that a typed collection can be created with allowed types.
     */
    public function test_constructor_accepts_valid_types(): void
    {
        // Arrange & Act
        $collection = new TypedCollection('int', 'string');

        // Assert
        $this->assertSame(['int', 'string'], $collection->getAllowedTypes());
    }

    /**
     * Test that constructor throws exception when no types provided.
     */
    public function test_constructor_throws_exception_when_no_types_provided(): void
    {
        // Arrange & Act & Assert
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('At least one type must be provided');

        new class extends TypedCollection
        {
            public function __construct()
            {
                parent::__construct();
            }
        };
    }

    /**
     * Test that constructor throws exception for invalid type.
     */
    public function test_constructor_throws_exception_for_invalid_type(): void
    {
        // Arrange & Act & Assert
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Type "NonExistentClass" is not a valid class');

        new TypedCollection('NonExistentClass');
    }

    /**
     * Test that mixed collection accepts all types by default.
     */
    public function test_mixed_collection_accepts_all_types_by_default(): void
    {
        // Arrange
        $collection = new TypedCollection;

        // Act
        $collection->add(42, 3.14, 'string', true, null, TestUserStatus::ACTIVE, new TestUserRecord(name: 'Test', email: TestEmailAddress::fromString('test@test.com')));

        // Assert
        $this->assertCount(7, $collection);
        $this->assertContains(42, $collection->toArray());
        $this->assertContains(3.14, $collection->toArray());
        $this->assertContains('string', $collection->toArray());
        $this->assertContains(true, $collection->toArray());
        $this->assertContains(null, $collection->toArray());
    }

    // ==================== ADD AND VALIDATION TESTS ====================

    /**
     * Test that add method correctly adds valid items.
     */
    public function test_add_appends_valid_items_to_collection(): void
    {
        // Arrange
        $collection = new IntTypedCollection;

        // Act
        $collection->add(1, 2, 3);
        $collection->add(4, 5);

        // Assert
        $this->assertCount(5, $collection);
        $this->assertSame([1, 2, 3, 4, 5], $collection->toArray());
    }

    /**
     * Test that add method throws exception for invalid type.
     */
    public function test_add_throws_exception_for_invalid_type(): void
    {
        // Arrange
        $collection = new IntTypedCollection;

        // Act & Assert
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected type(s) int');

        $collection->add('not an integer');
    }

    /**
     * Test that add method throws exception for invalid enum type.
     */
    public function test_add_throws_exception_for_enum_not_in_allowed_types(): void
    {
        // Arrange
        $collection = new TypedCollection(TestUserStatus::class);

        // Act & Assert
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Expected type.*TestUserStatus/');

        $collection->add(TestBackedIntEnum::VALUE_ONE);
    }

    /**
     * Test that add method accepts multiple items at once.
     */
    public function test_add_accepts_multiple_items_in_one_call(): void
    {
        // Arrange
        $collection = new StringTypedCollection;

        // Act
        $collection->add('apple', 'banana', 'cherry', 'date');

        // Assert
        $this->assertCount(4, $collection);
        $this->assertSame(['apple', 'banana', 'cherry', 'date'], $collection->toArray());
    }

    /**
     * Test that add method returns the collection instance for chaining.
     */
    public function test_add_returns_self_for_chaining(): void
    {
        // Arrange & Act
        $result = $this->intCollection->add(1)->add(2)->add(3);

        // Assert
        $this->assertSame($this->intCollection, $result);
        $this->assertCount(3, $this->intCollection);
    }

    // ==================== COUNT AND EMPTINESS TESTS ====================

    /**
     * Test that count method returns correct number of items.
     */
    public function test_count_returns_correct_number_of_items(): void
    {
        // Arrange
        $collection = new IntTypedCollection;

        // Act & Assert
        $this->assertCount(0, $collection);
        $this->assertSame(0, $collection->count());

        $collection->add(1, 2, 3);
        $this->assertCount(3, $collection);
        $this->assertSame(3, $collection->count());

        $collection->add(4);
        $this->assertCount(4, $collection);
    }

    /**
     * Test that isEmpty returns true for empty collection.
     */
    public function test_is_empty_returns_true_when_collection_empty(): void
    {
        // Arrange
        $collection = new StringTypedCollection;

        // Act & Assert
        $this->assertTrue($collection->isEmpty());
        $this->assertFalse($collection->isNotEmpty());
    }

    /**
     * Test that isEmpty returns false for non-empty collection.
     */
    public function test_is_empty_returns_false_when_collection_not_empty(): void
    {
        // Arrange
        $collection = new StringTypedCollection;
        $collection->add('test');

        // Act & Assert
        $this->assertFalse($collection->isEmpty());
        $this->assertTrue($collection->isNotEmpty());
    }

    // ==================== MAP TRANSFORMATION TESTS ====================

    /**
     * Test that map transforms each item and returns a new collection.
     */
    public function test_map_transforms_each_item_and_returns_new_collection(): void
    {
        // Arrange
        $this->intCollection->add(1, 2, 3, 4, 5);

        // Act
        $doubled = $this->intCollection->map(fn ($item) => $item * 2);

        // Assert
        $this->assertNotSame($this->intCollection, $doubled);
        $this->assertInstanceOf(IntTypedCollection::class, $doubled);
        $this->assertSame([2, 4, 6, 8, 10], $doubled->toArray());
        $this->assertSame([1, 2, 3, 4, 5], $this->intCollection->toArray()); // Original unchanged
    }

    /**
     * Test that map can change the type of collection.
     */
    public function test_map_can_change_collection_type_based_on_return_value(): void
    {
        // Arrange
        $this->intCollection->add(1, 2, 3);

        // Act
        $stringCollection = $this->intCollection->map(fn ($item) => "Number: {$item}");

        // Assert
        $this->assertInstanceOf(StringTypedCollection::class, $stringCollection);
        $this->assertSame(['Number: 1', 'Number: 2', 'Number: 3'], $stringCollection->toArray());
    }

    /**
     * Test that map on empty collection returns empty collection.
     */
    public function test_map_on_empty_collection_returns_empty_collection(): void
    {
        // Arrange
        $emptyCollection = new IntTypedCollection;

        // Act
        $result = $emptyCollection->map(fn ($item) => $item * 2);

        // Assert
        $this->assertCount(0, $result);
        $this->assertEmpty($result->toArray());
    }

    /**
     * Test that map preserves keys (sequential).
     */
    public function test_map_preserves_sequential_order(): void
    {
        // Arrange
        $this->stringCollection->add('a', 'b', 'c');

        // Act
        $uppercase = $this->stringCollection->map(fn ($item) => strtoupper($item));

        // Assert
        $this->assertSame(['A', 'B', 'C'], $uppercase->toArray());
    }

    // ==================== FILTER TESTS ====================

    /**
     * Test that filter keeps only items satisfying the callback.
     */
    public function test_filter_keeps_only_items_satisfying_callback(): void
    {
        // Arrange
        $this->intCollection->add(1, 2, 3, 4, 5, 6, 7, 8, 9, 10);

        // Act
        $evenNumbers = $this->intCollection->filter(fn ($item) => $item % 2 === 0);

        // Assert
        $this->assertSame([2, 4, 6, 8, 10], $evenNumbers->toArray());
        $this->assertCount(5, $evenNumbers);
    }

    /**
     * Test that filter returns empty collection when no items match.
     */
    public function test_filter_returns_empty_collection_when_no_items_match(): void
    {
        // Arrange
        $this->intCollection->add(1, 3, 5, 7, 9);

        // Act
        $evenNumbers = $this->intCollection->filter(fn ($item) => $item % 2 === 0);

        // Assert
        $this->assertCount(0, $evenNumbers);
        $this->assertTrue($evenNumbers->isEmpty());
    }

    /**
     * Test that filter returns new collection instance.
     */
    public function test_filter_returns_new_collection_instance(): void
    {
        // Arrange
        $this->intCollection->add(1, 2, 3);

        // Act
        $filtered = $this->intCollection->filter(fn ($item) => $item > 1);

        // Assert
        $this->assertNotSame($this->intCollection, $filtered);
        $this->assertSame([1, 2, 3], $this->intCollection->toArray()); // Original unchanged
        $this->assertSame([2, 3], $filtered->toArray());
    }

    /**
     * Test that filter with string collection works correctly.
     */
    public function test_filter_works_with_string_collection(): void
    {
        // Arrange
        $this->stringCollection->add('apple', 'banana', 'cherry', 'date', 'elderberry');

        // Act
        $longStrings = $this->stringCollection->filter(fn ($item) => strlen($item) > 5);

        // Assert
        $this->assertSame(['banana', 'cherry', 'elderberry'], $longStrings->toArray());
    }

    // ==================== REDUCE TESTS ====================

    /**
     * Test that reduce aggregates values correctly.
     */
    public function test_reduce_aggregates_values_correctly(): void
    {
        // Arrange
        $this->intCollection->add(1, 2, 3, 4, 5);

        // Act
        $sum = $this->intCollection->reduce(fn ($carry, $item) => $carry + $item, 0);

        // Assert
        $this->assertSame(15, $sum);
    }

    /**
     * Test that reduce with string concatenation works.
     */
    public function test_reduce_with_string_concatenation(): void
    {
        // Arrange
        $this->stringCollection->add('Hello', ' ', 'World', '!');

        // Act
        $result = $this->stringCollection->reduce(fn ($carry, $item) => $carry.$item, '');

        // Assert
        $this->assertSame('Hello World!', $result);
    }

    /**
     * Test that reduce with multiplication works.
     */
    public function test_reduce_with_multiplication(): void
    {
        // Arrange
        $this->intCollection->add(1, 2, 3, 4);

        // Act
        $product = $this->intCollection->reduce(fn ($carry, $item) => $carry * $item, 1);

        // Assert
        $this->assertSame(24, $product);
    }

    /**
     * Test that reduce on empty collection returns initial value.
     */
    public function test_reduce_on_empty_collection_returns_initial_value(): void
    {
        // Arrange
        $emptyCollection = new IntTypedCollection;

        // Act
        $result = $emptyCollection->reduce(fn ($carry, $item) => $carry + $item, 100);

        // Assert
        $this->assertSame(100, $result);
    }

    // ==================== FIND TESTS ====================

    /**
     * Test that find returns first item matching predicate.
     */
    public function test_find_returns_first_item_matching_predicate(): void
    {
        // Arrange
        $this->intCollection->add(1, 3, 5, 6, 7, 8);

        // Act
        $firstEven = $this->intCollection->find(fn ($item) => $item % 2 === 0);

        // Assert
        $this->assertSame(6, $firstEven);
    }

    /**
     * Test that find returns null when no item matches.
     */
    public function test_find_returns_null_when_no_item_matches(): void
    {
        // Arrange
        $this->intCollection->add(1, 3, 5, 7);

        // Act
        $result = $this->intCollection->find(fn ($item) => $item % 2 === 0);

        // Assert
        $this->assertNull($result);
    }

    /**
     * Test that find returns first match even when multiple exist.
     */
    public function test_find_returns_first_match_only(): void
    {
        // Arrange
        $this->stringCollection->add('apple', 'banana', 'avocado', 'apricot');

        // Act
        $firstA = $this->stringCollection->find(fn ($item) => str_starts_with($item, 'a'));

        // Assert
        $this->assertSame('apple', $firstA);
    }

    /**
     * Test that find on empty collection returns null.
     */
    public function test_find_on_empty_collection_returns_null(): void
    {
        // Arrange
        $emptyCollection = new StringTypedCollection;

        // Act
        $result = $emptyCollection->find(fn ($item) => true);

        // Assert
        $this->assertNull($result);
    }

    // ==================== EVERY AND SOME TESTS ====================

    /**
     * Test that every returns true when all items satisfy predicate.
     */
    public function test_every_returns_true_when_all_items_satisfy_predicate(): void
    {
        // Arrange
        $this->intCollection->add(2, 4, 6, 8, 10);

        // Act
        $allEven = $this->intCollection->every(fn ($item) => $item % 2 === 0);

        // Assert
        $this->assertTrue($allEven);
    }

    /**
     * Test that every returns false when any item fails predicate.
     */
    public function test_every_returns_false_when_any_item_fails_predicate(): void
    {
        // Arrange
        $this->intCollection->add(2, 4, 5, 6, 8);

        // Act
        $allEven = $this->intCollection->every(fn ($item) => $item % 2 === 0);

        // Assert
        $this->assertFalse($allEven);
    }

    /**
     * Test that every on empty collection returns true (vacuously true).
     */
    public function test_every_on_empty_collection_returns_true(): void
    {
        // Arrange
        $emptyCollection = new IntTypedCollection;

        // Act
        $result = $emptyCollection->every(fn ($item) => $item > 100);

        // Assert
        $this->assertTrue($result);
    }

    /**
     * Test that some returns true when at least one item satisfies predicate.
     */
    public function test_some_returns_true_when_at_least_one_item_satisfies_predicate(): void
    {
        // Arrange
        $this->intCollection->add(1, 3, 5, 6, 7, 9);

        // Act
        $hasEven = $this->intCollection->some(fn ($item) => $item % 2 === 0);

        // Assert
        $this->assertTrue($hasEven);
    }

    /**
     * Test that some returns false when no items satisfy predicate.
     */
    public function test_some_returns_false_when_no_items_satisfy_predicate(): void
    {
        // Arrange
        $this->intCollection->add(1, 3, 5, 7, 9);

        // Act
        $hasEven = $this->intCollection->some(fn ($item) => $item % 2 === 0);

        // Assert
        $this->assertFalse($hasEven);
    }

    /**
     * Test that some on empty collection returns false.
     */
    public function test_some_on_empty_collection_returns_false(): void
    {
        // Arrange
        $emptyCollection = new IntTypedCollection;

        // Act
        $result = $emptyCollection->some(fn ($item) => true);

        // Assert
        $this->assertFalse($result);
    }

    // ==================== SORT TESTS ====================

    /**
     * Test that sort orders items in ascending order.
     */
    public function test_sort_orders_items_in_ascending_order(): void
    {
        // Arrange
        $this->intCollection->add(5, 2, 8, 1, 9, 3);

        // Act
        $sorted = $this->intCollection->sort();

        // Assert
        $this->assertSame([1, 2, 3, 5, 8, 9], $sorted->toArray());
    }

    /**
     * Test that sort returns new collection instance.
     */
    public function test_sort_returns_new_collection_instance(): void
    {
        // Arrange
        $this->intCollection->add(3, 1, 2);

        // Act
        $sorted = $this->intCollection->sort();

        // Assert
        $this->assertNotSame($this->intCollection, $sorted);
        $this->assertSame([1, 2, 3], $sorted->toArray());
        $this->assertSame([3, 1, 2], $this->intCollection->toArray()); // Original unchanged
    }

    /**
     * Test that sort with SORT_NUMERIC flag works.
     */
    public function test_sort_with_numeric_flag_works(): void
    {
        // Arrange
        $collection = new TypedCollection('int', 'float');
        $collection->add(5, 2.5, 8, 1.2, 9, 3.7);

        // Act
        $sorted = $collection->sort(SORT_NUMERIC);

        // Assert
        $this->assertSame([1.2, 2.5, 3.7, 5, 8, 9], $sorted->toArray());
    }

    /**
     * Test that sort on empty collection returns empty collection.
     */
    public function test_sort_on_empty_collection_returns_empty_collection(): void
    {
        // Arrange
        $emptyCollection = new IntTypedCollection;

        // Act
        $sorted = $emptyCollection->sort();

        // Assert
        $this->assertCount(0, $sorted);
        $this->assertEmpty($sorted->toArray());
    }

    // ==================== REVERSE TESTS ====================

    /**
     * Test that reverse reverses the order of items.
     */
    public function test_reverse_reverses_order_of_items(): void
    {
        // Arrange
        $this->intCollection->add(1, 2, 3, 4, 5);

        // Act
        $reversed = $this->intCollection->reverse();

        // Assert
        $this->assertSame([5, 4, 3, 2, 1], $reversed->toArray());
    }

    /**
     * Test that reverse returns new collection instance.
     */
    public function test_reverse_returns_new_collection_instance(): void
    {
        // Arrange
        $this->stringCollection->add('a', 'b', 'c');

        // Act
        $reversed = $this->stringCollection->reverse();

        // Assert
        $this->assertNotSame($this->stringCollection, $reversed);
        $this->assertSame(['c', 'b', 'a'], $reversed->toArray());
        $this->assertSame(['a', 'b', 'c'], $this->stringCollection->toArray());
    }

    /**
     * Test that reverse on single item returns same item.
     */
    public function test_reverse_on_single_item_returns_single_item(): void
    {
        // Arrange
        $this->intCollection->add(42);

        // Act
        $reversed = $this->intCollection->reverse();

        // Assert
        $this->assertSame([42], $reversed->toArray());
    }

    /**
     * Test that reverse on empty collection returns empty collection.
     */
    public function test_reverse_on_empty_collection_returns_empty_collection(): void
    {
        // Arrange
        $emptyCollection = new IntTypedCollection;

        // Act
        $reversed = $emptyCollection->reverse();

        // Assert
        $this->assertCount(0, $reversed);
    }

    // ==================== MERGE TESTS ====================

    /**
     * Test that merge combines two collections.
     */
    public function test_merge_combines_two_collections(): void
    {
        // Arrange
        $collection1 = new IntTypedCollection;
        $collection2 = new IntTypedCollection;
        $collection1->add(1, 2, 3);
        $collection2->add(4, 5, 6);

        // Act
        $merged = $collection1->merge($collection2);

        // Assert
        $this->assertSame([1, 2, 3, 4, 5, 6], $merged->toArray());
        $this->assertCount(6, $merged);
    }

    /**
     * Test that merge returns new collection instance.
     */
    public function test_merge_returns_new_collection_instance(): void
    {
        // Arrange
        $collection1 = new IntTypedCollection;
        $collection2 = new IntTypedCollection;
        $collection1->add(1, 2);
        $collection2->add(3, 4);

        // Act
        $merged = $collection1->merge($collection2);

        // Assert
        $this->assertNotSame($collection1, $merged);
        $this->assertNotSame($collection2, $merged);
        $this->assertSame([1, 2], $collection1->toArray());
        $this->assertSame([3, 4], $collection2->toArray());
    }

    /**
     * Test that merge with empty collection returns original items.
     */
    public function test_merge_with_empty_collection_returns_original_items(): void
    {
        // Arrange
        $collection1 = new IntTypedCollection;
        $emptyCollection = new IntTypedCollection;
        $collection1->add(1, 2, 3);

        // Act
        $merged = $collection1->merge($emptyCollection);

        // Assert
        $this->assertSame([1, 2, 3], $merged->toArray());
    }

    /**
     * Test that merge preserves allowed types.
     */
    public function test_merge_preserves_allowed_types(): void
    {
        // Arrange
        $collection1 = new TypedCollection('int', 'string');
        $collection2 = new TypedCollection('int', 'string');
        $collection1->add(1, 'two');
        $collection2->add(3, 'four');

        // Act
        $merged = $collection1->merge($collection2);

        // Assert
        $this->assertSame(['int', 'string'], $merged->getAllowedTypes());
        $this->assertSame([1, 'two', 3, 'four'], $merged->toArray());
    }

    // ==================== CONTAINS TESTS ====================

    /**
     * Test that contains returns true when value exists.
     */
    public function test_contains_returns_true_when_value_exists(): void
    {
        // Arrange
        $this->stringCollection->add('apple', 'banana', 'cherry');

        // Act & Assert
        $this->assertTrue($this->stringCollection->contains('banana'));
        $this->assertTrue($this->stringCollection->contains('apple'));
        $this->assertTrue($this->stringCollection->contains('cherry'));
    }

    /**
     * Test that contains returns false when value does not exist.
     */
    public function test_contains_returns_false_when_value_does_not_exist(): void
    {
        // Arrange
        $this->stringCollection->add('apple', 'banana', 'cherry');

        // Act & Assert
        $this->assertFalse($this->stringCollection->contains('grape'));
        $this->assertFalse($this->stringCollection->contains('orange'));
    }

    /**
     * Test that contains with strict comparison works for enums.
     */
    public function test_contains_with_enum_values_works(): void
    {
        // Arrange
        $collection = new TypedCollection(TestUserStatus::class);
        $collection->add(TestUserStatus::ACTIVE, TestUserStatus::INACTIVE);

        // Act & Assert
        $this->assertTrue($collection->contains(TestUserStatus::ACTIVE));
        $this->assertTrue($collection->contains(TestUserStatus::INACTIVE));
        $this->assertFalse($collection->contains(TestUserStatus::SUSPENDED));
    }

    /**
     * Test that contains with objects works correctly.
     */
    public function test_contains_with_objects_works(): void
    {
        // Arrange
        $user1 = new TestUserRecord(name: 'John', email: TestEmailAddress::fromString('john@test.com'));
        $user2 = new TestUserRecord(name: 'Jane', email: TestEmailAddress::fromString('jane@test.com'));
        $collection = new TypedCollection(TestUserRecord::class);
        $collection->add($user1, $user2);

        // Act & Assert
        $this->assertTrue($collection->contains($user1));
        $this->assertTrue($collection->contains($user2));

        $newUser = new TestUserRecord(name: 'John', email: TestEmailAddress::fromString('john@test.com'));
        $this->assertFalse($collection->contains($newUser)); // Different instance
    }

    // ==================== EACH (ITERATION) TESTS ====================

    /**
     * Test that each executes callback for each item.
     */
    public function test_each_executes_callback_for_each_item(): void
    {
        // Arrange
        $this->intCollection->add(1, 2, 3);
        $sum = 0;

        // Act
        $result = $this->intCollection->each(function ($item) use (&$sum) {
            $sum += $item;
        });

        // Assert
        $this->assertSame(6, $sum);
        $this->assertSame($this->intCollection, $result); // Returns self for chaining
    }

    /**
     * Test that each returns the collection instance for chaining.
     */
    public function test_each_returns_self_for_chaining(): void
    {
        // Arrange
        $this->intCollection->add(1, 2, 3);
        $logs = [];

        // Act
        $result = $this->intCollection->each(function ($item) use (&$logs) {
            $logs[] = $item;
        })->add(4, 5);

        // Assert
        $this->assertSame($this->intCollection, $result);
        $this->assertSame([1, 2, 3, 4, 5], $this->intCollection->toArray());
    }

    /**
     * Test that each on empty collection does nothing.
     */
    public function test_each_on_empty_collection_does_nothing(): void
    {
        // Arrange
        $emptyCollection = new IntTypedCollection;
        $counter = 0;

        // Act
        $emptyCollection->each(function () use (&$counter) {
            $counter++;
        });

        // Assert
        $this->assertSame(0, $counter);
    }

    // ==================== ARRAY ACCESS TESTS ====================

    /**
     * Test that collection implements ArrayAccess for reading.
     */
    public function test_array_access_offset_get_returns_item_at_index(): void
    {
        // Arrange
        $this->stringCollection->add('apple', 'banana', 'cherry');

        // Act & Assert
        $this->assertSame('apple', $this->stringCollection[0]);
        $this->assertSame('banana', $this->stringCollection[1]);
        $this->assertSame('cherry', $this->stringCollection[2]);
    }

    /**
     * Test that array access offset get returns null for invalid index.
     */
    public function test_array_access_offset_get_returns_null_for_invalid_index(): void
    {
        // Arrange
        $this->intCollection->add(1, 2, 3);

        // Act & Assert
        $this->assertNull($this->intCollection[10]);
        $this->assertNull($this->intCollection[-1]);
    }

    /**
     * Test that array access offset exists returns correct boolean.
     */
    public function test_array_access_offset_exists_checks_index_existence(): void
    {
        // Arrange
        $this->intCollection->add(1, 2, 3);

        // Act & Assert
        $this->assertTrue(isset($this->intCollection[0]));
        $this->assertTrue(isset($this->intCollection[1]));
        $this->assertTrue(isset($this->intCollection[2]));
        $this->assertFalse(isset($this->intCollection[3]));
    }

    /**
     * Test that array access offset set adds item at specified index.
     */
    public function test_array_access_offset_set_adds_item_at_specified_index(): void
    {
        // Arrange
        $collection = new StringTypedCollection;
        $collection->add('first', 'second');

        // Act
        $collection[2] = 'third';

        // Assert
        $this->assertSame('third', $collection[2]);
        $this->assertCount(3, $collection);
    }

    /**
     * Test that array access offset set with null index appends.
     */
    public function test_array_access_offset_set_with_null_index_appends(): void
    {
        // Arrange
        $collection = new StringTypedCollection;
        $collection[] = 'first';
        $collection[] = 'second';
        $collection[] = 'third';

        // Assert
        $this->assertSame(['first', 'second', 'third'], $collection->toArray());
    }

    /**
     * Test that array access offset set validates type.
     */
    public function test_array_access_offset_set_validates_type(): void
    {
        // Arrange
        $collection = new IntTypedCollection;

        // Act & Assert
        $this->expectException(InvalidArgumentException::class);
        $collection[0] = 'not an integer';
    }

    /**
     * Test that array access offset unset removes item.
     */
    public function test_array_access_offset_unset_removes_item(): void
    {
        // Arrange
        $this->stringCollection->add('apple', 'banana', 'cherry');

        // Act
        unset($this->stringCollection[1]);

        // Assert
        $this->assertCount(2, $this->stringCollection);
        $this->assertSame('apple', $this->stringCollection[0]);
        $this->assertSame('cherry', $this->stringCollection[2]);
        $this->assertArrayNotHasKey(1, $this->stringCollection->toArray());
    }

    // ==================== ITERATOR TESTS ====================

    /**
     * Test that collection is traversable with foreach.
     */
    public function test_collection_is_iterable_with_foreach(): void
    {
        // Arrange
        $this->intCollection->add(1, 2, 3, 4, 5);
        $items = [];

        // Act
        foreach ($this->intCollection as $item) {
            $items[] = $item;
        }

        // Assert
        $this->assertSame([1, 2, 3, 4, 5], $items);
    }

    /**
     * Test that getIterator returns ArrayIterator.
     */
    public function test_get_iterator_returns_array_iterator(): void
    {
        // Arrange
        $this->intCollection->add(1, 2, 3);

        // Act
        $iterator = $this->intCollection->getIterator();

        // Assert
        $this->assertInstanceOf(\ArrayIterator::class, $iterator);
        $this->assertSame([1, 2, 3], iterator_to_array($iterator));
    }

    // ==================== TO_ARRAY TESTS ====================

    /**
     * Test that toArray returns plain array of items.
     */
    public function test_to_array_returns_plain_array(): void
    {
        // Arrange
        $this->stringCollection->add('a', 'b', 'c');

        // Act
        $array = $this->stringCollection->toArray();

        // Assert
        $this->assertIsArray($array);
        $this->assertSame(['a', 'b', 'c'], $array);
    }

    /**
     * Test that toArray on empty collection returns empty array.
     */
    public function test_to_array_on_empty_collection_returns_empty_array(): void
    {
        // Arrange
        $emptyCollection = new StringTypedCollection;

        // Act
        $array = $emptyCollection->toArray();

        // Assert
        $this->assertIsArray($array);
        $this->assertEmpty($array);
    }

    // ==================== JSON SERIALIZATION TESTS ====================

    /**
     * Test that collection implements JsonSerializable.
     */
    public function test_collection_implements_json_serializable(): void
    {
        // Assert
        $this->assertInstanceOf(\JsonSerializable::class, $this->intCollection);
    }

    /**
     * Test that jsonSerialize returns items array.
     */
    public function test_json_serialize_returns_items_array(): void
    {
        // Arrange
        $this->intCollection->add(1, 2, 3);

        // Act
        $serialized = $this->intCollection->jsonSerialize();

        // Assert
        $this->assertSame([1, 2, 3], $serialized);
    }

    /**
     * Test that collection can be JSON encoded.
     */
    public function test_collection_can_be_json_encoded(): void
    {
        // Arrange
        $this->intCollection->add(1, 2, 3);

        // Act
        $json = json_encode($this->intCollection);

        // Assert
        $this->assertSame('[1,2,3]', $json);
    }

    // ==================== NORMALIZATION TESTS ====================

    /**
     * Test that normalize returns array by default.
     */
    public function test_normalize_returns_array_by_default(): void
    {
        // Arrange
        $this->intCollection->add(1, 2, 3);

        // Act
        $result = $this->intCollection->normalize();

        // Assert
        $this->assertIsArray($result);
        $this->assertSame([1, 2, 3], $result);
    }

    /**
     * Test that normalize returns JSON string when mode is JSON.
     */
    public function test_normalize_returns_json_string_when_mode_json(): void
    {
        // Arrange
        $this->stringCollection->add('a', 'b', 'c');

        // Act
        $result = $this->stringCollection->normalize(NormalizeMode::JSON);

        // Assert
        $this->assertIsString($result);
        $this->assertJson($result);
        $this->assertSame('["a","b","c"]', $result);
    }

    /**
     * Test that normalize handles nested objects.
     */
    public function test_normalize_handles_nested_objects(): void
    {
        // Arrange
        $product1 = new TestProductRecord(id: 1, name: 'Product 1', price: 100);
        $product2 = new TestProductRecord(id: 2, name: 'Product 2', price: 200);
        $this->productCollection->add($product1, $product2);

        // Act
        $result = $this->productCollection->normalize();

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertSame([
            ['id' => 1, 'name' => 'Product 1', 'price' => 100, 'is_featured' => null, 'metadata' => null],
            ['id' => 2, 'name' => 'Product 2', 'price' => 200, 'is_featured' => null, 'metadata' => null],
        ], $result);
    }

    /**
     * Test that normalize with includeNulls false excludes null values.
     */
    public function test_normalize_with_include_nulls_false_excludes_null_values(): void
    {
        // Arrange
        $product = new TestProductRecord(id: null, name: 'Test', price: null);
        $this->productCollection->add($product);

        // Act
        $result = $this->productCollection->normalize(NormalizeMode::ARRAY, false);

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertArrayNotHasKey('id', $result[0]);
        $this->assertArrayNotHasKey('price', $result[0]);
        $this->assertArrayHasKey('name', $result[0]);
    }

    // ==================== TO_STRING TESTS ====================

    /**
     * Test that __toString returns string representation.
     */
    public function test_to_string_returns_string_representation(): void
    {
        // Arrange
        $collection = new TypedCollection('int', 'string');
        $collection->add(1, 'two', 3);

        // Act
        $string = (string) $collection;

        // Assert
        $this->assertIsString($string);
        $this->assertStringContainsString('int|string', $string);
        $this->assertStringContainsString('3 items', $string);
    }

    // ==================== CLONING TESTS ====================

    /**
     * Test that __clone creates deep copy of collection.
     */
    public function test_clone_creates_deep_copy_of_collection(): void
    {
        // Arrange
        $original = new IntTypedCollection;
        $original->add(1, 2, 3);

        // Act
        $cloned = clone $original;
        $cloned->add(4, 5);

        // Assert
        $this->assertSame([1, 2, 3], $original->toArray());
        $this->assertSame([1, 2, 3, 4, 5], $cloned->toArray());
    }

    /**
     * Test that clone creates deep copy of object items.
     */
    public function test_clone_creates_deep_copy_of_object_items(): void
    {
        // Arrange
        $original = new TypedCollection(TestUserRecord::class);
        $user = new TestUserRecord(name: 'John', email: TestEmailAddress::fromString('john@test.com'));
        $original->add($user);

        // Act
        $cloned = clone $original;
        $firstOriginal = $original->toArray()[0];
        $firstCloned = $cloned->toArray()[0];

        // Assert
        $this->assertNotSame($firstOriginal, $firstCloned);
        $this->assertEquals($firstOriginal, $firstCloned);
    }

    // ==================== COMPLEX WORKFLOW TESTS ====================

    /**
     * Test complete workflow with chaining operations.
     */
    public function test_complete_workflow_with_chaining_operations(): void
    {
        // Arrange
        $collection = new IntTypedCollection;
        $collection->add(5, 2, 8, 1, 9, 3, 6, 4, 7);

        // Act - Chain multiple operations
        $result = $collection
            ->filter(fn ($n) => $n % 2 === 0)        // Keep even: 2,8,6,4
            ->map(fn ($n) => $n * 10)               // Multiply by 10: 20,80,60,40
            ->sort()                                // Sort: 20,40,60,80
            ->reverse();                            // Reverse: 80,60,40,20

        // Assert
        $this->assertSame([80, 60, 40, 20], $result->toArray());
        $this->assertCount(4, $result);
    }

    /**
     * Test complex workflow with custom objects and transformations.
     */
    public function test_complex_workflow_with_custom_objects(): void
    {
        // Arrange
        $products = new ProductRecordCollection;
        $products->add(
            new TestProductRecord(id: 1, name: 'Laptop', price: 999, isFeatured: true),
            new TestProductRecord(id: 2, name: 'Mouse', price: 29, isFeatured: false),
            new TestProductRecord(id: 3, name: 'Keyboard', price: 89, isFeatured: true),
            new TestProductRecord(id: 4, name: 'Monitor', price: 299, isFeatured: false),
            new TestProductRecord(id: 5, name: 'Desk', price: 499, isFeatured: true),
        );

        // Act - Complex transformation pipeline
        $featuredProductNames = $products
            ->filter(fn ($product) => $product->isFeatured === true)  // Only featured
            ->filter(fn ($product) => ($product->price ?? 0) > 100)   // Price > 100
            ->map(fn ($product) => $product->name)                    // Get names only
            ->sort();                                                 // Sort alphabetically

        // Assert
        $this->assertCount(2, $featuredProductNames);
        $this->assertSame(['Desk', 'Laptop'], $featuredProductNames->toArray());
    }

    /**
     * Test collection with enums and string operations.
     */
    public function test_collection_with_enums_and_string_operations(): void
    {
        // Arrange
        $enums = new TypedCollection(TestBackedStringEnum::class);
        $enums->add(
            TestBackedStringEnum::VALUE_ONE,
            TestBackedStringEnum::VALUE_TWO,
            TestBackedStringEnum::VALUE_THREE
        );

        // Act
        $values = $enums->map(fn ($enum) => $enum->value)->toArray();

        // Assert
        $this->assertSame(['one', 'two', 'three'], $values);
    }

    /**
     * Test nested collections within collections.
     */
    public function test_nested_collections_work_correctly(): void
    {
        // Arrange
        $innerCollection = new StringTypedCollection;
        $innerCollection->add('a', 'b', 'c');

        $outerCollection = new TypedCollection(StringTypedCollection::class);
        $outerCollection->add($innerCollection);

        // Act
        $flattened = $outerCollection
            ->map(fn ($collection) => $collection->toArray())
            ->toArray();

        // Assert
        $this->assertSame([['a', 'b', 'c']], $flattened);
    }

    /**
     * Test collection with nullable values handling.
     */
    public function test_collection_handles_null_values_correctly(): void
    {
        // Arrange
        $collection = new TypedCollection('int', 'null');
        $collection->add(1, null, 2, null, 3);

        // Act
        $withoutNulls = $collection->filter(fn ($item) => $item !== null);

        // Assert
        $this->assertCount(5, $collection);
        $this->assertCount(3, $withoutNulls);
        $this->assertSame([1, 2, 3], $withoutNulls->toArray());
    }

    /**
     * Test performance-oriented workflow with reduce and map combination.
     */
    public function test_performance_workflow_with_reduce_and_map(): void
    {
        // Arrange
        $collection = new IntTypedCollection;
        $collection->add(1, 2, 3, 4, 5, 6, 7, 8, 9, 10);

        // Act - Calculate sum of squares of even numbers
        $result = $collection
            ->filter(fn ($n) => $n % 2 === 0)
            ->map(fn ($n) => $n * $n)
            ->reduce(fn ($carry, $n) => $carry + $n, 0);

        // Assert: 2² + 4² + 6² + 8² + 10² = 4 + 16 + 36 + 64 + 100 = 220
        $this->assertSame(220, $result);
    }

    /**
     * Test all method returns a new collection with same items.
     */
    public function test_all_returns_new_collection_with_same_items(): void
    {
        // Arrange
        $this->intCollection->add(1, 2, 3);

        // Act
        $newCollection = $this->intCollection->all();

        // Assert
        $this->assertNotSame($this->intCollection, $newCollection);
        $this->assertEquals($this->intCollection->toArray(), $newCollection->toArray());
    }

    /**
     * Test getAllowedTypes returns the configured types.
     */
    public function test_get_allowed_types_returns_configured_types(): void
    {
        // Arrange
        $collection = new TypedCollection('int', 'string', 'float');

        // Act
        $types = $collection->getAllowedTypes();

        // Assert
        $this->assertSame(['int', 'string', 'float'], $types);
    }
}
