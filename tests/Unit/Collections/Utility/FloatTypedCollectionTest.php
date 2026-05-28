<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Unit\Collections\Utility;

use AndyDefer\DomainStructures\Collections\Utility\FloatTypedCollection;
use AndyDefer\DomainStructures\Collections\Utility\IntTypedCollection;
use AndyDefer\DomainStructures\Tests\TestCase;

/**
 * Unit tests for FloatTypedCollection class.
 *
 * This test suite validates the float-specific collection functionality:
 * - Type safety (only float values allowed)
 * - Rounding operations (round, ceil, floor)
 * - Precision formatting
 * - Inheritance from AbstractNumberTypedCollection
 *
 * The tests follow the AAA pattern (Arrange, Act, Assert).
 */
final class FloatTypedCollectionTest extends TestCase
{
    // ==================== CONSTRUCTOR TESTS ====================

    /**
     * Test that FloatTypedCollection constructor sets float as allowed type.
     */
    public function test_constructor_sets_float_as_allowed_type(): void
    {
        // Arrange & Act
        $collection = new FloatTypedCollection;

        // Assert
        $this->assertSame(['float'], $collection->getAllowedTypes());
    }

    /**
     * Test that FloatTypedCollection accepts only float values.
     */
    public function test_collection_accepts_only_float_values(): void
    {
        // Arrange
        $collection = new FloatTypedCollection;

        // Act
        $collection->add(1.5, 2.7, 3.14, 4.99);

        // Assert
        $this->assertCount(4, $collection);
        $this->assertSame(1.5, $collection[0]);
        $this->assertSame(2.7, $collection[1]);
        $this->assertSame(3.14, $collection[2]);
        $this->assertSame(4.99, $collection[3]);
    }

    /**
     * Test that collection rejects non-float values.
     */
    public function test_collection_rejects_non_float_values(): void
    {
        // Arrange
        $collection = new FloatTypedCollection;

        // Act & Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected type(s) float');

        $collection->add(42); // int, not float
    }

    /**
     * Test that collection rejects string values.
     */
    public function test_collection_rejects_string_values(): void
    {
        // Arrange
        $collection = new FloatTypedCollection;

        // Act & Assert
        $this->expectException(\InvalidArgumentException::class);

        $collection->add('3.14');
    }

    // ==================== ROUND METHOD TESTS ====================

    /**
     * Test that round rounds to nearest integer by default.
     */
    public function test_round_rounds_to_nearest_integer_by_default(): void
    {
        // Arrange
        $collection = new FloatTypedCollection;
        $collection->add(1.4, 1.5, 2.3, 2.8, 3.49, 3.51);

        // Act
        $rounded = $collection->round();

        // Assert
        $this->assertSame([1.0, 2.0, 2.0, 3.0, 3.0, 4.0], $rounded->toArray());
        $this->assertCount(6, $rounded);
    }

    /**
     * Test that round with precision parameter works correctly.
     */
    public function test_round_with_precision_parameter_works_correctly(): void
    {
        // Arrange
        $collection = new FloatTypedCollection;
        $collection->add(1.2345, 2.3456, 3.4567, 4.5678);

        // Act
        $rounded1 = $collection->round(1);
        $rounded2 = $collection->round(2);
        $rounded3 = $collection->round(3);

        // Assert - 1 decimal
        $this->assertSame([1.2, 2.3, 3.5, 4.6], $rounded1->toArray());

        // Assert - 2 decimals
        $this->assertSame([1.23, 2.35, 3.46, 4.57], $rounded2->toArray());

        // Assert - 3 decimals
        $this->assertSame([1.235, 2.346, 3.457, 4.568], $rounded3->toArray());
    }

    /**
     * Test that round with negative precision works.
     */
    public function test_round_with_negative_precision_works(): void
    {
        // Arrange
        $collection = new FloatTypedCollection;
        $collection->add(123.45, 678.90, 1234.56);

        // Act
        $rounded = $collection->round(-1);

        // Assert - round to tens
        $this->assertSame([120.0, 680.0, 1230.0], $rounded->toArray());
    }

