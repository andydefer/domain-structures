<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Unit\Collections\Utility;

use AndyDefer\DomainStructures\Collections\Utility\AbstractNumberTypedCollection;
use AndyDefer\DomainStructures\Collections\Utility\FloatTypedCollection;
use AndyDefer\DomainStructures\Collections\Utility\IntTypedCollection;
use AndyDefer\DomainStructures\Collections\Utility\NumberTypedCollection;
use AndyDefer\DomainStructures\Tests\TestCase;
use InvalidArgumentException;

/**
 * Unit tests for AbstractNumberTypedCollection class.
 *
 * This test suite validates the abstract numeric collection functionality:
 * - Filtering positive/negative numbers
 * - Range filtering (between)
 * - Mathematical operations (average, sum)
 * - Static range generation
 * - Edge cases (empty collections, single elements, invalid inputs)
 *
 * The tests follow the AAA pattern (Arrange, Act, Assert).
 */
final class AbstractNumberTypedCollectionTest extends TestCase
{
    // ==================== POSITIVE METHOD TESTS ====================

    /**
     * Test that positive method returns only positive numbers.
     */
    public function test_positive_returns_only_positive_numbers(): void
    {
        // Arrange
        $collection = new IntTypedCollection;
        $collection->add(-5, -3, 0, 2, 4, 7, -1);

        // Act
        $positive = $collection->positive();

        // Assert
        $this->assertSame([2, 4, 7], $positive->toArray());
        $this->assertCount(3, $positive);
    }

    /**
     * Test that positive method excludes zero.
     */
    public function test_positive_excludes_zero(): void
    {
        // Arrange
        $collection = new IntTypedCollection;
        $collection->add(-2, -1, 0, 1, 2);

        // Act
        $positive = $collection->positive();

        // Assert
        $this->assertNotContains(0, $positive->toArray());
        $this->assertSame([1, 2], $positive->toArray());
    }

    /**
     * Test that positive on empty collection returns empty collection.
     */
    public function test_positive_on_empty_collection_returns_empty_collection(): void
    {
        // Arrange
        $emptyCollection = new IntTypedCollection;

        // Act
        $positive = $emptyCollection->positive();

        // Assert
        $this->assertCount(0, $positive);
        $this->assertTrue($positive->isEmpty());
    }

    /**
     * Test that positive on all negative numbers returns empty collection.
     */
    public function test_positive_on_all_negative_numbers_returns_empty_collection(): void
    {
        // Arrange
        $collection = new IntTypedCollection;
        $collection->add(-5, -4, -3, -2, -1);

        // Act
        $positive = $collection->positive();

        // Assert
        $this->assertCount(0, $positive);
        $this->assertTrue($positive->isEmpty());
    }

    /**
     * Test that positive method works with floats.
     */
    public function test_positive_works_with_floats(): void
    {
        // Arrange
        $collection = new FloatTypedCollection;
        $collection->add(-2.5, -1.5, 0.0, 1.5, 2.5, 3.7);

        // Act
        $positive = $collection->positive();

        // Assert
        $this->assertSame([1.5, 2.5, 3.7], $positive->toArray());
        $this->assertCount(3, $positive);
    }

    // ==================== NEGATIVE METHOD TESTS ====================

    /**
     * Test that negative method returns only negative numbers.
     */
    public function test_negative_returns_only_negative_numbers(): void
    {
        // Arrange
        $collection = new IntTypedCollection;
        $collection->add(-5, -3, 0, 2, 4, -7, -1);

        // Act
        $negative = $collection->negative();

        // Assert
        $this->assertSame([-5, -3, -7, -1], $negative->toArray());
        $this->assertCount(4, $negative);
    }

    /**
     * Test that negative method excludes zero.
     */
    public function test_negative_excludes_zero(): void
    {
        // Arrange
        $collection = new IntTypedCollection;
        $collection->add(-2, -1, 0, 1, 2);

        // Act
        $negative = $collection->negative();

        // Assert
        $this->assertNotContains(0, $negative->toArray());
        $this->assertSame([-2, -1], $negative->toArray());
    }

    /**
     * Test that negative on empty collection returns empty collection.
     */
    public function test_negative_on_empty_collection_returns_empty_collection(): void
    {
        // Arrange
        $emptyCollection = new IntTypedCollection;

        // Act
        $negative = $emptyCollection->negative();

        // Assert
        $this->assertCount(0, $negative);
        $this->assertTrue($negative->isEmpty());
    }

