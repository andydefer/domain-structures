<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Unit\Collections\Utility;

use AndyDefer\DomainStructures\Collections\Utility\BoolTypedCollection;
use AndyDefer\DomainStructures\Tests\TestCase;

/**
 * Unit tests for BoolTypedCollection class.
 *
 * This test suite validates the boolean collection functionality:
 * - Type safety (only boolean values allowed)
 * - Filtering true/false values
 * - Counting true/false values
 * - All/any boolean operations
 *
 * The tests follow the AAA pattern (Arrange, Act, Assert).
 */
final class BoolTypedCollectionTest extends TestCase
{
    // ==================== CONSTRUCTOR TESTS ====================

    /**
     * Test that BoolTypedCollection constructor sets bool as allowed type.
     */
    public function test_constructor_sets_bool_as_allowed_type(): void
    {
        // Arrange & Act
        $collection = new BoolTypedCollection;

        // Assert
        $this->assertSame(['bool'], $collection->getAllowedTypes());
    }

    /**
     * Test that BoolTypedCollection accepts only boolean values.
     */
    public function test_collection_accepts_only_boolean_values(): void
    {
        // Arrange
        $collection = new BoolTypedCollection;

        // Act
        $collection->add(true, false, true, false);

        // Assert
        $this->assertCount(4, $collection);
        $this->assertTrue($collection[0]);
        $this->assertFalse($collection[1]);
        $this->assertTrue($collection[2]);
        $this->assertFalse($collection[3]);
    }

    /**
     * Test that collection rejects non-boolean values.
     */
    public function test_collection_rejects_non_boolean_values(): void
    {
        // Arrange
        $collection = new BoolTypedCollection;

        // Act & Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected type(s) bool');

        $collection->add('not a boolean');
    }

    // ==================== TRUE_ONLY METHOD TESTS ====================

    /**
     * Test that trueOnly returns only true values.
     */
    public function test_true_only_returns_only_true_values(): void
    {
        // Arrange
        $collection = new BoolTypedCollection;
        $collection->add(true, false, true, false, true);

        // Act
        $trueOnly = $collection->trueOnly();

        // Assert
        $this->assertSame([true, true, true], $trueOnly->toArray());
        $this->assertCount(3, $trueOnly);
    }

    /**
     * Test that trueOnly returns empty collection when no true values.
     */
    public function test_true_only_returns_empty_collection_when_no_true_values(): void
    {
        // Arrange
        $collection = new BoolTypedCollection;
        $collection->add(false, false, false);

        // Act
        $trueOnly = $collection->trueOnly();

        // Assert
        $this->assertCount(0, $trueOnly);
        $this->assertTrue($trueOnly->isEmpty());
    }

    /**
     * Test that trueOnly on empty collection returns empty collection.
     */
    public function test_true_only_on_empty_collection_returns_empty_collection(): void
    {
        // Arrange
        $emptyCollection = new BoolTypedCollection;

        // Act
        $trueOnly = $emptyCollection->trueOnly();

        // Assert
        $this->assertCount(0, $trueOnly);
        $this->assertTrue($trueOnly->isEmpty());
    }

    /**
     * Test that trueOnly returns new collection instance.
     */
    public function test_true_only_returns_new_collection_instance(): void
    {
        // Arrange
        $collection = new BoolTypedCollection;
        $collection->add(true, false);

        // Act
        $trueOnly = $collection->trueOnly();

        // Assert
        $this->assertNotSame($collection, $trueOnly);
        $this->assertInstanceOf(BoolTypedCollection::class, $trueOnly);
    }

    // ==================== FALSE_ONLY METHOD TESTS ====================

    /**
     * Test that falseOnly returns only false values.
     */
    public function test_false_only_returns_only_false_values(): void
    {
        // Arrange
        $collection = new BoolTypedCollection;
        $collection->add(true, false, true, false, false);

        // Act
        $falseOnly = $collection->falseOnly();

        // Assert
        $this->assertSame([false, false, false], $falseOnly->toArray());
        $this->assertCount(3, $falseOnly);
    }