    /**
     * Test that round with zero precision works same as default.
     */
    public function test_round_with_zero_precision_works_same_as_default(): void
    {
        // Arrange
        $collection = new FloatTypedCollection;
        $collection->add(1.4, 1.5, 2.7, 2.3);

        // Act
        $roundedZero = $collection->round(0);
        $roundedDefault = $collection->round();

        // Assert
        $this->assertSame($roundedDefault->toArray(), $roundedZero->toArray());
    }

    /**
     * Test that round handles negative numbers correctly.
     */
    public function test_round_handles_negative_numbers_correctly(): void
    {
        // Arrange
        $collection = new FloatTypedCollection;
        $collection->add(-1.4, -1.5, -2.3, -2.8);

        // Act
        $rounded = $collection->round();

        // Assert
        $this->assertSame([-1.0, -2.0, -2.0, -3.0], $rounded->toArray());
    }

    /**
     * Test that round handles very small numbers correctly.
     */
    public function test_round_handles_very_small_numbers_correctly(): void
    {
        // Arrange
        $collection = new FloatTypedCollection;
        $collection->add(0.0001, 0.0005, 0.0009);

        // Act
        $rounded = $collection->round(3);

        // Assert
        $this->assertSame([0.0, 0.001, 0.001], $rounded->toArray());
    }

    /**
     * Test that round returns new collection instance.
     */
    public function test_round_returns_new_collection_instance(): void
    {
        // Arrange
        $collection = new FloatTypedCollection;
        $collection->add(1.1, 2.2, 3.3);

        // Act
        $rounded = $collection->round();

        // Assert
        $this->assertNotSame($collection, $rounded);
        $this->assertInstanceOf(FloatTypedCollection::class, $rounded);
    }

    /**
     * Test that original collection is unchanged after round.
     */
    public function test_original_collection_is_unchanged_after_round(): void
    {
        // Arrange
        $collection = new FloatTypedCollection;
        $collection->add(1.4, 2.6, 3.5);

        // Act
        $rounded = $collection->round();

        // Assert
        $this->assertSame([1.4, 2.6, 3.5], $collection->toArray());
        $this->assertSame([1.0, 3.0, 4.0], $rounded->toArray());
    }

    /**
     * Test that round on empty collection returns empty collection.
     */
    public function test_round_on_empty_collection_returns_empty_collection(): void
    {
        // Arrange
        $emptyCollection = new FloatTypedCollection;

        // Act
        $rounded = $emptyCollection->round();

        // Assert
        $this->assertCount(0, $rounded);
        $this->assertTrue($rounded->isEmpty());
    }

    // ==================== CEIL METHOD TESTS ====================

    /**
     * Test that ceil rounds up to next integer.
     */
    public function test_ceil_rounds_up_to_next_integer(): void
    {
        // Arrange
        $collection = new FloatTypedCollection;
        $collection->add(1.1, 1.9, 2.0, 2.1, 3.5);

        // Act
        $ceiled = $collection->ceil();

        // Assert
        $this->assertSame([2.0, 2.0, 2.0, 3.0, 4.0], $ceiled->toArray());
    }

    /**
     * Test that ceil handles negative numbers correctly.
     */
    public function test_ceil_handles_negative_numbers_correctly(): void
    {
        // Arrange
        $collection = new FloatTypedCollection;
        $collection->add(-1.1, -1.9, -2.0, -2.1, -3.5);

        // Act
        $ceiled = $collection->ceil();

        // Assert
        $this->assertSame([-1.0, -1.0, -2.0, -2.0, -3.0], $ceiled->toArray());
    }

