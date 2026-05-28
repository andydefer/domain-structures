<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Unit\Collections\Utility;

use AndyDefer\DomainStructures\Collections\Utility\FloatTypedCollection;
use AndyDefer\DomainStructures\Collections\Utility\IntTypedCollection;
use AndyDefer\DomainStructures\Collections\Utility\NumberTypedCollection;
use AndyDefer\DomainStructures\Tests\TestCase;
use InvalidArgumentException;

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
 *
 * The tests follow the AAA pattern (Arrange, Act, Assert).
 */
final class NumberTypedCollectionTest extends TestCase
{
    // ==================== CONSTRUCTOR TESTS ====================

    public function test_constructor_sets_int_and_float_as_allowed_types(): void
    {
        $collection = new NumberTypedCollection;

        $this->assertSame(['int', 'float'], $collection->getAllowedTypes());
    }

    public function test_collection_accepts_both_integers_and_floats(): void
    {
        $collection = new NumberTypedCollection;
        $collection->add(1, 2.5, 3, 4.7, 5, 6.1);

        $this->assertCount(6, $collection);
        $this->assertSame(1, $collection[0]);
        $this->assertSame(2.5, $collection[1]);
        $this->assertSame(3, $collection[2]);
        $this->assertSame(4.7, $collection[3]);
        $this->assertSame(5, $collection[4]);
        $this->assertSame(6.1, $collection[5]);
    }

    public function test_collection_rejects_non_numeric_values(): void
    {
        $collection = new NumberTypedCollection;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected type(s) int|float');

        $collection->add('not a number');
    }

    public function test_collection_rejects_boolean_values(): void
    {
        $collection = new NumberTypedCollection;

        $this->expectException(InvalidArgumentException::class);

        $collection->add(true);
    }

    public function test_collection_rejects_null_values(): void
    {
        $collection = new NumberTypedCollection;

        $this->expectException(InvalidArgumentException::class);

        $collection->add(null);
    }

    // ==================== ZERO METHOD TESTS ====================

    public function test_zero_returns_both_integer_zero_and_float_zero(): void
    {
        $collection = new NumberTypedCollection;
        $collection->add(-2, -1.5, 0, 0.0, 1, 2.5, 3);

        $zeros = $collection->zero();

        $this->assertCount(2, $zeros);
        $this->assertSame(0, $zeros[0]);
        $this->assertSame(0.0, $zeros[1]);
    }

    public function test_zero_returns_empty_collection_when_no_zeros(): void
    {
        $collection = new NumberTypedCollection;
        $collection->add(1, 2.5, 3, 4.7, 5);

        $zeros = $collection->zero();

        $this->assertCount(0, $zeros);
        $this->assertTrue($zeros->isEmpty());
    }

    public function test_zero_on_empty_collection_returns_empty_collection(): void
    {
        $emptyCollection = new NumberTypedCollection;

        $zeros = $emptyCollection->zero();

        $this->assertCount(0, $zeros);
        $this->assertTrue($zeros->isEmpty());
    }

    // ==================== NON_NEGATIVE METHOD TESTS ====================

    public function test_non_negative_returns_numbers_greater_than_or_equal_zero(): void
    {
        $collection = new NumberTypedCollection;
        $collection->add(-5, -3.5, -1, 0, 0.0, 1, 2.5, 3, 4.2, 5);

        $nonNegative = $collection->nonNegative();

        $this->assertSame([0, 0.0, 1, 2.5, 3, 4.2, 5], $nonNegative->toArray());
        $this->assertCount(7, $nonNegative);
    }

    public function test_non_negative_includes_both_zero_values(): void
    {
        $collection = new NumberTypedCollection;
        $collection->add(-1, 0, 0.0, 1);

        $nonNegative = $collection->nonNegative();

        $this->assertContains(0, $nonNegative->toArray());
        $this->assertContains(0.0, $nonNegative->toArray());
    }

    public function test_non_negative_returns_new_collection_instance(): void
    {
        $collection = new NumberTypedCollection;
        $collection->add(-2, -1, 0, 1, 2);

        $nonNegative = $collection->nonNegative();

        $this->assertNotSame($collection, $nonNegative);
        $this->assertInstanceOf(NumberTypedCollection::class, $nonNegative);
    }

    // ==================== ARE_ALL_INTEGERS METHOD TESTS ====================

