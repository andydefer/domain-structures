<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Unit\Normalizers;

use AndyDefer\DomainStructures\Normalizers\DataObjectNormalizer;
use AndyDefer\DomainStructures\Normalizers\RootNormalizer;
use AndyDefer\DomainStructures\Tests\TestCase;
use AndyDefer\DomainStructures\Utils\DataObject;

final class DataObjectNormalizerTest extends TestCase
{
    private DataObjectNormalizer $normalizer;

    private RootNormalizer $rootNormalizer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rootNormalizer = new RootNormalizer;

        $this->normalizer = new DataObjectNormalizer;
        $this->normalizer->setRecursiveNormalizer($this->rootNormalizer);
    }

    // ==================== SUPPORTS METHOD TESTS ====================

    public function test_supports_returns_true_for_data_object_instance(): void
    {
        $object = new DataObject(['name' => 'John', 'age' => 30]);
        $result = $this->normalizer->supports($object);

        $this->assertTrue($result);
    }

    public function test_supports_returns_true_for_data_object_with_properties(): void
    {
        $object = new DataObject(['name' => 'John', 'age' => 30]);
        $result = $this->normalizer->supports($object);

        $this->assertTrue($result);
    }

    public function test_supports_returns_false_for_non_data_object_objects(): void
    {
        $values = [
            new \DateTime,
            new class {},
        ];

        foreach ($values as $value) {
            $result = $this->normalizer->supports($value);
            $this->assertFalse($result, 'Failed for class: '.$value::class);
        }
    }

    public function test_supports_returns_false_for_non_object_values(): void
    {
        $values = [42, 'string', 3.14, true, null, []];

        foreach ($values as $value) {
            $result = $this->normalizer->supports($value);
            $this->assertFalse($result, 'Failed for value type: '.gettype($value));
        }
    }

    // ==================== NORMALIZE METHOD TESTS ====================

    public function test_normalize_converts_data_object_to_array(): void
    {
        $object = new DataObject(['name' => 'John', 'age' => 30, 'city' => 'Paris']);
        $normalized = $this->normalizer->normalize($object);

        $this->assertIsArray($normalized);
        $this->assertSame('John', $normalized['name']);
        $this->assertSame(30, $normalized['age']);
        $this->assertSame('Paris', $normalized['city']);
        $this->assertCount(3, $normalized);
    }

    public function test_normalize_preserves_property_names_as_keys(): void
    {
        $object = new DataObject(['firstName' => 'John', 'lastName' => 'Doe', 'age' => 30]);
        $normalized = $this->normalizer->normalize($object);

        $this->assertArrayHasKey('firstName', $normalized);
        $this->assertArrayHasKey('lastName', $normalized);
        $this->assertArrayHasKey('age', $normalized);
        $this->assertSame('John', $normalized['firstName']);
        $this->assertSame('Doe', $normalized['lastName']);
    }

    public function test_normalize_handles_nested_data_objects(): void
    {
        $nested = new DataObject(['value' => 'nested value', 'number' => 42]);
        $object = new DataObject(['name' => 'John', 'nested' => $nested]);
        $normalized = $this->normalizer->normalize($object);

        $this->assertIsArray($normalized);
        $this->assertIsArray($normalized['nested']);
        $this->assertSame('nested value', $normalized['nested']['value']);
        $this->assertSame(42, $normalized['nested']['number']);
    }

    public function test_normalize_handles_empty_data_object(): void
    {
        $object = new DataObject;
        $normalized = $this->normalizer->normalize($object);

        $this->assertIsArray($normalized);
        $this->assertEmpty($normalized);
    }

    public function test_normalize_forwards_to_next_normalizer_when_not_data_object(): void
    {
        $value = 'not a DataObject';
        $normalized = $this->normalizer->normalize($value);

        $this->assertSame('not a DataObject', $normalized);
    }

    public function test_normalize_forwards_null_to_next_normalizer(): void
    {
        $value = null;
        $normalized = $this->normalizer->normalize($value);

        $this->assertNull($normalized);
    }

    public function test_normalize_handles_data_object_with_various_value_types(): void
    {
        $object = new DataObject([
            'int' => 42,
            'string' => 'hello',
            'float' => 3.14,
            'bool' => true,
            'null' => null,
            'array' => [1, 2, 3],
        ]);

        $normalized = $this->normalizer->normalize($object);

        $this->assertSame(42, $normalized['int']);
        $this->assertSame('hello', $normalized['string']);
        $this->assertSame(3.14, $normalized['float']);
        $this->assertTrue($normalized['bool']);
        $this->assertNull($normalized['null']);
        $this->assertSame([1, 2, 3], $normalized['array']);
    }

    public function test_normalize_is_idempotent(): void
    {
        $object = new DataObject(['name' => 'John', 'age' => 30]);

        $first = $this->normalizer->normalize($object);
        $second = $this->normalizer->normalize($object);
        $third = $this->normalizer->normalize($object);

        $this->assertSame($first, $second);
        $this->assertSame($second, $third);
    }

    public function test_normalize_handles_data_object_with_numeric_keys(): void
    {
        $object = new DataObject(['0' => 'zero', '1' => 'one', '2' => 'two']);
        $normalized = $this->normalizer->normalize($object);

        $this->assertSame('zero', $normalized['0']);
        $this->assertSame('one', $normalized['1']);
        $this->assertSame('two', $normalized['2']);
    }

    public function test_normalize_handles_deeply_nested_data_objects(): void
    {
        $level3 = new DataObject(['deep' => 'deep value']);
        $level2 = new DataObject(['level3' => $level3]);
        $level1 = new DataObject(['level2' => $level2]);

        $normalized = $this->normalizer->normalize($level1);

        $this->assertIsArray($normalized);
        $this->assertIsArray($normalized['level2']);
        $this->assertIsArray($normalized['level2']['level3']);
        $this->assertSame('deep value', $normalized['level2']['level3']['deep']);
    }

    public function test_normalize_forwards_array_to_next_normalizer(): void
    {
        $value = [1, 2, 3];
        $normalized = $this->normalizer->normalize($value);

        $this->assertIsArray($normalized);
        $this->assertSame([1, 2, 3], $normalized);
    }

    public function test_normalize_forwards_integer_to_next_normalizer(): void
    {
        $value = 42;
        $normalized = $this->normalizer->normalize($value);

        $this->assertSame(42, $normalized);
    }
}