    /**
     * Test that ceil handles zero correctly.
     */
    public function test_ceil_handles_zero_correctly(): void
    {
        // Arrange
        $collection = new FloatTypedCollection;
        $collection->add(0.0, 0.1, -0.1);

        // Act
        $ceiled = $collection->ceil();

        // Assert
        $this->assertSame([0.0, 1.0, 0.0], $ceiled->toArray());
    }

    /**
     * Test that ceil returns new collection instance.
     */
    public function test_ceil_returns_new_collection_instance(): void
    {
        // Arrange
        $collection = new FloatTypedCollection;
        $collection->add(1.1, 2.2, 3.3);

        // Act
        $ceiled = $collection->ceil();

        // Assert
        $this->assertNotSame($collection, $ceiled);
        $this->assertInstanceOf(FloatTypedCollection::class, $ceiled);
    }

    /**
     * Test that original collection is unchanged after ceil.
     */
    public function test_original_collection_is_unchanged_after_ceil(): void
    {
        // Arrange
        $collection = new FloatTypedCollection;
        $collection->add(1.1, 2.9, 3.5);

        // Act
        $ceiled = $collection->ceil();

        // Assert
        $this->assertSame([1.1, 2.9, 3.5], $collection->toArray());
        $this->assertSame([2.0, 3.0, 4.0], $ceiled->toArray());
    }

    /**
     * Test that ceil on empty collection returns empty collection.
     */
    public function test_ceil_on_empty_collection_returns_empty_collection(): void
    {
        // Arrange
        $emptyCollection = new FloatTypedCollection;

        // Act
        $ceiled = $emptyCollection->ceil();

        // Assert
        $this->assertCount(0, $ceiled);
        $this->assertTrue($ceiled->isEmpty());
    }

    // ==================== FLOOR METHOD TESTS ====================

    /**
     * Test that floor rounds down to previous integer.
     */
    public function test_floor_rounds_down_to_previous_integer(): void
    {
        // Arrange
        $collection = new FloatTypedCollection;
        $collection->add(1.1, 1.9, 2.0, 2.1, 3.5);

        // Act
        $floored = $collection->floor();

        // Assert
        $this->assertSame([1.0, 1.0, 2.0, 2.0, 3.0], $floored->toArray());
    }

    /**
     * Test that floor handles negative numbers correctly.
     */
    public function test_floor_handles_negative_numbers_correctly(): void
    {
        // Arrange
        $collection = new FloatTypedCollection;
        $collection->add(-1.1, -1.9, -2.0, -2.1, -3.5);

        // Act
        $floored = $collection->floor();

        // Assert
        $this->assertSame([-2.0, -2.0, -2.0, -3.0, -4.0], $floored->toArray());
    }

    /**
     * Test that floor handles zero correctly.
     */
    public function test_floor_handles_zero_correctly(): void
    {
        // Arrange
        $collection = new FloatTypedCollection;
        $collection->add(0.0, 0.1, -0.1);

        // Act
        $floored = $collection->floor();

        // Assert
        $this->assertSame([0.0, 0.0, -1.0], $floored->toArray());
    }

    /**
     * Test that floor returns new collection instance.
     */
    public function test_floor_returns_new_collection_instance(): void
    {
        // Arrange
        $collection = new FloatTypedCollection;
        $collection->add(1.1, 2.2, 3.3);

        // Act
        $floored = $collection->floor();

        // Assert
        $this->assertNotSame($collection, $floored);
        $this->assertInstanceOf(FloatTypedCollection::class, $floored);
    }

    /**
     * Test that original collection is unchanged after floor.
     */
    public function test_original_collection_is_unchanged_after_floor(): void
    {
        // Arrange
        $collection = new FloatTypedCollection;
        $collection->add(1.1, 2.9, 3.5);

        // Act
        $floored = $collection->floor();

        // Assert
        $this->assertSame([1.1, 2.9, 3.5], $collection->toArray());
        $this->assertSame([1.0, 2.0, 3.0], $floored->toArray());
    }

