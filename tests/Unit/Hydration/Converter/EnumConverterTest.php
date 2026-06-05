<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Unit\Hydration\Converter;

use AndyDefer\DomainStructures\Hydration\Converter\EnumConverter;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestBackedIntEnum;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestBackedStringEnum;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestPureEnum;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestUserRole;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestUserStatus;
use AndyDefer\DomainStructures\Tests\TestCase;
use InvalidArgumentException;
use stdClass;

final class EnumConverterTest extends TestCase
{
    private EnumConverter $converter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->converter = new EnumConverter;
    }

    // ==================== SUPPORTS METHOD TESTS ====================

    public function test_supports_returns_true_for_backed_string_enum_type(): void
    {
        $result = $this->converter->supports(TestBackedStringEnum::class);
        $this->assertTrue($result);
    }

    public function test_supports_returns_true_for_backed_int_enum_type(): void
    {
        $result = $this->converter->supports(TestBackedIntEnum::class);
        $this->assertTrue($result);
    }

    public function test_supports_returns_true_for_pure_enum_type(): void
    {
        $result = $this->converter->supports(TestPureEnum::class);
        $this->assertTrue($result);
    }

    public function test_supports_returns_true_for_any_enum_type(): void
    {
        $enumTypes = [
            TestUserStatus::class,
            TestUserRole::class,
            TestBackedStringEnum::class,
            TestBackedIntEnum::class,
            TestPureEnum::class,
        ];

        foreach ($enumTypes as $type) {
            $result = $this->converter->supports($type);
            $this->assertTrue($result, "Failed for enum: {$type}");
        }
    }

    public function test_supports_returns_false_for_non_enum_types(): void
    {
        $nonEnumTypes = ['int', 'string', 'array', stdClass::class, self::class];

        foreach ($nonEnumTypes as $type) {
            $result = $this->converter->supports($type);
            $this->assertFalse($result, "Failed for type: {$type}");
        }
    }

    // ==================== CONVERT TO BACKED STRING ENUM TESTS ====================

    public function test_convert_converts_string_to_backed_string_enum(): void
    {
        $result = $this->converter->convert('one', TestBackedStringEnum::class, 'status');
        $this->assertSame(TestBackedStringEnum::VALUE_ONE, $result);
        $this->assertInstanceOf(TestBackedStringEnum::class, $result);
    }

    public function test_convert_converts_existing_enum_instance_to_same_instance(): void
    {
        $original = TestBackedStringEnum::VALUE_TWO;
        $result = $this->converter->convert($original, TestBackedStringEnum::class, 'status');
        $this->assertSame($original, $result);
    }

    public function test_convert_throws_exception_for_invalid_string_value(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid value "invalid" for enum');

        $this->converter->convert('invalid', TestBackedStringEnum::class, 'status');
    }

    // ==================== CONVERT TO BACKED INT ENUM TESTS ====================

    public function test_convert_converts_int_to_backed_int_enum(): void
    {
        $result = $this->converter->convert(2, TestBackedIntEnum::class, 'level');
        $this->assertSame(TestBackedIntEnum::VALUE_TWO, $result);
        $this->assertInstanceOf(TestBackedIntEnum::class, $result);
    }

    public function test_convert_converts_numeric_string_to_backed_int_enum(): void
    {
        $result = $this->converter->convert('3', TestBackedIntEnum::class, 'level');
        $this->assertSame(TestBackedIntEnum::VALUE_THREE, $result);
    }

    public function test_convert_converts_existing_backed_int_enum_to_same_instance(): void
    {
        $original = TestBackedIntEnum::VALUE_ONE;
        $result = $this->converter->convert($original, TestBackedIntEnum::class, 'level');
        $this->assertSame($original, $result);
    }

    public function test_convert_throws_exception_for_invalid_int_value(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid value "99" for enum');

        $this->converter->convert(99, TestBackedIntEnum::class, 'level');
    }

    // ==================== CONVERT TO PURE ENUM TESTS ====================

    public function test_convert_converts_case_name_to_pure_enum(): void
    {
        $result = $this->converter->convert('VALUE_TWO', TestPureEnum::class, 'option');
        $this->assertSame(TestPureEnum::VALUE_TWO, $result);
        $this->assertInstanceOf(TestPureEnum::class, $result);
    }

    public function test_convert_converts_existing_pure_enum_to_same_instance(): void
    {
        $original = TestPureEnum::VALUE_THREE;
        $result = $this->converter->convert($original, TestPureEnum::class, 'option');
        $this->assertSame($original, $result);
    }

    public function test_convert_throws_exception_for_invalid_pure_enum_case(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid value "INVALID_CASE" for enum');

        $this->converter->convert('INVALID_CASE', TestPureEnum::class, 'option');
    }

    // ==================== CONVERT FROM ARRAY TESTS ====================

    public function test_convert_converts_array_with_value_key_to_backed_string_enum(): void
    {
        $result = $this->converter->convert(['value' => 'three'], TestBackedStringEnum::class, 'status');
        $this->assertSame(TestBackedStringEnum::VALUE_THREE, $result);
    }

    public function test_convert_converts_array_with_value_key_to_backed_int_enum(): void
    {
        $result = $this->converter->convert(['value' => 1], TestBackedIntEnum::class, 'level');
        $this->assertSame(TestBackedIntEnum::VALUE_ONE, $result);
    }

    public function test_convert_converts_array_with_name_key_to_pure_enum(): void
    {
        $result = $this->converter->convert(['name' => 'VALUE_TWO'], TestPureEnum::class, 'option');
        $this->assertSame(TestPureEnum::VALUE_TWO, $result);
    }

    public function test_convert_throws_exception_for_array_without_valid_keys(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->converter->convert(['invalid' => 'data'], TestBackedStringEnum::class, 'status');
    }

    // ==================== CONVERT FROM OBJECT TESTS ====================

    public function test_convert_converts_object_with_value_property_to_enum(): void
    {
        $object = new class
        {
            public string $value = 'two';
        };

        $result = $this->converter->convert($object, TestBackedStringEnum::class, 'status');
        $this->assertSame(TestBackedStringEnum::VALUE_TWO, $result);
    }

    public function test_convert_converts_object_with_name_property_to_pure_enum(): void
    {
        $object = new class
        {
            public string $name = 'VALUE_THREE';
        };

        $result = $this->converter->convert($object, TestPureEnum::class, 'option');
        $this->assertSame(TestPureEnum::VALUE_THREE, $result);
    }

    // ==================== EDGE CASES TESTS ====================

    public function test_convert_handles_all_enum_cases(): void
    {
        $expected = [
            'one' => TestBackedStringEnum::VALUE_ONE,
            'two' => TestBackedStringEnum::VALUE_TWO,
            'three' => TestBackedStringEnum::VALUE_THREE,
        ];

        foreach ($expected as $value => $expectedEnum) {
            $result = $this->converter->convert($value, TestBackedStringEnum::class, 'status');
            $this->assertSame($expectedEnum, $result);
        }
    }

    public function test_convert_is_idempotent_for_enum_instances(): void
    {
        $enum = TestBackedStringEnum::VALUE_ONE;

        $first = $this->converter->convert($enum, TestBackedStringEnum::class, 'status');
        $second = $this->converter->convert($first, TestBackedStringEnum::class, 'status');

        $this->assertSame($enum, $first);
        $this->assertSame($first, $second);
    }
}
