<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Unit\EdgeCases;

use AndyDefer\DomainStructures\Collections\Core\DataCollection;
use AndyDefer\DomainStructures\Collections\Core\RecordCollection;
use AndyDefer\DomainStructures\Collections\Core\TypedCollection;
use AndyDefer\DomainStructures\Collections\Utility\BoolTypedCollection;
use AndyDefer\DomainStructures\Collections\Utility\FloatTypedCollection;
use AndyDefer\DomainStructures\Collections\Utility\IntTypedCollection;
use AndyDefer\DomainStructures\Collections\Utility\NumberTypedCollection;
use AndyDefer\DomainStructures\Collections\Utility\StringTypedCollection;
use AndyDefer\DomainStructures\Enums\NormalizeMode;
use AndyDefer\DomainStructures\Tests\Fixtures\ValueObjects\TestEmailAddress;
use AndyDefer\DomainStructures\Tests\TestCase;

/**
 * Edge case tests for empty collections.
 *
 * This test suite validates that all collection types behave correctly
 * when empty, including:
 * - Count and emptiness checks
 * - Operations on empty collections (map, filter, reduce, etc.)
 * - Normalization of empty collections
 * - JSON serialization
 * - Method chaining with empty results
 *
 * The tests follow the AAA pattern (Arrange, Act, Assert).
 */