    /**
     * Test that floor on empty collection returns empty collection.
     */
    public function test_floor_on_empty_collection_returns_empty_collection(): void
    {
        // Arrange
        $emptyCollection = new FloatTypedCollection;

        // Act
        $floored = $emptyCollection->floor();

        // Assert
        $this->assertCount(0, $floored);
        $this->assertTrue($floored->isEmpty());
    }

    // ==================== FORMAT METHOD TESTS ====================

    /**
     * Test that format rounds to 2 decimals by default.
     */
    public function test_format_rounds_to_two_decimals_by_default(): void
    {
        // Arrange
        $collection = new FloatTypedCollection;
        $collection->add(1.234, 2.345, 3.456, 4.567);

        // Act
        $formatted = $collection->format();

        // Assert
        $this->assertSame([1.23, 2.35, 3.46, 4.57], $formatted->toArray());
    }

    /**
     * Test that format with custom decimals works.
     */
    public function test_format_with_custom_decimals_works(): void
    {
        // Arrange
        $collection = new FloatTypedCollection;
        $collection->add(1.234567, 2.345678, 3.456789);

        // Act
        $format1 = $collection->format(3);
        $format2 = $collection->format(4);
        $format3 = $collection->format(5);

        // Assert
        $this->assertSame([1.235, 2.346, 3.457], $format1->toArray());
        $this->assertSame([1.2346, 2.3457, 3.4568], $format2->toArray());
        $this->assertSame([1.23457, 2.34568, 3.45679], $format3->toArray());
    }

    /**
     * Test that format is an alias of round.
     */
    public function test_format_is_alias_of_round(): void
    {
        // Arrange
        $collection = new FloatTypedCollection;
        $collection->add(1.2345, 2.3456, 3.4567);

        // Act
        $formatted = $collection->format(2);
        $rounded = $collection->round(2);

        // Assert
        $this->assertSame($rounded->toArray(), $formatted->toArray());
    }

    /**
     * Test that format returns new collection instance.
     */
    public function test_format_returns_new_collection_instance(): void
    {
        // Arrange
        $collection = new FloatTypedCollection;
        $collection->add(1.11, 2.22, 3.33);

        // Act
        $formatted = $collection->format();

        // Assert
        $this->assertNotSame($collection, $formatted);
        $this->assertInstanceOf(FloatTypedCollection::class, $formatted);
    }

    /**
     * Test that original collection is unchanged after format.
     */
    public function test_original_collection_is_unchanged_after_format(): void
    {
        // Arrange
        $collection = new FloatTypedCollection;
        $collection->add(1.234, 2.345, 3.456);

        // Act
        $formatted = $collection->format();

        // Assert
        $this->assertSame([1.234, 2.345, 3.456], $collection->toArray());
        $this->assertSame([1.23, 2.35, 3.46], $formatted->toArray());
    }

    /**
     * Test that format on empty collection returns empty collection.
     */
    public function test_format_on_empty_collection_returns_empty_collection(): void
    {
        // Arrange
        $emptyCollection = new FloatTypedCollection;

        // Act
        $formatted = $emptyCollection->format();

        // Assert
        $this->assertCount(0, $formatted);
        $this->assertTrue($formatted->isEmpty());
    }

    // ==================== INHERITED METHOD TESTS ====================
    // These tests verify that AbstractNumberTypedCollection methods work

    /**
     * Test that positive method works (inherited).
     */
    public function test_positive_method_works(): void
    {
        // Arrange
        $collection = new FloatTypedCollection;
        $collection->add(-2.5, -1.0, 0.0, 1.5, 2.7, 3.1);

        // Act
        $positive = $collection->positive();

        // Assert
        $this->assertSame([1.5, 2.7, 3.1], $positive->toArray());
    }

