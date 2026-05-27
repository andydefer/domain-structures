<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Unit\Collections\Utility;

use AndyDefer\DomainStructures\Collections\Utility\IntTypedCollection;
use AndyDefer\DomainStructures\Tests\TestCase;

/**
 * Unit tests for IntTypedCollection class.
 *
 * This test suite validates the integer-specific collection functionality:
 * - Type safety (only integer values allowed)
 * - Zero filtering
 * - Non-negative filtering
 * - Even/odd filtering
 * - Median calculation
 * - Inheritance from AbstractNumberTypedCollection
 *
 * The tests follow the AAA pattern (Arrange, Act, Assert).
 */
final class IntTypedCollectionTest extends TestCase
{
    // ==================== CONSTRUCTOR TESTS ====================

    /**
     * Test that IntTypedCollection constructor sets int as allowed type.
     */
    public function test_constructor_sets_int_as_allowed_type(): void
    {
        // Arrange & Act
        $collection = new IntTypedCollection;

        // Assert
        $this->assertSame(['int'], $collection->getAllowedTypes());
    }

    /**
     * Test that IntTypedCollection accepts only integer values.
     */
    public function test_collection_accepts_only_integer_values(): void
    {
        // Arrange
        $collection = new IntTypedCollection;

        // Act
        $collection->add(1, 2, 3, 4, 5, -10, -20, 0);

        // Assert
        $this->assertCount(8, $collection);
        $this->assertSame(1, $collection[0]);
        $this->assertSame(2, $collection[1]);
        $this->assertSame(-10, $collection[5]);
        $this->assertSame(0, $collection[7]);
    }

    /**
     * Test that collection rejects non-integer values.
     */
    public function test_collection_rejects_non_integer_values(): void
    {
        // Arrange
        $collection = new IntTypedCollection;

        // Act & Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected type(s) int');

        $collection->add(3.14); // float
    }

    /**
     * Test that collection rejects string numbers.
     */
    public function test_collection_rejects_string_numbers(): void
    {
        // Arrange
        $collection = new IntTypedCollection;

        // Act & Assert
        $this->expectException(\InvalidArgumentException::class);

        $collection->add('42');
    }

    // ==================== ZERO METHOD TESTS ====================

    /**
     * Test that zero returns only zero values.
     */
    public function test_zero_returns_only_zero_values(): void
    {
        // Arrange
        $collection = new IntTypedCollection;
        $collection->add(-2, -1, 0, 1, 2, 0, 3, 0);

        // Act
        $zeros = $collection->zero();

        // Assert
        $this->assertSame([0, 0, 0], $zeros->toArray());
        $this->assertCount(3, $zeros);
    }

    /**
     * Test that zero returns empty collection when no zeros.
     */
    public function test_zero_returns_empty_collection_when_no_zeros(): void
    {
        // Arrange
        $collection = new IntTypedCollection;
        $collection->add(1, 2, 3, 4, 5);

        // Act
        $zeros = $collection->zero();

        // Assert
        $this->assertCount(0, $zeros);
        $this->assertTrue($zeros->isEmpty());
    }

    /**
     * Test that zero on empty collection returns empty collection.
     */
    public function test_zero_on_empty_collection_returns_empty_collection(): void
    {
        // Arrange
        $emptyCollection = new IntTypedCollection;

        // Act
        $zeros = $emptyCollection->zero();

        // Assert
        $this->assertCount(0, $zeros);
        $this->assertTrue($zeros->isEmpty());
    }

    /**
     * Test that zero returns new collection instance.
     */
    public function test_zero_returns_new_collection_instance(): void
    {
        // Arrange
        $collection = new IntTypedCollection;
        $collection->add(0, 1, 2, 0, 3);

        // Act
        $zeros = $collection->zero();

        // Assert
        $this->assertNotSame($collection, $zeros);
        $this->assertInstanceOf(IntTypedCollection::class, $zeros);
    }

    // ==================== NON_NEGATIVE METHOD TESTS ====================

    /**
     * Test that nonNegative returns numbers >= 0.
     */
    public function test_non_negative_returns_numbers_greater_than_or_equal_zero(): void
    {
        // Arrange
        $collection = new IntTypedCollection;
        $collection->add(-5, -3, -1, 0, 1, 2, 3, 4, 5);

        // Act
        $nonNegative = $collection->nonNegative();

        // Assert
        $this->assertSame([0, 1, 2, 3, 4, 5], $nonNegative->toArray());
        $this->assertCount(6, $nonNegative);
    }

