<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Unit\EdgeCases;

use AndyDefer\DomainStructures\Collections\Core\DataCollection;
use AndyDefer\DomainStructures\Collections\Core\RecordCollection;
use AndyDefer\DomainStructures\Collections\Core\TypedCollection;
use AndyDefer\DomainStructures\Collections\Utility\BoolTypedCollection;
use AndyDefer\DomainStructures\Collections\Utility\FloatTypedCollection;
use AndyDefer\DomainStructures\Collections\Utility\IntTypedCollection;
use AndyDefer\DomainStructures\Collections\Utility\NumberTypedCollection;
use AndyDefer\DomainStructures\Collections\Utility\StringTypedCollection;
use AndyDefer\DomainStructures\Normalizers\NormalizerChain;
use AndyDefer\DomainStructures\Tests\Fixtures\Data\TestUserData;
use AndyDefer\DomainStructures\Tests\Fixtures\Records\TestUserRecord;
use AndyDefer\DomainStructures\Tests\Fixtures\ValueObjects\TestEmailAddress;
use AndyDefer\DomainStructures\Tests\TestCase;

/**
 * Edge case tests for empty collections.
 *
 * This test suite validates that all collection types behave correctly
 * when empty, including:
 * - Count and emptiness checks
 * - Operations on empty collections (map, filter, reduce, etc.)
 * - Normalization of empty collections
 * - JSON serialization
 * - Method chaining with empty results
 *
 * The tests follow the AAA pattern (Arrange, Act, Assert).
 */