    /**
     * Test that negative on all positive numbers returns empty collection.
     */
    public function test_negative_on_all_positive_numbers_returns_empty_collection(): void
    {
        // Arrange
        $collection = new IntTypedCollection;
        $collection->add(1, 2, 3, 4, 5);

        // Act
        $negative = $collection->negative();

        // Assert
        $this->assertCount(0, $negative);
        $this->assertTrue($negative->isEmpty());
    }

    /**
     * Test that negative method works with floats.
     */
    public function test_negative_works_with_floats(): void
    {
        // Arrange
        $collection = new FloatTypedCollection;
        $collection->add(-2.5, -1.5, 0.0, 1.5, -3.7, -0.5);

        // Act
        $negative = $collection->negative();

        // Assert
        $this->assertSame([-2.5, -1.5, -3.7, -0.5], $negative->toArray());
        $this->assertCount(4, $negative);
    }

    // ==================== BETWEEN METHOD TESTS ====================

    /**
     * Test that between returns numbers within inclusive range.
     */
    public function test_between_returns_numbers_within_inclusive_range(): void
    {
        // Arrange
        $collection = new IntTypedCollection;
        $collection->add(1, 2, 3, 4, 5, 6, 7, 8, 9, 10);

        // Act
        $between = $collection->between(3, 7);

        // Assert
        $this->assertSame([3, 4, 5, 6, 7], $between->toArray());
        $this->assertCount(5, $between);
    }

    /**
     * Test that between includes the lower bound.
     */
    public function test_between_includes_lower_bound(): void
    {
        // Arrange
        $collection = new IntTypedCollection;
        $collection->add(5, 6, 7, 8, 9);

        // Act
        $between = $collection->between(5, 9);

        // Assert
        $this->assertContains(5, $between->toArray());
        $this->assertSame([5, 6, 7, 8, 9], $between->toArray());
    }

    /**
     * Test that between includes the upper bound.
     */
    public function test_between_includes_upper_bound(): void
    {
        // Arrange
        $collection = new IntTypedCollection;
        $collection->add(1, 2, 3, 4, 5);

        // Act
        $between = $collection->between(1, 5);

        // Assert
        $this->assertContains(5, $between->toArray());
        $this->assertSame([1, 2, 3, 4, 5], $between->toArray());
    }

    /**
     * Test that between returns empty when no numbers in range.
     */
    public function test_between_returns_empty_when_no_numbers_in_range(): void
    {
        // Arrange
        $collection = new IntTypedCollection;
        $collection->add(1, 2, 3, 4, 5);

        // Act
        $between = $collection->between(10, 20);

        // Assert
        $this->assertCount(0, $between);
        $this->assertTrue($between->isEmpty());
    }

    /**
     * Test that between works with negative numbers.
     */
    public function test_between_works_with_negative_numbers(): void
    {
        // Arrange
        $collection = new IntTypedCollection;
        $collection->add(-10, -5, -3, -1, 0, 1, 3, 5, 10);

        // Act
        $between = $collection->between(-5, 5);

        // Assert
        $this->assertSame([-5, -3, -1, 0, 1, 3, 5], $between->toArray());
    }

    /**
     * Test that between works with floats.
     */
    public function test_between_works_with_floats(): void
    {
        // Arrange
        $collection = new FloatTypedCollection;
        $collection->add(1.5, 2.3, 3.7, 4.2, 5.9, 6.1, 7.8);

        // Act
        $between = $collection->between(2.0, 6.0);

        // Assert
        $this->assertSame([2.3, 3.7, 4.2, 5.9], $between->toArray());
    }

    /**
     * Test that between throws exception when min > max.
     */
    public function test_between_throws_exception_when_min_greater_than_max(): void
    {
        // Arrange
        $collection = new IntTypedCollection;
        $collection->add(1, 2, 3, 4, 5);

        // Act & Assert
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Minimum value (10) cannot be greater than maximum value (5)');

        $collection->between(10, 5);
    }

    // ==================== AVERAGE METHOD TESTS ====================

    /**
     * Test that average returns correct arithmetic mean.
     */
    public function test_average_returns_correct_arithmetic_mean(): void
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
     * Test that average returns 0.0 for empty collection.
     */
    public function test_average_returns_zero_for_empty_collection(): void
    {
        // Arrange
        $emptyCollection = new IntTypedCollection;

        // Act
        $average = $emptyCollection->average();

        // Assert
        $this->assertSame(0.0, $average);
    }