    public function test_are_all_integers_returns_true_when_all_values_are_integers(): void
    {
        $collection = new NumberTypedCollection;
        $collection->add(1, 2, 3, 4, 5);

        $result = $collection->areAllIntegers();

        $this->assertTrue($result);
    }

    public function test_are_all_integers_returns_false_when_any_value_is_float(): void
    {
        $collection = new NumberTypedCollection;
        $collection->add(1, 2, 3.5, 4, 5);

        $result = $collection->areAllIntegers();

        $this->assertFalse($result);
    }

    public function test_are_all_integers_returns_true_for_empty_collection(): void
    {
        $emptyCollection = new NumberTypedCollection;

        $result = $emptyCollection->areAllIntegers();

        $this->assertTrue($result);
    }

    public function test_are_all_integers_returns_false_for_only_floats(): void
    {
        $collection = new NumberTypedCollection;
        $collection->add(1.5, 2.7, 3.14);

        $result = $collection->areAllIntegers();

        $this->assertFalse($result);
    }

    // ==================== HAS_ANY_FLOAT METHOD TESTS ====================

    public function test_has_any_float_returns_true_when_at_least_one_float_exists(): void
    {
        $collection = new NumberTypedCollection;
        $collection->add(1, 2, 3.5, 4, 5);

        $result = $collection->hasAnyFloat();

        $this->assertTrue($result);
    }

    public function test_has_any_float_returns_false_when_no_floats_exist(): void
    {
        $collection = new NumberTypedCollection;
        $collection->add(1, 2, 3, 4, 5);

        $result = $collection->hasAnyFloat();

        $this->assertFalse($result);
    }

    public function test_has_any_float_returns_false_for_empty_collection(): void
    {
        $emptyCollection = new NumberTypedCollection;

        $result = $emptyCollection->hasAnyFloat();

        $this->assertFalse($result);
    }

    public function test_has_any_float_returns_true_for_only_floats(): void
    {
        $collection = new NumberTypedCollection;
        $collection->add(1.5, 2.7, 3.14);

        $result = $collection->hasAnyFloat();

        $this->assertTrue($result);
    }

    // ==================== TO_FLOATS METHOD TESTS ====================

    public function test_to_floats_converts_all_values_to_floats(): void
    {
        $collection = new NumberTypedCollection;
        $collection->add(1, 2, 3, 4, 5);

        $floats = $collection->toFloats();

        $this->assertInstanceOf(FloatTypedCollection::class, $floats);
        $this->assertSame([1.0, 2.0, 3.0, 4.0, 5.0], $floats->toArray());
    }

    public function test_to_floats_preserves_existing_floats_as_floats(): void
    {
        $collection = new NumberTypedCollection;
        $collection->add(1, 2.5, 3, 4.7, 5);

        $floats = $collection->toFloats();

        $this->assertSame([1.0, 2.5, 3.0, 4.7, 5.0], $floats->toArray());
    }

    public function test_to_floats_returns_new_collection_instance(): void
    {
        $collection = new NumberTypedCollection;
        $collection->add(1, 2, 3);

        $floats = $collection->toFloats();

        $this->assertNotSame($collection, $floats);
        $this->assertInstanceOf(FloatTypedCollection::class, $floats);
    }

    public function test_to_floats_on_empty_collection_returns_empty_float_collection(): void
    {
        $emptyCollection = new NumberTypedCollection;

        $floats = $emptyCollection->toFloats();

        $this->assertCount(0, $floats);
        $this->assertInstanceOf(FloatTypedCollection::class, $floats);
    }

    // ==================== TO_INTEGERS METHOD TESTS ====================

    public function test_to_integers_truncates_floats_to_integers(): void
    {
        $collection = new NumberTypedCollection;
        $collection->add(1.2, 2.7, 3.14, 4.99, 5.01);

        $integers = $collection->toIntegers();

        $this->assertInstanceOf(IntTypedCollection::class, $integers);
        $this->assertSame([1, 2, 3, 4, 5], $integers->toArray());
    }

    public function test_to_integers_preserves_existing_integers(): void
    {
        $collection = new NumberTypedCollection;
        $collection->add(1, 2.5, 3, 4.7, 5);

        $integers = $collection->toIntegers();

        $this->assertSame([1, 2, 3, 4, 5], $integers->toArray());
    }

    public function test_to_integers_truncates_toward_zero_for_negative_floats(): void
    {
        $collection = new NumberTypedCollection;
        $collection->add(-1.2, -2.7, -3.14, -4.99, -5.01);

        $integers = $collection->toIntegers();

        $this->assertSame([-1, -2, -3, -4, -5], $integers->toArray());
    }

