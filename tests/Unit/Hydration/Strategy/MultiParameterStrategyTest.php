<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Unit\Hydration\Strategy;

use AndyDefer\DomainStructures\Hydration\Converter\ClassConverter;
use AndyDefer\DomainStructures\Hydration\Converter\EnumConverter;
use AndyDefer\DomainStructures\Hydration\Converter\ScalarConverter;
use AndyDefer\DomainStructures\Hydration\Converter\TransformableConverter;
use AndyDefer\DomainStructures\Hydration\Strategy\MultiParameterStrategy;
use AndyDefer\DomainStructures\Tests\Fixtures\Records\TestUserRecord;
use AndyDefer\DomainStructures\Tests\Fixtures\ValueObjects\TestEmailAddress;
use AndyDefer\DomainStructures\Tests\TestCase;
use AndyDefer\DomainStructures\Tests\Unit\Fixtures\Hydration\ClassWithAllNullableParameters;
use AndyDefer\DomainStructures\Tests\Unit\Fixtures\Hydration\ClassWithBoolParameter;
use AndyDefer\DomainStructures\Tests\Unit\Fixtures\Hydration\ClassWithCamelCaseProperties;
use AndyDefer\DomainStructures\Tests\Unit\Fixtures\Hydration\ClassWithDefaultValues;
use AndyDefer\DomainStructures\Tests\Unit\Fixtures\Hydration\ClassWithFloatParameter;
use AndyDefer\DomainStructures\Tests\Unit\Fixtures\Hydration\ClassWithIntParameter;
use AndyDefer\DomainStructures\Tests\Unit\Fixtures\Hydration\ClassWithNullableParameters;
use AndyDefer\DomainStructures\Tests\Unit\Fixtures\Hydration\ClassWithRequiredParameters;
use AndyDefer\DomainStructures\Tests\Unit\Fixtures\Hydration\NoConstructorClass;
use AndyDefer\DomainStructures\Utils\DataObject;
use InvalidArgumentException;

final class MultiParameterStrategyTest extends TestCase
{
    private MultiParameterStrategy $strategy;

    protected function setUp(): void
    {
        parent::setUp();

        $converters = [
            new ScalarConverter,
            new EnumConverter,
            new TransformableConverter,
            new ClassConverter,
        ];

        $this->strategy = new MultiParameterStrategy($converters);
    }

    // ==================== SUPPORTS METHOD TESTS ====================

    public function test_supports_returns_true_for_class_with_multiple_parameters(): void
    {
        $result = $this->strategy->supports(TestUserRecord::class, ['name' => 'John']);
        $this->assertTrue($result);
    }

    public function test_supports_returns_false_for_class_with_single_parameter(): void
    {
        $result = $this->strategy->supports(TestEmailAddress::class, 'test@example.com');
        $this->assertFalse($result);
    }

    public function test_supports_returns_false_for_class_with_no_constructor(): void
    {
        // ZeroParameterStrategy gère les classes sans constructeur
        $result = $this->strategy->supports(NoConstructorClass::class, []);
        $this->assertFalse($result);
    }

    // ==================== HYDRATE FROM ARRAY ====================

    public function test_hydrate_creates_record_from_associative_array(): void
    {
        $data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ];

        $result = $this->strategy->hydrate(TestUserRecord::class, $data);

