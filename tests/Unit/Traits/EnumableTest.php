<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Unit\Traits;

use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestSingleCaseEnum;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestBackedIntEnum;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestBackedStringEnum;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestPureEnum;
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

    /**
     * Test that values returns backing values for backed string enum.
     */
    public function test_values_returns_backing_values_for_backed_string_enum(): void
    {
        // Arrange & Act
        $values = TestBackedStringEnum::values();

        // Assert
        $this->assertSame(['one', 'two', 'three'], $values);
        $this->assertCount(3, $values);
    }

    /**
     * Test that values returns backing values for backed int enum.
     */
    public function test_values_returns_backing_values_for_backed_int_enum(): void
    {
        // Arrange & Act
        $values = TestBackedIntEnum::values();

        // Assert
        $this->assertSame([1, 2, 3], $values);
        $this->assertCount(3, $values);
    }

    /**
     * Test that values returns case names for pure enum.
     */
    public function test_values_returns_case_names_for_pure_enum(): void
    {
        // Arrange & Act
        $values = TestPureEnum::values();

        // Assert
        $this->assertSame(['VALUE_ONE', 'VALUE_TWO', 'VALUE_THREE'], $values);
        $this->assertCount(3, $values);
    }

    /**
     * Test that values returns all enum values in correct order.
     */
    public function test_values_returns_all_enum_values_in_correct_order(): void
    {
        // Arrange & Act
        $statusValues = TestUserStatus::values();
        $roleValues = TestUserRole::values();
        $gradeValues = TestUserGrade::values();

        // Assert
        $this->assertSame(['active', 'inactive', 'suspended'], $statusValues);
        $this->assertSame(['admin', 'user', 'guest'], $roleValues);
        $this->assertSame([1, 2, 3, 4], $gradeValues);
    }

    // ==================== NAMES METHOD TESTS ====================

    /**
     * Test that names returns all case names for backed string enum.
     */
    public function test_names_returns_all_case_names_for_backed_string_enum(): void
    {
        // Arrange & Act
        $names = TestBackedStringEnum::names();

        // Assert
        $this->assertSame(['VALUE_ONE', 'VALUE_TWO', 'VALUE_THREE'], $names);
        $this->assertCount(3, $names);
    }

    /**
     * Test that names returns all case names for backed int enum.
     */
    public function test_names_returns_all_case_names_for_backed_int_enum(): void
    {
        // Arrange & Act
        $names = TestBackedIntEnum::names();

        // Assert
        $this->assertSame(['VALUE_ONE', 'VALUE_TWO', 'VALUE_THREE'], $names);
        $this->assertCount(3, $names);
    }

    /**
     * Test that names returns all case names for pure enum.
     */
    public function test_names_returns_all_case_names_for_pure_enum(): void
    {
        // Arrange & Act
        $names = TestPureEnum::names();

        // Assert
        $this->assertSame(['VALUE_ONE', 'VALUE_TWO', 'VALUE_THREE'], $names);
        $this->assertCount(3, $names);
    }

    /**
     * Test that names returns UPPER_CASE format names.
     */
    public function test_names_returns_upper_case_format_names(): void
    {
        // Arrange & Act
        $statusNames = TestUserStatus::names();

        // Assert
        $this->assertSame(['ACTIVE', 'INACTIVE', 'SUSPENDED'], $statusNames);
    }

    // ==================== TYPES_IN_ORDER METHOD TESTS ====================

    /**
     * Test that typesInOrder returns all cases in defined order.
     */
    public function test_types_in_order_returns_all_cases_in_defined_order(): void
    {
        // Arrange & Act
        $cases = TestUserStatus::typesInOrder();

        // Assert
        $this->assertCount(3, $cases);
        $this->assertSame(TestUserStatus::ACTIVE, $cases[0]);
        $this->assertSame(TestUserStatus::INACTIVE, $cases[1]);
        $this->assertSame(TestUserStatus::SUSPENDED, $cases[2]);
    }

    /**
     * Test that typesInOrder returns same as cases() method.
     */
    public function test_types_in_order_returns_same_as_cases_method(): void
    {
        // Arrange & Act
        $typesInOrder = TestUserStatus::typesInOrder();
        $cases = TestUserStatus::cases();

        // Assert
        $this->assertSame($cases, $typesInOrder);
    }

    // ==================== IS_VALID METHOD TESTS ====================

    /**
     * Test that isValid returns true for valid backed string enum value.
     */
    public function test_is_valid_returns_true_for_valid_backed_string_enum_value(): void
    {
        // Arrange & Act & Assert
        $this->assertTrue(TestBackedStringEnum::isValid('one'));
        $this->assertTrue(TestBackedStringEnum::isValid('two'));
        $this->assertTrue(TestBackedStringEnum::isValid('three'));
    }

    /**
     * Test that isValid returns false for invalid backed string enum value.
     */
    public function test_is_valid_returns_false_for_invalid_backed_string_enum_value(): void
    {
        // Arrange & Act & Assert
        $this->assertFalse(TestBackedStringEnum::isValid('four'));
        $this->assertFalse(TestBackedStringEnum::isValid('invalid'));
        $this->assertFalse(TestBackedStringEnum::isValid(''));
    }

    /**
     * Test that isValid returns true for valid backed int enum value.
     */
    public function test_is_valid_returns_true_for_valid_backed_int_enum_value(): void
    {
        // Arrange & Act & Assert
        $this->assertTrue(TestBackedIntEnum::isValid(1));
        $this->assertTrue(TestBackedIntEnum::isValid(2));
        $this->assertTrue(TestBackedIntEnum::isValid(3));
    }

    /**
     * Test that isValid returns false for invalid backed int enum value.
     */
    public function test_is_valid_returns_false_for_invalid_backed_int_enum_value(): void
    {
        // Arrange & Act & Assert
        $this->assertFalse(TestBackedIntEnum::isValid(0));
        $this->assertFalse(TestBackedIntEnum::isValid(4));
        $this->assertFalse(TestBackedIntEnum::isValid(100));
    }

    /**
     * Test that isValid returns true for valid pure enum case name.
     */
    public function test_is_valid_returns_true_for_valid_pure_enum_case_name(): void
    {
        // Arrange & Act & Assert
        $this->assertTrue(TestPureEnum::isValid('VALUE_ONE'));
        $this->assertTrue(TestPureEnum::isValid('VALUE_TWO'));
        $this->assertTrue(TestPureEnum::isValid('VALUE_THREE'));
    }

    /**
     * Test that isValid returns false for invalid pure enum case name.
     */
    public function test_is_valid_returns_false_for_invalid_pure_enum_case_name(): void
    {
        // Arrange & Act & Assert
        $this->assertFalse(TestPureEnum::isValid('VALUE_FOUR'));
        $this->assertFalse(TestPureEnum::isValid('invalid'));
        $this->assertFalse(TestPureEnum::isValid('value_one'));
    }

    /**
     * Test that isValid is case-sensitive for pure enums.
     */
    public function test_is_valid_is_case_sensitive_for_pure_enums(): void
    {
        // Arrange & Act & Assert
        $this->assertTrue(TestPureEnum::isValid('VALUE_ONE'));
        $this->assertFalse(TestPureEnum::isValid('value_one'));
        $this->assertFalse(TestPureEnum::isValid('Value_One'));
    }

    // ==================== FROM_VALUE METHOD TESTS ====================

    /**
     * Test that fromValue returns enum for valid backed string value.
     */
    public function test_from_value_returns_enum_for_valid_backed_string_value(): void
    {
        // Arrange & Act
        $enum = TestBackedStringEnum::fromValue('one');

        // Assert
        $this->assertNotNull($enum);
        $this->assertSame(TestBackedStringEnum::VALUE_ONE, $enum);
    }

    /**
     * Test that fromValue returns null for invalid backed string value.
     */
    public function test_from_value_returns_null_for_invalid_backed_string_value(): void
    {
        // Arrange & Act
        $enum = TestBackedStringEnum::fromValue('invalid');

        // Assert
        $this->assertNull($enum);
    }

    /**
     * Test that fromValue returns enum for valid backed int value.
     */
    public function test_from_value_returns_enum_for_valid_backed_int_value(): void
    {
        // Arrange & Act
        $enum = TestBackedIntEnum::fromValue(2);

        // Assert
        $this->assertNotNull($enum);
        $this->assertSame(TestBackedIntEnum::VALUE_TWO, $enum);
    }

    /**
     * Test that fromValue returns null for invalid backed int value.
     */
    public function test_from_value_returns_null_for_invalid_backed_int_value(): void
    {
        // Arrange & Act
        $enum = TestBackedIntEnum::fromValue(99);

        // Assert
        $this->assertNull($enum);
    }

    /**
     * Test that fromValue returns enum for valid pure enum case name.
     */
    public function test_from_value_returns_enum_for_valid_pure_enum_case_name(): void
    {
        // Arrange & Act
        $enum = TestPureEnum::fromValue('VALUE_TWO');

        // Assert
        $this->assertNotNull($enum);
        $this->assertSame(TestPureEnum::VALUE_TWO, $enum);
    }

    /**
     * Test that fromValue returns null for invalid pure enum case name.
     */
    public function test_from_value_returns_null_for_invalid_pure_enum_case_name(): void
    {
        // Arrange & Act
        $enum = TestPureEnum::fromValue('INVALID');

        // Assert
        $this->assertNull($enum);
    }

    /**
     * Test that fromValue is case-sensitive for pure enums.
     */
    public function test_from_value_is_case_sensitive_for_pure_enums(): void
    {
        // Arrange & Act
        $enum = TestPureEnum::fromValue('value_one');

        // Assert
        $this->assertNull($enum);
    }

    /**
     * Test that fromValue returns enum even when value is string but enum expects int.
     */
    public function test_from_value_converts_string_to_int_when_possible(): void
    {
        // Act
        $enum = TestBackedIntEnum::fromValue('2');

        // Assert - String '2' converts to int 2
        $this->assertNotNull($enum);
        $this->assertSame(TestBackedIntEnum::VALUE_TWO, $enum);
    }

    // ==================== EDGE CASES TESTS ====================

    /**
     * Test that values on enum with single case works.
     */
    public function test_values_on_enum_with_single_case_works(): void
    {
        // Arrange & Act : Create a test enum with single case
        $values = TestSingleCaseEnum::values();

        // Assert
        $this->assertSame(['single'], $values);
    }

    /**
     * Test that names on enum with single case works.
     */
    public function test_names_on_enum_with_single_case_works(): void
    {
        // Arrange & Act : Create a test enum with single case
        $names = TestSingleCaseEnum::names();

        // Assert
        $this->assertSame(['SINGLE'], $names);
    }

    /**
     * Test that fromValue returns null for empty string.
     */
    public function test_from_value_returns_null_for_empty_string(): void
    {
        // Arrange & Act
        $enum = TestBackedStringEnum::from('');

        // Assert
        $this->assertNull($enum);
    }

    /**
     * Test that isValid returns false for empty string.
     */
    public function test_is_valid_returns_false_for_empty_string(): void
    {
        // Arrange & Act & Assert
        $this->assertFalse(TestBackedStringEnum::isValid(''));
        $this->assertFalse(TestPureEnum::isValid(''));
    }
}
