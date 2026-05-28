<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Unit\Normalizers;

use AndyDefer\DomainStructures\Normalizers\RootNormalizer;
use AndyDefer\DomainStructures\Normalizers\ScalarNormalizer;
use AndyDefer\DomainStructures\Tests\TestCase;
use AndyDefer\DomainStructures\Utils\DataObject;

final class ScalarNormalizerTest extends TestCase
{
    private ScalarNormalizer $normalizer;

    private RootNormalizer $rootNormalizer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rootNormalizer = new RootNormalizer;

        $this->normalizer = new ScalarNormalizer;
        $this->normalizer->setRecursiveNormalizer($this->rootNormalizer);
    }

    // ==================== SUPPORTS METHOD TESTS ====================

    public function test_supports_returns_true_for_integer(): void
    {
        $value = 42;
        $result = $this->normalizer->supports($value);

        $this->assertTrue($result);
    }

    public function test_supports_returns_true_for_negative_integer(): void
    {
        $value = -42;
        $result = $this->normalizer->supports($value);

        $this->assertTrue($result);
    }

    public function test_supports_returns_true_for_zero(): void
    {
        $value = 0;
        $result = $this->normalizer->supports($value);

        $this->assertTrue($result);
    }

    public function test_supports_returns_true_for_string(): void
    {
        $value = 'hello world';
        $result = $this->normalizer->supports($value);

        $this->assertTrue($result);
    }

    public function test_supports_returns_true_for_empty_string(): void
    {
        $value = '';
        $result = $this->normalizer->supports($value);

        $this->assertTrue($result);
    }

    public function test_supports_returns_true_for_float(): void
    {
        $value = 3.14;
        $result = $this->normalizer->supports($value);

        $this->assertTrue($result);
    }

    public function test_supports_returns_true_for_negative_float(): void
    {
        $value = -3.14;
        $result = $this->normalizer->supports($value);

        $this->assertTrue($result);
    }

    public function test_supports_returns_true_for_boolean_true(): void
    {
        $value = true;
        $result = $this->normalizer->supports($value);

        $this->assertTrue($result);
    }

    public function test_supports_returns_true_for_boolean_false(): void
    {
        $value = false;
        $result = $this->normalizer->supports($value);

        $this->assertTrue($result);
    }

    public function test_supports_returns_false_for_non_scalar_values(): void
    {
        $values = [
            null,
            [],
            new DataObject,
            fopen('php://memory', 'r'),
        ];

        foreach ($values as $value) {
            $result = $this->normalizer->supports($value);
            $this->assertFalse($result, 'Failed for value type: '.(is_object($value) ? $value::class : gettype($value)));
        }

        if (isset($values[3]) && is_resource($values[3])) {
            fclose($values[3]);
        }
    }

    // ==================== NORMALIZE METHOD TESTS ====================

    public function test_normalize_returns_same_integer(): void
    {
        $value = 42;
        $normalized = $this->normalizer->normalize($value);

        $this->assertSame(42, $normalized);
        $this->assertIsInt($normalized);
    }

    public function test_normalize_returns_same_string(): void
    {
        $value = 'hello world';
        $normalized = $this->normalizer->normalize($value);

        $this->assertSame('hello world', $normalized);
        $this->assertIsString($normalized);
    }

    public function test_normalize_returns_same_float(): void
    {
        $value = 3.14;
        $normalized = $this->normalizer->normalize($value);

        $this->assertSame(3.14, $normalized);
        $this->assertIsFloat($normalized);
    }

    public function test_normalize_returns_same_boolean(): void
    {
        $trueValue = true;
        $falseValue = false;

        $normalizedTrue = $this->normalizer->normalize($trueValue);
        $normalizedFalse = $this->normalizer->normalize($falseValue);

        $this->assertTrue($normalizedTrue);
        $this->assertFalse($normalizedFalse);
        $this->assertIsBool($normalizedTrue);
        $this->assertIsBool($normalizedFalse);
    }

    public function test_normalize_forwards_non_scalar_to_next_normalizer(): void
    {
        $value = null;
        $normalized = $this->normalizer->normalize($value);

        $this->assertNull($normalized);
    }

    public function test_normalize_forwards_array_to_next_normalizer(): void
    {
        $value = [1, 2, 3];
        $normalized = $this->normalizer->normalize($value);

        $this->assertIsArray($normalized);
        $this->assertSame([1, 2, 3], $normalized);
    }

    public function test_normalize_forwards_object_to_next_normalizer(): void
    {
        $value = new DataObject(['name' => 'test']);
        $normalized = $this->normalizer->normalize($value);

        $this->assertIsArray($normalized);
        $this->assertSame(['name' => 'test'], $normalized);
    }

    public function test_normalize_handles_large_integers_correctly(): void
    {
        $value = PHP_INT_MAX;
        $normalized = $this->normalizer->normalize($value);

        $this->assertSame(PHP_INT_MAX, $normalized);
    }

    public function test_normalize_handles_scientific_notation_floats_correctly(): void
    {
        $value = 1.23e-4;
        $normalized = $this->normalizer->normalize($value);

        $this->assertSame(1.23e-4, $normalized);
    }

    public function test_normalize_is_idempotent(): void
    {
        $value = 42;
        $first = $this->normalizer->normalize($value);
        $second = $this->normalizer->normalize($value);
        $third = $this->normalizer->normalize($value);

        $this->assertSame($first, $second);
        $this->assertSame($second, $third);
    }

    /**
     * Test that normalize forwards DataObject with complex structure.
     */
    public function test_normalize_forwards_complex_data_object(): void
    {
        $value = new DataObject([
            'user' => new DataObject([
                'name' => 'Alice',
                'profile' => new DataObject([
                    'age' => 28,
                    'city' => 'Paris',
                ]),
            ]),
            'active' => true,
            'score' => 95.5,
        ]);

        $normalized = $this->normalizer->normalize($value);

        $this->assertIsArray($normalized);
        $this->assertIsArray($normalized['user']);
        $this->assertSame('Alice', $normalized['user']['name']);
        $this->assertIsArray($normalized['user']['profile']);
        $this->assertSame(28, $normalized['user']['profile']['age']);
        $this->assertSame('Paris', $normalized['user']['profile']['city']);
        $this->assertTrue($normalized['active']);
        $this->assertSame(95.5, $normalized['score']);
    }
}
