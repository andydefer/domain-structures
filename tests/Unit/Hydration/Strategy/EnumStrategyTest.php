<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Unit\Hydration\Strategy;

use AndyDefer\DomainStructures\Hydration\Strategy\EnumStrategy;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestPureEnum;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestUserGrade;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestUserStatus;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * Test suite for EnumStrategy.
 *
 * Verifies that enums (backed string, backed int, and pure enums)
 * are correctly hydrated from various source formats.
 */
final class EnumStrategyTest extends TestCase
{
    private EnumStrategy $strategy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->strategy = new EnumStrategy();
    }

    // ==================== SUPPORTS METHOD TESTS ====================

    /**
     * Test that supports() returns true for any enum class.
     */
    public function test_supports_returns_true_for_enum_class(): void
    {
        // Arrange: Strategy already initialized in setUp
        // Act: Call supports with an enum class
        $result = $this->strategy->supports(TestUserStatus::class, 'test');

        // Assert: Should return true for any enum
        $this->assertTrue($result, 'supports() should return true for enum classes');
    }

    /**
     * Test that supports() returns false for non-enum classes.
     */
    public function test_supports_returns_false_for_non_enum_class(): void
    {
        // Arrange: Strategy already initialized in setUp
        // Act: Call supports with a non-enum class
        $result = $this->strategy->supports(\stdClass::class, 'test');

        // Assert: Should return false for non-enum classes
        $this->assertFalse($result, 'supports() should return false for non-enum classes');
    }

    // ==================== BACKED STRING ENUM TESTS ====================

    /**
     * Test that a backed string enum can be hydrated from a string value.
     */
    public function test_hydrates_backed_string_enum_from_string(): void
    {
        // Arrange: Define the source value and expected enum
        $source = 'active';
        $expectedEnum = TestUserStatus::ACTIVE;

        // Act: Hydrate the enum from string source
        $actualEnum = $this->strategy->hydrate(TestUserStatus::class, $source);

        // Assert: Verify the correct enum instance and its value
        $this->assertSame($expectedEnum, $actualEnum, 'Should return ACTIVE enum instance');
        $this->assertSame('active', $actualEnum->value, 'Enum value should be "active"');
    }

    /**
     * Test that a backed string enum can be hydrated from an array with a 'value' key.
     */
    public function test_hydrates_backed_string_enum_from_array_with_value_key(): void
    {
        // Arrange: Define array source with 'value' key
        $source = ['value' => 'inactive'];
        $expectedEnum = TestUserStatus::INACTIVE;

        // Act: Hydrate the enum from array source
        $actualEnum = $this->strategy->hydrate(TestUserStatus::class, $source);

        // Assert: Verify the correct enum instance
        $this->assertSame($expectedEnum, $actualEnum, 'Should return INACTIVE enum instance from array with value key');
    }

    /**
     * Test that a backed string enum can be hydrated from a nested array structure.
     */
    public function test_hydrates_backed_string_enum_from_array_with_nested_value(): void
    {
        // Arrange: Create nested array structure mimicking real data
        $source = [
            'user' => ['status' => ['value' => 'pending']]
        ];
        $expectedEnum = TestUserStatus::PENDING;

        // Act: Extract nested value and hydrate
        $actualEnum = $this->strategy->hydrate(TestUserStatus::class, $source['user']['status']);

        // Assert: Verify the correct enum instance
        $this->assertSame($expectedEnum, $actualEnum, 'Should return PENDING enum instance from nested array');
    }

    /**
     * Test that passing an existing enum instance returns the same instance.
     */
    public function test_hydrates_backed_string_enum_from_existing_instance(): void
    {
        // Arrange: Create an existing enum instance
        $existingEnum = TestUserStatus::ACTIVE;

        // Act: Attempt to hydrate the same instance
        $result = $this->strategy->hydrate(TestUserStatus::class, $existingEnum);

        // Assert: Should return the same instance without modification
        $this->assertSame($existingEnum, $result, 'Should return the existing enum instance unchanged');
    }

    // ==================== BACKED INT ENUM TESTS ====================

    /**
     * Test that a backed int enum can be hydrated from an integer value.
     */
    public function test_hydrates_backed_int_enum_from_int(): void
    {
        // Arrange: Define integer source and expected enum
        $source = 1;
        $expectedEnum = TestUserGrade::BRONZE;

        // Act: Hydrate the enum from integer source
        $actualEnum = $this->strategy->hydrate(TestUserGrade::class, $source);

        // Assert: Verify the correct enum instance and its value
        $this->assertSame($expectedEnum, $actualEnum, 'Should return BRONZE enum instance from int 1');
        $this->assertSame(1, $actualEnum->value, 'Enum value should be 1');
    }

    /**
     * Test that a backed int enum can be hydrated from a numeric string.
     */
    public function test_hydrates_backed_int_enum_from_int_as_string(): void
    {
        // Arrange: Define string numeric source and expected enum
        $source = '1';
        $expectedEnum = TestUserGrade::BRONZE;

        // Act: Hydrate the enum from string numeric source
        $actualEnum = $this->strategy->hydrate(TestUserGrade::class, $source);

        // Assert: String "1" should be auto-converted to int 1
        $this->assertSame($expectedEnum, $actualEnum, 'Should convert string "1" to int and return BRONZE enum');
    }

    /**
     * Test that a backed int enum can be hydrated from an array with a 'value' key.
     */
    public function test_hydrates_backed_int_enum_from_array_with_value_key(): void
    {
        // Arrange: Define array source with integer value
        $source = ['value' => 2];
        $expectedEnum = TestUserGrade::SILVER;

        // Act: Hydrate the enum from array source
        $actualEnum = $this->strategy->hydrate(TestUserGrade::class, $source);

        // Assert: Verify the correct enum instance
        $this->assertSame($expectedEnum, $actualEnum, 'Should return SILVER enum instance from array with value 2');
    }

    /**
     * Test that a backed int enum can be hydrated from an array with a string numeric value.
     */
    public function test_hydrates_backed_int_enum_from_array_with_value_key_as_string(): void
    {
        // Arrange: Define array source with string numeric value
        $source = ['value' => '2'];
        $expectedEnum = TestUserGrade::SILVER;

        // Act: Hydrate the enum from array with string numeric value
        $actualEnum = $this->strategy->hydrate(TestUserGrade::class, $source);

        // Assert: String "2" should be auto-converted to int 2
        $this->assertSame($expectedEnum, $actualEnum, 'Should convert string "2" to int and return SILVER enum');
    }

    /**
     * Test that a backed int enum can be hydrated from a nested array structure.
     */
    public function test_hydrates_backed_int_enum_from_array_with_nested_value(): void
    {
        // Arrange: Create nested array structure
        $source = [
            'user' => ['grade' => ['value' => 3]]
        ];
        $expectedEnum = TestUserGrade::GOLD;

        // Act: Extract nested value and hydrate
        $actualEnum = $this->strategy->hydrate(TestUserGrade::class, $source['user']['grade']);

        // Assert: Verify the correct enum instance
        $this->assertSame($expectedEnum, $actualEnum, 'Should return GOLD enum instance from nested array with value 3');
    }

    /**
     * Test that passing an existing enum instance returns the same instance.
     */
    public function test_hydrates_backed_int_enum_from_existing_instance(): void
    {
        // Arrange: Create an existing enum instance
        $existingEnum = TestUserGrade::BRONZE;

        // Act: Attempt to hydrate the same instance
        $result = $this->strategy->hydrate(TestUserGrade::class, $existingEnum);

        // Assert: Should return the same instance without modification
        $this->assertSame($existingEnum, $result, 'Should return the existing enum instance unchanged');
    }

    // ==================== PURE ENUM TESTS ====================

    /**
     * Test that a pure enum can be hydrated from a case name string.
     */
    public function test_hydrates_pure_enum_from_string(): void
    {
        // Arrange: Define case name source and expected enum
        $source = 'VALUE_ONE';
        $expectedEnum = TestPureEnum::VALUE_ONE;

        // Act: Hydrate the pure enum from string source
        $actualEnum = $this->strategy->hydrate(TestPureEnum::class, $source);

        // Assert: Verify the correct enum instance and its name
        $this->assertSame($expectedEnum, $actualEnum, 'Should return VALUE_ONE pure enum instance');
        $this->assertSame('VALUE_ONE', $actualEnum->name, 'Enum name should be "VALUE_ONE"');
    }

    /**
     * Test that a pure enum can be hydrated from an array with a 'name' key.
     */
    public function test_hydrates_pure_enum_from_array_with_name_key(): void
    {
        // Arrange: Define array source with 'name' key
        $source = ['name' => 'VALUE_TWO'];
        $expectedEnum = TestPureEnum::VALUE_TWO;

        // Act: Hydrate the pure enum from array source
        $actualEnum = $this->strategy->hydrate(TestPureEnum::class, $source);

        // Assert: Verify the correct enum instance
        $this->assertSame($expectedEnum, $actualEnum, 'Should return VALUE_TWO pure enum instance from array with name key');
    }

    /**
     * Test that a pure enum can be hydrated from a nested array structure.
     */
    public function test_hydrates_pure_enum_from_array_with_nested_name(): void
    {
        // Arrange: Create nested array structure
        $source = [
            'user' => ['role' => ['name' => 'VALUE_THREE']]
        ];
        $expectedEnum = TestPureEnum::VALUE_THREE;

        // Act: Extract nested value and hydrate
        $actualEnum = $this->strategy->hydrate(TestPureEnum::class, $source['user']['role']);

        // Assert: Verify the correct enum instance
        $this->assertSame($expectedEnum, $actualEnum, 'Should return VALUE_THREE pure enum instance from nested array');
    }

    /**
     * Test that passing an existing pure enum instance returns the same instance.
     */
    public function test_hydrates_pure_enum_from_existing_instance(): void
    {
        // Arrange: Create an existing enum instance
        $existingEnum = TestPureEnum::VALUE_ONE;

        // Act: Attempt to hydrate the same instance
        $result = $this->strategy->hydrate(TestPureEnum::class, $existingEnum);

        // Assert: Should return the same instance without modification
        $this->assertSame($existingEnum, $result, 'Should return the existing pure enum instance unchanged');
    }

    // ==================== ERROR HANDLING TESTS ====================

    /**
     * Test that an exception is thrown for an invalid backed string enum value.
     */
    public function test_throws_exception_for_invalid_backed_string_enum_value(): void
    {
        // Arrange: Define invalid string value
        $invalidSource = 'invalid';

        // Expect: InvalidArgumentException with specific message
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid value "invalid" for enum');

        // Act: Attempt to hydrate with invalid value
        $this->strategy->hydrate(TestUserStatus::class, $invalidSource);
    }

    /**
     * Test that an exception is thrown for an invalid backed int enum value.
     */
    public function test_throws_exception_for_invalid_backed_int_enum_value(): void
    {
        // Arrange: Define invalid integer value
        $invalidSource = 99;

        // Expect: InvalidArgumentException with specific message
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid value "99" for enum');

        // Act: Attempt to hydrate with invalid value
        $this->strategy->hydrate(TestUserGrade::class, $invalidSource);
    }

    /**
     * Test that an exception is thrown for an invalid backed int enum value as a string.
     */
    public function test_throws_exception_for_invalid_backed_int_enum_value_as_string(): void
    {
        // Arrange: Define invalid string numeric value
        $invalidSource = '99';

        // Expect: InvalidArgumentException with specific message
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid value "99" for enum');

        // Act: Attempt to hydrate with invalid value
        $this->strategy->hydrate(TestUserGrade::class, $invalidSource);
    }

    /**
     * Test that an exception is thrown for an invalid pure enum case name.
     */
    public function test_throws_exception_for_invalid_pure_enum_value(): void
    {
        // Arrange: Define invalid case name
        $invalidSource = 'INVALID';

        // Expect: InvalidArgumentException with specific message
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid value "INVALID" for enum');

        // Act: Attempt to hydrate with invalid case name
        $this->strategy->hydrate(TestPureEnum::class, $invalidSource);
    }

    /**
     * Test that an exception is thrown for an array without 'value' or 'name' keys.
     */
    public function test_throws_exception_for_array_without_value_or_name_key(): void
    {
        // Arrange: Define array without required keys
        $invalidSource = ['wrong' => 'active'];

        // Expect: InvalidArgumentException with specific message
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot hydrate enum ' . TestUserStatus::class . ' from array without "value" or "name" key');

        // Act: Attempt to hydrate with invalid array structure
        $this->strategy->hydrate(TestUserStatus::class, $invalidSource);
    }

    /**
     * Test that an exception is thrown for a null source.
     */
    public function test_throws_exception_for_null_source(): void
    {
        // Arrange: Define null source
        $invalidSource = null;

        // Expect: InvalidArgumentException with specific message
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot hydrate enum ' . TestUserStatus::class . ' from source type: NULL');

        // Act: Attempt to hydrate with null source
        $this->strategy->hydrate(TestUserStatus::class, $invalidSource);
    }

    // ==================== NESTED HYDRATION TESTS ====================

    /**
     * Test that a backed int enum can be hydrated from a deeply nested array.
     */
    public function test_deeply_nested_array_hydration_with_int_enum(): void
    {
        // Arrange: Create deeply nested array structure
        $source = [
            'level1' => [
                'level2' => [
                    'level3' => [
                        'value' => 3
                    ]
                ]
            ]
        ];
        $expectedEnum = TestUserGrade::GOLD;

        // Act: Extract deeply nested value and hydrate
        $actualEnum = $this->strategy->hydrate(TestUserGrade::class, $source['level1']['level2']['level3']);

        // Assert: Verify the correct enum instance
        $this->assertSame($expectedEnum, $actualEnum, 'Should hydrate GOLD enum from deeply nested array with value 3');
    }

    /**
     * Test that a backed int enum can be hydrated from a deeply nested array with a string numeric value.
     */
    public function test_deeply_nested_array_hydration_with_int_enum_as_string(): void
    {
        // Arrange: Create deeply nested array with string numeric value
        $source = [
            'level1' => [
                'level2' => [
                    'level3' => [
                        'value' => '3'
                    ]
                ]
            ]
        ];
        $expectedEnum = TestUserGrade::GOLD;

        // Act: Extract deeply nested value and hydrate
        $actualEnum = $this->strategy->hydrate(TestUserGrade::class, $source['level1']['level2']['level3']);

        // Assert: String "3" should be auto-converted to int 3
        $this->assertSame($expectedEnum, $actualEnum, 'Should convert string "3" to int and hydrate GOLD enum from deeply nested array');
    }
}
