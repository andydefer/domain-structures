<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Unit\Hydration\Converter;

use AndyDefer\DomainStructures\Hydration\Converter\ClassConverter;
use AndyDefer\DomainStructures\Tests\TestCase;
use AndyDefer\DomainStructures\Tests\Unit\Fixtures\Hydration\SimpleClassWithArrayParam;
use AndyDefer\DomainStructures\Tests\Unit\Fixtures\Hydration\SimpleClassWithBoolParam;
use AndyDefer\DomainStructures\Tests\Unit\Fixtures\Hydration\SimpleClassWithFloatParam;
use AndyDefer\DomainStructures\Tests\Unit\Fixtures\Hydration\SimpleClassWithIntParam;
use AndyDefer\DomainStructures\Tests\Unit\Fixtures\Hydration\SimpleClassWithNullableParam;
use AndyDefer\DomainStructures\Tests\Unit\Fixtures\Hydration\SimpleClassWithObjectParam;
use AndyDefer\DomainStructures\Tests\Unit\Fixtures\Hydration\SimpleClassWithStringParam;

final class ClassConverterTest extends TestCase
{
    private ClassConverter $converter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->converter = new ClassConverter;
    }

    // ==================== SUPPORTS METHOD TESTS ====================

    public function test_supports_returns_true_for_existing_class(): void
    {
        $result = $this->converter->supports(self::class);
        $this->assertTrue($result);
    }

    public function test_supports_returns_true_for_std_class(): void
    {
        $result = $this->converter->supports(\stdClass::class);
        $this->assertTrue($result);
    }

    public function test_supports_returns_false_for_non_existent_class(): void
    {
        $result = $this->converter->supports('NonExistentClass123');
        $this->assertFalse($result);
    }

    public function test_supports_returns_false_for_primitive_types(): void
    {
        $primitiveTypes = ['int', 'string', 'float', 'bool', 'array', 'callable'];

        foreach ($primitiveTypes as $type) {
            $result = $this->converter->supports($type);
            $this->assertFalse($result, "Failed for type: {$type}");
        }
    }

    // ==================== CONVERT TESTS ====================

    public function test_convert_creates_instance_with_single_parameter(): void
    {
        $result = $this->converter->convert('test value', SimpleClassWithStringParam::class, 'param');

        $this->assertInstanceOf(SimpleClassWithStringParam::class, $result);
        $this->assertSame('test value', $result->value);
    }

    public function test_convert_creates_std_class_instance(): void
    {
        $result = $this->converter->convert('any value', \stdClass::class, 'param');

        $this->assertInstanceOf(\stdClass::class, $result);
    }

    public function test_convert_creates_instance_with_int_parameter(): void
    {
        $result = $this->converter->convert(42, SimpleClassWithIntParam::class, 'param');

        $this->assertInstanceOf(SimpleClassWithIntParam::class, $result);
        $this->assertSame(42, $result->value);
    }

    public function test_convert_creates_instance_with_float_parameter(): void
    {
        $result = $this->converter->convert(3.14, SimpleClassWithFloatParam::class, 'param');

        $this->assertInstanceOf(SimpleClassWithFloatParam::class, $result);
        $this->assertSame(3.14, $result->value);
    }

    public function test_convert_creates_instance_with_bool_parameter(): void
    {
        $result = $this->converter->convert(true, SimpleClassWithBoolParam::class, 'param');

        $this->assertInstanceOf(SimpleClassWithBoolParam::class, $result);
        $this->assertTrue($result->value);
    }

    public function test_convert_creates_instance_with_null_parameter_when_allowed(): void
    {
        $result = $this->converter->convert(null, SimpleClassWithNullableParam::class, 'param');

        $this->assertInstanceOf(SimpleClassWithNullableParam::class, $result);
        $this->assertNull($result->value);
    }

    // ==================== EDGE CASES TESTS ====================

    public function test_convert_handles_array_parameter(): void
    {
        $array = ['key' => 'value', 1, 2, 3];
        $result = $this->converter->convert($array, SimpleClassWithArrayParam::class, 'param');

        $this->assertInstanceOf(SimpleClassWithArrayParam::class, $result);
        $this->assertSame($array, $result->value);
    }

    public function test_convert_handles_object_parameter(): void
    {
        $object = new \stdClass;
        $object->property = 'value';

        $result = $this->converter->convert($object, SimpleClassWithObjectParam::class, 'param');

        $this->assertInstanceOf(SimpleClassWithObjectParam::class, $result);
        $this->assertSame($object, $result->value);
    }
}
