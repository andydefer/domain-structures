<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Unit\Collections\Utility;

use AndyDefer\DomainStructures\Collections\Utility\FloatTypedCollection;
use AndyDefer\DomainStructures\Collections\Utility\IntTypedCollection;
use AndyDefer\DomainStructures\Collections\Utility\NumberTypedCollection;
use AndyDefer\DomainStructures\Tests\TestCase;

/**
 * Unit tests for NumberTypedCollection class.
 *
 * This test suite validates the mixed numeric collection functionality:
 * - Type safety (both int and float values allowed)
 * - Zero filtering (works for both 0 and 0.0)
 * - Non-negative filtering
 * - Type checking (areAllIntegers, hasAnyFloat)
 * - Type conversion (toFloats, toIntegers)
 * - Type separation (separateTypes)
 * - Inheritance from AbstractNumberTypedCollection
 *
 * The tests follow the AAA pattern (Arrange, Act, Assert).
 */
final class NumberTypedCollectionTest extends TestCase
{
    // ==================== CONSTRUCTOR TESTS ====================

    /**
     * Test that NumberTypedCollection constructor sets int and float as allowed types.
     */
    public function test_constructor_sets_int_and_float_as_allowed_types(): void
    {
        // Arrange & Act
        $collection = new NumberTypedCollection;

        // Assert
        $this->assertSame(['int', 'float'], $collection->getAllowedTypes());
    }

    /**
     * Test that NumberTypedCollection accepts both integers and floats.
     */
    public function test_collection_accepts_both_integers_and_floats(): void
    {
        // Arrange
        $collection = new NumberTypedCollection;

        // Act
        $collection->add(1, 2.5, 3, 4.7, 5, 6.1);

        // Assert
        $this->assertCount(6, $collection);
        $this->assertSame(1, $collection[0]);
        $this->assertSame(2.5, $collection[1]);
        $this->assertSame(3, $collection[2]);
        $this->assertSame(4.7, $collection[3]);
        $this->assertSame(5, $collection[4]);
        $this->assertSame(6.1, $collection[5]);
    }

    /**
     * Test that collection rejects non-numeric values.
     */
    public function test_collection_rejects_non_numeric_values(): void
    {
        // Arrange
        $collection = new NumberTypedCollection;

        // Act & Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected type(s) int|float');

        $collection->add('not a number');
    }

    /**
     * Test that collection rejects boolean values.
     */
    public function test_collection_rejects_boolean_values(): void
    {
        // Arrange
        $collection = new NumberTypedCollection;

        // Act & Assert
        $this->expectException(\InvalidArgumentException::class);

        $collection->add(true);
    }

    /**
     * Test that collection rejects null values.
     */
    public function test_collection_rejects_null_values(): void
    {
        // Arrange
        $collection = new NumberTypedCollection;

        // Act & Assert
        $this->expectException(\InvalidArgumentException::class);

        $collection->add(null);
    }

    // ==================== ZERO METHOD TESTS ====================

    /**
     * Test that zero returns both integer 0 and float 0.0.
     */
    public function test_zero_returns_both_integer_zero_and_float_zero(): void
    {
        // Arrange
        $collection = new NumberTypedCollection;
        $collection->add(-2, -1.5, 0, 0.0, 1, 2.5, 3);

        // Act
        $zeros = $collection->zero();

        // Assert
        $this->assertCount(2, $zeros);
        $this->assertSame(0, $zeros[0]);
        $this->assertSame(0.0, $zeros[1]);
    }

    /**
     * Test that zero returns empty collection when no zeros.
     */
    public function test_zero_returns_empty_collection_when_no_zeros(): void
    {
        // Arrange
        $collection = new NumberTypedCollection;
        $collection->add(1, 2.5, 3, 4.7, 5);

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
        $emptyCollection = new NumberTypedCollection;

        // Act
        $zeros = $emptyCollection->zero();

        // Assert
        $this->assertCount(0, $zeros);
        $this->assertTrue($zeros->isEmpty());
    }

    // ==================== NON_NEGATIVE METHOD TESTS ====================

    /**
     * Test that nonNegative returns numbers >= 0 for both int and float.
     */
    public function test_non_negative_returns_numbers_greater_than_or_equal_zero(): void
    {
        // Arrange
        $collection = new NumberTypedCollection;
        $collection->add(-5, -3.5, -1, 0, 0.0, 1, 2.5, 3, 4.2, 5);

        // Act
        $nonNegative = $collection->nonNegative();

        // Assert
        $this->assertSame([0, 0.0, 1, 2.5, 3, 4.2, 5], $nonNegative->toArray());
        $this->assertCount(7, $nonNegative);
    }