final class EmptyCollectionsTest extends TestCase
{
    private TestEmailAddress $testEmail;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testEmail = TestEmailAddress::from('test@example.com');
    }

    // ==================== BASIC EMPTINESS TESTS ====================

    /**
     * Test that all collection types start empty.
     */
    public function test_all_collection_types_start_empty(): void
    {
        // Arrange & Act
        $typedCollection = new TypedCollection('int');
        $intCollection = new IntTypedCollection;
        $floatCollection = new FloatTypedCollection;
        $stringCollection = new StringTypedCollection;
        $boolCollection = new BoolTypedCollection;
        $numberCollection = new NumberTypedCollection;
        $recordCollection = new RecordCollection;
        $dataCollection = new DataCollection;

        // Assert
        $this->assertTrue($typedCollection->isEmpty());
        $this->assertTrue($intCollection->isEmpty());
        $this->assertTrue($floatCollection->isEmpty());
        $this->assertTrue($stringCollection->isEmpty());
        $this->assertTrue($boolCollection->isEmpty());
        $this->assertTrue($numberCollection->isEmpty());
        $this->assertTrue($recordCollection->isEmpty());
        $this->assertTrue($dataCollection->isEmpty());
    }

    /**
     * Test that empty collection has count of zero.
     */
    public function test_empty_collection_has_count_zero(): void
    {
        // Arrange
        $collection = new TypedCollection('int');

        // Act & Assert
        $this->assertSame(0, $collection->count());
        $this->assertCount(0, $collection);
    }

    /**
     * Test that isEmpty returns true and isNotEmpty returns false.
     */
    public function test_is_empty_returns_true_is_not_empty_returns_false(): void
    {
        // Arrange
        $collection = new TypedCollection('int');

        // Assert
        $this->assertTrue($collection->isEmpty());
        $this->assertFalse($collection->isNotEmpty());
    }

    // ==================== TO_ARRAY ON EMPTY COLLECTION TESTS ====================

    /**
     * Test that toArray on empty collection returns empty array.
     */
    public function test_to_array_on_empty_collection_returns_empty_array(): void
    {
        // Arrange
        $collections = [
            new TypedCollection('int'),
            new IntTypedCollection,
            new FloatTypedCollection,
            new StringTypedCollection,
            new BoolTypedCollection,
            new NumberTypedCollection,
            new RecordCollection,
            new DataCollection,
        ];

        foreach ($collections as $collection) {
            // Act
            $array = $collection->toArray();

            // Assert
            $this->assertIsArray($array);
            $this->assertEmpty($array);
        }
    }

    // ==================== ALL METHOD ON EMPTY COLLECTION TESTS ====================

    /**
     * Test that all on empty collection returns empty collection.
     */
    public function test_all_on_empty_collection_returns_empty_collection(): void
    {
        // Arrange
        $emptyCollection = new TypedCollection('int');

        // Act
        $result = $emptyCollection->all();

        // Assert
        $this->assertNotSame($emptyCollection, $result);
        $this->assertCount(0, $result);
        $this->assertTrue($result->isEmpty());
        $this->assertSame(['int'], $result->getAllowedTypes());
    }

    // ==================== MAP ON EMPTY COLLECTION TESTS ====================

    /**
     * Test that map on empty collection returns empty collection.
     */
    public function test_map_on_empty_collection_returns_empty_collection(): void
    {
        // Arrange
        $emptyCollection = new IntTypedCollection;

        // Act
        $result = $emptyCollection->map(fn($item) => $item * 2);

        // Assert
        $this->assertCount(0, $result);
        $this->assertTrue($result->isEmpty());
    }

    /**
     * Test that map on empty collection preserves allowed types.
     */
    public function test_map_on_empty_collection_preserves_allowed_types(): void
    {
        // Arrange
        $emptyCollection = new TypedCollection('int', 'string', 'float');

        // Act
        $result = $emptyCollection->map(fn($item) => $item);

        // Assert
        $this->assertSame(['int', 'string', 'float'], $result->getAllowedTypes());
    }

    // ==================== FILTER ON EMPTY COLLECTION TESTS ====================

    /**
     * Test that filter on empty collection returns empty collection.
     */
    public function test_filter_on_empty_collection_returns_empty_collection(): void
    {
        // Arrange
        $emptyCollection = new IntTypedCollection;

        // Act
        $result = $emptyCollection->filter(fn($item) => $item > 0);

        // Assert
        $this->assertCount(0, $result);
        $this->assertTrue($result->isEmpty());
    }

    /**
     * Test that filter on empty collection returns new instance.
     */
    public function test_filter_on_empty_collection_returns_new_instance(): void
    {
        // Arrange
        $emptyCollection = new IntTypedCollection;

        // Act
        $result = $emptyCollection->filter(fn($item) => $item > 0);

        // Assert
        $this->assertNotSame($emptyCollection, $result);
    }

    // ==================== REDUCE ON EMPTY COLLECTION TESTS ====================

    /**
     * Test that reduce on empty collection returns initial value.
     */
    public function test_reduce_on_empty_collection_returns_initial_value(): void
    {
        // Arrange
        $emptyCollection = new IntTypedCollection;

        // Act
        $result = $emptyCollection->reduce(fn($carry, $item) => $carry + $item, 100);

        // Assert
        $this->assertSame(100, $result);
    }

    /**
     * Test that reduce on empty collection with null initial returns null.
     */
    public function test_reduce_on_empty_collection_with_null_initial_returns_null(): void
    {
        // Arrange
        $emptyCollection = new IntTypedCollection;

        // Act
        $result = $emptyCollection->reduce(fn($carry, $item) => $carry + $item, null);

        // Assert
        $this->assertNull($result);
    }

    // ==================== FIND ON EMPTY COLLECTION TESTS ====================

    /**
     * Test that find on empty collection returns null.
     */
    public function test_find_on_empty_collection_returns_null(): void
    {
        // Arrange
        $emptyCollection = new IntTypedCollection;

        // Act
        $result = $emptyCollection->find(fn($item) => true);

        // Assert
        $this->assertNull($result);
    }

    // ==================== EVERY ON EMPTY COLLECTION TESTS ====================

    /**
     * Test that every on empty collection returns true (vacuously true).
     */
    public function test_every_on_empty_collection_returns_true(): void
    {
        // Arrange
        $emptyCollection = new IntTypedCollection;

        // Act
        $result = $emptyCollection->every(fn($item) => $item > 100);

        // Assert
        $this->assertTrue($result);
    }

    // ==================== SOME ON EMPTY COLLECTION TESTS ====================

    /**
     * Test that some on empty collection returns false.
     */
    public function test_some_on_empty_collection_returns_false(): void
    {
        // Arrange
        $emptyCollection = new IntTypedCollection;

        // Act
        $result = $emptyCollection->some(fn($item) => true);

        // Assert
        $this->assertFalse($result);
    }

    // ==================== SORT ON EMPTY COLLECTION TESTS ====================

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
        $this->assertTrue($sorted->isEmpty());
    }

    // ==================== REVERSE ON EMPTY COLLECTION TESTS ====================

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
        $this->assertTrue($reversed->isEmpty());
    }

    // ==================== MERGE ON EMPTY COLLECTION TESTS ====================

    /**
     * Test that merging empty collection with another returns the other.
     */
    public function test_merge_empty_collection_with_non_empty_returns_non_empty(): void
    {
        // Arrange
        $emptyCollection = new IntTypedCollection;
        $nonEmptyCollection = new IntTypedCollection;
        $nonEmptyCollection->add(1, 2, 3);

        // Act
        $merged = $emptyCollection->merge($nonEmptyCollection);

        // Assert
        $this->assertCount(3, $merged);
        $this->assertSame([1, 2, 3], $merged->toArray());
    }

    /**
     * Test that merging non-empty with empty returns non-empty.
     */
    public function test_merge_non_empty_with_empty_collection_returns_non_empty(): void
    {
        // Arrange
        $nonEmptyCollection = new IntTypedCollection;
        $emptyCollection = new IntTypedCollection;
        $nonEmptyCollection->add(1, 2, 3);

        // Act
        $merged = $nonEmptyCollection->merge($emptyCollection);

        // Assert
        $this->assertCount(3, $merged);
        $this->assertSame([1, 2, 3], $merged->toArray());
    }

    /**
     * Test that merging two empty collections returns empty collection.
     */
    public function test_merge_two_empty_collections_returns_empty_collection(): void
    {
        // Arrange
        $empty1 = new IntTypedCollection;
        $empty2 = new IntTypedCollection;

        // Act
        $merged = $empty1->merge($empty2);

        // Assert
        $this->assertCount(0, $merged);
        $this->assertTrue($merged->isEmpty());
    }

    // ==================== CONTAINS ON EMPTY COLLECTION TESTS ====================

    /**
     * Test that contains on empty collection returns false.
     */
    public function test_contains_on_empty_collection_returns_false(): void
    {
        // Arrange
        $emptyCollection = new IntTypedCollection;

        // Act & Assert
        $this->assertFalse($emptyCollection->contains(1));
        $this->assertFalse($emptyCollection->contains(null));
        $this->assertFalse($emptyCollection->contains('test'));
    }

    // ==================== EACH ON EMPTY COLLECTION TESTS ====================

    /**
     * Test that each on empty collection does nothing.
     */
    public function test_each_on_empty_collection_does_nothing(): void
    {
        // Arrange
        $emptyCollection = new IntTypedCollection;
        $counter = 0;

        // Act
        $result = $emptyCollection->each(function () use (&$counter) {
            $counter++;
        });

        // Assert
        $this->assertSame(0, $counter);
        $this->assertSame($emptyCollection, $result);
    }

    // ==================== NORMALIZATION ON EMPTY COLLECTION TESTS ====================

    /**
     * Test that normalize on empty collection returns empty array.
     */
    public function test_normalize_on_empty_collection_returns_empty_array(): void
    {
        // Arrange
        $emptyCollection = new IntTypedCollection;

        // Act
        $result = $emptyCollection->normalize();

        // Assert
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * Test that normalize on empty collection returns empty JSON array.
     */
    public function test_normalize_on_empty_collection_returns_empty_json_array(): void
    {
        // Arrange
        $emptyCollection = new IntTypedCollection;

        // Act
        $json = $emptyCollection->normalize(NormalizeMode::JSON);

        // Assert
        $this->assertSame('[]', $json);
    }

    // ==================== JSON SERIALIZATION ON EMPTY COLLECTION TESTS ====================

    /**
     * Test that jsonSerialize on empty collection returns empty array.
     */
    public function test_json_serialize_on_empty_collection_returns_empty_array(): void
    {
        // Arrange
        $emptyCollection = new IntTypedCollection;

        // Act
        $serialized = $emptyCollection->jsonSerialize();

        // Assert
        $this->assertIsArray($serialized);
        $this->assertEmpty($serialized);
    }

    /**
     * Test that json_encode on empty collection returns '[]'.
     */
    public function test_json_encode_on_empty_collection_returns_empty_array_string(): void
    {
        // Arrange
        $emptyCollection = new IntTypedCollection;

        // Act
        $json = json_encode($emptyCollection);

        // Assert
        $this->assertSame('[]', $json);
    }

    // ==================== MAGIC TO_STRING ON EMPTY COLLECTION TESTS ====================

    /**
     * Test that __toString on empty collection returns JSON representation.
     */
    public function test_to_string_on_empty_collection_returns_json_representation(): void
    {
        // Arrange
        $emptyCollection = new StringTypedCollection;

        // Act
        $string = (string) $emptyCollection;

        // Assert
        $this->assertSame('[]', $string);
    }

    // ==================== ARRAY ACCESS ON EMPTY COLLECTION TESTS ====================

    /**
     * Test that array access offset exists returns false for empty collection.
     */
    public function test_array_access_offset_exists_returns_false_for_empty_collection(): void
    {
        // Arrange
        $emptyCollection = new StringTypedCollection;

        // Act & Assert
        $this->assertFalse(isset($emptyCollection[0]));
        $this->assertFalse(isset($emptyCollection[100]));
    }

    /**
     * Test that array access offset get returns null for empty collection.
     */
    public function test_array_access_offset_get_returns_null_for_empty_collection(): void
    {
        // Arrange
        $emptyCollection = new StringTypedCollection;

        // Act & Assert
        $this->assertNull($emptyCollection[0]);
        $this->assertNull($emptyCollection[100]);
    }

    // ==================== ITERATOR ON EMPTY COLLECTION TESTS ====================

    /**
     * Test that foreach on empty collection iterates zero times.
     */
    public function test_foreach_on_empty_collection_iterates_zero_times(): void
    {
        // Arrange
        $emptyCollection = new IntTypedCollection;
        $count = 0;

        // Act
        foreach ($emptyCollection as $item) {
            $count++;
        }

        // Assert
        $this->assertSame(0, $count);
    }

    /**
     * Test that getIterator returns empty ArrayIterator.
     */
    public function test_get_iterator_on_empty_collection_returns_empty_array_iterator(): void
    {
        // Arrange
        $emptyCollection = new IntTypedCollection;

        // Act
        $iterator = $emptyCollection->getIterator();

        // Assert
        $this->assertInstanceOf(\ArrayIterator::class, $iterator);
        $this->assertCount(0, $iterator);
    }

    // ==================== SPECIFIC COLLECTION TYPE EMPTY BEHAVIOR TESTS ====================

    /**
     * Test that IntTypedCollection specific methods work on empty.
     */
    public function test_int_typed_collection_specific_methods_on_empty(): void
    {
        // Arrange
        $emptyCollection = new IntTypedCollection;

        // Act & Assert
        $this->assertSame([], $emptyCollection->positive()->toArray());
        $this->assertSame([], $emptyCollection->negative()->toArray());
        $this->assertSame([], $emptyCollection->zero()->toArray());
        $this->assertSame([], $emptyCollection->nonNegative()->toArray());
        $this->assertSame([], $emptyCollection->even()->toArray());
        $this->assertSame([], $emptyCollection->odd()->toArray());
        $this->assertSame(0.0, $emptyCollection->median());
        $this->assertSame(0, $emptyCollection->sum());
        $this->assertSame(0.0, $emptyCollection->average());
    }

    /**
     * Test that FloatTypedCollection specific methods work on empty.
     */
    public function test_float_typed_collection_specific_methods_on_empty(): void
    {
        // Arrange
        $emptyCollection = new FloatTypedCollection;

        // Act & Assert
        $this->assertSame([], $emptyCollection->round()->toArray());
        $this->assertSame([], $emptyCollection->ceil()->toArray());
        $this->assertSame([], $emptyCollection->floor()->toArray());
        $this->assertSame([], $emptyCollection->format()->toArray());
    }

    /**
     * Test that StringTypedCollection specific methods work on empty.
     */
    public function test_string_typed_collection_specific_methods_on_empty(): void
    {
        // Arrange
        $emptyCollection = new StringTypedCollection;

        // Act & Assert
        $this->assertSame([], $emptyCollection->toLowercase()->toArray());
        $this->assertSame([], $emptyCollection->toUppercase()->toArray());
        $this->assertSame([], $emptyCollection->filterEmpty()->toArray());
        $this->assertSame('', $emptyCollection->join());
        $this->assertSame(0, $emptyCollection->lengths()->count());
        $this->assertSame('[]', (string) $emptyCollection);
    }

    /**
     * Test that BoolTypedCollection specific methods work on empty.
     */
    public function test_bool_typed_collection_specific_methods_on_empty(): void
    {
        // Arrange
        $emptyCollection = new BoolTypedCollection;

        // Act & Assert
        $this->assertSame([], $emptyCollection->trueOnly()->toArray());
        $this->assertSame([], $emptyCollection->falseOnly()->toArray());
        $this->assertSame(0, $emptyCollection->countTrue());
        $this->assertSame(0, $emptyCollection->countFalse());
        $this->assertTrue($emptyCollection->allTrue());
        $this->assertTrue($emptyCollection->allFalse());
        $this->assertFalse($emptyCollection->anyTrue());
        $this->assertFalse($emptyCollection->anyFalse());
    }

    /**
     * Test that NumberTypedCollection specific methods work on empty.
     */
    public function test_number_typed_collection_specific_methods_on_empty(): void
    {
        // Arrange
        $emptyCollection = new NumberTypedCollection;

        // Act & Assert
        $this->assertSame([], $emptyCollection->zero()->toArray());
        $this->assertSame([], $emptyCollection->nonNegative()->toArray());
        $this->assertTrue($emptyCollection->areAllIntegers());
        $this->assertFalse($emptyCollection->hasAnyFloat());
        $this->assertCount(0, $emptyCollection->toFloats());
        $this->assertCount(0, $emptyCollection->toIntegers());
    }

    // ==================== CHAINING EMPTY OPERATIONS TESTS ====================

    /**
     * Test chaining multiple operations on empty collection.
     */
    public function test_chaining_multiple_operations_on_empty_collection(): void
    {
        // Arrange
        $emptyCollection = new IntTypedCollection;

        // Act
        $result = $emptyCollection
            ->filter(fn($n) => $n > 0)
            ->map(fn($n) => $n * 2)
            ->sort()
            ->reverse()
            ->positive()
            ->even();

        // Assert
        $this->assertCount(0, $result);
        $this->assertTrue($result->isEmpty());
    }

    /**
     * Test that empty collection can be used in fluent interface.
     */
    public function test_empty_collection_supports_fluent_interface(): void
    {
        // Arrange & Act
        $result = (new IntTypedCollection)
            ->filter(fn($n) => $n > 0)
            ->map(fn($n) => $n * 2)
            ->add(1, 2, 3)  // Add after operations on empty
            ->positive();

        // Assert
        $this->assertCount(3, $result);
        $this->assertSame([1, 2, 3], $result->toArray());
    }
}
