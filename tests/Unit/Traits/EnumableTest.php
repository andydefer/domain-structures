<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Unit\Traits;

use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestBackedIntEnum;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestBackedStringEnum;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestPureEnum;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestSingleCaseEnum;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestUserGrade;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestUserRole;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestUserStatus;
use AndyDefer\DomainStructures\Tests\TestCase;
use AndyDefer\DomainStructures\Traits\Enumable;

/**
 * Unit tests for Enumable trait.
 *
 * This test suite validates the Enumable trait which provides
 * utility methods for PHP 8.1+ Enums.
 *
 * The tests follow the AAA pattern (Arrange, Act, Assert).
 */
final class EnumableTest extends TestCase
{
    // ==================== VALUES METHOD TESTS ====================

    public function test_values_returns_backing_values_for_backed_string_enum(): void
    {
        $values = TestBackedStringEnum::values();

        $this->assertSame(['one', 'two', 'three'], $values);
        $this->assertCount(3, $values);
    }

    public function test_values_returns_backing_values_for_backed_int_enum(): void
    {
        $values = TestBackedIntEnum::values();

        $this->assertSame([1, 2, 3], $values);
        $this->assertCount(3, $values);
    }

    public function test_values_returns_case_names_for_pure_enum(): void
    {
        $values = TestPureEnum::values();

        $this->assertSame(['VALUE_ONE', 'VALUE_TWO', 'VALUE_THREE'], $values);
        $this->assertCount(3, $values);
    }

    public function test_values_returns_all_enum_values_in_correct_order(): void
    {
        $statusValues = TestUserStatus::values();
        $roleValues = TestUserRole::values();
        $gradeValues = TestUserGrade::values();

        $this->assertSame(['active', 'inactive', 'suspended'], $statusValues);
        $this->assertSame(['admin', 'user', 'guest'], $roleValues);
        $this->assertSame([1, 2, 3, 4], $gradeValues);
    }

    // ==================== NAMES METHOD TESTS ====================

    public function test_names_returns_all_case_names_for_backed_string_enum(): void
    {
        $names = TestBackedStringEnum::names();

        $this->assertSame(['VALUE_ONE', 'VALUE_TWO', 'VALUE_THREE'], $names);
        $this->assertCount(3, $names);
    }

    public function test_names_returns_all_case_names_for_backed_int_enum(): void
    {
        $names = TestBackedIntEnum::names();

        $this->assertSame(['VALUE_ONE', 'VALUE_TWO', 'VALUE_THREE'], $names);
        $this->assertCount(3, $names);
    }

    public function test_names_returns_all_case_names_for_pure_enum(): void
    {
        $names = TestPureEnum::names();

        $this->assertSame(['VALUE_ONE', 'VALUE_TWO', 'VALUE_THREE'], $names);
        $this->assertCount(3, $names);
    }

    public function test_names_returns_upper_case_format_names(): void
    {
        $statusNames = TestUserStatus::names();

        $this->assertSame(['ACTIVE', 'INACTIVE', 'SUSPENDED'], $statusNames);
    }

    // ==================== TYPES_IN_ORDER METHOD TESTS ====================

    public function test_types_in_order_returns_all_cases_in_defined_order(): void
    {
        $cases = TestUserStatus::typesInOrder();

        $this->assertCount(3, $cases);
        $this->assertSame(TestUserStatus::ACTIVE, $cases[0]);
        $this->assertSame(TestUserStatus::INACTIVE, $cases[1]);
        $this->assertSame(TestUserStatus::SUSPENDED, $cases[2]);
    }

    public function test_types_in_order_returns_same_as_cases_method(): void
    {
        $typesInOrder = TestUserStatus::typesInOrder();
        $cases = TestUserStatus::cases();

        $this->assertSame($cases, $typesInOrder);
    }

    // ==================== IS_VALID METHOD TESTS ====================

    public function test_is_valid_returns_true_for_valid_backed_string_enum_value(): void
    {
        $this->assertTrue(TestBackedStringEnum::isValid('one'));
        $this->assertTrue(TestBackedStringEnum::isValid('two'));
        $this->assertTrue(TestBackedStringEnum::isValid('three'));
    }

    public function test_is_valid_returns_false_for_invalid_backed_string_enum_value(): void
    {
        $this->assertFalse(TestBackedStringEnum::isValid('four'));
        $this->assertFalse(TestBackedStringEnum::isValid('invalid'));
        $this->assertFalse(TestBackedStringEnum::isValid(''));
    }