        $this->assertInstanceOf(TestUserRecord::class, $result);
        $this->assertSame('John Doe', $result->name);
        $this->assertSame('john@example.com', $result->email->getValue());
    }

    public function test_hydrate_creates_record_with_enum_from_string(): void
    {
        $data = [
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'status' => 'active',
            'role' => 'admin',
        ];

        $result = $this->strategy->hydrate(TestUserRecord::class, $data);

        $this->assertInstanceOf(TestUserRecord::class, $result);
        $this->assertSame('admin', $result->role->value);
    }

    // ==================== HYDRATE FROM JSON STRING ====================

    public function test_hydrate_creates_record_from_json_string(): void
    {
        $json = '{"name":"JSON User","email":"json@example.com"}';

        $result = $this->strategy->hydrate(TestUserRecord::class, $json);

        $this->assertSame('JSON User', $result->name);
        $this->assertSame('json@example.com', $result->email->getValue());
    }

    // ==================== HYDRATE FROM DATA OBJECT ====================

    public function test_hydrate_creates_record_from_data_object(): void
    {
        $dataObject = new DataObject([
            'name' => 'DataObject User',
            'email' => 'dataobject@example.com',
        ]);

        $result = $this->strategy->hydrate(TestUserRecord::class, $dataObject);

        $this->assertSame('DataObject User', $result->name);
        $this->assertSame('dataobject@example.com', $result->email->getValue());
    }

    // ==================== DEFAULT VALUES HANDLING ====================

    public function test_hydrate_uses_default_value_when_parameter_missing(): void
    {
        $data = ['name' => 'User With Defaults'];

        $result = $this->strategy->hydrate(ClassWithDefaultValues::class, $data);

        $this->assertSame('User With Defaults', $result->name);
        $this->assertSame('default@example.com', $result->email);
        $this->assertSame('active', $result->status);
    }

    public function test_hydrate_sets_null_when_parameter_allows_null_and_missing(): void
    {
        $data = ['name' => 'User With Nullable'];

        $result = $this->strategy->hydrate(ClassWithNullableParameters::class, $data);

        $this->assertSame('User With Nullable', $result->name);
        $this->assertNull($result->email);
        $this->assertNull($result->status);
    }

    // ==================== VALIDATION TESTS ====================

    public function test_hydrate_throws_exception_for_missing_required_parameter(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf(
            'Missing required parameter "$name" for %s',
            ClassWithRequiredParameters::class
        ));

        $this->strategy->hydrate(ClassWithRequiredParameters::class, []);
    }

    public function test_hydrate_throws_exception_for_null_value_when_not_allowed(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf(
            'Parameter "$name" for %s cannot be null',
            ClassWithRequiredParameters::class
        ));

        $this->strategy->hydrate(ClassWithRequiredParameters::class, ['name' => null]);
    }

    // ==================== TYPE CONVERSION TESTS ====================

    public function test_hydrate_converts_string_to_int_when_expected(): void
    {
        $data = ['count' => '42'];

        $result = $this->strategy->hydrate(ClassWithIntParameter::class, $data);

        $this->assertSame(42, $result->count);
        $this->assertIsInt($result->count);
    }

    public function test_hydrate_converts_string_to_float_when_expected(): void
    {
        $data = ['price' => '99.99'];

        $result = $this->strategy->hydrate(ClassWithFloatParameter::class, $data);

        $this->assertSame(99.99, $result->price);
        $this->assertIsFloat($result->price);
    }

    public function test_hydrate_converts_string_to_bool_when_expected(): void
    {
        $data = ['active' => 'true'];

        $result = $this->strategy->hydrate(ClassWithBoolParameter::class, $data);

        $this->assertTrue($result->active);
        $this->assertIsBool($result->active);
    }

    // ==================== CASE INSENSITIVITY TESTS ====================

    public function test_hydrate_handles_snake_case_keys(): void
    {
        $data = [
            'full_name' => 'Snake Case User',
            'email_address' => 'snake@example.com',
        ];

        $result = $this->strategy->hydrate(ClassWithCamelCaseProperties::class, $data);

        $this->assertSame('Snake Case User', $result->fullName);
        $this->assertSame('snake@example.com', $result->emailAddress);
    }

    // ==================== EDGE CASES TESTS ====================

    public function test_hydrate_handles_empty_array(): void
    {
        $result = $this->strategy->hydrate(ClassWithAllNullableParameters::class, []);

        $this->assertInstanceOf(ClassWithAllNullableParameters::class, $result);
        $this->assertNull($result->value1);
        $this->assertNull($result->value2);
    }

    public function test_hydrate_is_idempotent(): void
    {
        $data = [
            'name' => 'Idempotent User',
            'email' => 'idempotent@example.com',
        ];

        $first = $this->strategy->hydrate(TestUserRecord::class, $data);
        $second = $this->strategy->hydrate(TestUserRecord::class, $first);

        $this->assertEquals($first, $second);
    }
}
