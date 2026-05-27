<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Unit\Normalizers;

use AndyDefer\DomainStructures\Normalizers\NullNormalizer;
use AndyDefer\DomainStructures\Normalizers\RootNormalizer;
use AndyDefer\DomainStructures\Tests\TestCase;

final class NullNormalizerTest extends TestCase
{
    private NullNormalizer $normalizer;
    private RootNormalizer $rootNormalizer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rootNormalizer = new RootNormalizer();

        $this->normalizer = new NullNormalizer();
        $this->normalizer->setRecursiveNormalizer($this->rootNormalizer);
    }

    // ==================== SUPPORTS METHOD TESTS ====================

    public function test_supports_returns_true_for_null(): void
    {
        $value = null;
        $result = $this->normalizer->supports($value);

        $this->assertTrue($result);
    }

    public function test_supports_returns_false_for_non_null_values(): void
    {
        $values = [
            42,
            'string',
            3.14,
            true,
            false,
            [],
            new \stdClass,
        ];

        foreach ($values as $value) {
            $result = $this->normalizer->supports($value);
            $this->assertFalse($result, 'Failed for value type: ' . gettype($value));
        }
    }

    // ==================== NORMALIZE METHOD TESTS ====================

    public function test_normalize_returns_null_for_null_input(): void
    {
        $value = null;
        $normalized = $this->normalizer->normalize($value);

        $this->assertNull($normalized);
    }

    public function test_normalize_forwards_non_null_to_next_normalizer(): void
    {
        $value = 42;
        $normalized = $this->normalizer->normalize($value);

        $this->assertSame(42, $normalized);
    }

    public function test_normalize_forwards_string_to_next_normalizer(): void
    {
        $value = 'test string';
        $normalized = $this->normalizer->normalize($value);

        $this->assertSame('test string', $normalized);
    }

    public function test_normalize_forwards_float_to_next_normalizer(): void
    {
        $value = 3.14;
        $normalized = $this->normalizer->normalize($value);

        $this->assertSame(3.14, $normalized);
    }

    public function test_normalize_forwards_boolean_to_next_normalizer(): void
    {
        $value = true;
        $normalized = $this->normalizer->normalize($value);

        $this->assertTrue($normalized);
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
        $value = new \stdClass();
        $value->name = 'test';
        $value->age = 30;
        $normalized = $this->normalizer->normalize($value);

        $this->assertIsArray($normalized);
        $this->assertSame(['name' => 'test', 'age' => 30], $normalized);
    }

    // ==================== EDGE CASES TESTS ====================

    public function test_normalize_is_idempotent_for_null(): void
    {
        $value = null;
        $first = $this->normalizer->normalize($value);
        $second = $this->normalizer->normalize($value);
        $third = $this->normalizer->normalize($value);

        $this->assertSame($first, $second);
        $this->assertSame($second, $third);
    }

    public function test_normalize_handles_multiple_null_values_correctly(): void
    {
        $values = [null, null, null];

        foreach ($values as $value) {
            $normalized = $this->normalizer->normalize($value);
            $this->assertNull($normalized);
        }
    }

    public function test_normalize_forwards_complex_nested_structure(): void
    {
        $value = [
            'user' => [
                'name' => 'John',
                'age' => 30,
                'tags' => ['premium', 'vip']
            ],
            'metadata' => new \stdClass(),
        ];
        $value['metadata']->version = '1.0';

        $normalized = $this->normalizer->normalize($value);

        $this->assertIsArray($normalized);
        $this->assertIsArray($normalized['user']);
        $this->assertSame('John', $normalized['user']['name']);
        $this->assertSame(30, $normalized['user']['age']);
        $this->assertSame(['premium', 'vip'], $normalized['user']['tags']);
        $this->assertIsArray($normalized['metadata']);
        $this->assertSame('1.0', $normalized['metadata']['version']);
    }
}
