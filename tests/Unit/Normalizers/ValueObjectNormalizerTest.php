<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Unit\Normalizers;

use AndyDefer\DomainStructures\Normalizers\RootNormalizer;
use AndyDefer\DomainStructures\Normalizers\ValueObjectNormalizer;
use AndyDefer\DomainStructures\Tests\Fixtures\Records\TestUserRecord;
use AndyDefer\DomainStructures\Tests\Fixtures\ValueObjects\TestEmailAddress;
use AndyDefer\DomainStructures\Tests\Fixtures\ValueObjects\TestIso8601DateTime;
use AndyDefer\DomainStructures\Tests\Fixtures\ValueObjects\TestMoney;
use AndyDefer\DomainStructures\Tests\Fixtures\ValueObjects\TestPostalCode;
use AndyDefer\DomainStructures\Tests\TestCase;
use AndyDefer\DomainStructures\Utils\DataObject;

final class ValueObjectNormalizerTest extends TestCase
{
    private ValueObjectNormalizer $normalizer;

    private RootNormalizer $rootNormalizer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rootNormalizer = new RootNormalizer;

        $this->normalizer = new ValueObjectNormalizer;
        $this->normalizer->setRecursiveNormalizer($this->rootNormalizer);
    }

    // ==================== SUPPORTS METHOD TESTS ====================

    public function test_supports_returns_true_for_email_value_object(): void
    {
        $email = TestEmailAddress::from('test@example.com');
        $result = $this->normalizer->supports($email);

        $this->assertTrue($result);
    }

    public function test_supports_returns_true_for_postal_code_value_object(): void
    {
        $postalCode = TestPostalCode::from('75001');
        $result = $this->normalizer->supports($postalCode);

        $this->assertTrue($result);
    }

    public function test_supports_returns_true_for_datetime_value_object(): void
    {
        $datetime = TestIso8601DateTime::from('2024-01-01T12:00:00+00:00');
        $result = $this->normalizer->supports($datetime);

        $this->assertTrue($result);
    }

    public function test_supports_returns_true_for_money_value_object(): void
    {
        $money = TestMoney::from(['amount' => 99.99, 'currency' => 'EUR']);
        $result = $this->normalizer->supports($money);

        $this->assertTrue($result);
    }

    public function test_supports_returns_false_for_non_value_object_values(): void
    {
        $values = [
            42,
            'string',
            3.14,
            true,
            null,
            new DataObject,
            new TestUserRecord(name: 'Test', email: TestEmailAddress::from('test@example.com')),
            [],
        ];

        foreach ($values as $value) {
            $result = $this->normalizer->supports($value);
            $this->assertFalse($result, 'Failed for value type: '.(is_object($value) ? $value::class : gettype($value)));
        }
    }

    // ==================== NORMALIZE METHOD TESTS ====================

    public function test_normalize_returns_scalar_value_from_email_vo(): void
    {
        $email = TestEmailAddress::from('test@example.com');
        $normalized = $this->normalizer->normalize($email);

        $this->assertSame('test@example.com', $normalized);
        $this->assertIsString($normalized);
    }

    public function test_normalize_returns_scalar_value_from_postal_code_vo(): void
    {
        $postalCode = TestPostalCode::from('75001');
        $normalized = $this->normalizer->normalize($postalCode);

        $this->assertSame('75001', $normalized);
        $this->assertIsString($normalized);
    }

    public function test_normalize_returns_string_from_datetime_vo(): void
    {
        $datetime = TestIso8601DateTime::from('2024-01-01T12:00:00+00:00');
        $normalized = $this->normalizer->normalize($datetime);

        $this->assertSame('2024-01-01T12:00:00+00:00', $normalized);
        $this->assertIsString($normalized);
    }

    public function test_normalize_returns_record_from_money_vo(): void
    {
        $money = TestMoney::from(['amount' => 99.99, 'currency' => 'EUR']);
        $normalized = $this->normalizer->normalize($money);

        $this->assertIsArray($normalized);
        $this->assertArrayHasKey('amount', $normalized);
        $this->assertArrayHasKey('currency', $normalized);
        $this->assertSame(99.99, $normalized['amount']);
        $this->assertSame('EUR', $normalized['currency']);
    }

    public function test_normalize_handles_null_values_from_vo_properly(): void
    {
        $value = null;
        $normalized = $this->normalizer->normalize($value);
        $this->assertNull($normalized);
    }

    public function test_normalize_forwards_to_next_normalizer_when_not_vo(): void
    {
        $value = 'not a value object';
        $normalized = $this->normalizer->normalize($value);
        $this->assertSame('not a value object', $normalized);
    }

    public function test_normalize_forwards_integer_to_next_normalizer(): void
    {
        $value = 42;
        $normalized = $this->normalizer->normalize($value);
        $this->assertSame(42, $normalized);
    }

    public function test_normalize_forwards_null_to_next_normalizer(): void
    {
        $value = null;
        $normalized = $this->normalizer->normalize($value);
        $this->assertNull($normalized);
    }

    public function test_normalize_handles_vo_that_returns_another_vo(): void
    {
        $money = TestMoney::from(['amount' => 99.99, 'currency' => 'EUR']);
        $normalized = $this->normalizer->normalize($money);

        $this->assertIsArray($normalized);
        $this->assertSame(99.99, $normalized['amount']);
    }

    public function test_normalize_is_idempotent_for_simple_vo(): void
    {
        $email = TestEmailAddress::from('test@example.com');
        $first = $this->normalizer->normalize($email);
        $second = $this->normalizer->normalize($email);
        $third = $this->normalizer->normalize($email);

        $this->assertSame($first, $second);
        $this->assertSame($second, $third);
    }

    public function test_normalize_is_idempotent_for_complex_vo(): void
    {
        $money = TestMoney::from(['amount' => 99.99, 'currency' => 'EUR']);
        $first = $this->normalizer->normalize($money);
        $second = $this->normalizer->normalize($money);
        $third = $this->normalizer->normalize($money);

        $this->assertSame($first, $second);
        $this->assertSame($second, $third);
    }

    public function test_normalize_preserves_data_integrity(): void
    {
        $originalEmail = 'preserve@example.com';
        $email = TestEmailAddress::from($originalEmail);
        $originalPostalCode = '75001';
        $postalCode = TestPostalCode::from($originalPostalCode);

        $normalizedEmail = $this->normalizer->normalize($email);
        $normalizedPostalCode = $this->normalizer->normalize($postalCode);

        $this->assertSame($originalEmail, $normalizedEmail);
        $this->assertSame($originalPostalCode, $normalizedPostalCode);
    }

    public function test_normalize_handles_vo_with_special_characters(): void
    {
        $email = TestEmailAddress::from('user+tag@example.com');
        $normalized = $this->normalizer->normalize($email);

        $this->assertSame('user+tag@example.com', $normalized);
    }

    public function test_normalize_handles_vo_with_unicode_characters(): void
    {
        $email = TestEmailAddress::from('unicode@example.com');
        $normalized = $this->normalizer->normalize($email);

        $this->assertSame('unicode@example.com', $normalized);
    }
}