    /**
     * Test that average with single element returns that element as float.
     */
    public function test_average_with_single_element_returns_that_element_as_float(): void
    {
        // Arrange
        $collection = new IntTypedCollection;
        $collection->add(42);

        // Act
        $average = $collection->average();

        // Assert
        $this->assertSame(42.0, $average);
    }

    /**
     * Test that average with negative numbers works correctly.
     */
    public function test_average_with_negative_numbers_works_correctly(): void
    {
        // Arrange
        $collection = new IntTypedCollection;
        $collection->add(-10, -20, -30, -40, -50);

        // Act
        $average = $collection->average();

        // Assert
        $this->assertSame(-30.0, $average);
    }

    /**
     * Test that average with floats returns float.
     */
    public function test_average_with_floats_returns_float(): void
    {
        // Arrange
        $collection = new FloatTypedCollection;
        $collection->add(1.5, 2.5, 3.5, 4.5);

        // Act
        $average = $collection->average();

        // Assert
        $this->assertSame(3.0, $average);
    }

    /**
     * Test that average with mixed numbers works correctly.
     */
    public function test_average_with_mixed_numbers_works_correctly(): void
    {
        // This test uses NumberTypedCollection which accepts both int and float
        $collection = new NumberTypedCollection;
        $collection->add(10, 20.5, 30, 40.5, 50);

        // Act
        $average = $collection->average();

        // Assert
        $this->assertSame(30.2, $average);
    }

    // ==================== SUM METHOD TESTS ====================

    /**
     * Test that sum returns correct total.
     */
    public function test_sum_returns_correct_total(): void
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
     * Test that sum returns 0 for empty collection.
     */
    public function test_sum_returns_zero_for_empty_collection(): void
    {
        // Arrange
        $emptyCollection = new IntTypedCollection;

        // Act
        $sum = $emptyCollection->sum();

        // Assert
        $this->assertSame(0, $sum);
    }

    /**
     * Test that sum with single element returns that element.
     */
    public function test_sum_with_single_element_returns_that_element(): void
    {
        // Arrange
        $collection = new IntTypedCollection;
        $collection->add(42);

        // Act
        $sum = $collection->sum();

        // Assert
        $this->assertSame(42, $sum);
    }

    /**
     * Test that sum with negative numbers works correctly.
     */
    public function test_sum_with_negative_numbers_works_correctly(): void
    {
        // Arrange
        $collection = new IntTypedCollection;
        $collection->add(-10, -20, -30, -40, -50);

        // Act
        $sum = $collection->sum();

        // Assert
        $this->assertSame(-150, $sum);
    }

    /**
     * Test that sum with floats returns float.
     */
    public function test_sum_with_floats_returns_float(): void
    {
        // Arrange
        $collection = new FloatTypedCollection;
        $collection->add(1.5, 2.5, 3.5, 4.5);

        // Act
        $sum = $collection->sum();

        // Assert
        $this->assertSame(12.0, $sum);
    }

    /**
     * Test that sum with mixed int and float returns float.
     */
    public function test_sum_with_mixed_int_and_float_returns_float(): void
    {
        // Arrange
        $collection = new NumberTypedCollection;
        $collection->add(10, 20.5, 30, 40.5, 50);

        // Act
        $sum = $collection->sum();

        // Assert
        $this->assertSame(151.0, $sum);
    }

    // ==================== RANGE METHOD TESTS ====================

    /**
     * Test that range generates ascending sequence with step 1.
     */
    public function test_range_generates_ascending_sequence_with_step_one(): void
    {
        // Act
        $collection = IntTypedCollection::range(1, 5);

        // Assert
        $this->assertSame([1, 2, 3, 4, 5], $collection->toArray());
        $this->assertCount(5, $collection);
    }

    /**
     * Test that range generates ascending sequence with custom step.
     */
    public function test_range_generates_ascending_sequence_with_custom_step(): void
    {
        // Act
        $collection = IntTypedCollection::range(1, 10, 2);

        // Assert
        $this->assertSame([1, 3, 5, 7, 9], $collection->toArray());
    }

    /**
     * Test that range generates descending sequence with negative step.
     */
    public function test_range_generates_descending_sequence_with_negative_step(): void
    {
        // Act
        $collection = IntTypedCollection::range(10, 1, -2);

        // Assert
        $this->assertSame([10, 8, 6, 4, 2], $collection->toArray());
    }

    /**
     * Test that range with start equals end returns single element.
     */
    public function test_range_with_start_equals_end_returns_single_element(): void
    {
        // Act
        $collection = IntTypedCollection::range(5, 5);

        // Assert
        $this->assertSame([5], $collection->toArray());
        $this->assertCount(1, $collection);
    }

