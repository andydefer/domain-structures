<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Unit\Normalizers;

use AndyDefer\DomainStructures\Collections\Core\DataCollection;
use AndyDefer\DomainStructures\Collections\Core\RecordCollection;
use AndyDefer\DomainStructures\Collections\Core\TypedCollection;
use AndyDefer\DomainStructures\Collections\Utility\IntTypedCollection;
use AndyDefer\DomainStructures\Collections\Utility\StringTypedCollection;
use AndyDefer\DomainStructures\Normalizers\RootNormalizer;
use AndyDefer\DomainStructures\Normalizers\TypedCollectionNormalizer;
use AndyDefer\DomainStructures\Tests\Fixtures\Collections\TestUserRoleCollection;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestUserRole;
use AndyDefer\DomainStructures\Tests\Fixtures\Records\TestUserRecord;
use AndyDefer\DomainStructures\Tests\Fixtures\ValueObjects\TestEmailAddress;
use AndyDefer\DomainStructures\Tests\TestCase;

final class TypedCollectionNormalizerTest extends TestCase
{
    private TypedCollectionNormalizer $normalizer;
    private RootNormalizer $rootNormalizer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rootNormalizer = new RootNormalizer();

        $this->normalizer = new TypedCollectionNormalizer();
        $this->normalizer->setRecursiveNormalizer($this->rootNormalizer);
    }

    private function createTestUserRecord(int $id, string $name): TestUserRecord
    {
        $cleanName = str_replace(' ', '', $name);
        $email = TestEmailAddress::from("{$cleanName}@example.com");

        return new TestUserRecord(
            id: $id,
            name: $name,
            email: $email
        );
    }

    // ==================== SUPPORTS METHOD TESTS ====================

    public function test_supports_returns_true_for_typed_collection_instance(): void
    {
        $collection = new TypedCollection('int');
        $result = $this->normalizer->supports($collection);

        $this->assertTrue($result);
    }

    public function test_supports_returns_true_for_int_typed_collection_instance(): void
    {
        $collection = new IntTypedCollection;
        $result = $this->normalizer->supports($collection);

        $this->assertTrue($result);
    }

    public function test_supports_returns_true_for_string_typed_collection_instance(): void
    {
        $collection = new StringTypedCollection;
        $result = $this->normalizer->supports($collection);

        $this->assertTrue($result);
    }

    public function test_supports_returns_true_for_record_collection_instance(): void
    {
        $collection = new RecordCollection;
        $result = $this->normalizer->supports($collection);

        $this->assertTrue($result);
    }

    public function test_supports_returns_true_for_data_collection_instance(): void
    {
        $collection = new DataCollection;
        $result = $this->normalizer->supports($collection);

        $this->assertTrue($result);
    }

    public function test_supports_returns_false_for_non_collection_values(): void
    {
        $values = [
            42,
            'string',
            3.14,
            true,
            null,
            new \stdClass,
            TestEmailAddress::from('test@example.com'),
            [],
        ];

        foreach ($values as $value) {
            $result = $this->normalizer->supports($value);
            $this->assertFalse($result, 'Failed for value type: ' . (is_object($value) ? $value::class : gettype($value)));
        }
    }

    // ==================== NORMALIZE METHOD TESTS ====================

    public function test_normalize_returns_array_representation_of_collection(): void
    {
        $collection = new IntTypedCollection;
        $collection->add(1, 2, 3, 4, 5);

        $normalized = $this->normalizer->normalize($collection);

        $this->assertIsArray($normalized);
        $this->assertSame([1, 2, 3, 4, 5], $normalized);
    }

    public function test_normalize_handles_collection_of_strings(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('apple', 'banana', 'cherry');

        $normalized = $this->normalizer->normalize($collection);

        $this->assertSame(['apple', 'banana', 'cherry'], $normalized);
    }

    public function test_normalize_handles_collection_of_records(): void
    {
        $collection = new RecordCollection;
        $collection->add(
            $this->createTestUserRecord(1, 'User1'),
            $this->createTestUserRecord(2, 'User2')
        );

        $normalized = $this->normalizer->normalize($collection);

        $this->assertIsArray($normalized);
        $this->assertCount(2, $normalized);
        $this->assertSame(1, $normalized[0]['id']);
        $this->assertSame('User1', $normalized[0]['name']);
        $this->assertSame(2, $normalized[1]['id']);
        $this->assertSame('User2', $normalized[1]['name']);
    }

    public function test_normalize_handles_collection_of_enums(): void
    {
        $collection = new TestUserRoleCollection;
        $collection->add(TestUserRole::ADMIN, TestUserRole::USER, TestUserRole::GUEST);

        $normalized = $this->normalizer->normalize($collection);

        $this->assertSame(['admin', 'user', 'guest'], $normalized);
    }

    public function test_normalize_handles_empty_collection(): void
    {
        $emptyCollection = new IntTypedCollection;
        $normalized = $this->normalizer->normalize($emptyCollection);

        $this->assertIsArray($normalized);
        $this->assertEmpty($normalized);
    }

    public function test_normalize_handles_nested_collections(): void
    {
        $innerCollection = new StringTypedCollection;
        $innerCollection->add('a', 'b', 'c');

        $outerCollection = new TypedCollection(StringTypedCollection::class);
        $outerCollection->add($innerCollection);

        $normalized = $this->normalizer->normalize($outerCollection);

        $this->assertIsArray($normalized);
        $this->assertCount(1, $normalized);
        $this->assertIsArray($normalized[0]);
        $this->assertSame(['a', 'b', 'c'], $normalized[0]);
    }

    public function test_normalize_handles_collection_with_mixed_types(): void
    {
        $collection = new TypedCollection('int', 'string', 'float', 'bool');
        $collection->add(42, 'hello', 3.14, true);

        $normalized = $this->normalizer->normalize($collection);

        $this->assertSame([42, 'hello', 3.14, true], $normalized);
    }

    public function test_normalize_forwards_to_next_normalizer_when_not_collection(): void
    {
        $value = 'not a collection';
        $normalized = $this->normalizer->normalize($value);

        $this->assertSame('not a collection', $normalized);
    }

    public function test_normalize_forwards_null_to_next_normalizer(): void
    {
        $value = null;
        $normalized = $this->normalizer->normalize($value);

        $this->assertNull($normalized);
    }

    public function test_normalize_preserves_order_of_items(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('first', 'second', 'third', 'fourth');

        $normalized = $this->normalizer->normalize($collection);

        $this->assertSame(['first', 'second', 'third', 'fourth'], $normalized);
    }

    public function test_normalize_is_idempotent(): void
    {
        $collection = new IntTypedCollection;
        $collection->add(1, 2, 3, 4, 5);

        $first = $this->normalizer->normalize($collection);
        $second = $this->normalizer->normalize($collection);
        $third = $this->normalizer->normalize($collection);

        $this->assertSame($first, $second);
        $this->assertSame($second, $third);
    }

    public function test_normalize_handles_large_collections(): void
    {
        $collection = new IntTypedCollection;
        for ($i = 1; $i <= 100; $i++) {
            $collection->add($i);
        }

        $normalized = $this->normalizer->normalize($collection);

        $this->assertCount(100, $normalized);
        $this->assertSame(50, $normalized[49]);
        $this->assertSame(100, $normalized[99]);
    }

    public function test_normalize_forwards_integer_to_next_normalizer(): void
    {
        $value = 42;
        $normalized = $this->normalizer->normalize($value);

        $this->assertSame(42, $normalized);
    }

    public function test_normalize_forwards_array_to_next_normalizer(): void
    {
        $value = [1, 2, 3];
        $normalized = $this->normalizer->normalize($value);

        $this->assertIsArray($normalized);
        $this->assertSame([1, 2, 3], $normalized);
    }
}
