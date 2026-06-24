<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Unit\Collections\Utility;

use AndyDefer\DomainStructures\Tests\Fixtures\Records\TestUserRecord;
use AndyDefer\DomainStructures\Tests\Fixtures\ValueObjects\TestEmailAddress;
use AndyDefer\DomainStructures\Tests\TestCase;
use AndyDefer\DomainStructures\Utils\ListCollection;

final class ListCollectionTest extends TestCase
{
    private TestEmailAddress $testEmail;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testEmail = TestEmailAddress::from('test@example.com');
    }

    // ==================== CONSTRUCTION TESTS ====================

    public function test_can_be_created_empty(): void
    {
        $list = new ListCollection;

        $this->assertTrue($list->isEmpty());
        $this->assertCount(0, $list);
        $this->assertSame([], $list->toArray());
    }

    public function test_can_be_created_with_items(): void
    {
        $list = new ListCollection([1, 2, 3, 4, 5]);

        $this->assertCount(5, $list);
        $this->assertSame([1, 2, 3, 4, 5], $list->toArray());
    }

    public function test_reindexes_items_on_construction(): void
    {
        $list = new ListCollection([1 => 'a', 3 => 'b', 5 => 'c']);

        $this->assertSame([0 => 'a', 1 => 'b', 2 => 'c'], $list->toArray());
    }

    public function test_normalizes_items_on_construction(): void
    {
        $email = TestEmailAddress::from('test@example.com');
        $list = new ListCollection([$email]);

        $this->assertCount(1, $list);
        $this->assertSame('test@example.com', $list[0]);
    }

    // ==================== TRANSFORMABLE TESTS ====================

    public function test_from_creates_from_array(): void
    {
        $list = ListCollection::from([1, 2, 3]);

        $this->assertInstanceOf(ListCollection::class, $list);
        $this->assertSame([1, 2, 3], $list->toArray());
    }

    public function test_from_creates_from_object(): void
    {
        $obj = new \stdClass;
        $obj->a = 1;
        $obj->b = 2;

        $list = ListCollection::from($obj);

        $this->assertSame([1, 2], $list->toArray());
    }

    public function test_from_creates_from_transformable_object(): void
    {
        $record = new TestUserRecord(name: 'John', email: $this->testEmail);
        $list = ListCollection::from($record);

        $this->assertCount(1, $list);
        $this->assertIsArray($list[0]);
        $this->assertArrayHasKey('name', $list[0]);
        $this->assertSame('John', $list[0]['name']);
    }

    public function test_from_creates_from_scalar(): void
    {
        $list = ListCollection::from('hello');

        $this->assertSame(['hello'], $list->toArray());
    }

    public function test_from_returns_same_instance_if_already_list(): void
    {
        $original = new ListCollection([1, 2, 3]);
        $result = ListCollection::from($original);

        $this->assertSame($original, $result);
    }

    public function test_from_throws_exception_for_invalid_source(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot create');

        ListCollection::from(new \DateTime);
    }

    public function test_from_json_creates_from_valid_json(): void
    {
        $json = '[1,2,3,4,5]';
        $list = ListCollection::fromJson($json);

        $this->assertSame([1, 2, 3, 4, 5], $list->toArray());
    }

    public function test_from_json_throws_exception_for_invalid_json(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid JSON');

        ListCollection::fromJson('{invalid json}');
    }

    public function test_collect_creates_from_iterable(): void
    {
        $sources = [1, 2, 3, 4, 5];
        $list = ListCollection::collect($sources);

        $this->assertSame([1, 2, 3, 4, 5], $list->toArray());
    }

    public function test_collect_handles_objects(): void
    {
        $record = new TestUserRecord(name: 'John', email: $this->testEmail);
        $list = ListCollection::collect([$record]);

        $this->assertCount(1, $list);
        $this->assertIsArray($list[0]);
        $this->assertArrayHasKey('name', $list[0]);
        $this->assertSame('John', $list[0]['name']);
    }

    // ==================== FIRST / LAST / GET TESTS ====================

    public function test_first_returns_first_item(): void
    {
        $list = new ListCollection([1, 2, 3]);

        $this->assertSame(1, $list->first());
    }

    public function test_first_returns_null_when_empty(): void
    {
        $list = new ListCollection;

        $this->assertNull($list->first());
    }

    public function test_last_returns_last_item(): void
    {
        $list = new ListCollection([1, 2, 3]);

        $this->assertSame(3, $list->last());
    }

    public function test_last_returns_null_when_empty(): void
    {
        $list = new ListCollection;

        $this->assertNull($list->last());
    }

    public function test_get_returns_item_at_index(): void
    {
        $list = new ListCollection([1, 2, 3]);

        $this->assertSame(1, $list->get(0));
        $this->assertSame(2, $list->get(1));
        $this->assertSame(3, $list->get(2));
    }

    public function test_get_returns_null_for_invalid_index(): void
    {
        $list = new ListCollection([1, 2, 3]);

        $this->assertNull($list->get(5));
        $this->assertNull($list->get(-1));
    }

    // ==================== INDEX OF / CONTAINS TESTS ====================

    public function test_index_of_returns_correct_index(): void
    {
        $list = new ListCollection(['a', 'b', 'c', 'b']);

        $this->assertSame(0, $list->indexOf('a'));
        $this->assertSame(1, $list->indexOf('b')); // ✅ Première occurrence
        $this->assertNull($list->indexOf('z'));
    }

    public function test_contains_returns_true_if_item_exists(): void
    {
        $list = new ListCollection([1, 2, 3]);

        $this->assertTrue($list->contains(1));
        $this->assertTrue($list->contains(2));
        $this->assertTrue($list->contains(3));
    }

    public function test_contains_returns_false_if_item_not_found(): void
    {
        $list = new ListCollection([1, 2, 3]);

        $this->assertFalse($list->contains(99));
    }

    public function test_contains_is_case_sensitive(): void
    {
        $list = new ListCollection(['Apple', 'Banana']);

        $this->assertTrue($list->contains('Apple'));
        $this->assertFalse($list->contains('apple'));
    }

    // ==================== ADD / PREPEND / INSERT TESTS ====================

    public function test_add_appends_item_to_end(): void
    {
        $list = new ListCollection([1, 2, 3]);
        $new = $list->add(4);

        $this->assertNotSame($list, $new);
        $this->assertSame([1, 2, 3, 4], $new->toArray());
        $this->assertSame([1, 2, 3], $list->toArray());
    }

    public function test_add_normalizes_item(): void
    {
        $list = new ListCollection([1, 2]);
        $email = TestEmailAddress::from('test@example.com');
        $new = $list->add($email);

        $this->assertSame('test@example.com', $new[2]);
    }

    public function test_add_chaining_works(): void
    {
        $list = new ListCollection;
        $result = $list->add(1)->add(2)->add(3);

        $this->assertSame([1, 2, 3], $result->toArray());
    }

    public function test_prepend_adds_item_to_beginning(): void
    {
        $list = new ListCollection([2, 3, 4]);
        $new = $list->prepend(1);

        $this->assertSame([1, 2, 3, 4], $new->toArray());
    }

    public function test_insert_adds_item_at_specific_position(): void
    {
        $list = new ListCollection([1, 2, 4, 5]);
        $new = $list->insert(2, 3);

        $this->assertSame([1, 2, 3, 4, 5], $new->toArray());
    }

    public function test_insert_at_beginning_works(): void
    {
        $list = new ListCollection([2, 3, 4]);
        $new = $list->insert(0, 1);

        $this->assertSame([1, 2, 3, 4], $new->toArray());
    }

    public function test_insert_at_end_works(): void
    {
        $list = new ListCollection([1, 2, 3]);
        $new = $list->insert(3, 4);

        $this->assertSame([1, 2, 3, 4], $new->toArray());
    }

    public function test_insert_throws_exception_for_invalid_index(): void
    {
        $list = new ListCollection([1, 2, 3]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Index 5 is out of range');

        $list->insert(5, 99);
    }

    // ==================== REMOVE TESTS ====================

    public function test_remove_at_removes_item_at_index(): void
    {
        $list = new ListCollection([1, 2, 3, 4, 5]);
        $new = $list->removeAt(2);

        $this->assertSame([1, 2, 4, 5], $new->toArray());
    }

    public function test_remove_at_throws_exception_for_invalid_index(): void
    {
        $list = new ListCollection([1, 2, 3]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Index 5 is out of range');

        $list->removeAt(5);
    }

    public function test_remove_removes_first_occurrence(): void
    {
        $list = new ListCollection([1, 2, 3, 2, 4]);
        $new = $list->remove(2);

        $this->assertSame([1, 3, 2, 4], $new->toArray());
    }

    public function test_remove_does_nothing_if_not_found(): void
    {
        $list = new ListCollection([1, 2, 3]);
        $new = $list->remove(99);

        $this->assertSame($list, $new);
    }

    public function test_replace_replaces_item_at_index(): void
    {
        $list = new ListCollection([1, 2, 3, 4, 5]);
        $new = $list->replace(2, 99);

        $this->assertSame([1, 2, 99, 4, 5], $new->toArray());
    }

    public function test_replace_throws_exception_for_invalid_index(): void
    {
        $list = new ListCollection([1, 2, 3]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Index 5 is out of range');

        $list->replace(5, 99);
    }

    // ==================== FILTER / MAP / REDUCE TESTS ====================

    public function test_filter_keeps_items_satisfying_callback(): void
    {
        $list = new ListCollection([1, 2, 3, 4, 5, 6, 7, 8, 9, 10]);

        $filtered = $list->filter(fn ($item) => $item % 2 === 0);

        $this->assertSame([2, 4, 6, 8, 10], $filtered->toArray());
    }

    public function test_filter_reindexes_keys(): void
    {
        $list = new ListCollection(['Apple', 'Banana', 'Cherry', 'apple']);

        $filtered = $list->filter(fn ($item) => str_contains($item, 'a'));

        $this->assertSame(['Banana', 'apple'], $filtered->toArray());
        $this->assertSame([0, 1], array_keys($filtered->toArray()));
    }

    public function test_map_transforms_each_item(): void
    {
        $list = new ListCollection([1, 2, 3, 4, 5]);

        $mapped = $list->map(fn ($item) => $item * 2);

        $this->assertSame([2, 4, 6, 8, 10], $mapped->toArray());
    }

    public function test_map_reindexes_keys(): void
    {
        $list = new ListCollection(['Apple', 'Banana', 'Cherry', 'apple']);

        $mapped = $list->map(fn ($item) => strtoupper($item));

        $this->assertSame(['APPLE', 'BANANA', 'CHERRY', 'APPLE'], $mapped->toArray());
        $this->assertSame([0, 1, 2, 3], array_keys($mapped->toArray()));
    }

    public function test_chaining_filter_and_map_preserves_order(): void
    {
        $list = new ListCollection(['Apple', 'Banana', 'Cherry', 'apple']);

        $result = $list
            ->filter(fn ($item) => str_contains($item, 'a'))
            ->map(fn ($item) => strtoupper($item));

        $this->assertSame(['BANANA', 'APPLE'], $result->toArray());
    }

    public function test_reduce_aggregates_values(): void
    {
        $list = new ListCollection([1, 2, 3, 4, 5]);

        $sum = $list->reduce(fn ($carry, $item) => $carry + $item, 0);

        $this->assertSame(15, $sum);
    }

    // ==================== REVERSE / SORT TESTS ====================

    public function test_reverse_reverses_order(): void
    {
        $list = new ListCollection([1, 2, 3, 4, 5]);

        $reversed = $list->reverse();

        $this->assertSame([5, 4, 3, 2, 1], $reversed->toArray());
    }

    public function test_sort_orders_ascending_by_default(): void
    {
        $list = new ListCollection([5, 2, 8, 1, 9, 3]);

        $sorted = $list->sort();

        $this->assertSame([1, 2, 3, 5, 8, 9], $sorted->toArray());
    }

    public function test_sort_with_custom_callback(): void
    {
        $list = new ListCollection([5, 2, 8, 1, 9, 3]);

        $sorted = $list->sort(fn ($a, $b) => $b <=> $a);

        $this->assertSame([9, 8, 5, 3, 2, 1], $sorted->toArray());
    }

    // ==================== SLICE / TAKE / SKIP TESTS ====================

    public function test_slice_returns_subset(): void
    {
        $list = new ListCollection([1, 2, 3, 4, 5, 6, 7, 8, 9, 10]);

        $slice = $list->slice(2, 4);

        $this->assertSame([3, 4, 5, 6], $slice->toArray());
    }

    public function test_slice_without_length_goes_to_end(): void
    {
        $list = new ListCollection([1, 2, 3, 4, 5]);

        $slice = $list->slice(2);

        $this->assertSame([3, 4, 5], $slice->toArray());
    }

    public function test_take_returns_first_n_items(): void
    {
        $list = new ListCollection([1, 2, 3, 4, 5]);

        $taken = $list->take(3);

        $this->assertSame([1, 2, 3], $taken->toArray());
    }

    public function test_skip_skips_first_n_items(): void
    {
        $list = new ListCollection([1, 2, 3, 4, 5]);

        $skipped = $list->skip(2);

        $this->assertSame([3, 4, 5], $skipped->toArray());
    }

    // ==================== MERGE TESTS ====================

    public function test_merge_combines_two_lists(): void
    {
        $list1 = new ListCollection([1, 2, 3]);
        $list2 = new ListCollection([4, 5, 6]);

        $merged = $list1->merge($list2);

        $this->assertSame([1, 2, 3, 4, 5, 6], $merged->toArray());
    }

    public function test_merge_array_combines_with_array(): void
    {
        $list = new ListCollection([1, 2, 3]);

        $merged = $list->mergeArray([4, 5, 6]);

        $this->assertSame([1, 2, 3, 4, 5, 6], $merged->toArray());
    }

    // ==================== ARRAY ACCESS TESTS ====================

    public function test_array_access_works(): void
    {
        $list = new ListCollection([1, 2, 3]);

        $this->assertTrue(isset($list[0]));
        $this->assertSame(1, $list[0]);
        $this->assertFalse(isset($list[3]));
    }

    public function test_array_access_offset_set_throws_exception(): void
    {
        $list = new ListCollection([1, 2, 3]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('immutable');

        $list[0] = 99;
    }

    public function test_array_access_offset_unset_throws_exception(): void
    {
        $list = new ListCollection([1, 2, 3]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('immutable');

        unset($list[0]);
    }

    // ==================== ITERATOR TESTS ====================

    public function test_is_iterable(): void
    {
        $list = new ListCollection([1, 2, 3, 4, 5]);
        $items = [];

        foreach ($list as $item) {
            $items[] = $item;
        }

        $this->assertSame([1, 2, 3, 4, 5], $items);
    }

    // ==================== JSON TESTS ====================

    public function test_to_json_returns_json_string(): void
    {
        $list = new ListCollection([1, 2, 3]);

        $this->assertSame('[1,2,3]', $list->toJson());
    }

    public function test_to_string_returns_json_string(): void
    {
        $list = new ListCollection([1, 2, 3]);

        $this->assertSame('[1,2,3]', (string) $list);
    }

    public function test_json_serialize_works(): void
    {
        $list = new ListCollection([1, 2, 3]);

        $serialized = json_encode($list);

        $this->assertSame('[1,2,3]', $serialized);
    }

    // ==================== IMMUTABILITY TESTS ====================

    public function test_immutability(): void
    {
        $list = new ListCollection([1, 2, 3]);
        $original = $list->toArray();

        $list->add(4);
        $list->removeAt(0);
        $list->replace(1, 99);

        $this->assertSame([1, 2, 3], $original);
        $this->assertSame([1, 2, 3], $list->toArray());
    }
}