    public function test_is_valid_returns_true_for_valid_backed_int_enum_value(): void
    {
        $this->assertTrue(TestBackedIntEnum::isValid(1));
        $this->assertTrue(TestBackedIntEnum::isValid(2));
        $this->assertTrue(TestBackedIntEnum::isValid(3));
    }

    public function test_is_valid_returns_false_for_invalid_backed_int_enum_value(): void
    {
        $this->assertFalse(TestBackedIntEnum::isValid(0));
        $this->assertFalse(TestBackedIntEnum::isValid(4));
        $this->assertFalse(TestBackedIntEnum::isValid(100));
    }

    public function test_is_valid_returns_true_for_valid_pure_enum_case_name(): void
    {
        $this->assertTrue(TestPureEnum::isValid('VALUE_ONE'));
        $this->assertTrue(TestPureEnum::isValid('VALUE_TWO'));
        $this->assertTrue(TestPureEnum::isValid('VALUE_THREE'));
    }

    public function test_is_valid_returns_false_for_invalid_pure_enum_case_name(): void
    {
        $this->assertFalse(TestPureEnum::isValid('VALUE_FOUR'));
        $this->assertFalse(TestPureEnum::isValid('invalid'));
        $this->assertFalse(TestPureEnum::isValid('value_one'));
    }

    public function test_is_valid_is_case_sensitive_for_pure_enums(): void
    {
        $this->assertTrue(TestPureEnum::isValid('VALUE_ONE'));
        $this->assertFalse(TestPureEnum::isValid('value_one'));
        $this->assertFalse(TestPureEnum::isValid('Value_One'));
    }

    // ==================== FROM_VALUE METHOD TESTS ====================

    public function test_from_value_returns_enum_for_valid_backed_string_value(): void
    {
        $enum = TestBackedStringEnum::fromValue('one');

        $this->assertNotNull($enum);
        $this->assertSame(TestBackedStringEnum::VALUE_ONE, $enum);
    }

    public function test_from_value_returns_null_for_invalid_backed_string_value(): void
    {
        $enum = TestBackedStringEnum::fromValue('invalid');

        $this->assertNull($enum);
    }

    public function test_from_value_returns_enum_for_valid_backed_int_value(): void
    {
        $enum = TestBackedIntEnum::fromValue(2);

        $this->assertNotNull($enum);
        $this->assertSame(TestBackedIntEnum::VALUE_TWO, $enum);
    }

    public function test_from_value_returns_null_for_invalid_backed_int_value(): void
    {
        $enum = TestBackedIntEnum::fromValue(99);

        $this->assertNull($enum);
    }

    public function test_from_value_returns_enum_for_valid_pure_enum_case_name(): void
    {
        $enum = TestPureEnum::fromValue('VALUE_TWO');

        $this->assertNotNull($enum);
        $this->assertSame(TestPureEnum::VALUE_TWO, $enum);
    }

    public function test_from_value_returns_null_for_invalid_pure_enum_case_name(): void
    {
        $enum = TestPureEnum::fromValue('INVALID');

        $this->assertNull($enum);
    }

    public function test_from_value_is_case_sensitive_for_pure_enums(): void
    {
        $enum = TestPureEnum::fromValue('value_one');

        $this->assertNull($enum);
    }

    public function test_from_value_converts_string_to_int_when_possible(): void
    {
        $enum = TestBackedIntEnum::fromValue('2');

        $this->assertNotNull($enum);
        $this->assertSame(TestBackedIntEnum::VALUE_TWO, $enum);
    }

    /**
     * Test that fromValue returns null for empty string.
     * CORRIGÉ: Utiliser fromValue au lieu de from
     */
    public function test_from_value_returns_null_for_empty_string(): void
    {
        $enum = TestBackedStringEnum::fromValue('');

        $this->assertNull($enum);
    }

    /**
     * Test that from throws exception for empty string.
     */
    public function test_from_throws_exception_for_empty_string(): void
    {
        $this->expectException(\ValueError::class);
        $this->expectExceptionMessage('is not a valid backing value for enum');

        TestBackedStringEnum::from('');
    }

    // ==================== EDGE CASES TESTS ====================

    public function test_values_on_enum_with_single_case_works(): void
    {
        $values = TestSingleCaseEnum::values();

        $this->assertSame(['single'], $values);
    }

    public function test_names_on_enum_with_single_case_works(): void
    {
        $names = TestSingleCaseEnum::names();

        $this->assertSame(['SINGLE'], $names);
    }

    public function test_is_valid_returns_false_for_empty_string(): void
    {
        $this->assertFalse(TestBackedStringEnum::isValid(''));
        $this->assertFalse(TestPureEnum::isValid(''));
    }
}