    /**
     * Test that falseOnly returns empty collection when no false values.
     */
    public function test_false_only_returns_empty_collection_when_no_false_values(): void
    {
        // Arrange
        $collection = new BoolTypedCollection;
        $collection->add(true, true, true);

        // Act
        $falseOnly = $collection->falseOnly();

        // Assert
        $this->assertCount(0, $falseOnly);
        $this->assertTrue($falseOnly->isEmpty());
    }

    /**
     * Test that falseOnly on empty collection returns empty collection.
     */
    public function test_false_only_on_empty_collection_returns_empty_collection(): void
    {
        // Arrange
        $emptyCollection = new BoolTypedCollection;

        // Act
        $falseOnly = $emptyCollection->falseOnly();

        // Assert
        $this->assertCount(0, $falseOnly);
        $this->assertTrue($falseOnly->isEmpty());
    }

    /**
     * Test that falseOnly returns new collection instance.
     */
    public function test_false_only_returns_new_collection_instance(): void
    {
        // Arrange
        $collection = new BoolTypedCollection;
        $collection->add(true, false);

        // Act
        $falseOnly = $collection->falseOnly();

        // Assert
        $this->assertNotSame($collection, $falseOnly);
        $this->assertInstanceOf(BoolTypedCollection::class, $falseOnly);
    }

    // ==================== COUNT_TRUE METHOD TESTS ====================

    /**
     * Test that countTrue returns correct number of true values.
     */
    public function test_count_true_returns_correct_number_of_true_values(): void
    {
        // Arrange
        $collection = new BoolTypedCollection;
        $collection->add(true, false, true, false, true, true);

        // Act
        $count = $collection->countTrue();

        // Assert
        $this->assertSame(4, $count);
    }

    /**
     * Test that countTrue returns 0 when no true values.
     */
    public function test_count_true_returns_zero_when_no_true_values(): void
    {
        // Arrange
        $collection = new BoolTypedCollection;
        $collection->add(false, false, false);

        // Act
        $count = $collection->countTrue();

        // Assert
        $this->assertSame(0, $count);
    }

    /**
     * Test that countTrue on empty collection returns 0.
     */
    public function test_count_true_on_empty_collection_returns_zero(): void
    {
        // Arrange
        $emptyCollection = new BoolTypedCollection;

        // Act
        $count = $emptyCollection->countTrue();

        // Assert
        $this->assertSame(0, $count);
    }

    /**
     * Test that countTrue works with mixed values.
     */
    public function test_count_true_works_with_mixed_values(): void
    {
        // Arrange
        $collection = new BoolTypedCollection;
        $collection->add(true, true, false, true, false, false, true);

        // Act
        $count = $collection->countTrue();

        // Assert
        $this->assertSame(4, $count);
    }

    // ==================== COUNT_FALSE METHOD TESTS ====================

    /**
     * Test that countFalse returns correct number of false values.
     */
    public function test_count_false_returns_correct_number_of_false_values(): void
    {
        // Arrange
        $collection = new BoolTypedCollection;
        $collection->add(true, false, true, false, false, true);

        // Act
        $count = $collection->countFalse();

        // Assert
        $this->assertSame(3, $count);
    }

    /**
     * Test that countFalse returns 0 when no false values.
     */
    public function test_count_false_returns_zero_when_no_false_values(): void
    {
        // Arrange
        $collection = new BoolTypedCollection;
        $collection->add(true, true, true);

        // Act
        $count = $collection->countFalse();

        // Assert
        $this->assertSame(0, $count);
    }

    /**
     * Test that countFalse on empty collection returns 0.
     */
    public function test_count_false_on_empty_collection_returns_zero(): void
    {
        // Arrange
        $emptyCollection = new BoolTypedCollection;

        // Act
        $count = $emptyCollection->countFalse();

        // Assert
        $this->assertSame(0, $count);
    }