    /**
     * Test that nonNegative includes zero.
     */
    public function test_non_negative_includes_zero(): void
    {
        // Arrange
        $collection = new IntTypedCollection;
        $collection->add(-2, -1, 0, 1, 2);

        // Act
        $nonNegative = $collection->nonNegative();

        // Assert
        $this->assertContains(0, $nonNegative->toArray());
        $this->assertSame([0, 1, 2], $nonNegative->toArray());
    }

    /**
     * Test that nonNegative excludes negative numbers.
     */
    public function test_non_negative_excludes_negative_numbers(): void
    {
        // Arrange
        $collection = new IntTypedCollection;
        $collection->add(-5, -4, -3, -2, -1, 0, 1);

        // Act
        $nonNegative = $collection->nonNegative();

        // Assert
        $this->assertNotContains(-5, $nonNegative->toArray());
        $this->assertNotContains(-1, $nonNegative->toArray());
        $this->assertSame([0, 1], $nonNegative->toArray());
    }

    /**
     * Test that nonNegative on all negative numbers returns empty.
     */
    public function test_non_negative_on_all_negative_returns_empty(): void
    {
        // Arrange
        $collection = new IntTypedCollection;
        $collection->add(-5, -4, -3, -2, -1);

        // Act
        $nonNegative = $collection->nonNegative();

        // Assert
        $this->assertCount(0, $nonNegative);
        $this->assertTrue($nonNegative->isEmpty());
    }

    /**
     * Test that nonNegative returns new collection instance.
     */
    public function test_non_negative_returns_new_collection_instance(): void
    {
        // Arrange
        $collection = new IntTypedCollection;
        $collection->add(-2, -1, 0, 1, 2);

        // Act
        $nonNegative = $collection->nonNegative();

        // Assert
        $this->assertNotSame($collection, $nonNegative);
        $this->assertInstanceOf(IntTypedCollection::class, $nonNegative);
    }

    // ==================== EVEN METHOD TESTS ====================

    /**
     * Test that even returns only even numbers.
     */
    public function test_even_returns_only_even_numbers(): void
    {
        // Arrange
        $collection = new IntTypedCollection;
        $collection->add(1, 2, 3, 4, 5, 6, 7, 8, 9, 10);

        // Act
        $even = $collection->even();

        // Assert
        $this->assertSame([2, 4, 6, 8, 10], $even->toArray());
        $this->assertCount(5, $even);
    }

    /**
     * Test that even includes negative even numbers.
     */
    public function test_even_includes_negative_even_numbers(): void
    {
        // Arrange
        $collection = new IntTypedCollection;
        $collection->add(-10, -9, -8, -7, -6, -5, -4, -3, -2, -1);

        // Act
        $even = $collection->even();

        // Assert
        $this->assertSame([-10, -8, -6, -4, -2], $even->toArray());
    }

    /**
     * Test that even includes zero (which is even).
     */
    public function test_even_includes_zero(): void
    {
        // Arrange
        $collection = new IntTypedCollection;
        $collection->add(-2, -1, 0, 1, 2);

        // Act
        $even = $collection->even();

        // Assert
        $this->assertContains(0, $even->toArray());
        $this->assertSame([-2, 0, 2], $even->toArray());
    }

    /**
     * Test that even returns empty when no even numbers.
     */
    public function test_even_returns_empty_when_no_even_numbers(): void
    {
        // Arrange
        $collection = new IntTypedCollection;
        $collection->add(1, 3, 5, 7, 9);

        // Act
        $even = $collection->even();

        // Assert
        $this->assertCount(0, $even);
        $this->assertTrue($even->isEmpty());
    }

    /**
     * Test that even returns new collection instance.
     */
    public function test_even_returns_new_collection_instance(): void
    {
        // Arrange
        $collection = new IntTypedCollection;
        $collection->add(1, 2, 3, 4);

        // Act
        $even = $collection->even();

        // Assert
        $this->assertNotSame($collection, $even);
        $this->assertInstanceOf(IntTypedCollection::class, $even);
    }

    // ==================== ODD METHOD TESTS ====================

    /**
     * Test that odd returns only odd numbers.
     */
    public function test_odd_returns_only_odd_numbers(): void
    {
        // Arrange
        $collection = new IntTypedCollection;
        $collection->add(1, 2, 3, 4, 5, 6, 7, 8, 9, 10);

        // Act
        $odd = $collection->odd();

        // Assert
        $this->assertSame([1, 3, 5, 7, 9], $odd->toArray());
        $this->assertCount(5, $odd);
    }

