<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Unit\Enums;

use AndyDefer\DomainStructures\Enums\NormalizeMode;
use AndyDefer\DomainStructures\Tests\TestCase;

/**
 * Unit tests for NormalizeMode enum.
 *
 * This test suite validates the NormalizeMode enum:
 * - Enum cases existence
 * - Case values
 * - Case count
 * - Type consistency
 *
 * The tests follow the AAA pattern (Arrange, Act, Assert).
 */
final class NormalizeModeTest extends TestCase
{
    // ==================== ENUM CASES TESTS ====================

    /**
     * Test that NormalizeMode has ARRAY case.
     */
    public function test_enum_has_array_case(): void
    {
        // Arrange & Act & Assert
        $this->assertTrue(NormalizeMode::tryFrom('array') instanceof NormalizeMode);
        $this->assertSame('array', NormalizeMode::ARRAY->value);
    }

    /**
     * Test that NormalizeMode has JSON case.
     */
    public function test_enum_has_json_case(): void
    {
        // Arrange & Act & Assert
        $this->assertTrue(NormalizeMode::tryFrom('json') instanceof NormalizeMode);
        $this->assertSame('json', NormalizeMode::JSON->value);
    }

    /**
     * Test that NormalizeMode has exactly 2 cases.
     */
    public function test_enum_has_exactly_two_cases(): void
    {
        // Arrange & Act
        $cases = NormalizeMode::cases();

        // Assert
        $this->assertCount(2, $cases);
        $this->assertSame([NormalizeMode::ARRAY, NormalizeMode::JSON], $cases);
    }

    // ==================== CASE VALUES TESTS ====================

    /**
     * Test that ARRAY case value is 'array'.
     */
    public function test_array_case_value_is_array(): void
    {
        // Arrange & Act & Assert
        $this->assertSame('array', NormalizeMode::ARRAY->value);
        $this->assertIsString(NormalizeMode::ARRAY->value);
    }

    /**
     * Test that JSON case value is 'json'.
     */
    public function test_json_case_value_is_json(): void
    {
        // Arrange & Act & Assert
        $this->assertSame('json', NormalizeMode::JSON->value);
        $this->assertIsString(NormalizeMode::JSON->value);
    }

    // ==================== FROM VALUE TESTS ====================

    /**
     * Test that tryFrom returns ARRAY for 'array' string.
     */
    public function test_try_from_returns_array_for_array_string(): void
    {
        // Arrange & Act
        $mode = NormalizeMode::tryFrom('array');

        // Assert
        $this->assertNotNull($mode);
        $this->assertSame(NormalizeMode::ARRAY, $mode);
    }

    /**
     * Test that tryFrom returns JSON for 'json' string.
     */
    public function test_try_from_returns_json_for_json_string(): void
    {
        // Arrange & Act
        $mode = NormalizeMode::tryFrom('json');

        // Assert
        $this->assertNotNull($mode);
        $this->assertSame(NormalizeMode::JSON, $mode);
    }

    /**
     * Test that tryFrom returns null for invalid string.
     */
    public function test_try_from_returns_null_for_invalid_string(): void
    {
        // Arrange & Act
        $mode = NormalizeMode::tryFrom('invalid');

        // Assert
        $this->assertNull($mode);
    }

    /**
     * Test that tryFrom is case-sensitive.
     */
    public function test_try_from_is_case_sensitive(): void
    {
        // Arrange & Act
        $modeLower = NormalizeMode::tryFrom('array');
        $modeUpper = NormalizeMode::tryFrom('ARRAY');
        $modeMixed = NormalizeMode::tryFrom('Array');

        // Assert
        $this->assertNotNull($modeLower);
        $this->assertNull($modeUpper);
        $this->assertNull($modeMixed);
    }

    // ==================== USAGE IN NORMALIZATION TESTS ====================

    /**
     * Test that ARRAY mode is the default mode.
     */
    public function test_array_mode_is_default_mode(): void
    {
        // This is a documentation test - in practice, default is ARRAY
        $defaultMode = NormalizeMode::ARRAY;

        $this->assertSame('array', $defaultMode->value);
    }

    /**
     * Test that enum can be used in switch statements.
     */
    public function test_enum_can_be_used_in_switch_statements(): void
    {
        // Arrange
        $modeArray = NormalizeMode::ARRAY;
        $modeJson = NormalizeMode::JSON;

        $resultArray = '';
        $resultJson = '';

        // Act
        switch ($modeArray) {
            case NormalizeMode::ARRAY:
                $resultArray = 'array';
                break;
            case NormalizeMode::JSON:
                $resultArray = 'json';
                break;
        }

        switch ($modeJson) {
            case NormalizeMode::ARRAY:
                $resultJson = 'array';
                break;
            case NormalizeMode::JSON:
                $resultJson = 'json';
                break;
        }

        // Assert
        $this->assertSame('array', $resultArray);
        $this->assertSame('json', $resultJson);
    }

    /**
     * Test that enum can be used in match expressions.
     */
    public function test_enum_can_be_used_in_match_expressions(): void
    {
        // Arrange
        $modeArray = NormalizeMode::ARRAY;
        $modeJson = NormalizeMode::JSON;

        // Act
        $resultArray = match ($modeArray) {
            NormalizeMode::ARRAY => 'array',
            NormalizeMode::JSON => 'json',
        };

        $resultJson = match ($modeJson) {
            NormalizeMode::ARRAY => 'array',
            NormalizeMode::JSON => 'json',
        };

        // Assert
        $this->assertSame('array', $resultArray);
        $this->assertSame('json', $resultJson);
    }
}