    /**
     * Test that countFalse works with mixed values.
     */
    public function test_count_false_works_with_mixed_values(): void
    {
        // Arrange
        $collection = new BoolTypedCollection;
        $collection->add(true, true, false, true, false, false, true);

        // Act
        $count = $collection->countFalse();

        // Assert
        $this->assertSame(3, $count);
    }

    // ==================== ALL_TRUE METHOD TESTS ====================

    /**
     * Test that allTrue returns true when all values are true.
     */
    public function test_all_true_returns_true_when_all_values_are_true(): void
    {
        // Arrange
        $collection = new BoolTypedCollection;
        $collection->add(true, true, true, true);

        // Act
        $allTrue = $collection->allTrue();

        // Assert
        $this->assertTrue($allTrue);
    }

    /**
     * Test that allTrue returns false when any value is false.
     */
    public function test_all_true_returns_false_when_any_value_is_false(): void
    {
        // Arrange
        $collection = new BoolTypedCollection;
        $collection->add(true, true, false, true);

        // Act
        $allTrue = $collection->allTrue();

        // Assert
        $this->assertFalse($allTrue);
    }

    /**
     * Test that allTrue returns true for empty collection (vacuously true).
     */
    public function test_all_true_returns_true_for_empty_collection(): void
    {
        // Arrange
        $emptyCollection = new BoolTypedCollection;

        // Act
        $allTrue = $emptyCollection->allTrue();

        // Assert
        $this->assertTrue($allTrue);
    }

    /**
     * Test that allTrue on single true returns true.
     */
    public function test_all_true_on_single_true_returns_true(): void
    {
        // Arrange
        $collection = new BoolTypedCollection;
        $collection->add(true);

        // Act
        $allTrue = $collection->allTrue();

        // Assert
        $this->assertTrue($allTrue);
    }

    /**
     * Test that allTrue on single false returns false.
     */
    public function test_all_true_on_single_false_returns_false(): void
    {
        // Arrange
        $collection = new BoolTypedCollection;
        $collection->add(false);

        // Act
        $allTrue = $collection->allTrue();

        // Assert
        $this->assertFalse($allTrue);
    }

    // ==================== ALL_FALSE METHOD TESTS ====================

    /**
     * Test that allFalse returns true when all values are false.
     */
    public function test_all_false_returns_true_when_all_values_are_false(): void
    {
        // Arrange
        $collection = new BoolTypedCollection;
        $collection->add(false, false, false, false);

        // Act
        $allFalse = $collection->allFalse();

        // Assert
        $this->assertTrue($allFalse);
    }

    /**
     * Test that allFalse returns false when any value is true.
     */
    public function test_all_false_returns_false_when_any_value_is_true(): void
    {
        // Arrange
        $collection = new BoolTypedCollection;
        $collection->add(false, false, true, false);

        // Act
        $allFalse = $collection->allFalse();

        // Assert
        $this->assertFalse($allFalse);
    }

    /**
     * Test that allFalse returns true for empty collection (vacuously true).
     */
    public function test_all_false_returns_true_for_empty_collection(): void
    {
        // Arrange
        $emptyCollection = new BoolTypedCollection;

        // Act
        $allFalse = $emptyCollection->allFalse();

        // Assert
        $this->assertTrue($allFalse);
    }

    /**
     * Test that allFalse on single false returns true.
     */
    public function test_all_false_on_single_false_returns_true(): void
    {
        // Arrange
        $collection = new BoolTypedCollection;
        $collection->add(false);

        // Act
        $allFalse = $collection->allFalse();

        // Assert
        $this->assertTrue($allFalse);
    }