    /**
     * Test that odd includes negative odd numbers.
     */
    public function test_odd_includes_negative_odd_numbers(): void
    {
        // Arrange
        $collection = new IntTypedCollection;
        $collection->add(-10, -9, -8, -7, -6, -5, -4, -3, -2, -1);

        // Act
        $odd = $collection->odd();

        // Assert
        $this->assertSame([-9, -7, -5, -3, -1], $odd->toArray());
    }

    /**
     * Test that odd excludes zero (zero is even).
     */
    public function test_odd_excludes_zero(): void
    {
        // Arrange
        $collection = new IntTypedCollection;
        $collection->add(-2, -1, 0, 1, 2);

        // Act
        $odd = $collection->odd();

        // Assert
        $this->assertNotContains(0, $odd->toArray());
        $this->assertSame([-1, 1], $odd->toArray());
    }

    /**
     * Test that odd returns empty when no odd numbers.
     */
    public function test_odd_returns_empty_when_no_odd_numbers(): void
    {
        // Arrange
        $collection = new IntTypedCollection;
        $collection->add(2, 4, 6, 8, 10);

        // Act
        $odd = $collection->odd();

        // Assert
        $this->assertCount(0, $odd);
        $this->assertTrue($odd->isEmpty());
    }

    /**
     * Test that odd returns new collection instance.
     */
    public function test_odd_returns_new_collection_instance(): void
    {
        // Arrange
        $collection = new IntTypedCollection;
        $collection->add(1, 2, 3, 4);

        // Act
        $odd = $collection->odd();

        // Assert
        $this->assertNotSame($collection, $odd);
        $this->assertInstanceOf(IntTypedCollection::class, $odd);
    }

    // ==================== MEDIAN METHOD TESTS ====================

    /**
     * Test that median with odd count returns middle value.
     */
    public function test_median_with_odd_count_returns_middle_value(): void
    {
        // Arrange
        $collection = new IntTypedCollection;
        $collection->add(1, 3, 2, 5, 4);

        // Act
        $median = $collection->median();

        // Assert: sorted = [1,2,3,4,5], middle = 3
        $this->assertSame(3.0, $median);
    }

    /**
     * Test that median with even count returns average of two middle values.
     */
    public function test_median_with_even_count_returns_average_of_two_middle_values(): void
    {
        // Arrange
        $collection = new IntTypedCollection;
        $collection->add(1, 3, 2, 4);

        // Act
        $median = $collection->median();

        // Assert: sorted = [1,2,3,4], middle values = 2 and 3, average = 2.5
        $this->assertSame(2.5, $median);
    }

    /**
     * Test that median with sorted array works correctly.
     */
    public function test_median_with_sorted_array_works_correctly(): void
    {
        // Arrange
        $collection = new IntTypedCollection;
        $collection->add(10, 20, 30, 40, 50);

        // Act
        $median = $collection->median();

        // Assert
        $this->assertSame(30.0, $median);
    }

    /**
     * Test that median with unsorted array sorts automatically.
     */
    public function test_median_with_unsorted_array_sorts_automatically(): void
    {
        // Arrange
        $collection = new IntTypedCollection;
        $collection->add(50, 10, 40, 20, 30);

        // Act
        $median = $collection->median();

        // Assert
        $this->assertSame(30.0, $median);
    }

    /**
     * Test that median with negative numbers works.
     */
    public function test_median_with_negative_numbers_works(): void
    {
        // Arrange
        $collection = new IntTypedCollection;
        $collection->add(-5, -3, -1, -2, -4);

        // Act
        $median = $collection->median();

        // Assert: sorted = [-5,-4,-3,-2,-1], median = -3
        $this->assertSame(-3.0, $median);
    }

    /**
     * Test that median with negative and positive works.
     */
    public function test_median_with_negative_and_positive_works(): void
    {
        // Arrange
        $collection = new IntTypedCollection;
        $collection->add(-5, -2, 0, 2, 5);

        // Act
        $median = $collection->median();

        // Assert: sorted = [-5,-2,0,2,5], median = 0
        $this->assertSame(0.0, $median);
    }

    /**
     * Test that median with single element returns that element.
     */
    public function test_median_with_single_element_returns_that_element(): void
    {
        // Arrange
        $collection = new IntTypedCollection;
        $collection->add(42);

        // Act
        $median = $collection->median();

        // Assert
        $this->assertSame(42.0, $median);
    }

