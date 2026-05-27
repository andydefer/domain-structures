<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Unit\Normalizers;

use AndyDefer\DomainStructures\Collections\Core\TypedCollection;
use AndyDefer\DomainStructures\Normalizers\ArrayNormalizer;
use AndyDefer\DomainStructures\Normalizers\RootNormalizer;
use AndyDefer\DomainStructures\Tests\Fixtures\Records\TestUserRecord;
use AndyDefer\DomainStructures\Tests\Fixtures\ValueObjects\TestEmailAddress;
use AndyDefer\DomainStructures\Tests\TestCase;

final class ArrayNormalizerTest extends TestCase
{
    private ArrayNormalizer $normalizer;
    private RootNormalizer $rootNormalizer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rootNormalizer = new RootNormalizer();

        $this->normalizer = new ArrayNormalizer();
        $this->normalizer->setRecursiveNormalizer($this->rootNormalizer);
    }

    // ==================== SUPPORTS METHOD TESTS ====================

    public function test_supports_returns_true_for_array(): void
    {
        $value = ['a', 'b', 'c'];
        $result = $this->normalizer->supports($value);

        $this->assertTrue($result);
    }

    public function test_supports_returns_false_for_non_array(): void
    {
        $values = [42, 'string', 3.14, true, null, new \stdClass];

        foreach ($values as $value) {
            $result = $this->normalizer->supports($value);
            $this->assertFalse($result, 'Failed for value: ' . gettype($value));
        }
    }

    // ==================== NORMALIZE METHOD TESTS ====================

    public function test_normalize_returns_same_array_for_simple_values(): void
    {
        $array = [1, 2, 3, 4, 5];
        $normalized = $this->normalizer->normalize($array);

        $this->assertSame([1, 2, 3, 4, 5], $normalized);
    }

    public function test_normalize_processes_nested_arrays(): void
    {
        $array = [
            'name' => 'John',
            'tags' => ['php', 'laravel', 'testing'],
            'metadata' => ['version' => '1.0', 'author' => 'Test'],
        ];

        $normalized = $this->normalizer->normalize($array);

        $this->assertIsArray($normalized);
        $this->assertSame('John', $normalized['name']);
        $this->assertSame(['php', 'laravel', 'testing'], $normalized['tags']);
        $this->assertSame(['version' => '1.0', 'author' => 'Test'], $normalized['metadata']);
    }

    public function test_normalize_processes_nested_objects_inside_arrays(): void
    {
        $email = TestEmailAddress::from('test@example.com');
        $array = [
            'user' => new TestUserRecord(name: 'John', email: $email),
            'tags' => ['a', 'b', 'c'],
        ];

        $normalized = $this->normalizer->normalize($array);

        $this->assertIsArray($normalized);
        $this->assertIsArray($normalized['user']);
        $this->assertSame('John', $normalized['user']['name']);
        $this->assertSame('test@example.com', $normalized['user']['email']);
        $this->assertSame(['a', 'b', 'c'], $normalized['tags']);
    }

    public function test_normalize_processes_nested_collections_inside_arrays(): void
    {
        $collection = new TypedCollection('int');
        $collection->add(1, 2, 3);

        $array = [
            'numbers' => $collection,
            'name' => 'test',
        ];

        $normalized = $this->normalizer->normalize($array);

        $this->assertIsArray($normalized);
        $this->assertIsArray($normalized['numbers']);
        $this->assertSame([1, 2, 3], $normalized['numbers']);
        $this->assertSame('test', $normalized['name']);
    }

    public function test_normalize_handles_empty_array(): void
    {
        $array = [];
        $normalized = $this->normalizer->normalize($array);

        $this->assertIsArray($normalized);
        $this->assertEmpty($normalized);
    }

    public function test_normalize_preserves_array_keys(): void
    {
        $array = [
            'first' => 1,
            'second' => 2,
            'third' => 3,
            'nested' => ['a' => 'apple', 'b' => 'banana'],
        ];

        $normalized = $this->normalizer->normalize($array);

        $this->assertArrayHasKey('first', $normalized);
        $this->assertArrayHasKey('second', $normalized);
        $this->assertArrayHasKey('third', $normalized);
        $this->assertArrayHasKey('nested', $normalized);
        $this->assertArrayHasKey('a', $normalized['nested']);
        $this->assertArrayHasKey('b', $normalized['nested']);
    }

    public function test_normalize_handles_indexed_arrays(): void
    {
        $array = [10, 20, 30, 40, 50];
        $normalized = $this->normalizer->normalize($array);

        $this->assertSame([10, 20, 30, 40, 50], $normalized);
        $this->assertSame([0, 1, 2, 3, 4], array_keys($normalized));
    }

    public function test_normalize_handles_associative_arrays(): void
    {
        $array = ['name' => 'John', 'age' => 30, 'city' => 'Paris'];
        $normalized = $this->normalizer->normalize($array);

        $this->assertSame(['name' => 'John', 'age' => 30, 'city' => 'Paris'], $normalized);
    }

    public function test_normalize_handles_mixed_arrays(): void
    {
        $array = [0 => 'first', 'key' => 'value', 2 => 'third'];
        $normalized = $this->normalizer->normalize($array);

        $this->assertSame([0 => 'first', 'key' => 'value', 2 => 'third'], $normalized);
    }

    public function test_normalize_handles_deep_nested_structures(): void
    {
        $array = [
            'level1' => [
                'level2' => [
                    'level3' => [
                        'level4' => 'deep value',
                    ],
                ],
            ],
        ];

        $normalized = $this->normalizer->normalize($array);

        $this->assertIsArray($normalized);
        $this->assertIsArray($normalized['level1']);
        $this->assertIsArray($normalized['level1']['level2']);
        $this->assertIsArray($normalized['level1']['level2']['level3']);
        $this->assertSame('deep value', $normalized['level1']['level2']['level3']['level4']);
    }

    public function test_normalize_handles_arrays_with_various_scalar_types(): void
    {
        $array = [
            'int' => 42,
            'string' => 'hello',
            'float' => 3.14,
            'bool_true' => true,
            'bool_false' => false,
            'null' => null,
        ];

        $normalized = $this->normalizer->normalize($array);

        $this->assertSame(42, $normalized['int']);
        $this->assertSame('hello', $normalized['string']);
        $this->assertSame(3.14, $normalized['float']);
        $this->assertTrue($normalized['bool_true']);
        $this->assertFalse($normalized['bool_false']);
        $this->assertNull($normalized['null']);
    }

    public function test_normalize_handles_large_arrays(): void
    {
        $array = [];
        for ($i = 0; $i < 1000; $i++) {
            $array["key_{$i}"] = $i;
        }

        $normalized = $this->normalizer->normalize($array);

        $this->assertCount(1000, $normalized);
        $this->assertSame(500, $normalized['key_500']);
    }

    public function test_normalize_forwards_to_next_normalizer_when_value_not_array(): void
    {
        $value = 'not an array';
        $normalized = $this->normalizer->normalize($value);

        $this->assertSame('not an array', $normalized);
    }
}