    /**
     * Test that negative method works (inherited).
     */
    public function test_negative_method_works(): void
    {
        // Arrange
        $collection = new FloatTypedCollection;
        $collection->add(-2.5, -1.0, 0.0, 1.5, -3.7, 2.7);

        // Act
        $negative = $collection->negative();

        // Assert
        $this->assertSame([-2.5, -1.0, -3.7], $negative->toArray());
    }

    /**
     * Test that between method works (inherited).
     */
    public function test_between_method_works(): void
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
     * Test that average method works (inherited).
     */
    public function test_avg_method_works(): void
    {
        // Arrange
        $collection = new FloatTypedCollection;
        $collection->add(10.5, 20.5, 30.5, 40.5, 50.5);

        // Act
        $average = $collection->avg();

        // Assert
        $this->assertSame(30.5, $average);
    }

    /**
     * Test that sum method works (inherited).
     */
    public function test_sum_method_works(): void
    {
        // Arrange
        $collection = new FloatTypedCollection;
        $collection->add(10.5, 20.5, 30.5, 40.5, 50.5);

        // Act
        $sum = $collection->sum();

        // Assert
        $this->assertSame(152.5, $sum);
    }

    /**
     * Test that range method works (inherited).
     */
    public function test_range_method_works(): void
    {
        // Act
        $collection = FloatTypedCollection::range(1.5, 5.5, 1.0);

        // Assert
        $this->assertSame([1.5, 2.5, 3.5, 4.5, 5.5], $collection->toArray());
    }

    // ==================== CHAINING OPERATIONS TESTS ====================

    /**
     * Test chaining multiple operations.
     */
    public function test_chaining_multiple_operations_works(): void
    {
        // Arrange
        $collection = new FloatTypedCollection;
        $collection->add(-2.5, -1.2, 0.0, 1.4, 2.6, 3.8, 4.1, 5.9);

        // Act - Get positive numbers, round them, then avg
        $result = $collection
            ->positive()
            ->round()
            ->avg();

        // Assert: 1.4→1, 2.6→3, 3.8→4, 4.1→4, 5.9→6 => (1+3+4+4+6)/5 = 18/5 = 3.6
        $this->assertSame(3.6, $result);
    }

    /**
     * Test round then floor chain.
     */
    public function test_round_then_floor_chain_works(): void
    {
        // Arrange
        $collection = new FloatTypedCollection;
        $collection->add(1.4, 2.6, 3.5, 4.1);

        // Act
        $result = $collection->round()->floor();

        // Assert: round(1.4)=1, round(2.6)=3, round(3.5)=4, round(4.1)=4 -> floor all = [1,3,4,4]
        $this->assertSame([1.0, 3.0, 4.0, 4.0], $result->toArray());
    }

    /**
     * Test ceil then round chain.
     */
    public function test_ceil_then_round_chain_works(): void
    {
        // Arrange
        $collection = new FloatTypedCollection;
        $collection->add(1.1, 2.2, 3.3, 4.4);

        // Act
        $result = $collection->ceil()->round();

        // Assert: ceil(1.1)=2, ceil(2.2)=3, ceil(3.3)=4, ceil(4.4)=5 -> round same
        $this->assertSame([2.0, 3.0, 4.0, 5.0], $result->toArray());
    }

    // ==================== EDGE CASES TESTS ====================

    /**
     * Test that collection handles very large floats.
     */
    public function test_collection_handles_very_large_floats(): void
    {
        // Arrange
        $collection = new FloatTypedCollection;
        $collection->add(1.0e15, 2.0e15, 3.0e15);

        // Act
        $sum = $collection->sum();
        $average = $collection->avg();

        // Assert
        $this->assertSame(6.0e15, $sum);
        $this->assertSame(2.0e15, $average);
    }

    /**
     * Test that collection handles very small floats.
     */
    public function test_collection_handles_very_small_floats(): void
    {
        // Arrange
        $collection = new FloatTypedCollection;
        $collection->add(1.0e-10, 2.0e-10, 3.0e-10);

        // Act
        $sum = $collection->sum();
        $average = $collection->avg();

        // Assert
        $this->assertSame(6.0e-10, $sum);
        $this->assertSame(2.0e-10, $average);
    }