    /**
     * Test that median with two elements returns their average.
     */
    public function test_median_with_two_elements_returns_their_average(): void
    {
        // Arrange
        $collection = new IntTypedCollection;
        $collection->add(10, 20);

        // Act
        $median = $collection->median();

        // Assert
        $this->assertSame(15.0, $median);
    }

    /**
     * Test that median with empty collection returns 0.0.
     */
    public function test_median_with_empty_collection_returns_zero(): void
    {
        // Arrange
        $emptyCollection = new IntTypedCollection;

        // Act
        $median = $emptyCollection->median();

        // Assert
        $this->assertSame(0.0, $median);
    }

    /**
     * Test that median preserves original collection order.
     */
    public function test_median_preserves_original_collection_order(): void
    {
        // Arrange
        $collection = new IntTypedCollection;
        $originalOrder = [50, 10, 40, 20, 30];
        $collection->add(...$originalOrder);

        // Act
        $median = $collection->median();

        // Assert
        $this->assertSame([50, 10, 40, 20, 30], $collection->toArray());
        $this->assertSame(30.0, $median);
    }

    // ==================== INHERITED METHOD TESTS ====================

    /**
     * Test that positive method works (inherited).
     */
    public function test_positive_method_works(): void
    {
        // Arrange
        $collection = new IntTypedCollection;
        $collection->add(-5, -3, -1, 0, 1, 2, 3, 4, 5);

        // Act
        $positive = $collection->positive();

        // Assert
        $this->assertSame([1, 2, 3, 4, 5], $positive->toArray());
    }

    /**
     * Test that negative method works (inherited).
     */
    public function test_negative_method_works(): void
    {
        // Arrange
        $collection = new IntTypedCollection;
        $collection->add(-5, -4, -3, -2, -1, 0, 1, 2, 3);

        // Act
        $negative = $collection->negative();

        // Assert
        $this->assertSame([-5, -4, -3, -2, -1], $negative->toArray());
    }

    /**
     * Test that between method works (inherited).
     */
    public function test_between_method_works(): void
    {
        // Arrange
        $collection = new IntTypedCollection;
        $collection->add(1, 5, 10, 15, 20, 25, 30);

        // Act
        $between = $collection->between(5, 25);

        // Assert
        $this->assertSame([5, 10, 15, 20, 25], $between->toArray());
    }

    /**
     * Test that average method works (inherited).
     */
    public function test_average_method_works(): void
    {
        // Arrange
        $collection = new IntTypedCollection;
        $collection->add(10, 20, 30, 40, 50);

        // Act
        $average = $collection->average();

        // Assert
        $this->assertSame(30.0, $average);
    }

    /**
     * Test that sum method works (inherited).
     */
    public function test_sum_method_works(): void
    {
        // Arrange
        $collection = new IntTypedCollection;
        $collection->add(10, 20, 30, 40, 50);

        // Act
        $sum = $collection->sum();

        // Assert
        $this->assertSame(150, $sum);
    }

    /**
     * Test that range method works (inherited).
     */
    public function test_range_method_works(): void
    {
        // Act
        $collection = IntTypedCollection::range(1, 10, 2);

        // Assert
        $this->assertSame([1, 3, 5, 7, 9], $collection->toArray());
    }

    // ==================== CHAINING OPERATIONS TESTS ====================

    /**
     * Test chaining multiple operations.
     */
    public function test_chaining_multiple_operations_works(): void
    {
        // Arrange
        $collection = new IntTypedCollection;
        $collection->add(-10, -5, -2, 0, 3, 6, 9, 12, 15);

        // Act - Get positive even numbers, then get median
        $result = $collection
            ->positive()
            ->even()
            ->median();

        // Assert: positive = [3,6,9,12,15], even = [6,12], median = (6+12)/2 = 9
        $this->assertSame(9.0, $result);
    }

    /**
     * Test even then odd chain (should return empty).
     */
    public function test_even_then_odd_chain_returns_empty(): void
    {
        // Arrange
        $collection = new IntTypedCollection;
        $collection->add(1, 2, 3, 4, 5, 6, 7, 8, 9, 10);

        // Act
        $result = $collection->even()->odd();

        // Assert
        $this->assertCount(0, $result);
        $this->assertTrue($result->isEmpty());
    }