    public function test_to_integers_returns_new_collection_instance(): void
    {
        $collection = new NumberTypedCollection;
        $collection->add(1.5, 2.5, 3.5);

        $integers = $collection->toIntegers();

        $this->assertNotSame($collection, $integers);
        $this->assertInstanceOf(IntTypedCollection::class, $integers);
    }

    public function test_to_integers_on_empty_collection_returns_empty_int_collection(): void
    {
        $emptyCollection = new NumberTypedCollection;

        $integers = $emptyCollection->toIntegers();

        $this->assertCount(0, $integers);
        $this->assertInstanceOf(IntTypedCollection::class, $integers);
    }

    // ==================== SEPARATE_TYPES METHOD TESTS ====================

    public function test_separate_types_separates_integers_and_floats_correctly(): void
    {
        $collection = new NumberTypedCollection;
        $collection->add(1, 2.5, 3, 4.7, 5, 6.2, 7);

        $separated = $collection->separateTypes();

        $this->assertArrayHasKey('integers', $separated);
        $this->assertArrayHasKey('floats', $separated);

        $this->assertInstanceOf(IntTypedCollection::class, $separated['integers']);
        $this->assertInstanceOf(FloatTypedCollection::class, $separated['floats']);

        $this->assertSame([1, 3, 5, 7], $separated['integers']->toArray());
        $this->assertSame([2.5, 4.7, 6.2], $separated['floats']->toArray());
    }

    public function test_separate_types_handles_only_integers(): void
    {
        $collection = new NumberTypedCollection;
        $collection->add(1, 2, 3, 4, 5);

        $separated = $collection->separateTypes();

        $this->assertSame([1, 2, 3, 4, 5], $separated['integers']->toArray());
        $this->assertCount(0, $separated['floats']);
        $this->assertTrue($separated['floats']->isEmpty());
    }

    public function test_separate_types_handles_only_floats(): void
    {
        $collection = new NumberTypedCollection;
        $collection->add(1.5, 2.7, 3.14, 4.99);

        $separated = $collection->separateTypes();

        $this->assertCount(0, $separated['integers']);
        $this->assertTrue($separated['integers']->isEmpty());
        $this->assertSame([1.5, 2.7, 3.14, 4.99], $separated['floats']->toArray());
    }

    public function test_separate_types_handles_empty_collection(): void
    {
        $emptyCollection = new NumberTypedCollection;

        $separated = $emptyCollection->separateTypes();

        $this->assertCount(0, $separated['integers']);
        $this->assertCount(0, $separated['floats']);
        $this->assertTrue($separated['integers']->isEmpty());
        $this->assertTrue($separated['floats']->isEmpty());
    }

    public function test_separate_types_preserves_order_within_each_type(): void
    {
        $collection = new NumberTypedCollection;
        $collection->add(5, 2.5, 3, 1.2, 7, 4.8, 2, 9.1);

        $separated = $collection->separateTypes();

        $this->assertSame([5, 3, 7, 2], $separated['integers']->toArray());
        $this->assertSame([2.5, 1.2, 4.8, 9.1], $separated['floats']->toArray());
    }

    // ==================== POSITIVE METHOD TESTS (héritée mais testée ici) ====================

    public function test_positive_method_works(): void
    {
        $collection = new NumberTypedCollection;
        $collection->add(-5, -2.5, -1, 0, 0.0, 1, 2.5, 3, 4.2, 5);

        $positive = $collection->positive();

        $this->assertSame([1, 2.5, 3, 4.2, 5], $positive->toArray());
    }

    // ==================== NEGATIVE METHOD TESTS (héritée mais testée ici) ====================

    public function test_negative_method_works(): void
    {
        $collection = new NumberTypedCollection;
        $collection->add(-5, -4.5, -3, -2.2, -1, 0, 1, 2, 3);

        $negative = $collection->negative();

        $this->assertSame([-5, -4.5, -3, -2.2, -1], $negative->toArray());
    }

    // ==================== BETWEEN METHOD TESTS (héritée mais testée ici) ====================

    public function test_between_method_works(): void
    {
        $collection = new NumberTypedCollection;
        $collection->add(1, 2.5, 3, 4.7, 5, 6.2, 7, 8.1, 9);

        $between = $collection->between(3, 7);

        $this->assertSame([3, 4.7, 5, 6.2, 7], $between->toArray());
    }