final class EmptyCollectionsTest extends TestCase
{
    private TestEmailAddress $testEmail;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testEmail = TestEmailAddress::from('test@example.com');
    }

    // ==================== BASIC EMPTINESS TESTS ====================

    public function test_all_collection_types_start_empty(): void
    {
        $typedCollection = new TypedCollection('int');
        $intCollection = new IntTypedCollection;
        $floatCollection = new FloatTypedCollection;
        $stringCollection = new StringTypedCollection;
        $boolCollection = new BoolTypedCollection;
        $numberCollection = new NumberTypedCollection;
        $recordCollection = new RecordCollection(TestUserRecord::class);
        $dataCollection = new DataCollection(TestUserData::class);

        $this->assertTrue($typedCollection->isEmpty());
        $this->assertTrue($intCollection->isEmpty());
        $this->assertTrue($floatCollection->isEmpty());
        $this->assertTrue($stringCollection->isEmpty());
        $this->assertTrue($boolCollection->isEmpty());
        $this->assertTrue($numberCollection->isEmpty());
        $this->assertTrue($recordCollection->isEmpty());
        $this->assertTrue($dataCollection->isEmpty());
    }

    public function test_empty_collection_has_count_zero(): void
    {
        $collection = new TypedCollection('int');

        $this->assertSame(0, $collection->count());
        $this->assertCount(0, $collection);
    }

    public function test_is_empty_returns_true_is_not_empty_returns_false(): void
    {
        $collection = new TypedCollection('int');

        $this->assertTrue($collection->isEmpty());
        $this->assertFalse($collection->isNotEmpty());
    }

    // ==================== TO_ARRAY ON EMPTY COLLECTION TESTS ====================

    public function test_to_array_on_empty_collection_returns_empty_array(): void
    {
        $collections = [
            new TypedCollection('int'),
            new IntTypedCollection,
            new FloatTypedCollection,
            new StringTypedCollection,
            new BoolTypedCollection,
            new NumberTypedCollection,
            new RecordCollection(TestUserRecord::class),
            new DataCollection(TestUserData::class),
        ];

        foreach ($collections as $collection) {
            $array = $collection->toArray();

            $this->assertIsArray($array);
            $this->assertEmpty($array);
        }
    }

    // ==================== ALL METHOD ON EMPTY COLLECTION TESTS ====================

    public function test_all_on_empty_collection_returns_empty_collection(): void
    {
        $emptyCollection = new TypedCollection('int');

        $result = $emptyCollection->all();

        $this->assertNotSame($emptyCollection, $result);
        $this->assertCount(0, $result);
        $this->assertTrue($result->isEmpty());
        $this->assertSame(['int'], $result->getAllowedTypes());
    }

    // ==================== MAP ON EMPTY COLLECTION TESTS ====================

    public function test_map_on_empty_collection_returns_empty_collection(): void
    {
        $emptyCollection = new IntTypedCollection;

        $result = $emptyCollection->map(fn ($item) => $item * 2);

        $this->assertCount(0, $result);
        $this->assertTrue($result->isEmpty());
    }

    public function test_map_on_empty_collection_preserves_allowed_types(): void
    {
        $emptyCollection = new TypedCollection('int', 'string', 'float');

        $result = $emptyCollection->map(fn ($item) => $item);

        $this->assertSame(['int', 'string', 'float'], $result->getAllowedTypes());
    }

    // ==================== FILTER ON EMPTY COLLECTION TESTS ====================

    public function test_filter_on_empty_collection_returns_empty_collection(): void
    {
        $emptyCollection = new IntTypedCollection;

        $result = $emptyCollection->filter(fn ($item) => $item > 0);

        $this->assertCount(0, $result);
        $this->assertTrue($result->isEmpty());
    }

    public function test_filter_on_empty_collection_returns_new_instance(): void
    {
        $emptyCollection = new IntTypedCollection;

        $result = $emptyCollection->filter(fn ($item) => $item > 0);

        $this->assertNotSame($emptyCollection, $result);
    }

    // ==================== REDUCE ON EMPTY COLLECTION TESTS ====================

    public function test_reduce_on_empty_collection_returns_initial_value(): void
    {
        $emptyCollection = new IntTypedCollection;

        $result = $emptyCollection->reduce(fn ($carry, $item) => $carry + $item, 100);

        $this->assertSame(100, $result);
    }

    public function test_reduce_on_empty_collection_with_null_initial_returns_null(): void
    {
        $emptyCollection = new IntTypedCollection;

        $result = $emptyCollection->reduce(fn ($carry, $item) => $carry + $item, null);

        $this->assertNull($result);
    }

    // ==================== FIND ON EMPTY COLLECTION TESTS ====================

    public function test_find_on_empty_collection_returns_null(): void
    {
        $emptyCollection = new IntTypedCollection;

        $result = $emptyCollection->find(fn ($item) => true);

        $this->assertNull($result);
    }

    // ==================== EVERY ON EMPTY COLLECTION TESTS ====================

    public function test_every_on_empty_collection_returns_true(): void
    {
        $emptyCollection = new IntTypedCollection;

        $result = $emptyCollection->every(fn ($item) => $item > 100);

        $this->assertTrue($result);
    }

    // ==================== SOME ON EMPTY COLLECTION TESTS ====================

    public function test_some_on_empty_collection_returns_false(): void
    {
        $emptyCollection = new IntTypedCollection;

        $result = $emptyCollection->some(fn ($item) => true);

        $this->assertFalse($result);
    }

    // ==================== SORT ON EMPTY COLLECTION TESTS ====================

    public function test_sort_on_empty_collection_returns_empty_collection(): void
    {
        $emptyCollection = new IntTypedCollection;

        $sorted = $emptyCollection->sort();

        $this->assertCount(0, $sorted);
        $this->assertTrue($sorted->isEmpty());
    }

    // ==================== REVERSE ON EMPTY COLLECTION TESTS ====================

    public function test_reverse_on_empty_collection_returns_empty_collection(): void
    {
        $emptyCollection = new IntTypedCollection;

        $reversed = $emptyCollection->reverse();

        $this->assertCount(0, $reversed);
        $this->assertTrue($reversed->isEmpty());
    }

    // ==================== MERGE ON EMPTY COLLECTION TESTS ====================

    public function test_merge_empty_collection_with_non_empty_returns_non_empty(): void
    {
        $emptyCollection = new IntTypedCollection;
        $nonEmptyCollection = new IntTypedCollection;
        $nonEmptyCollection->add(1, 2, 3);

        $merged = $emptyCollection->merge($nonEmptyCollection);

        $this->assertCount(3, $merged);
        $this->assertSame([1, 2, 3], $merged->toArray());
    }

    public function test_merge_non_empty_with_empty_collection_returns_non_empty(): void
    {
        $nonEmptyCollection = new IntTypedCollection;
        $emptyCollection = new IntTypedCollection;
        $nonEmptyCollection->add(1, 2, 3);

        $merged = $nonEmptyCollection->merge($emptyCollection);

        $this->assertCount(3, $merged);
        $this->assertSame([1, 2, 3], $merged->toArray());
    }

    public function test_merge_two_empty_collections_returns_empty_collection(): void
    {
        $empty1 = new IntTypedCollection;
        $empty2 = new IntTypedCollection;

        $merged = $empty1->merge($empty2);

        $this->assertCount(0, $merged);
        $this->assertTrue($merged->isEmpty());
    }

    // ==================== CONTAINS ON EMPTY COLLECTION TESTS ====================

    public function test_contains_on_empty_collection_returns_false(): void
    {
        $emptyCollection = new IntTypedCollection;

        $this->assertFalse($emptyCollection->contains(1));
        $this->assertFalse($emptyCollection->contains(null));
        $this->assertFalse($emptyCollection->contains('test'));
    }

    // ==================== EACH ON EMPTY COLLECTION TESTS ====================

    public function test_each_on_empty_collection_does_nothing(): void
    {
        $emptyCollection = new IntTypedCollection;
        $counter = 0;

        $result = $emptyCollection->each(function () use (&$counter) {
            $counter++;
        });

        $this->assertSame(0, $counter);
        $this->assertSame($emptyCollection, $result);
    }

    // ==================== NORMALIZATION ON EMPTY COLLECTION TESTS ====================

    public function test_normalize_on_empty_collection_returns_empty_array(): void
    {
        $emptyCollection = new IntTypedCollection;

        $result = NormalizerChain::get()->normalize($emptyCollection);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_json_encode_on_empty_collection_returns_empty_json_array(): void
    {
        $emptyCollection = new IntTypedCollection;

        $json = json_encode($emptyCollection);

        $this->assertSame('[]', $json);
    }

    // ==================== JSON SERIALIZATION ON EMPTY COLLECTION TESTS ====================

    public function test_json_serialize_on_empty_collection_returns_empty_array(): void
    {
        $emptyCollection = new IntTypedCollection;

        $serialized = $emptyCollection->jsonSerialize();

        $this->assertIsArray($serialized);
        $this->assertEmpty($serialized);
    }

    public function test_json_encode_on_empty_collection_returns_empty_array_string(): void
    {
        $emptyCollection = new IntTypedCollection;

        $json = json_encode($emptyCollection);

        $this->assertSame('[]', $json);
    }

    // ==================== MAGIC TO_STRING ON EMPTY COLLECTION TESTS ====================

    public function test_to_string_on_empty_collection_returns_json_representation(): void
    {
        $emptyCollection = new StringTypedCollection;

        $string = (string) $emptyCollection;

        $this->assertSame('[]', $string);
    }

    // ==================== ARRAY ACCESS ON EMPTY COLLECTION TESTS ====================

    public function test_array_access_offset_exists_returns_false_for_empty_collection(): void
    {
        $emptyCollection = new StringTypedCollection;

        $this->assertFalse(isset($emptyCollection[0]));
        $this->assertFalse(isset($emptyCollection[100]));
    }

    public function test_array_access_offset_get_returns_null_for_empty_collection(): void
    {
        $emptyCollection = new StringTypedCollection;

        $this->assertNull($emptyCollection[0]);
        $this->assertNull($emptyCollection[100]);
    }

    // ==================== ITERATOR ON EMPTY COLLECTION TESTS ====================

    public function test_foreach_on_empty_collection_iterates_zero_times(): void
    {
        $emptyCollection = new IntTypedCollection;
        $count = 0;

        foreach ($emptyCollection as $item) {
            $count++;
        }

        $this->assertSame(0, $count);
    }

    public function test_get_iterator_on_empty_collection_returns_empty_array_iterator(): void
    {
        $emptyCollection = new IntTypedCollection;

        $iterator = $emptyCollection->getIterator();

        $this->assertInstanceOf(\ArrayIterator::class, $iterator);
        $this->assertCount(0, $iterator);
    }

    // ==================== SPECIFIC COLLECTION TYPE EMPTY BEHAVIOR TESTS ====================

    public function test_int_typed_collection_specific_methods_on_empty(): void
    {
        $emptyCollection = new IntTypedCollection;

        $this->assertSame([], $emptyCollection->positive()->toArray());
        $this->assertSame([], $emptyCollection->negative()->toArray());
        $this->assertSame([], $emptyCollection->zero()->toArray());
        $this->assertSame([], $emptyCollection->nonNegative()->toArray());
        $this->assertSame([], $emptyCollection->even()->toArray());
        $this->assertSame([], $emptyCollection->odd()->toArray());
        $this->assertSame(0.0, $emptyCollection->median());
        $this->assertSame(0, $emptyCollection->sum());
        $this->assertSame(0.0, $emptyCollection->avg());
    }

    public function test_float_typed_collection_specific_methods_on_empty(): void
    {
        $emptyCollection = new FloatTypedCollection;

        $this->assertSame([], $emptyCollection->round()->toArray());
        $this->assertSame([], $emptyCollection->ceil()->toArray());
        $this->assertSame([], $emptyCollection->floor()->toArray());
        $this->assertSame([], $emptyCollection->format()->toArray());
    }

    public function test_string_typed_collection_specific_methods_on_empty(): void
    {
        $emptyCollection = new StringTypedCollection;

        $this->assertSame([], $emptyCollection->toLowercase()->toArray());
        $this->assertSame([], $emptyCollection->toUppercase()->toArray());
        $this->assertSame([], $emptyCollection->filterEmpty()->toArray());
        $this->assertSame('', $emptyCollection->join());
        $this->assertSame(0, $emptyCollection->lengths()->count());
        $this->assertSame('[]', (string) $emptyCollection);
    }

    public function test_bool_typed_collection_specific_methods_on_empty(): void
    {
        $emptyCollection = new BoolTypedCollection;

        $this->assertSame([], $emptyCollection->trueOnly()->toArray());
        $this->assertSame([], $emptyCollection->falseOnly()->toArray());
        $this->assertSame(0, $emptyCollection->countTrue());
        $this->assertSame(0, $emptyCollection->countFalse());
        $this->assertTrue($emptyCollection->allTrue());
        $this->assertTrue($emptyCollection->allFalse());
        $this->assertFalse($emptyCollection->anyTrue());
        $this->assertFalse($emptyCollection->anyFalse());
    }

    public function test_number_typed_collection_specific_methods_on_empty(): void
    {
        $emptyCollection = new NumberTypedCollection;

        $this->assertSame([], $emptyCollection->zero()->toArray());
        $this->assertSame([], $emptyCollection->nonNegative()->toArray());
        $this->assertTrue($emptyCollection->areAllIntegers());
        $this->assertFalse($emptyCollection->hasAnyFloat());
        $this->assertCount(0, $emptyCollection->toFloats());
        $this->assertCount(0, $emptyCollection->toIntegers());
    }

    // ==================== CHAINING EMPTY OPERATIONS TESTS ====================

    public function test_chaining_multiple_operations_on_empty_collection(): void
    {
        $emptyCollection = new IntTypedCollection;

        $result = $emptyCollection
            ->filter(fn ($n) => $n > 0)
            ->map(fn ($n) => $n * 2)
            ->sort()
            ->reverse()
            ->filter(fn ($n) => $n > 0)
            ->filter(fn ($n) => $n % 2 === 0);

        $this->assertCount(0, $result);
        $this->assertTrue($result->isEmpty());
    }

    public function test_chaining_operations_staying_in_int_collection(): void
    {
        $emptyCollection = new IntTypedCollection;

        $result = $emptyCollection
            ->filter(fn ($n) => $n > 0)
            ->positive()
            ->even();

        $this->assertCount(0, $result);
        $this->assertTrue($result->isEmpty());
        $this->assertInstanceOf(IntTypedCollection::class, $result);
    }

    public function test_empty_collection_supports_fluent_interface(): void
    {
        $result = (new IntTypedCollection)
            ->filter(fn ($n) => $n > 0)
            ->add(1, 2, 3)
            ->positive();

        $this->assertCount(3, $result);
        $this->assertSame([1, 2, 3], $result->toArray());
    }
}