    /**
     * Test that nonNegative includes both zero values.
     */
    public function test_non_negative_includes_both_zero_values(): void
    {
        // Arrange
        $collection = new NumberTypedCollection;
        $collection->add(-1, 0, 0.0, 1);

        // Act
        $nonNegative = $collection->nonNegative();

        // Assert
        $this->assertContains(0, $nonNegative->toArray());
        $this->assertContains(0.0, $nonNegative->toArray());
    }

    /**
     * Test that nonNegative returns new collection instance.
     */
    public function test_non_negative_returns_new_collection_instance(): void
    {
        // Arrange
        $collection = new NumberTypedCollection;
        $collection->add(-2, -1, 0, 1, 2);

        // Act
        $nonNegative = $collection->nonNegative();

        // Assert
        $this->assertNotSame($collection, $nonNegative);
        $this->assertInstanceOf(NumberTypedCollection::class, $nonNegative);
    }

    // ==================== ARE_ALL_INTEGERS METHOD TESTS ====================

    /**
     * Test that areAllIntegers returns true when all values are integers.
     */
    public function test_are_all_integers_returns_true_when_all_values_are_integers(): void
    {
        // Arrange
        $collection = new NumberTypedCollection;
        $collection->add(1, 2, 3, 4, 5);

        // Act
        $result = $collection->areAllIntegers();

        // Assert
        $this->assertTrue($result);
    }

    /**
     * Test that areAllIntegers returns false when any value is float.
     */
    public function test_are_all_integers_returns_false_when_any_value_is_float(): void
    {
        // Arrange
        $collection = new NumberTypedCollection;
        $collection->add(1, 2, 3.5, 4, 5);

        // Act
        $result = $collection->areAllIntegers();

        // Assert
        $this->assertFalse($result);
    }

    /**
     * Test that areAllIntegers returns true for empty collection.
     */
    public function test_are_all_integers_returns_true_for_empty_collection(): void
    {
        // Arrange
        $emptyCollection = new NumberTypedCollection;

        // Act
        $result = $emptyCollection->areAllIntegers();

        // Assert
        $this->assertTrue($result);
    }

    /**
     * Test that areAllIntegers returns false for collection with only floats.
     */
    public function test_are_all_integers_returns_false_for_only_floats(): void
    {
        // Arrange
        $collection = new NumberTypedCollection;
        $collection->add(1.5, 2.7, 3.14);

        // Act
        $result = $collection->areAllIntegers();

        // Assert
        $this->assertFalse($result);
    }

    // ==================== HAS_ANY_FLOAT METHOD TESTS ====================

    /**
     * Test that hasAnyFloat returns true when at least one float exists.
     */
    public function test_has_any_float_returns_true_when_at_least_one_float_exists(): void
    {
        // Arrange
        $collection = new NumberTypedCollection;
        $collection->add(1, 2, 3.5, 4, 5);

        // Act
        $result = $collection->hasAnyFloat();

        // Assert
        $this->assertTrue($result);
    }

    /**
     * Test that hasAnyFloat returns false when no floats exist.
     */
    public function test_has_any_float_returns_false_when_no_floats_exist(): void
    {
        // Arrange
        $collection = new NumberTypedCollection;
        $collection->add(1, 2, 3, 4, 5);

        // Act
        $result = $collection->hasAnyFloat();

        // Assert
        $this->assertFalse($result);
    }

    /**
     * Test that hasAnyFloat returns false for empty collection.
     */
    public function test_has_any_float_returns_false_for_empty_collection(): void
    {
        // Arrange
        $emptyCollection = new NumberTypedCollection;

        // Act
        $result = $emptyCollection->hasAnyFloat();

        // Assert
        $this->assertFalse($result);
    }

    /**
     * Test that hasAnyFloat returns true for collection with only floats.
     */
    public function test_has_any_float_returns_true_for_only_floats(): void
    {
        // Arrange
        $collection = new NumberTypedCollection;
        $collection->add(1.5, 2.7, 3.14);

        // Act
        $result = $collection->hasAnyFloat();

        // Assert
        $this->assertTrue($result);
    }

    // ==================== TO_FLOATS METHOD TESTS ====================

