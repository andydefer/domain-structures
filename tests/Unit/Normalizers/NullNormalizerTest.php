<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Unit\Normalizers;

use AndyDefer\DomainStructures\Normalizers\NullNormalizer;
use AndyDefer\DomainStructures\Normalizers\RootNormalizer;
use AndyDefer\DomainStructures\Tests\TestCase;
use AndyDefer\DomainStructures\Utils\DataObject;

final class NullNormalizerTest extends TestCase
{
    private NullNormalizer $normalizer;

    private RootNormalizer $rootNormalizer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rootNormalizer = new RootNormalizer;

        $this->normalizer = new NullNormalizer;
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
            new DataObject,
        ];

        foreach ($values as $value) {
            $result = $this->normalizer->supports($value);
            $this->assertFalse($result, 'Failed for value type: '.(is_object($value) ? $value::class : gettype($value)));
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
        $value = new DataObject(['name' => 'test', 'age' => 30]);
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
                'tags' => ['premium', 'vip'],
            ],
            'metadata' => new DataObject(['version' => '1.0']),
        ];

        $normalized = $this->normalizer->normalize($value);

        $this->assertIsArray($normalized);
        $this->assertIsArray($normalized['user']);
        $this->assertSame('John', $normalized['user']['name']);
        $this->assertSame(30, $normalized['user']['age']);
        $this->assertSame(['premium', 'vip'], $normalized['user']['tags']);
        $this->assertIsArray($normalized['metadata']);
        $this->assertSame('1.0', $normalized['metadata']['version']);
    }

    /**
     * Test that normalize forwards DataObject with nested structure.
     */
    public function test_normalize_forwards_data_object_with_nested_structure(): void
    {
        $value = new DataObject([
            'user' => new DataObject([
                'name' => 'Jane',
                'age' => 25,
                'tags' => ['standard', 'basic'],
            ]),
            'metadata' => new DataObject([
                'version' => '2.0',
                'environment' => 'test',
            ]),
        ]);

        $normalized = $this->normalizer->normalize($value);

        $this->assertIsArray($normalized);
        $this->assertIsArray($normalized['user']);
        $this->assertSame('Jane', $normalized['user']['name']);
        $this->assertSame(25, $normalized['user']['age']);
        $this->assertSame(['standard', 'basic'], $normalized['user']['tags']);
        $this->assertIsArray($normalized['metadata']);
        $this->assertSame('2.0', $normalized['metadata']['version']);
        $this->assertSame('test', $normalized['metadata']['environment']);
    }

    /**
     * Test that normalize forwards DataObject with null values.
     */
    public function test_normalize_forwards_data_object_with_null_values(): void
    {
        $value = new DataObject([
            'id' => null,
            'name' => 'Test',
            'optional' => null,
        ]);

        $normalized = $this->normalizer->normalize($value);

        $this->assertIsArray($normalized);
        $this->assertNull($normalized['id']);
        $this->assertSame('Test', $normalized['name']);
        $this->assertNull($normalized['optional']);
    }
}