    /**
     * Test that allFalse on single true returns false.
     */
    public function test_all_false_on_single_true_returns_false(): void
    {
        // Arrange
        $collection = new BoolTypedCollection;
        $collection->add(true);

        // Act
        $allFalse = $collection->allFalse();

        // Assert
        $this->assertFalse($allFalse);
    }

    // ==================== ANY_TRUE METHOD TESTS ====================

    /**
     * Test that anyTrue returns true when at least one true exists.
     */
    public function test_any_true_returns_true_when_at_least_one_true_exists(): void
    {
        // Arrange
        $collection = new BoolTypedCollection;
        $collection->add(false, false, true, false);

        // Act
        $anyTrue = $collection->anyTrue();

        // Assert
        $this->assertTrue($anyTrue);
    }

    /**
     * Test that anyTrue returns false when no true values exist.
     */
    public function test_any_true_returns_false_when_no_true_values_exist(): void
    {
        // Arrange
        $collection = new BoolTypedCollection;
        $collection->add(false, false, false, false);

        // Act
        $anyTrue = $collection->anyTrue();

        // Assert
        $this->assertFalse($anyTrue);
    }

    /**
     * Test that anyTrue on empty collection returns false.
     */
    public function test_any_true_on_empty_collection_returns_false(): void
    {
        // Arrange
        $emptyCollection = new BoolTypedCollection;

        // Act
        $anyTrue = $emptyCollection->anyTrue();

        // Assert
        $this->assertFalse($anyTrue);
    }

    /**
     * Test that anyTrue on single true returns true.
     */
    public function test_any_true_on_single_true_returns_true(): void
    {
        // Arrange
        $collection = new BoolTypedCollection;
        $collection->add(true);

        // Act
        $anyTrue = $collection->anyTrue();

        // Assert
        $this->assertTrue($anyTrue);
    }

    /**
     * Test that anyTrue on single false returns false.
     */
    public function test_any_true_on_single_false_returns_false(): void
    {
        // Arrange
        $collection = new BoolTypedCollection;
        $collection->add(false);

        // Act
        $anyTrue = $collection->anyTrue();

        // Assert
        $this->assertFalse($anyTrue);
    }

    // ==================== ANY_FALSE METHOD TESTS ====================

    /**
     * Test that anyFalse returns true when at least one false exists.
     */
    public function test_any_false_returns_true_when_at_least_one_false_exists(): void
    {
        // Arrange
        $collection = new BoolTypedCollection;
        $collection->add(true, true, false, true);

        // Act
        $anyFalse = $collection->anyFalse();

        // Assert
        $this->assertTrue($anyFalse);
    }

    /**
     * Test that anyFalse returns false when no false values exist.
     */
    public function test_any_false_returns_false_when_no_false_values_exist(): void
    {
        // Arrange
        $collection = new BoolTypedCollection;
        $collection->add(true, true, true, true);

        // Act
        $anyFalse = $collection->anyFalse();

        // Assert
        $this->assertFalse($anyFalse);
    }

    /**
     * Test that anyFalse on empty collection returns false.
     */
    public function test_any_false_on_empty_collection_returns_false(): void
    {
        // Arrange
        $emptyCollection = new BoolTypedCollection;

        // Act
        $anyFalse = $emptyCollection->anyFalse();

        // Assert
        $this->assertFalse($anyFalse);
    }

    /**
     * Test that anyFalse on single true returns false.
     */
    public function test_any_false_on_single_true_returns_false(): void
    {
        // Arrange
        $collection = new BoolTypedCollection;
        $collection->add(true);

        // Act
        $anyFalse = $collection->anyFalse();

        // Assert
        $this->assertFalse($anyFalse);
    }

    /**
     * Test that anyFalse on single false returns true.
     */
    public function test_any_false_on_single_false_returns_true(): void
    {
        // Arrange
        $collection = new BoolTypedCollection;
        $collection->add(false);

        // Act
        $anyFalse = $collection->anyFalse();

        // Assert
        $this->assertTrue($anyFalse);
    }

