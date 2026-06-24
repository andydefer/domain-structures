<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Unit\Abstracts;

use AndyDefer\DomainStructures\Normalizers\NormalizerChain;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestUserStatus;
use AndyDefer\DomainStructures\Tests\Fixtures\Records\TestUserRecord;
use AndyDefer\DomainStructures\Tests\Fixtures\ValueObjects\TestEmailAddress;
use AndyDefer\DomainStructures\Tests\TestCase;
use AndyDefer\DomainStructures\Utils\Sequential;
use InvalidArgumentException;

final class AbstractSequentialTest extends TestCase
{
    private TestEmailAddress $testEmail;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testEmail = TestEmailAddress::from('test@example.com');
    }

    // ==================== CONSTRUCTION TESTS ====================

    public function test_sequential_can_be_created_empty(): void
    {
        $sequential = new Sequential;

        $this->assertInstanceOf(Sequential::class, $sequential);
        $this->assertTrue($sequential->isEmpty());
        $this->assertCount(0, $sequential);
    }

    public function test_sequential_can_be_created_with_initial_items(): void
    {
        $sequential = new Sequential([1, 2, 3, 4, 5]);

        $this->assertCount(5, $sequential);
        $this->assertSame([1, 2, 3, 4, 5], $sequential->toArray());
    }

    public function test_sequential_normalizes_items_on_construction(): void
    {
        $email = TestEmailAddress::from('test@example.com');
        $sequential = new Sequential([$email]);

        $this->assertCount(1, $sequential);
        $this->assertSame('test@example.com', $sequential[0]);
    }

    public function test_sequential_normalizes_complex_objects_on_construction(): void
    {
        $record = new TestUserRecord(name: 'John', email: $this->testEmail);
        $sequential = new Sequential([$record]);

        $this->assertCount(1, $sequential);
        $this->assertIsArray($sequential[0]);
        $this->assertArrayHasKey('name', $sequential[0]);
        $this->assertSame('John', $sequential[0]['name']);
    }

    public function test_sequential_with_mixed_types(): void
    {
        $sequential = new Sequential([1, 'hello', true, 3.14, null]);

        $this->assertCount(5, $sequential);
        $this->assertSame(1, $sequential[0]);
        $this->assertSame('hello', $sequential[1]);
        $this->assertTrue($sequential[2]);
        $this->assertSame(3.14, $sequential[3]);
        $this->assertNull($sequential[4]);
    }

    // ==================== CASE SENSITIVE TESTS ====================

    public function test_sequential_is_case_sensitive(): void
    {
        $sequential = new Sequential(['A', 'B', 'C']);

        $this->assertTrue($sequential->contains('A'));
        $this->assertFalse($sequential->contains('a'));
        $this->assertSame(0, $sequential->indexOf('A'));
        $this->assertNull($sequential->indexOf('a'));
    }

    public function test_sequential_with_mixed_case_remove(): void
    {
        $sequential = new Sequential(['A', 'B', 'C']);

        $result = $sequential->removeElement('a');
        $this->assertSame(['A', 'B', 'C'], $result->toArray());
    }

    // ==================== ADD METHOD TESTS ====================

    public function test_add_appends_item_to_end(): void
    {
        $sequential = new Sequential([1, 2, 3]);
        $newSequential = $sequential->add(4);

        $this->assertNotSame($sequential, $newSequential);
        $this->assertSame([1, 2, 3, 4], $newSequential->toArray());
        $this->assertSame([1, 2, 3], $sequential->toArray());
    }

    public function test_add_normalizes_item(): void
    {
        $sequential = new Sequential([1, 2]);
        $email = TestEmailAddress::from('test@example.com');
        $newSequential = $sequential->add($email);

        $this->assertSame('test@example.com', $newSequential[2]);
    }

    public function test_add_returns_new_instance(): void
    {
        $sequential = new Sequential([1]);
        $newSequential = $sequential->add(2);

        $this->assertNotSame($sequential, $newSequential);
    }

    public function test_add_chaining_works(): void
    {
        $sequential = new Sequential;
        $result = $sequential->add(1)->add(2)->add(3);

        $this->assertSame([1, 2, 3], $result->toArray());
    }

    // ==================== PREPEND METHOD TESTS ====================

    public function test_prepend_adds_item_to_beginning(): void
    {
        $sequential = new Sequential([1, 2, 3]);
        $newSequential = $sequential->prepend(0);

        $this->assertSame([0, 1, 2, 3], $newSequential->toArray());
        $this->assertSame([1, 2, 3], $sequential->toArray());
    }

    public function test_prepend_normalizes_item(): void
    {
        $sequential = new Sequential([1, 2]);
        $email = TestEmailAddress::from('test@example.com');
        $newSequential = $sequential->prepend($email);

        $this->assertSame('test@example.com', $newSequential[0]);
        $this->assertSame(1, $newSequential[1]);
        $this->assertSame(2, $newSequential[2]);
    }

    // ==================== INSERT METHOD TESTS ====================

    public function test_insert_adds_item_at_specific_position(): void
    {
        $sequential = new Sequential([1, 2, 3]);
        $newSequential = $sequential->insert(1, 99);

        $this->assertSame([1, 99, 2, 3], $newSequential->toArray());
    }

    public function test_insert_at_beginning_works(): void
    {
        $sequential = new Sequential([1, 2, 3]);
        $newSequential = $sequential->insert(0, 99);

        $this->assertSame([99, 1, 2, 3], $newSequential->toArray());
    }

    public function test_insert_at_end_works(): void
    {
        $sequential = new Sequential([1, 2, 3]);
        $newSequential = $sequential->insert(3, 99);

        $this->assertSame([1, 2, 3, 99], $newSequential->toArray());
    }

    public function test_insert_throws_exception_for_invalid_index(): void
    {
        $sequential = new Sequential([1, 2, 3]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Index 5 is out of range');

        $sequential->insert(5, 99);
    }

    public function test_insert_normalizes_item(): void
    {
        $sequential = new Sequential([1, 2]);
        $email = TestEmailAddress::from('test@example.com');
        $newSequential = $sequential->insert(1, $email);

        $this->assertSame('test@example.com', $newSequential[1]);
    }

    // ==================== REMOVE METHOD TESTS ====================

    public function test_remove_removes_item_at_index(): void
    {
        $sequential = new Sequential([1, 2, 3, 4, 5]);
        $newSequential = $sequential->remove(2);

        $this->assertSame([1, 2, 4, 5], $newSequential->toArray());
        $this->assertSame([1, 2, 3, 4, 5], $sequential->toArray());
    }

    public function test_remove_throws_exception_for_invalid_index(): void
    {
        $sequential = new Sequential([1, 2, 3]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Index 5 is out of range');

        $sequential->remove(5);
    }

    // ==================== REMOVE ELEMENT METHOD TESTS ====================

    public function test_remove_element_removes_first_occurrence(): void
    {
        $sequential = new Sequential([1, 2, 3, 2, 4]);
        $newSequential = $sequential->removeElement(2);

        $this->assertSame([1, 3, 2, 4], $newSequential->toArray());
    }

    public function test_remove_element_does_nothing_if_not_found(): void
    {
        $sequential = new Sequential([1, 2, 3]);
        $newSequential = $sequential->removeElement(99);

        $this->assertSame($sequential, $newSequential);
    }

    // ==================== REPLACE METHOD TESTS ====================

    public function test_replace_replaces_item_at_index(): void
    {
        $sequential = new Sequential([1, 2, 3, 4, 5]);
        $newSequential = $sequential->replace(2, 99);

        $this->assertSame([1, 2, 99, 4, 5], $newSequential->toArray());
    }

    public function test_replace_normalizes_item(): void
    {
        $sequential = new Sequential([1, 2, 3]);
        $email = TestEmailAddress::from('test@example.com');
        $newSequential = $sequential->replace(1, $email);

        $this->assertSame('test@example.com', $newSequential[1]);
    }

    public function test_replace_throws_exception_for_invalid_index(): void
    {
        $sequential = new Sequential([1, 2, 3]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Index 5 is out of range');

        $sequential->replace(5, 99);
    }

    // ==================== GET METHOD TESTS ====================

    public function test_get_returns_item_at_index(): void
    {
        $sequential = new Sequential([1, 2, 3, 4, 5]);

        $this->assertSame(1, $sequential->get(0));
        $this->assertSame(3, $sequential->get(2));
        $this->assertSame(5, $sequential->get(4));
    }

    public function test_get_returns_null_for_invalid_index(): void
    {
        $sequential = new Sequential([1, 2, 3]);

        $this->assertNull($sequential->get(5));
        $this->assertNull($sequential->get(-1));
    }

    // ==================== FIRST AND LAST TESTS ====================

    public function test_first_returns_first_item(): void
    {
        $sequential = new Sequential([1, 2, 3, 4, 5]);

        $this->assertSame(1, $sequential->first());
    }

    public function test_first_returns_null_for_empty(): void
    {
        $sequential = new Sequential;

        $this->assertNull($sequential->first());
    }

    public function test_last_returns_last_item(): void
    {
        $sequential = new Sequential([1, 2, 3, 4, 5]);

        $this->assertSame(5, $sequential->last());
    }

    public function test_last_returns_null_for_empty(): void
    {
        $sequential = new Sequential;

        $this->assertNull($sequential->last());
    }

    // ==================== INDEX OF TESTS ====================

    public function test_index_of_returns_correct_index(): void
    {
        $sequential = new Sequential([1, 2, 3, 2, 4]);

        $this->assertSame(0, $sequential->indexOf(1));
        $this->assertSame(1, $sequential->indexOf(2));
        $this->assertSame(2, $sequential->indexOf(3));
        $this->assertSame(4, $sequential->indexOf(4));
    }

    public function test_index_of_returns_null_if_not_found(): void
    {
        $sequential = new Sequential([1, 2, 3]);

        $this->assertNull($sequential->indexOf(99));
    }

    // ==================== CONTAINS TESTS ====================

    public function test_contains_returns_true_if_item_exists(): void
    {
        $sequential = new Sequential([1, 2, 3, 4, 5]);

        $this->assertTrue($sequential->contains(1));
        $this->assertTrue($sequential->contains(3));
        $this->assertTrue($sequential->contains(5));
    }

    public function test_contains_returns_false_if_item_not_found(): void
    {
        $sequential = new Sequential([1, 2, 3]);

        $this->assertFalse($sequential->contains(99));
    }

    // ==================== SLICE TESTS ====================

    public function test_slice_returns_subset(): void
    {
        $sequential = new Sequential([1, 2, 3, 4, 5, 6, 7, 8, 9, 10]);

        $slice = $sequential->slice(2, 4);
        $this->assertSame([3, 4, 5, 6], $slice->toArray());
    }

    public function test_slice_without_length_goes_to_end(): void
    {
        $sequential = new Sequential([1, 2, 3, 4, 5]);

        $slice = $sequential->slice(2);
        $this->assertSame([3, 4, 5], $slice->toArray());
    }

    public function test_slice_returns_new_instance(): void
    {
        $sequential = new Sequential([1, 2, 3, 4, 5]);
        $slice = $sequential->slice(1, 2);

        $this->assertNotSame($sequential, $slice);
    }

    // ==================== TAKE AND SKIP TESTS ====================

    public function test_take_returns_first_n_items(): void
    {
        $sequential = new Sequential([1, 2, 3, 4, 5]);

        $taken = $sequential->take(3);
        $this->assertSame([1, 2, 3], $taken->toArray());
    }

    public function test_take_with_n_less_than_count_works(): void
    {
        $sequential = new Sequential([1, 2, 3, 4, 5]);

        $taken = $sequential->take(2);
        $this->assertSame([1, 2], $taken->toArray());
    }

    public function test_skip_skips_first_n_items(): void
    {
        $sequential = new Sequential([1, 2, 3, 4, 5]);

        $skipped = $sequential->skip(2);
        $this->assertSame([3, 4, 5], $skipped->toArray());
    }

    // ==================== FILTER TESTS ====================

    public function test_filter_keeps_items_satisfying_callback(): void
    {
        $sequential = new Sequential([1, 2, 3, 4, 5, 6, 7, 8, 9, 10]);

        $filtered = $sequential->filter(fn ($item) => $item % 2 === 0);
        $this->assertSame([2, 4, 6, 8, 10], $filtered->toArray());
    }

    public function test_filter_returns_new_instance(): void
    {
        $sequential = new Sequential([1, 2, 3, 4, 5]);
        $filtered = $sequential->filter(fn ($item) => $item > 2);

        $this->assertNotSame($sequential, $filtered);
        $this->assertSame([1, 2, 3, 4, 5], $sequential->toArray());
    }

    // ==================== MAP TESTS ====================

    public function test_map_transforms_each_item(): void
    {
        $sequential = new Sequential([1, 2, 3, 4, 5]);

        $mapped = $sequential->map(fn ($item) => $item * 2);
        $this->assertSame([2, 4, 6, 8, 10], $mapped->toArray());
    }

    public function test_map_returns_new_instance(): void
    {
        $sequential = new Sequential([1, 2, 3]);
        $mapped = $sequential->map(fn ($item) => $item * 2);

        $this->assertNotSame($sequential, $mapped);
    }

    public function test_map_with_string_transformation(): void
    {
        $sequential = new Sequential([1, 2, 3]);

        $mapped = $sequential->map(fn ($item) => "Number: {$item}");
        $this->assertSame(['Number: 1', 'Number: 2', 'Number: 3'], $mapped->toArray());
    }

    // ==================== REDUCE TESTS ====================

    public function test_reduce_aggregates_values(): void
    {
        $sequential = new Sequential([1, 2, 3, 4, 5]);

        $sum = $sequential->reduce(fn ($carry, $item) => $carry + $item, 0);
        $this->assertSame(15, $sum);
    }

    public function test_reduce_with_string_concatenation(): void
    {
        $sequential = new Sequential(['Hello', ' ', 'World', '!']);

        $result = $sequential->reduce(fn ($carry, $item) => $carry.$item, '');
        $this->assertSame('Hello World!', $result);
    }

    public function test_reduce_on_empty_returns_initial(): void
    {
        $sequential = new Sequential;

        $result = $sequential->reduce(fn ($carry, $item) => $carry + $item, 100);
        $this->assertSame(100, $result);
    }

    // ==================== REVERSE TESTS ====================

    public function test_reverse_reverses_order(): void
    {
        $sequential = new Sequential([1, 2, 3, 4, 5]);

        $reversed = $sequential->reverse();
        $this->assertSame([5, 4, 3, 2, 1], $reversed->toArray());
    }

    public function test_reverse_returns_new_instance(): void
    {
        $sequential = new Sequential([1, 2, 3]);
        $reversed = $sequential->reverse();

        $this->assertNotSame($sequential, $reversed);
    }

    // ==================== SORT TESTS ====================

    public function test_sort_orders_ascending_by_default(): void
    {
        $sequential = new Sequential([5, 2, 8, 1, 9, 3]);

        $sorted = $sequential->sort();
        $this->assertSame([1, 2, 3, 5, 8, 9], $sorted->toArray());
    }

    public function test_sort_with_custom_callback(): void
    {
        $sequential = new Sequential([5, 2, 8, 1, 9, 3]);

        $sorted = $sequential->sort(fn ($a, $b) => $b <=> $a);
        $this->assertSame([9, 8, 5, 3, 2, 1], $sorted->toArray());
    }

    public function test_sort_returns_new_instance(): void
    {
        $sequential = new Sequential([3, 1, 2]);
        $sorted = $sequential->sort();

        $this->assertNotSame($sequential, $sorted);
    }

    // ==================== MERGE TESTS ====================

    public function test_merge_combines_two_sequences(): void
    {
        $seq1 = new Sequential([1, 2, 3]);
        $seq2 = new Sequential([4, 5, 6]);

        $merged = $seq1->merge($seq2);
        $this->assertSame([1, 2, 3, 4, 5, 6], $merged->toArray());
    }

    public function test_merge_returns_new_instance(): void
    {
        $seq1 = new Sequential([1, 2]);
        $seq2 = new Sequential([3, 4]);

        $merged = $seq1->merge($seq2);
        $this->assertNotSame($seq1, $merged);
        $this->assertNotSame($seq2, $merged);
    }

    public function test_merge_array_combines_with_array(): void
    {
        $seq = new Sequential([1, 2, 3]);

        $merged = $seq->mergeArray([4, 5, 6]);
        $this->assertSame([1, 2, 3, 4, 5, 6], $merged->toArray());
    }

    // ==================== EMPTINESS TESTS ====================

    public function test_is_empty_returns_true_for_empty(): void
    {
        $sequential = new Sequential;

        $this->assertTrue($sequential->isEmpty());
        $this->assertFalse($sequential->isNotEmpty());
    }

    public function test_is_empty_returns_false_for_non_empty(): void
    {
        $sequential = new Sequential([1]);

        $this->assertFalse($sequential->isEmpty());
        $this->assertTrue($sequential->isNotEmpty());
    }

    // ==================== COUNT TESTS ====================

    public function test_count_returns_correct_number(): void
    {
        $sequential = new Sequential([1, 2, 3, 4, 5]);

        $this->assertCount(5, $sequential);
        $this->assertSame(5, $sequential->count());
    }

    public function test_count_on_empty_returns_zero(): void
    {
        $sequential = new Sequential;

        $this->assertCount(0, $sequential);
        $this->assertSame(0, $sequential->count());
    }

    // ==================== ITERATOR TESTS ====================

    public function test_sequential_is_iterable(): void
    {
        $sequential = new Sequential([1, 2, 3, 4, 5]);
        $items = [];

        foreach ($sequential as $item) {
            $items[] = $item;
        }

        $this->assertSame([1, 2, 3, 4, 5], $items);
    }

    public function test_get_iterator_returns_array_iterator(): void
    {
        $sequential = new Sequential([1, 2, 3]);

        $iterator = $sequential->getIterator();
        $this->assertInstanceOf(\ArrayIterator::class, $iterator);
        $this->assertSame([1, 2, 3], iterator_to_array($iterator));
    }

    // ==================== ARRAY ACCESS TESTS ====================

    public function test_array_access_offset_get_works(): void
    {
        $sequential = new Sequential([1, 2, 3]);

        $this->assertSame(1, $sequential[0]);
        $this->assertSame(2, $sequential[1]);
        $this->assertSame(3, $sequential[2]);
    }

    public function test_array_access_offset_exists_works(): void
    {
        $sequential = new Sequential([1, 2, 3]);

        $this->assertTrue(isset($sequential[0]));
        $this->assertTrue(isset($sequential[2]));
        $this->assertFalse(isset($sequential[3]));
    }

    public function test_array_access_offset_set_throws_exception(): void
    {
        $sequential = new Sequential([1, 2, 3]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('immutable');

        $sequential[0] = 99;
    }

    public function test_array_access_offset_unset_throws_exception(): void
    {
        $sequential = new Sequential([1, 2, 3]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('immutable');

        unset($sequential[0]);
    }

    // ==================== TO ARRAY TESTS ====================

    public function test_to_array_returns_plain_array(): void
    {
        $sequential = new Sequential([1, 2, 3, 4, 5]);

        $this->assertSame([1, 2, 3, 4, 5], $sequential->toArray());
    }

    public function test_to_array_on_empty_returns_empty_array(): void
    {
        $sequential = new Sequential;

        $this->assertSame([], $sequential->toArray());
    }

    // ==================== JSON TESTS ====================

    public function test_to_json_returns_json_string(): void
    {
        $sequential = new Sequential([1, 2, 3]);

        $this->assertSame('[1,2,3]', $sequential->toJson());
    }

    public function test_to_string_returns_json_string(): void
    {
        $sequential = new Sequential([1, 2, 3]);

        $this->assertSame('[1,2,3]', (string) $sequential);
    }

    public function test_json_serialize_works(): void
    {
        $sequential = new Sequential([1, 2, 3]);

        $serialized = json_encode($sequential);
        $this->assertSame('[1,2,3]', $serialized);
    }

    // ==================== FROM METHOD TESTS ====================

    public function test_from_creates_from_array(): void
    {
        $sequential = Sequential::from([1, 2, 3, 4, 5]);

        $this->assertInstanceOf(Sequential::class, $sequential);
        $this->assertSame([1, 2, 3, 4, 5], $sequential->toArray());
    }

    public function test_from_creates_from_object(): void
    {
        $obj = new \stdClass;
        $obj->items = [1, 2, 3];
        $obj->other = 'test';

        $sequential = Sequential::from($obj);

        $this->assertInstanceOf(Sequential::class, $sequential);
        $this->assertSame([1, 2, 3, 'test'], $sequential->toArray());
    }

    public function test_from_returns_same_instance_if_already_sequential(): void
    {
        $original = new Sequential([1, 2, 3]);

        $result = Sequential::from($original);

        $this->assertSame($original, $result);
    }

    public function test_from_throws_exception_for_invalid_source(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot create');

        Sequential::from(new \DateTime);
    }

    // ==================== FROM JSON TESTS ====================

    public function test_from_json_creates_from_valid_json(): void
    {
        $json = '[1,2,3,4,5]';

        $sequential = Sequential::fromJson($json);

        $this->assertInstanceOf(Sequential::class, $sequential);
        $this->assertSame([1, 2, 3, 4, 5], $sequential->toArray());
    }

    public function test_from_json_creates_from_json_with_mixed_types(): void
    {
        $json = '[1,"hello",true,3.14,null]';

        $sequential = Sequential::fromJson($json);

        $this->assertCount(5, $sequential);
        $this->assertSame(1, $sequential[0]);
        $this->assertSame('hello', $sequential[1]);
        $this->assertTrue($sequential[2]);
        $this->assertSame(3.14, $sequential[3]);
        $this->assertNull($sequential[4]);
    }

    public function test_from_json_throws_exception_for_invalid_json(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid JSON');

        Sequential::fromJson('{invalid json}');
    }

    // ==================== COLLECT METHOD TESTS ====================

    public function test_collect_creates_sequential_from_iterable(): void
    {
        $sources = [1, 2, 3, 4, 5];

        $collection = Sequential::collect($sources);

        $this->assertInstanceOf(Sequential::class, $collection);
        $this->assertSame([1, 2, 3, 4, 5], $collection->toArray());
    }

    public function test_collect_with_custom_sequential_class(): void
    {
        $sources = [1, 2, 3];

        $collection = Sequential::collect($sources, Sequential::class);

        $this->assertInstanceOf(Sequential::class, $collection);
        $this->assertSame([1, 2, 3], $collection->toArray());
    }

    public function test_collect_with_sequential_class(): void
    {
        $sources = ['A', 'B', 'C'];

        $collection = Sequential::collect($sources, Sequential::class);

        $this->assertInstanceOf(Sequential::class, $collection);
        $this->assertTrue($collection->contains('A'));
        $this->assertFalse($collection->contains('a'));
        $this->assertSame(0, $collection->indexOf('A'));
        $this->assertNull($collection->indexOf('a'));
    }

    public function test_collect_handles_objects(): void
    {
        $record = new TestUserRecord(name: 'John', email: $this->testEmail);
        $sources = [$record];

        $collection = Sequential::collect($sources);

        // ✅ Nouveau comportement : 1 élément (l'objet normalisé)
        $this->assertCount(1, $collection);
        $this->assertIsArray($collection[0]);
        $this->assertArrayHasKey('name', $collection[0]);
        $this->assertSame('John', $collection[0]['name']);
        $this->assertSame('test@example.com', $collection[0]['email']);
    }

    public function test_collect_throws_exception_for_invalid_class(): void
    {
        $sources = [1, 2, 3];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('must extend');

        Sequential::collect($sources, \stdClass::class);
    }

    // ==================== NORMALIZATION TESTS ====================

    public function test_normalize_returns_array(): void
    {
        $sequential = new Sequential([1, 2, 3]);

        $result = NormalizerChain::get()->normalize($sequential);

        $this->assertIsArray($result);
        $this->assertSame([1, 2, 3], $result);
    }

    public function test_normalize_with_objects_returns_normalized_array(): void
    {
        $record = new TestUserRecord(name: 'John', email: $this->testEmail);
        $sequential = new Sequential([$record]);

        $result = NormalizerChain::get()->normalize($sequential);

        $this->assertIsArray($result);
        $this->assertIsArray($result[0]);
        $this->assertArrayHasKey('name', $result[0]);
        $this->assertSame('John', $result[0]['name']);
    }

    public function test_normalize_on_empty_returns_empty_array(): void
    {
        $sequential = new Sequential;

        $result = NormalizerChain::get()->normalize($sequential);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    // ==================== EDGE CASES TESTS ====================

    public function test_sequential_with_duplicates_keeps_all(): void
    {
        $sequential = new Sequential([1, 2, 2, 3, 3, 3]);

        $this->assertCount(6, $sequential);
        $this->assertSame([1, 2, 2, 3, 3, 3], $sequential->toArray());
    }

    public function test_sequential_with_enum_values(): void
    {
        $sequential = new Sequential([TestUserStatus::ACTIVE, TestUserStatus::INACTIVE]);

        $this->assertCount(2, $sequential);
        $this->assertSame('active', $sequential[0]);
        $this->assertSame('inactive', $sequential[1]);
    }

    public function test_sequential_with_null_values(): void
    {
        $sequential = new Sequential([1, null, 3, null, 5]);

        $this->assertCount(5, $sequential);
        $this->assertSame(1, $sequential[0]);
        $this->assertNull($sequential[1]);
        $this->assertSame(3, $sequential[2]);
        $this->assertNull($sequential[3]);
        $this->assertSame(5, $sequential[4]);
    }

    public function test_immutability_of_sequential(): void
    {
        $sequential = new Sequential([1, 2, 3]);
        $original = $sequential->toArray();

        $sequential->add(4);
        $sequential->remove(0);
        $sequential->replace(1, 99);

        $this->assertSame([1, 2, 3], $original);
        $this->assertSame([1, 2, 3], $sequential->toArray());
    }

    // ==================== CHAINING TESTS ====================

    public function test_chaining_multiple_operations(): void
    {
        $sequential = new Sequential([1, 2, 3, 4, 5, 6, 7, 8, 9, 10]);

        $result = $sequential
            ->filter(fn ($item) => $item % 2 === 0)
            ->map(fn ($item) => $item * 2)
            ->reverse()
            ->take(3);

        $this->assertSame([20, 16, 12], $result->toArray());
    }

    public function test_chaining_with_case_sensitive(): void
    {
        $sequential = new Sequential(['Apple', 'Banana', 'Cherry', 'apple']);

        $result = $sequential
            ->filter(fn ($item) => str_contains($item, 'a'))
            ->map(fn ($item) => strtoupper($item));

        // ✅ Correction : 'Apple' ne contient pas 'a' minuscule
        $this->assertSame(['BANANA', 'APPLE'], $result->toArray());
    }
}
