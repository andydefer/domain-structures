<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Unit\Hydration\Converter;

use AndyDefer\DomainStructures\Hydration\Converter\ScalarConverter;
use AndyDefer\DomainStructures\Tests\TestCase;
use InvalidArgumentException;

final class ScalarConverterTest extends TestCase
{
    private ScalarConverter $converter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->converter = new ScalarConverter;
    }

    // ==================== SUPPORTS METHOD TESTS ====================

    public function test_supports_returns_true_for_int_type(): void
    {
        $result = $this->converter->supports('int');
        $this->assertTrue($result);
    }

    public function test_supports_returns_true_for_integer_type(): void
    {
        $result = $this->converter->supports('integer');
        $this->assertTrue($result);
    }

    public function test_supports_returns_true_for_float_type(): void
    {
        $result = $this->converter->supports('float');
        $this->assertTrue($result);
    }

    public function test_supports_returns_true_for_double_type(): void
    {
        $result = $this->converter->supports('double');
        $this->assertTrue($result);
    }

    public function test_supports_returns_true_for_string_type(): void
    {
        $result = $this->converter->supports('string');
        $this->assertTrue($result);
    }

    public function test_supports_returns_true_for_bool_type(): void
    {
        $result = $this->converter->supports('bool');
        $this->assertTrue($result);
    }

    public function test_supports_returns_true_for_boolean_type(): void
    {
        $result = $this->converter->supports('boolean');
        $this->assertTrue($result);
    }

    public function test_supports_returns_false_for_non_scalar_types(): void
    {
        $nonScalarTypes = ['array', 'object', 'resource', 'NULL', 'callable'];

        foreach ($nonScalarTypes as $type) {
            $result = $this->converter->supports($type);
            $this->assertFalse($result, "Failed for type: {$type}");
        }
    }

    // ==================== CONVERT TO INT TESTS ====================

    public function test_convert_converts_numeric_string_to_int(): void
    {
        $result = $this->converter->convert('123', 'int', 'userId');
        $this->assertSame(123, $result);
        $this->assertIsInt($result);
    }

    public function test_convert_converts_float_to_int(): void
    {
        $result = $this->converter->convert(99.99, 'int', 'price');
        $this->assertSame(99, $result);
        $this->assertIsInt($result);
    }

    public function test_convert_converts_boolean_true_to_int(): void
    {
        $result = $this->converter->convert(true, 'int', 'flag');
        $this->assertSame(1, $result);
        $this->assertIsInt($result);
    }

    public function test_convert_converts_boolean_false_to_int(): void
    {
        $result = $this->converter->convert(false, 'int', 'flag');
        $this->assertSame(0, $result);
        $this->assertIsInt($result);
    }

    public function test_convert_converts_negative_numeric_string_to_int(): void
    {
        $result = $this->converter->convert('-456', 'int', 'balance');
        $this->assertSame(-456, $result);
    }

    public function test_convert_converts_zero_to_int(): void
    {
        $result = $this->converter->convert('0', 'int', 'count');
        $this->assertSame(0, $result);
    }

    public function test_convert_throws_exception_for_non_numeric_string_to_int(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot convert value to int for parameter $userId');

        $this->converter->convert('not a number', 'int', 'userId');
    }

    // ==================== CONVERT TO FLOAT TESTS ====================

    public function test_convert_converts_numeric_string_to_float(): void
    {
        $result = $this->converter->convert('123.45', 'float', 'price');
        $this->assertSame(123.45, $result);
        $this->assertIsFloat($result);
    }

    public function test_convert_converts_int_to_float(): void
    {
        $result = $this->converter->convert(123, 'float', 'price');
        $this->assertSame(123.0, $result);
        $this->assertIsFloat($result);
    }

    public function test_convert_converts_boolean_true_to_float(): void
    {
        $result = $this->converter->convert(true, 'float', 'flag');
        $this->assertSame(1.0, $result);
    }

    public function test_convert_converts_boolean_false_to_float(): void
    {
        $result = $this->converter->convert(false, 'float', 'flag');
        $this->assertSame(0.0, $result);
    }

    public function test_convert_converts_negative_numeric_string_to_float(): void
    {
        $result = $this->converter->convert('-99.99', 'float', 'balance');
        $this->assertSame(-99.99, $result);
    }

    public function test_convert_throws_exception_for_non_numeric_string_to_float(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot convert value to float for parameter $price');

        $this->converter->convert('abc', 'float', 'price');
    }

    // ==================== CONVERT TO STRING TESTS ====================

    public function test_convert_converts_int_to_string(): void
    {
        $result = $this->converter->convert(123, 'string', 'label');
        $this->assertSame('123', $result);
        $this->assertIsString($result);
    }

    public function test_convert_converts_float_to_string(): void
    {
        $result = $this->converter->convert(99.99, 'string', 'price');
        $this->assertSame('99.99', $result);
    }

    public function test_convert_converts_boolean_true_to_string(): void
    {
        $result = $this->converter->convert(true, 'string', 'flag');
        $this->assertSame('1', $result);
    }

    public function test_convert_converts_boolean_false_to_string(): void
    {
        $result = $this->converter->convert(false, 'string', 'flag');
        $this->assertSame('', $result);
    }

    public function test_convert_preserves_string_as_is(): void
    {
        $result = $this->converter->convert('hello world', 'string', 'message');
        $this->assertSame('hello world', $result);
    }

    public function test_convert_converts_object_with_to_string_to_string(): void
    {
        $object = new class
        {
            public function __toString(): string
            {
                return 'object string';
            }
        };

        $result = $this->converter->convert($object, 'string', 'value');
        $this->assertSame('object string', $result);
    }

    public function test_convert_throws_exception_for_null_to_string(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot convert null to string for parameter $label');

        $this->converter->convert(null, 'string', 'label');
    }

    public function test_convert_throws_exception_for_array_to_string(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot convert value to string for parameter $data');

        $this->converter->convert([1, 2, 3], 'string', 'data');
    }

    // ==================== CONVERT TO BOOL TESTS ====================

    public function test_convert_keeps_boolean_true(): void
    {
        $result = $this->converter->convert(true, 'bool', 'active');
        $this->assertTrue($result);
        $this->assertIsBool($result);
    }

    public function test_convert_keeps_boolean_false(): void
    {
        $result = $this->converter->convert(false, 'bool', 'active');
        $this->assertFalse($result);
    }

    public function test_convert_converts_numeric_one_to_true(): void
    {
        $result = $this->converter->convert(1, 'bool', 'flag');
        $this->assertTrue($result);
    }

    public function test_convert_converts_numeric_zero_to_false(): void
    {
        $result = $this->converter->convert(0, 'bool', 'flag');
        $this->assertFalse($result);
    }

    public function test_convert_converts_string_true_to_true(): void
    {
        $result = $this->converter->convert('true', 'bool', 'flag');
        $this->assertTrue($result);
    }

    public function test_convert_converts_string_false_to_false(): void
    {
        $result = $this->converter->convert('false', 'bool', 'flag');
        $this->assertFalse($result);
    }

    public function test_convert_converts_string_one_to_true(): void
    {
        $result = $this->converter->convert('1', 'bool', 'flag');
        $this->assertTrue($result);
    }

    public function test_convert_converts_string_zero_to_false(): void
    {
        $result = $this->converter->convert('0', 'bool', 'flag');
        $this->assertFalse($result);
    }

    public function test_convert_converts_string_on_to_true(): void
    {
        $result = $this->converter->convert('on', 'bool', 'flag');
        $this->assertTrue($result);
    }

    public function test_convert_converts_string_yes_to_true(): void
    {
        $result = $this->converter->convert('yes', 'bool', 'flag');
        $this->assertTrue($result);
    }

    public function test_convert_converts_any_other_string_to_false(): void
    {
        $result = $this->converter->convert('anything', 'bool', 'flag');
        $this->assertFalse($result);
    }

    public function test_convert_throws_exception_for_array_to_bool(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot convert value to bool for parameter $flag');

        $this->converter->convert([1, 2], 'bool', 'flag');
    }

    // ==================== EDGE CASES TESTS ====================

    public function test_convert_handles_scientific_notation_string_to_float(): void
    {
        $result = $this->converter->convert('1.23e-4', 'float', 'value');
        $this->assertSame(0.000123, $result);
    }

    public function test_convert_handles_large_integer_values(): void
    {
        $result = $this->converter->convert(PHP_INT_MAX, 'int', 'max');
        $this->assertSame(PHP_INT_MAX, $result);
    }

    public function test_convert_handles_negative_zero_string_to_float(): void
    {
        $result = $this->converter->convert('-0.00', 'float', 'value');
        $this->assertSame(0.0, $result);
    }
}