    // ==================== COLLECTION OPERATIONS TESTS ====================

    /**
     * Test that filter works with BoolTypedCollection.
     */
    public function test_filter_works_with_bool_collection(): void
    {
        // Arrange
        $collection = new BoolTypedCollection;
        $collection->add(true, false, true, false, true);

        // Act
        $filtered = $collection->filter(fn ($item) => $item === true);

        // Assert
        $this->assertSame([true, true, true], $filtered->toArray());
        $this->assertInstanceOf(BoolTypedCollection::class, $filtered);
    }

    /**
     * Test that map works with BoolTypedCollection.
     */
    public function test_map_works_with_bool_collection(): void
    {
        // Arrange
        $collection = new BoolTypedCollection;
        $collection->add(true, false, true);

        // Act
        $mapped = $collection->map(fn ($item) => ! $item);

        // Assert
        $this->assertSame([false, true, false], $mapped->toArray());
    }

    /**
     * Test that count works with BoolTypedCollection.
     */
    public function test_count_works_with_bool_collection(): void
    {
        // Arrange
        $collection = new BoolTypedCollection;
        $collection->add(true, false, true, false);

        // Act & Assert
        $this->assertCount(4, $collection);
        $this->assertSame(4, $collection->count());
    }

    /**
     * Test that isEmpty works with BoolTypedCollection.
     */
    public function test_is_empty_works_with_bool_collection(): void
    {
        // Arrange
        $emptyCollection = new BoolTypedCollection;
        $nonEmptyCollection = new BoolTypedCollection;
        $nonEmptyCollection->add(true);

        // Act & Assert
        $this->assertTrue($emptyCollection->isEmpty());
        $this->assertFalse($nonEmptyCollection->isEmpty());
    }

    // ==================== EDGE CASES TESTS ====================

    /**
     * Test that collection handles large number of booleans.
     */
    public function test_collection_handles_large_number_of_booleans(): void
    {
        // Arrange
        $collection = new BoolTypedCollection;

        for ($i = 1; $i <= 1000; $i++) {
            $collection->add($i % 2 === 0);
        }

        // Act & Assert
        $this->assertCount(1000, $collection);
        $this->assertSame(500, $collection->countTrue());
        $this->assertSame(500, $collection->countFalse());
    }

    /**
     * Test that operations preserve collection type.
     */
    public function test_operations_preserve_collection_type(): void
    {
        // Arrange
        $collection = new BoolTypedCollection;
        $collection->add(true, false, true, false, true);

        // Act
        $trueOnly = $collection->trueOnly();
        $falseOnly = $collection->falseOnly();

        // Assert
        $this->assertInstanceOf(BoolTypedCollection::class, $trueOnly);
        $this->assertInstanceOf(BoolTypedCollection::class, $falseOnly);
    }

    /**
     * Test that chaining operations works correctly.
     */
    public function test_chaining_operations_works_correctly(): void
    {
        // Arrange
        $collection = new BoolTypedCollection;
        $collection->add(true, false, true, false, true, false, true);

        // Act - Get true values, then count them
        $count = $collection->trueOnly()->count();

        // Assert
        $this->assertSame(4, $count);
    }

    /**
     * Test that collection can be JSON serialized.
     */
    public function test_collection_can_be_json_serialized(): void
    {
        // Arrange
        $collection = new BoolTypedCollection;
        $collection->add(true, false, true);

        // Act
        $json = json_encode($collection);

        // Assert
        $this->assertSame('[true,false,true]', $json);
    }

    /**
     * Test that normalize works with BoolTypedCollection.
     */
    public function test_normalize_works_with_bool_collection(): void
    {
        // Arrange
        $collection = new BoolTypedCollection;
        $collection->add(true, false, true);

        // Act
        $normalized = $collection->normalize();

        // Assert
        $this->assertSame([true, false, true], $normalized);
    }
}