    /**
     * Test that range with positive step when start > end returns empty.
     */
    public function test_range_with_positive_step_when_start_greater_than_end_returns_empty(): void
    {
        // Act
        $collection = IntTypedCollection::range(10, 1, 1);

        // Assert
        $this->assertCount(0, $collection);
        $this->assertTrue($collection->isEmpty());
    }

    /**
     * Test that range with negative step when start < end returns empty.
     */
    public function test_range_with_negative_step_when_start_less_than_end_returns_empty(): void
    {
        // Act
        $collection = IntTypedCollection::range(1, 10, -1);

        // Assert
        $this->assertCount(0, $collection);
        $this->assertTrue($collection->isEmpty());
    }

    /**
     * Test that range with float values works.
     */
    public function test_range_with_float_values_works(): void
    {
        // Act
        $collection = FloatTypedCollection::range(1.5, 4.5, 1.0);

        // Assert
        $this->assertSame([1.5, 2.5, 3.5, 4.5], $collection->toArray());
    }

    /**
     * Test that range with float step works.
     */
    public function test_range_with_float_step_works(): void
    {
        // Act
        $collection = FloatTypedCollection::range(1.0, 2.0, 0.25);

        // Assert
        $this->assertSame([1.0, 1.25, 1.5, 1.75, 2.0], $collection->toArray());
    }

    /**
     * Test that range with negative step and floats works.
     */
    public function test_range_with_negative_step_and_floats_works(): void
    {
        // Act
        $collection = FloatTypedCollection::range(5.0, 1.0, -1.0);

        // Assert
        $this->assertSame([5.0, 4.0, 3.0, 2.0, 1.0], $collection->toArray());
    }

    /**
     * Test that range throws exception when step is zero.
     */
    public function test_range_throws_exception_when_step_is_zero(): void
    {
        // Act & Assert
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Step value cannot be zero');

        IntTypedCollection::range(1, 10, 0);
    }

    /**
     * Test that range returns new collection instance.
     */
    public function test_range_returns_new_collection_instance(): void
    {
        // Act
        $collection = IntTypedCollection::range(1, 5);

        // Assert
        $this->assertInstanceOf(IntTypedCollection::class, $collection);
    }

    // ==================== EDGE CASES TESTS ====================

    /**
     * Test that operations preserve collection type.
     */
    public function test_operations_preserve_collection_type(): void
    {
        // Arrange
        $intCollection = new IntTypedCollection;
        $intCollection->add(-3, 0, 5, 8, -2);

        // Act
        $positive = $intCollection->positive();
        $negative = $intCollection->negative();
        $between = $intCollection->between(0, 10);

        // Assert
        $this->assertInstanceOf(IntTypedCollection::class, $positive);
        $this->assertInstanceOf(IntTypedCollection::class, $negative);
        $this->assertInstanceOf(IntTypedCollection::class, $between);
    }

    /**
     * Test that chaining operations works correctly.
     */
    public function test_chaining_operations_works_correctly(): void
    {
        // Arrange
        $collection = new IntTypedCollection;
        $collection->add(-10, -5, -1, 0, 2, 4, 6, 8, 10, 15, 20);

        // Act - Get positive numbers between 5 and 15, then average them
        $result = $collection
            ->positive()
            ->between(5, 15)
            ->average();

        // Assert: 6, 8, 10, 15 -> average = (6+8+10+15)/4 = 39/4 = 9.75
        $this->assertSame(9.75, $result);
    }

    /**
     * Test that very large numbers are handled correctly.
     */
    public function test_very_large_numbers_are_handled_correctly(): void
    {
        // Arrange
        $collection = new IntTypedCollection;
        $collection->add(PHP_INT_MAX - 2, PHP_INT_MAX - 1, PHP_INT_MAX);

        // Act
        $sum = $collection->sum();
        $average = $collection->average();

        // Assert
        $this->assertIsInt($sum);
        $this->assertIsFloat($average);
    }

    /**
     * Test that precision is maintained for decimal numbers.
     */
    public function test_precision_is_maintained_for_decimal_numbers(): void
    {
        // Arrange
        $collection = new FloatTypedCollection;
        $collection->add(0.1, 0.2, 0.3, 0.4, 0.5);

        // Act
        $sum = $collection->sum();
        $average = $collection->average();

        // Assert
        $this->assertEqualsWithDelta(1.5, $sum, 0.0001);
        $this->assertEqualsWithDelta(0.3, $average, 0.0001);
    }
}