    /**
     * Test odd then even chain (should return empty).
     */
    public function test_odd_then_even_chain_returns_empty(): void
    {
        // Arrange
        $collection = new IntTypedCollection;
        $collection->add(1, 2, 3, 4, 5, 6, 7, 8, 9, 10);

        // Act
        $result = $collection->odd()->even();

        // Assert
        $this->assertCount(0, $result);
        $this->assertTrue($result->isEmpty());
    }

    /**
     * Test zero then positive chain (should return empty).
     */
    public function test_zero_then_positive_chain_returns_empty(): void
    {
        // Arrange
        $collection = new IntTypedCollection;
        $collection->add(-2, -1, 0, 1, 2);

        // Act
        $result = $collection->zero()->positive();

        // Assert
        $this->assertCount(0, $result);
    }

    /**
     * Test nonNegative then even chain.
     */
    public function test_non_negative_then_even_chain(): void
    {
        // Arrange
        $collection = new IntTypedCollection;
        $collection->add(-5, -4, -3, -2, -1, 0, 1, 2, 3, 4, 5);

        // Act
        $result = $collection->nonNegative()->even();

        // Assert: nonNegative = [0,1,2,3,4,5], even = [0,2,4]
        $this->assertSame([0, 2, 4], $result->toArray());
    }

    // ==================== EDGE CASES TESTS ====================

    /**
     * Test that collection handles very large integers.
     */
    public function test_collection_handles_very_large_integers(): void
    {
        // Arrange
        $collection = new IntTypedCollection;
        $collection->add(PHP_INT_MAX - 2, PHP_INT_MAX - 1, PHP_INT_MAX);

        // Act
        $sum = $collection->sum();
        $median = $collection->median();

        // Assert
        $this->assertIsInt($sum);
        $this->assertIsFloat($median);
    }

    /**
     * Test that collection handles very small integers (negative large).
     */
    public function test_collection_handles_very_small_integers(): void
    {
        // Arrange
        $collection = new IntTypedCollection;
        $collection->add(PHP_INT_MIN + 2, PHP_INT_MIN + 1, PHP_INT_MIN);

        // Act
        $sum = $collection->sum();
        $median = $collection->median();

        // Assert
        $this->assertIsInt($sum);
        $this->assertIsFloat($median);
    }

    /**
     * Test that collection handles duplicate values.
     */
    public function test_collection_handles_duplicate_values(): void
    {
        // Arrange
        $collection = new IntTypedCollection;
        $collection->add(1, 1, 2, 2, 3, 3, 3);

        // Act
        $median = $collection->median();

        // Assert: sorted = [1,1,2,2,3,3,3], middle (4th) = 2
        $this->assertSame(2.0, $median);
    }

    /**
     * Test that collection handles single element for all operations.
     */
    public function test_collection_handles_single_element_for_all_operations(): void
    {
        // Arrange
        $collection = new IntTypedCollection;
        $collection->add(5);

        // Act & Assert
        $this->assertSame([5], $collection->positive()->toArray());
        $this->assertSame([], $collection->negative()->toArray());
        $this->assertSame([], $collection->zero()->toArray());
        $this->assertSame([5], $collection->nonNegative()->toArray());
        $this->assertSame([], $collection->even()->toArray());
        $this->assertSame([5], $collection->odd()->toArray());
        $this->assertSame(5.0, $collection->median());
        $this->assertSame(5, $collection->sum());
        $this->assertSame(5.0, $collection->average());
    }

    /**
     * Test that collection preserves order after filtering.
     */
    public function test_collection_preserves_order_after_filtering(): void
    {
        // Arrange
        $collection = new IntTypedCollection;
        $collection->add(5, 3, 1, 2, 4, 6, 8, 7);

        // Act
        $even = $collection->even();

        // Assert: original order preserved, only even numbers
        $this->assertSame([2, 4, 6, 8], $even->toArray());
    }

    /**
     * Test that multiple filters can be combined.
     */
    public function test_multiple_filters_can_be_combined(): void
    {
        // Arrange
        $collection = new IntTypedCollection;
        $collection->add(-10, -8, -5, -3, 0, 2, 4, 5, 7, 9, 10, 12);

        // Act - Filter: positive, even, between 2 and 10
        $result = $collection
            ->positive()
            ->even()
            ->between(2, 10);

        // Assert: positive = [2,4,5,7,9,10,12], even = [2,4,10,12], between 2-10 = [2,4,10]
        $this->assertSame([2, 4, 10], $result->toArray());
    }
}