    /**
     * Test that toFloats converts all values to floats.
     */
    public function test_to_floats_converts_all_values_to_floats(): void
    {
        // Arrange
        $collection = new NumberTypedCollection;
        $collection->add(1, 2, 3, 4, 5);

        // Act
        $floats = $collection->toFloats();

        // Assert
        $this->assertInstanceOf(FloatTypedCollection::class, $floats);
        $this->assertSame([1.0, 2.0, 3.0, 4.0, 5.0], $floats->toArray());
    }

    /**
     * Test that toFloats preserves existing floats as floats.
     */
    public function test_to_floats_preserves_existing_floats_as_floats(): void
    {
        // Arrange
        $collection = new NumberTypedCollection;
        $collection->add(1, 2.5, 3, 4.7, 5);

        // Act
        $floats = $collection->toFloats();

        // Assert
        $this->assertSame([1.0, 2.5, 3.0, 4.7, 5.0], $floats->toArray());
    }

    /**
     * Test that toFloats returns new collection instance.
     */
    public function test_to_floats_returns_new_collection_instance(): void
    {
        // Arrange
        $collection = new NumberTypedCollection;
        $collection->add(1, 2, 3);

        // Act
        $floats = $collection->toFloats();

        // Assert
        $this->assertNotSame($collection, $floats);
        $this->assertInstanceOf(FloatTypedCollection::class, $floats);
    }

    /**
     * Test that toFloats on empty collection returns empty FloatTypedCollection.
     */
    public function test_to_floats_on_empty_collection_returns_empty_float_collection(): void
    {
        // Arrange
        $emptyCollection = new NumberTypedCollection;

        // Act
        $floats = $emptyCollection->toFloats();

        // Assert
        $this->assertCount(0, $floats);
        $this->assertInstanceOf(FloatTypedCollection::class, $floats);
    }

    // ==================== TO_INTEGERS METHOD TESTS ====================

    /**
     * Test that toIntegers truncates floats to integers.
     */
    public function test_to_integers_truncates_floats_to_integers(): void
    {
        // Arrange
        $collection = new NumberTypedCollection;
        $collection->add(1.2, 2.7, 3.14, 4.99, 5.01);

        // Act
        $integers = $collection->toIntegers();

        // Assert
        $this->assertInstanceOf(IntTypedCollection::class, $integers);
        $this->assertSame([1, 2, 3, 4, 5], $integers->toArray());
    }

    /**
     * Test that toIntegers preserves existing integers.
     */
    public function test_to_integers_preserves_existing_integers(): void
    {
        // Arrange
        $collection = new NumberTypedCollection;
        $collection->add(1, 2.5, 3, 4.7, 5);

        // Act
        $integers = $collection->toIntegers();

        // Assert
        $this->assertSame([1, 2, 3, 4, 5], $integers->toArray());
    }

    /**
     * Test that toIntegers truncates toward zero for negative floats.
     */
    public function test_to_integers_truncates_toward_zero_for_negative_floats(): void
    {
        // Arrange
        $collection = new NumberTypedCollection;
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
        $collection = new NumberTypedCollection;
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
        $emptyCollection = new NumberTypedCollection;

        // Act
        $integers = $emptyCollection->toIntegers();

        // Assert
        $this->assertCount(0, $integers);
        $this->assertInstanceOf(IntTypedCollection::class, $integers);
    }

    // ==================== SEPARATE_TYPES METHOD TESTS ====================

    /**
     * Test that separateTypes separates integers and floats correctly.
     */
    public function test_separate_types_separates_integers_and_floats_correctly(): void
    {
        // Arrange
        $collection = new NumberTypedCollection;
        $collection->add(1, 2.5, 3, 4.7, 5, 6.2, 7);

        // Act
        $separated = $collection->separateTypes();

        // Assert
        $this->assertArrayHasKey('integers', $separated);
        $this->assertArrayHasKey('floats', $separated);

        $this->assertInstanceOf(IntTypedCollection::class, $separated['integers']);
        $this->assertInstanceOf(FloatTypedCollection::class, $separated['floats']);

        $this->assertSame([1, 3, 5, 7], $separated['integers']->toArray());
        $this->assertSame([2.5, 4.7, 6.2], $separated['floats']->toArray());
    }

