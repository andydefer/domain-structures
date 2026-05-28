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

    public function test_constructor_sets_int_as_allowed_type(): void
    {
        $collection = new IntTypedCollection;

        $this->assertSame(['int'], $collection->getAllowedTypes());
    }

    public function test_collection_accepts_only_integer_values(): void
    {
        $collection = new IntTypedCollection;
        $collection->add(1, 2, 3, 4, 5, -10, -20, 0);

        $this->assertCount(8, $collection);
        $this->assertSame(1, $collection[0]);
        $this->assertSame(2, $collection[1]);
        $this->assertSame(-10, $collection[5]);
        $this->assertSame(0, $collection[7]);
    }

    public function test_collection_rejects_non_integer_values(): void
    {
        $collection = new IntTypedCollection;

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected type(s) int');

        $collection->add(3.14);
    }

    public function test_collection_rejects_string_numbers(): void
    {
        $collection = new IntTypedCollection;

        $this->expectException(\InvalidArgumentException::class);

        $collection->add('42');
    }

    // ==================== ZERO METHOD TESTS ====================

    public function test_zero_returns_only_zero_values(): void
    {
        $collection = new IntTypedCollection;
        $collection->add(-2, -1, 0, 1, 2, 0, 3, 0);

        $zeros = $collection->zero();

        $this->assertSame([0, 0, 0], $zeros->toArray());
        $this->assertCount(3, $zeros);
    }

    public function test_zero_returns_empty_collection_when_no_zeros(): void
    {
        $collection = new IntTypedCollection;
        $collection->add(1, 2, 3, 4, 5);

        $zeros = $collection->zero();

        $this->assertCount(0, $zeros);
        $this->assertTrue($zeros->isEmpty());
    }

    public function test_zero_on_empty_collection_returns_empty_collection(): void
    {
        $emptyCollection = new IntTypedCollection;

        $zeros = $emptyCollection->zero();

        $this->assertCount(0, $zeros);
        $this->assertTrue($zeros->isEmpty());
    }

    public function test_zero_returns_new_collection_instance(): void
    {
        $collection = new IntTypedCollection;
        $collection->add(0, 1, 2, 0, 3);

        $zeros = $collection->zero();

        $this->assertNotSame($collection, $zeros);
        $this->assertInstanceOf(IntTypedCollection::class, $zeros);
    }

    // ==================== NON_NEGATIVE METHOD TESTS ====================

    public function test_non_negative_returns_numbers_greater_than_or_equal_zero(): void
    {
        $collection = new IntTypedCollection;
        $collection->add(-5, -3, -1, 0, 1, 2, 3, 4, 5);

        $nonNegative = $collection->nonNegative();

        $this->assertSame([0, 1, 2, 3, 4, 5], $nonNegative->toArray());
        $this->assertCount(6, $nonNegative);
    }

    public function test_non_negative_includes_zero(): void
    {
        $collection = new IntTypedCollection;
        $collection->add(-2, -1, 0, 1, 2);

        $nonNegative = $collection->nonNegative();

        $this->assertContains(0, $nonNegative->toArray());
        $this->assertSame([0, 1, 2], $nonNegative->toArray());
    }

    public function test_non_negative_excludes_negative_numbers(): void
    {
        $collection = new IntTypedCollection;
        $collection->add(-5, -4, -3, -2, -1, 0, 1);

        $nonNegative = $collection->nonNegative();

        $this->assertNotContains(-5, $nonNegative->toArray());
        $this->assertNotContains(-1, $nonNegative->toArray());
        $this->assertSame([0, 1], $nonNegative->toArray());
    }

    public function test_non_negative_on_all_negative_returns_empty(): void
    {
        $collection = new IntTypedCollection;
        $collection->add(-5, -4, -3, -2, -1);

        $nonNegative = $collection->nonNegative();

        $this->assertCount(0, $nonNegative);
        $this->assertTrue($nonNegative->isEmpty());
    }

    public function test_non_negative_returns_new_collection_instance(): void
    {
        $collection = new IntTypedCollection;
        $collection->add(-2, -1, 0, 1, 2);

        $nonNegative = $collection->nonNegative();

        $this->assertNotSame($collection, $nonNegative);
        $this->assertInstanceOf(IntTypedCollection::class, $nonNegative);
    }

    // ==================== EVEN METHOD TESTS ====================

    public function test_even_returns_only_even_numbers(): void
    {
        $collection = new IntTypedCollection;
        $collection->add(1, 2, 3, 4, 5, 6, 7, 8, 9, 10);

        $even = $collection->even();

        $this->assertSame([2, 4, 6, 8, 10], $even->toArray());
        $this->assertCount(5, $even);
    }

    public function test_even_includes_negative_even_numbers(): void
    {
        $collection = new IntTypedCollection;
        $collection->add(-10, -9, -8, -7, -6, -5, -4, -3, -2, -1);

        $even = $collection->even();

        $this->assertSame([-10, -8, -6, -4, -2], $even->toArray());
    }

    public function test_even_includes_zero(): void
    {
        $collection = new IntTypedCollection;
        $collection->add(-2, -1, 0, 1, 2);

        $even = $collection->even();

        $this->assertContains(0, $even->toArray());
        $this->assertSame([-2, 0, 2], $even->toArray());
    }

    public function test_even_returns_empty_when_no_even_numbers(): void
    {
        $collection = new IntTypedCollection;
        $collection->add(1, 3, 5, 7, 9);

        $even = $collection->even();

        $this->assertCount(0, $even);
        $this->assertTrue($even->isEmpty());
    }

    public function test_even_returns_new_collection_instance(): void
    {
        $collection = new IntTypedCollection;
        $collection->add(1, 2, 3, 4);

        $even = $collection->even();

        $this->assertNotSame($collection, $even);
        $this->assertInstanceOf(IntTypedCollection::class, $even);
    }

    // ==================== ODD METHOD TESTS ====================

    public function test_odd_returns_only_odd_numbers(): void
    {
        $collection = new IntTypedCollection;
        $collection->add(1, 2, 3, 4, 5, 6, 7, 8, 9, 10);

        $odd = $collection->odd();

        $this->assertSame([1, 3, 5, 7, 9], $odd->toArray());
        $this->assertCount(5, $odd);
    }

    public function test_odd_includes_negative_odd_numbers(): void
    {
        $collection = new IntTypedCollection;
        $collection->add(-10, -9, -8, -7, -6, -5, -4, -3, -2, -1);

        $odd = $collection->odd();

        $this->assertSame([-9, -7, -5, -3, -1], $odd->toArray());
    }

    public function test_odd_excludes_zero(): void
    {
        $collection = new IntTypedCollection;
        $collection->add(-2, -1, 0, 1, 2);

        $odd = $collection->odd();

        $this->assertNotContains(0, $odd->toArray());
        $this->assertSame([-1, 1], $odd->toArray());
    }

    public function test_odd_returns_empty_when_no_odd_numbers(): void
    {
        $collection = new IntTypedCollection;
        $collection->add(2, 4, 6, 8, 10);

        $odd = $collection->odd();

        $this->assertCount(0, $odd);
        $this->assertTrue($odd->isEmpty());
    }

    public function test_odd_returns_new_collection_instance(): void
    {
        $collection = new IntTypedCollection;
        $collection->add(1, 2, 3, 4);

        $odd = $collection->odd();

        $this->assertNotSame($collection, $odd);
        $this->assertInstanceOf(IntTypedCollection::class, $odd);
    }

    // ==================== MEDIAN METHOD TESTS ====================

    public function test_median_with_odd_count_returns_middle_value(): void
    {
        $collection = new IntTypedCollection;
        $collection->add(1, 3, 2, 5, 4);

        $median = $collection->median();

        $this->assertSame(3.0, $median);
    }

    public function test_median_with_even_count_returns_average_of_two_middle_values(): void
    {
        $collection = new IntTypedCollection;
        $collection->add(1, 3, 2, 4);

        $median = $collection->median();

        $this->assertSame(2.5, $median);
    }

    public function test_median_with_sorted_array_works_correctly(): void
    {
        $collection = new IntTypedCollection;
        $collection->add(10, 20, 30, 40, 50);

        $median = $collection->median();

        $this->assertSame(30.0, $median);
    }

    public function test_median_with_unsorted_array_sorts_automatically(): void
    {
        $collection = new IntTypedCollection;
        $collection->add(50, 10, 40, 20, 30);

        $median = $collection->median();

        $this->assertSame(30.0, $median);
    }

    public function test_median_with_negative_numbers_works(): void
    {
        $collection = new IntTypedCollection;
        $collection->add(-5, -3, -1, -2, -4);

        $median = $collection->median();

        $this->assertSame(-3.0, $median);
    }

    public function test_median_with_negative_and_positive_works(): void
    {
        $collection = new IntTypedCollection;
        $collection->add(-5, -2, 0, 2, 5);

        $median = $collection->median();

        $this->assertSame(0.0, $median);
    }

    public function test_median_with_single_element_returns_that_element(): void
    {
        $collection = new IntTypedCollection;
        $collection->add(42);

        $median = $collection->median();

        $this->assertSame(42.0, $median);
    }

    public function test_median_with_two_elements_returns_their_average(): void
    {
        $collection = new IntTypedCollection;
        $collection->add(10, 20);

        $median = $collection->median();

        $this->assertSame(15.0, $median);
    }

    public function test_median_with_empty_collection_returns_zero(): void
    {
        $emptyCollection = new IntTypedCollection;

        $median = $emptyCollection->median();

        $this->assertSame(0.0, $median);
    }

    public function test_median_preserves_original_collection_order(): void
    {
        $collection = new IntTypedCollection;
        $originalOrder = [50, 10, 40, 20, 30];
        $collection->add(...$originalOrder);

        $median = $collection->median();

        $this->assertSame([50, 10, 40, 20, 30], $collection->toArray());
        $this->assertSame(30.0, $median);
    }

    // ==================== INHERITED METHOD TESTS ====================

    public function test_positive_method_works(): void
    {
        $collection = new IntTypedCollection;
        $collection->add(-5, -3, -1, 0, 1, 2, 3, 4, 5);

        $positive = $collection->positive();

        $this->assertSame([1, 2, 3, 4, 5], $positive->toArray());
    }

    public function test_negative_method_works(): void
    {
        $collection = new IntTypedCollection;
        $collection->add(-5, -4, -3, -2, -1, 0, 1, 2, 3);

        $negative = $collection->negative();

        $this->assertSame([-5, -4, -3, -2, -1], $negative->toArray());
    }

    public function test_between_method_works(): void
    {
        $collection = new IntTypedCollection;
        $collection->add(1, 5, 10, 15, 20, 25, 30);

        $between = $collection->between(5, 25);

        $this->assertSame([5, 10, 15, 20, 25], $between->toArray());
    }

    public function test_average_method_works(): void
    {
        $collection = new IntTypedCollection;
        $collection->add(10, 20, 30, 40, 50);

        $average = $collection->average();

        $this->assertSame(30.0, $average);
    }

    public function test_sum_method_works(): void
    {
        $collection = new IntTypedCollection;
        $collection->add(10, 20, 30, 40, 50);

        $sum = $collection->sum();

        $this->assertSame(150, $sum);
    }

    public function test_range_method_works(): void
    {
        $collection = IntTypedCollection::range(1, 10, 2);

        $this->assertSame([1, 3, 5, 7, 9], $collection->toArray());
    }

    // ==================== CHAINING OPERATIONS TESTS ====================

    public function test_chaining_multiple_operations_works(): void
    {
        $collection = new IntTypedCollection;
        $collection->add(-10, -5, -2, 0, 3, 6, 9, 12, 15);

        $result = $collection
            ->positive()
            ->even()
            ->median();

        $this->assertSame(9.0, $result);
    }

    public function test_even_then_odd_chain_returns_empty(): void
    {
        $collection = new IntTypedCollection;
        $collection->add(1, 2, 3, 4, 5, 6, 7, 8, 9, 10);

        $result = $collection->even()->odd();

        $this->assertCount(0, $result);
        $this->assertTrue($result->isEmpty());
    }

    public function test_odd_then_even_chain_returns_empty(): void
    {
        $collection = new IntTypedCollection;
        $collection->add(1, 2, 3, 4, 5, 6, 7, 8, 9, 10);

        $result = $collection->odd()->even();

        $this->assertCount(0, $result);
        $this->assertTrue($result->isEmpty());
    }

    public function test_zero_then_positive_chain_returns_empty(): void
    {
        $collection = new IntTypedCollection;
        $collection->add(-2, -1, 0, 1, 2);

        $result = $collection->zero()->positive();

        $this->assertCount(0, $result);
    }

    public function test_non_negative_then_even_chain(): void
    {
        $collection = new IntTypedCollection;
        $collection->add(-5, -4, -3, -2, -1, 0, 1, 2, 3, 4, 5);

        $result = $collection->nonNegative()->even();

        $this->assertSame([0, 2, 4], $result->toArray());
    }

    // ==================== EDGE CASES TESTS ====================

    public function test_collection_handles_very_large_integers(): void
    {
        $collection = new IntTypedCollection;
        $collection->add(PHP_INT_MAX - 2, PHP_INT_MAX - 1, PHP_INT_MAX);

        $sum = $collection->sum();
        $median = $collection->median();

        // La somme peut dépasser PHP_INT_MAX, donc accepter int ou float
        $this->assertTrue(is_int($sum) || is_float($sum), 'Sum should be int or float');
        $this->assertIsFloat($median);

        $expectedSum = (PHP_INT_MAX - 2) + (PHP_INT_MAX - 1) + PHP_INT_MAX;
        $this->assertEqualsWithDelta($expectedSum, $sum, 1e-10);
    }

    public function test_collection_handles_very_small_integers(): void
    {
        $collection = new IntTypedCollection;
        $collection->add(PHP_INT_MIN + 2, PHP_INT_MIN + 1, PHP_INT_MIN);

        $sum = $collection->sum();
        $median = $collection->median();

        // La somme peut dépasser PHP_INT_MIN, donc accepter int ou float
        $this->assertTrue(is_int($sum) || is_float($sum), 'Sum should be int or float');
        $this->assertIsFloat($median);

        $expectedSum = (PHP_INT_MIN + 2) + (PHP_INT_MIN + 1) + PHP_INT_MIN;
        $this->assertEqualsWithDelta($expectedSum, $sum, 1e-10);
    }

    public function test_collection_handles_duplicate_values(): void
    {
        $collection = new IntTypedCollection;
        $collection->add(1, 1, 2, 2, 3, 3, 3);

        $median = $collection->median();

        $this->assertSame(2.0, $median);
    }

    public function test_collection_handles_single_element_for_all_operations(): void
    {
        $collection = new IntTypedCollection;
        $collection->add(5);

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

    public function test_collection_preserves_order_after_filtering(): void
    {
        $collection = new IntTypedCollection;
        $collection->add(5, 3, 1, 2, 4, 6, 8, 7);

        $even = $collection->even();

        $this->assertSame([2, 4, 6, 8], $even->toArray());
    }

    public function test_multiple_filters_can_be_combined(): void
    {
        $collection = new IntTypedCollection;
        $collection->add(-10, -8, -5, -3, 0, 2, 4, 5, 7, 9, 10, 12);

        $result = $collection
            ->positive()
            ->even()
            ->between(2, 10);

        $this->assertSame([2, 4, 10], $result->toArray());
    }
}
