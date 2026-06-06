<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Unit\Hydration\Strategy;

use AndyDefer\DomainStructures\Hydration\Converter\ClassConverter;
use AndyDefer\DomainStructures\Hydration\Converter\EnumConverter;
use AndyDefer\DomainStructures\Hydration\Converter\ScalarConverter;
use AndyDefer\DomainStructures\Hydration\Converter\TransformableConverter;
use AndyDefer\DomainStructures\Hydration\Strategy\SingleParameterStrategy;
use AndyDefer\DomainStructures\Tests\Fixtures\Records\TestUserRecord;
use AndyDefer\DomainStructures\Tests\Fixtures\ValueObjects\TestEmailAddress;
use AndyDefer\DomainStructures\Tests\Fixtures\ValueObjects\TestPostalCode;
use AndyDefer\DomainStructures\Tests\TestCase;
use AndyDefer\DomainStructures\Tests\Unit\Fixtures\Hydration\NoConstructorClass;
use AndyDefer\DomainStructures\Tests\Unit\Fixtures\Hydration\NullableParameterClass;
use AndyDefer\DomainStructures\Tests\Unit\Fixtures\Hydration\StringParameterClass;
use InvalidArgumentException;

final class SingleParameterStrategyTest extends TestCase
{
    private SingleParameterStrategy $strategy;

    protected function setUp(): void
    {
        parent::setUp();

        $converters = [
            new ScalarConverter,
            new EnumConverter,
            new TransformableConverter,
            new ClassConverter,
        ];

        $this->strategy = new SingleParameterStrategy($converters);
    }

    // ==================== SUPPORTS METHOD TESTS ====================

    public function test_supports_returns_true_for_class_with_single_parameter_constructor(): void
    {
        $result = $this->strategy->supports(TestEmailAddress::class, 'test@example.com');
        $this->assertTrue($result);
    }

    public function test_supports_returns_true_for_postal_code_with_single_parameter(): void
    {
        $result = $this->strategy->supports(TestPostalCode::class, '75001');
        $this->assertTrue($result);
    }

    public function test_supports_returns_false_for_class_without_constructor(): void
    {
        $result = $this->strategy->supports(NoConstructorClass::class, null);
        $this->assertFalse($result);
    }

    public function test_supports_returns_false_for_class_with_multi_parameter_constructor(): void
    {
        $result = $this->strategy->supports(TestUserRecord::class, ['name' => 'John']);
        $this->assertFalse($result);
    }

    // ==================== HYDRATE WITH SCALAR ====================

    public function test_hydrate_creates_email_vo_from_string(): void
    {
        $result = $this->strategy->hydrate(TestEmailAddress::class, 'test@example.com');

        $this->assertInstanceOf(TestEmailAddress::class, $result);
        $this->assertSame('test@example.com', $result->getValue());
    }

    public function test_hydrate_creates_postal_code_vo_from_string(): void
    {
        $result = $this->strategy->hydrate(TestPostalCode::class, '75001');

        $this->assertInstanceOf(TestPostalCode::class, $result);
        $this->assertSame('75001', $result->getValue());
    }

    // ==================== HYDRATE WITH ASSOCIATIVE ARRAY (SINGLE KEY) ====================
    // Support pour ['value' => '...'] ou ['email' => '...'] pour compatibilité ascendante

    public function test_hydrate_extracts_string_from_associative_array_with_value_key(): void
    {
        // ['value' => '...'] -> '...' -> constructeur(string)
        $result = $this->strategy->hydrate(TestEmailAddress::class, ['value' => 'array@example.com']);

        $this->assertSame('array@example.com', $result->getValue());
    }

    public function test_hydrate_extracts_string_from_associative_array_with_any_key(): void
    {
        // N'importe quelle clé associative unique est acceptée pour compatibilité
        $result = $this->strategy->hydrate(TestEmailAddress::class, ['email' => 'email@example.com']);

        $this->assertSame('email@example.com', $result->getValue());
    }

    // ==================== INVALID USAGE ====================

    public function test_hydrate_throws_exception_for_indexed_array_when_string_expected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        // Un tableau indexé n'est pas supporté
        $this->strategy->hydrate(TestEmailAddress::class, ['test@example.com']);
    }

    public function test_hydrate_throws_exception_for_array_with_multiple_keys(): void
    {
        $this->expectException(InvalidArgumentException::class);

        // Un tableau avec plusieurs clés ne peut pas être réduit
        $this->strategy->hydrate(TestEmailAddress::class, [
            'email' => 'complex@example.com',
            'extra' => 'ignored',
        ]);
    }

    public function test_hydrate_throws_exception_for_invalid_email_string(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid email: invalid-email');

        $this->strategy->hydrate(TestEmailAddress::class, 'invalid-email');
    }

    public function test_hydrate_throws_exception_for_invalid_postal_code(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid postal code: 1234');

        $this->strategy->hydrate(TestPostalCode::class, '1234');
    }

    // ==================== NULL HANDLING ====================

    public function test_hydrate_handles_null_source_when_parameter_allows_null(): void
    {
        $result = $this->strategy->hydrate(NullableParameterClass::class, null);

        $this->assertInstanceOf(NullableParameterClass::class, $result);
        $this->assertNull($result->value);
    }

    public function test_hydrate_throws_exception_for_null_source_when_parameter_does_not_allow_null(): void
    {
        $this->expectException(InvalidArgumentException::class);

        // TestEmailAddress n'accepte pas null
        $this->strategy->hydrate(TestEmailAddress::class, null);
    }

    // ==================== FLOAT NORMALIZATION TESTS ====================

    public function test_hydrate_normalizes_float_with_more_than_2_decimals_to_string(): void
    {
        $result = $this->strategy->hydrate(StringParameterClass::class, 99.999);

        $this->assertInstanceOf(StringParameterClass::class, $result);
        $this->assertSame('100.00', $result->value);
    }

    public function test_hydrate_preserves_float_with_2_decimals(): void
    {
        $result = $this->strategy->hydrate(StringParameterClass::class, 99.99);

        $this->assertSame('99.99', $result->value);
    }
}