    /**
     * Test that separateTypes handles collection with only integers.
     */
    public function test_separate_types_handles_only_integers(): void
    {
        // Arrange
        $collection = new NumberTypedCollection;
        $collection->add(1, 2, 3, 4, 5);

        // Act
        $separated = $collection->separateTypes();

        // Assert
        $this->assertSame([1, 2, 3, 4, 5], $separated['integers']->toArray());
        $this->assertCount(0, $separated['floats']);
        $this->assertTrue($separated['floats']->isEmpty());
    }

    /**
     * Test that separateTypes handles collection with only floats.
     */
    public function test_separate_types_handles_only_floats(): void
    {
        // Arrange
        $collection = new NumberTypedCollection;
        $collection->add(1.5, 2.7, 3.14, 4.99);

        // Act
        $separated = $collection->separateTypes();

        // Assert
        $this->assertCount(0, $separated['integers']);
        $this->assertTrue($separated['integers']->isEmpty());
        $this->assertSame([1.5, 2.7, 3.14, 4.99], $separated['floats']->toArray());
    }

    /**
     * Test that separateTypes handles empty collection.
     */
    public function test_separate_types_handles_empty_collection(): void
    {
        // Arrange
        $emptyCollection = new NumberTypedCollection;

        // Act
        $separated = $emptyCollection->separateTypes();

        // Assert
        $this->assertCount(0, $separated['integers']);
        $this->assertCount(0, $separated['floats']);
        $this->assertTrue($separated['integers']->isEmpty());
        $this->assertTrue($separated['floats']->isEmpty());
    }

    /**
     * Test that separateTypes preserves order within each type.
     */
    public function test_separate_types_preserves_order_within_each_type(): void
    {
        // Arrange
        $collection = new NumberTypedCollection;
        $collection->add(5, 2.5, 3, 1.2, 7, 4.8, 2, 9.1);

        // Act
        $separated = $collection->separateTypes();

        // Assert - order preserved
        $this->assertSame([5, 3, 7, 2], $separated['integers']->toArray());
        $this->assertSame([2.5, 1.2, 4.8, 9.1], $separated['floats']->toArray());
    }

    // ==================== INHERITED METHOD TESTS ====================

    /**
     * Test that positive method works (inherited).
     */
    public function test_positive_method_works(): void
    {
        // Arrange
        $collection = new NumberTypedCollection;
        $collection->add(-5, -2.5, -1, 0, 0.0, 1, 2.5, 3, 4.2, 5);

        // Act
        $positive = $collection->positive();

        // Assert
        $this->assertSame([1, 2.5, 3, 4.2, 5], $positive->toArray());
    }

    /**
     * Test that negative method works (inherited).
     */
    public function test_negative_method_works(): void
    {
        // Arrange
        $collection = new NumberTypedCollection;
        $collection->add(-5, -4.5, -3, -2.2, -1, 0, 1, 2, 3);

        // Act
        $negative = $collection->negative();

        // Assert
        $this->assertSame([-5, -4.5, -3, -2.2, -1], $negative->toArray());
    }

    /**
     * Test that between method works (inherited).
     */
    public function test_between_method_works(): void
    {
        // Arrange
        $collection = new NumberTypedCollection;
        $collection->add(1, 2.5, 3, 4.7, 5, 6.2, 7, 8.1, 9);

        // Act
        $between = $collection->between(3, 7);

        // Assert
        $this->assertSame([3, 4.7, 5, 6.2, 7], $between->toArray());
    }

    /**
     * Test that average method works (inherited).
     */
    public function test_average_method_works(): void
    {
        // Arrange
        $collection = new NumberTypedCollection;
        $collection->add(10, 20.5, 30, 40.5, 50);

        // Act
        $average = $collection->average();

        // Assert: (10 + 20.5 + 30 + 40.5 + 50) / 5 = 151 / 5 = 30.2
        $this->assertSame(30.2, $average);
    }

    /**
     * Test that sum method works (inherited).
     */
    public function test_sum_method_works(): void
    {
        // Arrange
        $collection = new NumberTypedCollection;
        $collection->add(10, 20.5, 30, 40.5, 50);

        // Act
        $sum = $collection->sum();

        // Assert
        $this->assertSame(151.0, $sum);
    }