    /**
     * Test that collection handles NaN values (mathematically invalid).
     */
    public function test_collection_handles_nan_values(): void
    {
        // Arrange
        $collection = new FloatTypedCollection;
        $collection->add(NAN, 1.5, 2.7);

        // Act & Assert - NAN should not break the collection
        $this->assertCount(3, $collection);
        $this->assertTrue(is_nan($collection[0]));
    }

    /**
     * Test that collection handles INF values.
     */
    public function test_collection_handles_inf_values(): void
    {
        // Arrange
        $collection = new FloatTypedCollection;
        $collection->add(INF, -INF, 1.5, 2.7);

        // Act & Assert
        $this->assertCount(4, $collection);
        $this->assertTrue(is_infinite($collection[0]));
        $this->assertTrue(is_infinite($collection[1]));
    }

    /**
     * Test that collection handles single element correctly.
     */
    public function test_collection_handles_single_element_correctly(): void
    {
        // Arrange
        $collection = new FloatTypedCollection;
        $collection->add(3.14159);

        // Act
        $rounded = $collection->round(2);
        $ceiled = $collection->ceil();
        $floored = $collection->floor();

        // Assert
        $this->assertSame([3.14], $rounded->toArray());
        $this->assertSame([4.0], $ceiled->toArray());
        $this->assertSame([3.0], $floored->toArray());
    }

    /**
     * Test that collection preserves order after operations.
     */
    public function test_collection_preserves_order_after_operations(): void
    {
        // Arrange
        $collection = new FloatTypedCollection;
        $collection->add(3.1, 1.4, 4.2, 2.7, 5.9);

        // Act
        $rounded = $collection->round();

        // Assert
        $this->assertSame([3.0, 1.0, 4.0, 3.0, 6.0], $rounded->toArray());
    }

    // ==================== TO_INTEGERS METHOD TESTS ====================

    /**
     * Test that toIntegers truncates floats to integers.
     */
    public function test_to_integers_truncates_floats_to_integers(): void
    {
        // Arrange
        $collection = new FloatTypedCollection;
        $collection->add(1.2, 2.7, 3.14, 4.99, 5.01);

        // Act
        $integers = $collection->toIntegers();

        // Assert
        $this->assertInstanceOf(IntTypedCollection::class, $integers);
        $this->assertSame([1, 2, 3, 4, 5], $integers->toArray());
    }

    /**
     * Test that toIntegers truncates toward zero for negative floats.
     */
    public function test_to_integers_truncates_toward_zero_for_negative_floats(): void
    {
        // Arrange
        $collection = new FloatTypedCollection;
        $collection->add(-1.2, -2.7, -3.14, -4.99, -5.01);

        // Act
        $integers = $collection->toIntegers();

        // Assert
        $this->assertSame([-1, -2, -3, -4, -5], $integers->toArray());
    }

    /**
     * Test that toIntegers returns new collection instance.
     */
    public function test_to_integers_returns_new_collection_instance(): void
    {
        // Arrange
        $collection = new FloatTypedCollection;
        $collection->add(1.5, 2.5, 3.5);

        // Act
        $integers = $collection->toIntegers();

        // Assert
        $this->assertNotSame($collection, $integers);
        $this->assertInstanceOf(IntTypedCollection::class, $integers);
    }

    /**
     * Test that toIntegers on empty collection returns empty IntTypedCollection.
     */
    public function test_to_integers_on_empty_collection_returns_empty_int_collection(): void
    {
        // Arrange
        $emptyCollection = new FloatTypedCollection;

        // Act
        $integers = $emptyCollection->toIntegers();

        // Assert
        $this->assertCount(0, $integers);
        $this->assertInstanceOf(IntTypedCollection::class, $integers);
    }
}
