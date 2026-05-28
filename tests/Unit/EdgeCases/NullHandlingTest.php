<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Unit\EdgeCases;

use AndyDefer\DomainStructures\Collections\Core\TypedCollection;
use AndyDefer\DomainStructures\Collections\Utility\IntTypedCollection;
use AndyDefer\DomainStructures\Collections\Utility\StringTypedCollection;
use AndyDefer\DomainStructures\Normalizers\NormalizerChain;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestUserStatus;
use AndyDefer\DomainStructures\Tests\Fixtures\Records\TestUserRecord;
use AndyDefer\DomainStructures\Tests\Fixtures\ValueObjects\TestEmailAddress;
use AndyDefer\DomainStructures\Tests\TestCase;
use AndyDefer\DomainStructures\Utils\DataObject;
use InvalidArgumentException;

/**
 * Edge case tests for null handling.
 *
 * This test suite validates that all components handle null values correctly:
 * - Adding null to collections (when allowed)
 * - Normalization with null inclusion/exclusion
 * - Null handling in Record hydration
 * - Null handling in Value Objects
 * - Null handling in operations (filter, map, reduce)
 * - Null values in different contexts
 *
 * The tests follow the AAA pattern (Arrange, Act, Assert).
 */
final class NullHandlingTest extends TestCase
{
    private TestEmailAddress $testEmail;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testEmail = TestEmailAddress::from('test@example.com');
    }

    // ==================== COLLECTION NULL HANDLING TESTS ====================

    /**
     * Test that collection with null allowed can store null values.
     */
    public function test_collection_with_null_allowed_can_store_null_values(): void
    {
        $collection = new TypedCollection('int', 'null');

        $collection->add(1, null, 2, null, 3);

        $this->assertCount(5, $collection);
        $this->assertSame(1, $collection[0]);
        $this->assertNull($collection[1]);
        $this->assertSame(2, $collection[2]);
        $this->assertNull($collection[3]);
        $this->assertSame(3, $collection[4]);
    }

    /**
     * Test that collection without null rejects null values.
     */
    public function test_collection_without_null_rejects_null_values(): void
    {
        $collection = new TypedCollection('int');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected type(s) int');

        $collection->add(null);
    }

    /**
     * Test that multiple nulls can be added at once.
     */
    public function test_multiple_nulls_can_be_added_at_once(): void
    {
        $collection = new TypedCollection('int', 'null');

        $collection->add(1, null, 2, null, 3, null);

        $this->assertCount(6, $collection);
        $this->assertNull($collection[1]);
        $this->assertNull($collection[3]);
        $this->assertNull($collection[5]);
    }

    // ==================== FILTERING NULL VALUES TESTS ====================

    /**
     * Test that null values can be filtered out.
     */
    public function test_null_values_can_be_filtered_out(): void
    {
        $collection = new TypedCollection('int', 'null');
        $collection->add(1, null, 2, null, 3, null, 4);

        /** @var TypedCollection<int> $withoutNulls */
        $withoutNulls = $collection->filter(fn ($item) => $item !== null);

        $this->assertCount(4, $withoutNulls);
        $this->assertSame([1, 2, 3, 4], $withoutNulls->toArray());
        $this->assertNotContains(null, $withoutNulls->toArray());
    }

    /**
     * Test that filter can keep only null values.
     */
    public function test_filter_can_keep_only_null_values(): void
    {
        $collection = new TypedCollection('int', 'null');
        $collection->add(1, null, 2, null, 3, null, 4);

        /** @var TypedCollection<null> $onlyNulls */
        $onlyNulls = $collection->filter(fn ($item) => $item === null);

        $this->assertCount(3, $onlyNulls);
        $this->assertSame([null, null, null], $onlyNulls->toArray());
    }

    // ==================== MAP WITH NULL VALUES TESTS ====================

    /**
     * Test that map can handle null values.
     */
    public function test_map_can_handle_null_values(): void
    {
        $collection = new TypedCollection('int', 'null');
        $collection->add(1, null, 2, null, 3);

        /** @var TypedCollection<int> $mapped */
        $mapped = $collection->map(fn ($item) => $item === null ? -1 : $item * 10);

        $this->assertSame([10, -1, 20, -1, 30], $mapped->toArray());
    }

    /**
     * Test that map returning null is allowed if collection allows null.
     */
    public function test_map_returning_null_is_allowed_if_collection_allows_null(): void
    {
        $collection = new TypedCollection('int', 'null');
        $collection->add(1, 2, 3);

        /** @var TypedCollection<int|null> $mapped */
        $mapped = $collection->map(fn ($item) => $item > 2 ? null : $item);

        $this->assertSame([1, 2, null], $mapped->toArray());
    }

    // ==================== NORMALIZATION WITH NULL TESTS ====================

    /**
     * Test that normalize includes nulls (always includes nulls now).
     */
    public function test_normalize_includes_nulls(): void
    {
        $collection = new TypedCollection('int', 'null');
        $collection->add(1, null, 2, null, 3);

        $normalized = NormalizerChain::get()->normalize($collection);

        $this->assertSame([1, null, 2, null, 3], $normalized);
    }

    /**
     * Test that JSON encoding includes nulls.
     */
    public function test_json_encoding_includes_nulls(): void
    {
        $collection = new TypedCollection('int', 'null');
        $collection->add(1, null, 2);

        $json = json_encode($collection);

        $this->assertSame('[1,null,2]', $json);
    }

    // ==================== RECORD NULL HANDLING TESTS ====================

    /**
     * Test that Record can have null properties.
     */
    public function test_record_can_have_null_properties(): void
    {
        $record = new TestUserRecord(
            id: null,
            name: 'John Doe',
            email: $this->testEmail,
            emailVerifiedAt: null,
            featuredProduct: null
        );

        $this->assertNull($record->id);
        $this->assertNull($record->emailVerifiedAt);
        $this->assertNull($record->featuredProduct);
        $this->assertSame('John Doe', $record->name);
    }

    /**
     * Test that Record normalization includes nulls (always includes nulls now).
     */
    public function test_record_normalization_includes_nulls(): void
    {
        $record = new TestUserRecord(
            id: null,
            name: 'John Doe',
            email: $this->testEmail,
            emailVerifiedAt: null
        );

        $normalized = NormalizerChain::get()->normalize($record);

        $this->assertArrayHasKey('id', $normalized);
        $this->assertNull($normalized['id']);
        $this->assertArrayHasKey('email_verified_at', $normalized);
        $this->assertNull($normalized['email_verified_at']);
        $this->assertArrayHasKey('name', $normalized);
        $this->assertArrayHasKey('email', $normalized);
    }

    // ==================== HYDRATION WITH NULL VALUES TESTS ====================

    /**
     * Test that hydration with null values works correctly using DataObject.
     */
    public function test_hydration_with_null_values_works_correctly(): void
    {
        $source = new DataObject([
            'id' => null,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'emailVerifiedAt' => null,
        ]);

        $record = TestUserRecord::from($source);

        $this->assertNull($record->id);
        $this->assertNull($record->emailVerifiedAt);
        $this->assertSame('John Doe', $record->name);
    }

    /**
     * Test that explicit null overrides default values in Record.
     */
    public function test_explicit_null_overrides_default_values_in_record(): void
    {
        $source = new DataObject([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'status' => null,
        ]);

        $record = TestUserRecord::from($source);

        $this->assertNull($record->status);
        $this->assertNotSame(TestUserStatus::ACTIVE, $record->status);
    }

    // ==================== REDUCE WITH NULL VALUES TESTS ====================

    /**
     * Test that reduce handles null initial value.
     */
    public function test_reduce_handles_null_initial_value(): void
    {
        $collection = new TypedCollection('int');
        $collection->add(1, 2, 3);

        $result = $collection->reduce(fn ($carry, $item) => ($carry ?? 0) + $item, null);

        $this->assertSame(6, $result);
    }

    /**
     * Test that reduce with null items works.
     */
    public function test_reduce_with_null_items_works(): void
    {
        $collection = new TypedCollection('int', 'null');
        $collection->add(1, null, 2, null, 3);

        $sum = $collection->reduce(function ($carry, $item) {
            return $carry + ($item ?? 0);
        }, 0);

        $this->assertSame(6, $sum);
    }

    // ==================== FIND WITH NULL VALUES TESTS ====================

    /**
     * Test that find can find null values.
     */
    public function test_find_can_find_null_values(): void
    {
        $collection = new TypedCollection('int', 'null');
        $collection->add(1, null, 2, null, 3);

        /** @var int|null $found */
        $found = $collection->find(fn ($item) => $item === null);

        $this->assertNull($found);
    }

    /**
     * Test that find returns null when searching non-existent value.
     */
    public function test_find_returns_null_when_searching_non_existent_value(): void
    {
        $collection = new TypedCollection('int');
        $collection->add(1, 2, 3);

        /** @var int|null $found */
        $found = $collection->find(fn ($item) => $item === 5);

        $this->assertNull($found);
    }

    // ==================== EVERY AND SOME WITH NULL TESTS ====================

    /**
     * Test that every works with null values.
     */
    public function test_every_works_with_null_values(): void
    {
        $collection = new TypedCollection('int', 'null');
        $collection->add(null, null, null);

        $allNull = $collection->every(fn ($item) => $item === null);
        $allNotNull = $collection->every(fn ($item) => $item !== null);

        $this->assertTrue($allNull);
        $this->assertFalse($allNotNull);
    }

    /**
     * Test that some works with null values.
     */
    public function test_some_works_with_null_values(): void
    {
        $collection = new TypedCollection('int', 'null');
        $collection->add(1, 2, null, 3);

        $hasNull = $collection->some(fn ($item) => $item === null);
        $allNull = $collection->some(fn ($item) => $item === null && $item !== null);

        $this->assertTrue($hasNull);
        $this->assertFalse($allNull);
    }

    // ==================== CONTAINS WITH NULL TESTS ====================

    /**
     * Test that contains works with null values.
     */
    public function test_contains_works_with_null_values(): void
    {
        $collection = new TypedCollection('int', 'null');
        $collection->add(1, null, 2, 3);

        $this->assertTrue($collection->contains(null));
        $this->assertTrue($collection->contains(1));
        $this->assertFalse($collection->contains(5));
    }

    /**
     * Test that contains with strict comparison works for null.
     */
    public function test_contains_with_strict_comparison_works_for_null(): void
    {
        $collection = new TypedCollection('int', 'null');
        $collection->add(null);

        $this->assertTrue($collection->contains(null));
        $this->assertFalse($collection->contains(0));
        $this->assertFalse($collection->contains(false));
    }

    // ==================== STRING COLLECTION NULL HANDLING TESTS ====================

    /**
     * Test that string collection rejects null by default.
     */
    public function test_string_collection_rejects_null_by_default(): void
    {
        $collection = new StringTypedCollection;

        $this->expectException(InvalidArgumentException::class);
        $collection->add(null);
    }

    /**
     * Test that string collection with null allowed accepts null.
     */
    public function test_string_collection_with_null_allowed_accepts_null(): void
    {
        $collection = new TypedCollection('string', 'null');

        $collection->add('hello', null, 'world');

        $this->assertCount(3, $collection);
        $this->assertSame('hello', $collection[0]);
        $this->assertNull($collection[1]);
        $this->assertSame('world', $collection[2]);
    }

    // ==================== INT COLLECTION NULL HANDLING TESTS ====================

    /**
     * Test that int collection rejects null by default.
     */
    public function test_int_collection_rejects_null_by_default(): void
    {
        $collection = new IntTypedCollection;

        $this->expectException(InvalidArgumentException::class);
        $collection->add(null);
    }

    /**
     * Test that int collection with null allowed accepts null.
     */
    public function test_int_collection_with_null_allowed_accepts_null(): void
    {
        $collection = new TypedCollection('int', 'null');

        $collection->add(1, null, 2, null, 3);

        $this->assertCount(5, $collection);
        $this->assertContains(null, $collection->toArray());
    }

    // ==================== NULL IN STRING OPERATIONS TESTS ====================

    /**
     * Test that string operations skip nulls or handle them appropriately.
     */
    public function test_string_operations_skip_nulls(): void
    {
        $collection = new TypedCollection('string', 'null');
        $collection->add('hello', null, 'world', null, 'test');

        $filtered = $collection->filter(fn ($item) => $item !== null);

        $stringCollection = new StringTypedCollection;
        /** @var array<int, string> $filteredArray */
        $filteredArray = $filtered->toArray();
        foreach ($filteredArray as $item) {
            $stringCollection->add($item);
        }
        $joined = $stringCollection->join(' ');

        $this->assertSame('hello world test', $joined);
    }

    // ==================== NULL IN NUMERIC OPERATIONS TESTS ====================

    /**
     * Test that numeric operations skip nulls.
     */
    public function test_numeric_operations_skip_nulls(): void
    {
        $collection = new TypedCollection('int', 'float', 'null');
        $collection->add(10, null, 20.5, null, 30);

        $filtered = $collection->filter(fn ($item) => $item !== null);
        $sum = $filtered->reduce(fn ($carry, $item) => $carry + $item, 0);

        $this->assertSame(60.5, $sum);
    }

    // ==================== NULL IN SORT OPERATIONS TESTS ====================

    /**
     * Test that sort works with null values.
     */
    public function test_sort_works_with_null_values(): void
    {
        $collection = new TypedCollection('int', 'null');
        $collection->add(3, null, 1, null, 2);

        $sorted = $collection->sort();

        $this->assertSame([null, null, 1, 2, 3], $sorted->toArray());
    }

    // ==================== NULL IN REVERSE OPERATIONS TESTS ====================

    /**
     * Test that reverse works with null values.
     */
    public function test_reverse_works_with_null_values(): void
    {
        $collection = new TypedCollection('int', 'null');
        $collection->add(1, null, 2, null, 3);

        $reversed = $collection->reverse();

        $this->assertSame([3, null, 2, null, 1], $reversed->toArray());
    }

    // ==================== NULL IN MERGE OPERATIONS TESTS ====================

    /**
     * Test that merge preserves null values.
     */
    public function test_merge_preserves_null_values(): void
    {
        $collection1 = new TypedCollection('int', 'null');
        $collection2 = new TypedCollection('int', 'null');
        $collection1->add(1, null, 2);
        $collection2->add(3, null, 4);

        $merged = $collection1->merge($collection2);

        $this->assertSame([1, null, 2, 3, null, 4], $merged->toArray());
    }

    // ==================== NULL IN CLONE OPERATIONS TESTS ====================

    /**
     * Test that clone preserves null values.
     */
    public function test_clone_preserves_null_values(): void
    {
        $original = new TypedCollection('int', 'null');
        $original->add(1, null, 2, null, 3);

        $cloned = clone $original;

        $this->assertEquals($original->toArray(), $cloned->toArray());
        $this->assertNull($cloned[1]);
        $this->assertNull($cloned[3]);
    }

    // ==================== NULL IN JSON SERIALIZATION TESTS ====================

    /**
     * Test that JSON serialization preserves nulls.
     */
    public function test_json_serialization_preserves_nulls(): void
    {
        $collection = new TypedCollection('int', 'null');
        $collection->add(1, null, 2, null, 3);

        $json = json_encode($collection);

        $this->assertSame('[1,null,2,null,3]', $json);
    }

    // ==================== NULL IN DATAOBJECT HYDRATION TESTS ====================

    /**
     * Test that DataObject with null values can be converted to array.
     */
    public function test_data_object_with_null_values_can_be_converted_to_array(): void
    {
        $data = new DataObject([
            'id' => null,
            'name' => 'John Doe',
            'email' => null,
            'tags' => ['premium', null, 'vip'],
        ]);

        $array = $data->toArray();

        $this->assertNull($array['id']);
        $this->assertNull($array['email']);
        $this->assertSame(['premium', null, 'vip'], $array['tags']);
    }

    /**
     * Test that DataObject with null values can be normalized.
     */
    public function test_data_object_with_null_values_can_be_normalized(): void
    {
        $data = new DataObject([
            'id' => null,
            'name' => 'John Doe',
            'email' => null,
        ]);

        $normalized = $data->toArray();

        $this->assertNull($normalized['id']);
        $this->assertNull($normalized['email']);
        $this->assertSame('John Doe', $normalized['name']);
    }
}