    /**
     * Test that range method works (inherited).
     */
    public function test_range_method_works(): void
    {
        // Act
        $collection = NumberTypedCollection::range(1, 10, 2);

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
        $collection = new NumberTypedCollection;
        $collection->add(-10, -5.5, -2, 0, 0.0, 3, 6.5, 9, 12.3, 15);

        // Act - Get positive numbers, then convert to integers, then get sum
        $result = $collection
            ->positive()
            ->toIntegers()
            ->sum();

        // Assert: positive = [3,6.5,9,12.3,15], toIntegers = [3,6,9,12,15], sum = 45
        $this->assertSame(45, $result);
    }

    /**
     * Test toFloats then toIntegers chain (round trip with data loss).
     */
    public function test_to_floats_then_to_integers_chain(): void
    {
        // Arrange
        $collection = new NumberTypedCollection;
        $collection->add(1, 2.7, 3, 4.2, 5);

        // Act
        $result = $collection->toFloats()->toIntegers();

        // Assert: toFloats = [1.0,2.7,3.0,4.2,5.0], toIntegers = [1,2,3,4,5]
        $this->assertSame([1, 2, 3, 4, 5], $result->toArray());
    }

    /**
     * Test separateTypes then recompose.
     */
    public function test_separate_types_then_recompose(): void
    {
        // Arrange
        $collection = new NumberTypedCollection;
        $collection->add(1, 2.5, 3, 4.7, 5, 6.2, 7);

        // Act
        $separated = $collection->separateTypes();
        $recomposed = new NumberTypedCollection;

        foreach ($separated['integers']->toArray() as $int) {
            $recomposed->add($int);
        }
        foreach ($separated['floats']->toArray() as $float) {
            $recomposed->add($float);
        }

        // Assert - order may differ, but values should be the same
        $this->assertCount(7, $recomposed);
        $this->assertContains(1, $recomposed->toArray());
        $this->assertContains(2.5, $recomposed->toArray());
        $this->assertContains(3, $recomposed->toArray());
        $this->assertContains(4.7, $recomposed->toArray());
        $this->assertContains(5, $recomposed->toArray());
        $this->assertContains(6.2, $recomposed->toArray());
        $this->assertContains(7, $recomposed->toArray());
    }

    // ==================== EDGE CASES TESTS ====================

    /**
     * Test that collection handles very large mixed numbers.
     */
    public function test_collection_handles_very_large_mixed_numbers(): void
    {
        // Arrange
        $collection = new NumberTypedCollection;
        $collection->add(PHP_INT_MAX, PHP_INT_MAX - 1.5, PHP_INT_MAX - 2);

        // Act
        $sum = $collection->sum();
        $areAllIntegers = $collection->areAllIntegers();
        $hasFloat = $collection->hasAnyFloat();

        // Assert
        $this->assertIsFloat($sum);
        $this->assertFalse($areAllIntegers);
        $this->assertTrue($hasFloat);
    }

    /**
     * Test that zero values are properly handled in all operations.
     */
    public function test_zero_values_properly_handled_in_all_operations(): void
    {
        // Arrange
        $collection = new NumberTypedCollection;
        $collection->add(0, 0.0, -0, -0.0);

        // Act
        $zeros = $collection->zero();
        $nonNegative = $collection->nonNegative();
        $positive = $collection->positive();
        $negative = $collection->negative();

        // Assert
        $this->assertCount(2, $zeros); // 0 and 0.0
        $this->assertCount(2, $nonNegative); // 0 and 0.0
        $this->assertCount(0, $positive);
        $this->assertCount(0, $negative);
    }

    /**
     * Test that collection preserves type detection after operations.
     */
    public function test_collection_preserves_type_detection_after_operations(): void
    {
        // Arrange
        $collection = new NumberTypedCollection;
        $collection->add(1, 2.5, 3);

        // Act
        $positive = $collection->positive();

        // Assert
        $this->assertInstanceOf(NumberTypedCollection::class, $positive);
        $this->assertTrue($positive->hasAnyFloat());
        $this->assertFalse($positive->areAllIntegers());
    }

    /**
     * Test that toFloats and toIntegers maintain collection properties.
     */
    public function test_to_floats_and_to_integers_maintain_collection_properties(): void
    {
        // Arrange
        $collection = new NumberTypedCollection;
        $collection->add(1, 2, 3);

        // Act
        $floats = $collection->toFloats();
        $integers = $collection->toIntegers();

        // Assert
        $this->assertCount(3, $floats);
        $this->assertCount(3, $integers);
        $this->assertSame(['float'], $floats->getAllowedTypes());
        $this->assertSame(['int'], $integers->getAllowedTypes());
    }
}