    // ==================== AVERAGE METHOD TESTS (héritée mais testée ici) ====================

    public function test_average_method_works(): void
    {
        $collection = new NumberTypedCollection;
        $collection->add(10, 20.5, 30, 40.5, 50);

        $average = $collection->average();

        $this->assertSame(30.2, $average);
    }

    // ==================== SUM METHOD TESTS (héritée mais testée ici) ====================

    public function test_sum_method_works(): void
    {
        $collection = new NumberTypedCollection;
        $collection->add(10, 20.5, 30, 40.5, 50);

        $sum = $collection->sum();

        $this->assertSame(151.0, $sum);
    }

    // ==================== RANGE METHOD TESTS (héritée mais testée ici) ====================

    public function test_range_method_works(): void
    {
        $collection = NumberTypedCollection::range(1, 10, 2);

        $this->assertSame([1, 3, 5, 7, 9], $collection->toArray());
    }

    public function test_range_throws_exception_when_step_is_zero(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Step value cannot be zero');

        NumberTypedCollection::range(1, 10, 0);
    }

    // ==================== CHAINING OPERATIONS TESTS ====================

    public function test_chaining_multiple_operations_works(): void
    {
        $collection = new NumberTypedCollection;
        $collection->add(-10, -5.5, -2, 0, 0.0, 3, 6.5, 9, 12.3, 15);

        $result = $collection
            ->positive()
            ->toIntegers()
            ->sum();

        $this->assertSame(45, $result);
    }

    public function test_to_floats_then_to_integers_chain(): void
    {
        $collection = new NumberTypedCollection;
        $collection->add(1, 2.7, 3, 4.2, 5);

        $result = $collection->toFloats()->toIntegers();

        $this->assertSame([1, 2, 3, 4, 5], $result->toArray());
    }

    public function test_separate_types_then_recompose(): void
    {
        $collection = new NumberTypedCollection;
        $collection->add(1, 2.5, 3, 4.7, 5, 6.2, 7);

        $separated = $collection->separateTypes();
        $recomposed = new NumberTypedCollection;

        foreach ($separated['integers']->toArray() as $int) {
            $recomposed->add($int);
        }
        foreach ($separated['floats']->toArray() as $float) {
            $recomposed->add($float);
        }

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

    public function test_collection_handles_very_large_mixed_numbers(): void
    {
        $collection = new NumberTypedCollection;
        $collection->add(PHP_INT_MAX, PHP_INT_MAX - 1.5, PHP_INT_MAX - 2);

        $sum = $collection->sum();
        $areAllIntegers = $collection->areAllIntegers();
        $hasFloat = $collection->hasAnyFloat();

        $this->assertIsFloat($sum);
        $this->assertFalse($areAllIntegers);
        $this->assertTrue($hasFloat);
    }

    public function test_very_large_numbers_are_handled_correctly(): void
    {
        $collection = new NumberTypedCollection;
        $collection->add(PHP_INT_MAX, PHP_INT_MAX);

        $result = $collection->sum();

        $this->assertIsFloat($result);
        $this->assertEquals(PHP_INT_MAX * 2, $result);
    }

    public function test_zero_values_properly_handled_in_all_operations(): void
    {
        $collection = new NumberTypedCollection;
        $collection->add(0, 0.0, -0, -0.0);

        $zeros = $collection->zero();
        $nonNegative = $collection->nonNegative();
        $positive = $collection->positive();
        $negative = $collection->negative();

        $this->assertCount(4, $zeros);
        $this->assertCount(4, $nonNegative);
        $this->assertCount(0, $positive);
        $this->assertCount(0, $negative);
    }

    public function test_collection_preserves_type_detection_after_operations(): void
    {
        $collection = new NumberTypedCollection;
        $collection->add(1, 2.5, 3);

        $positive = $collection->positive();

        $this->assertInstanceOf(NumberTypedCollection::class, $positive);
        $this->assertTrue($positive->hasAnyFloat());
        $this->assertFalse($positive->areAllIntegers());
    }

    public function test_to_floats_and_to_integers_maintain_collection_properties(): void
    {
        $collection = new NumberTypedCollection;
        $collection->add(1, 2, 3);

        $floats = $collection->toFloats();
        $integers = $collection->toIntegers();

        $this->assertCount(3, $floats);
        $this->assertCount(3, $integers);
        $this->assertSame(['float'], $floats->getAllowedTypes());
        $this->assertSame(['int'], $integers->getAllowedTypes());
    }
}
