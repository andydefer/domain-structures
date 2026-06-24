<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Unit\Collections\Utility;

use AndyDefer\DomainStructures\Tests\Fixtures\Records\TestUserRecord;
use AndyDefer\DomainStructures\Tests\Fixtures\ValueObjects\TestEmailAddress;
use AndyDefer\DomainStructures\Tests\TestCase;
use AndyDefer\DomainStructures\Utils\SetCollection;

final class SetCollectionTest extends TestCase
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
        $set = new SetCollection;

        $this->assertTrue($set->isEmpty());
        $this->assertCount(0, $set);
        $this->assertSame([], $set->toArray());
    }

    public function test_removes_duplicates_on_construction(): void
    {
        $set = new SetCollection([1, 2, 2, 3, 3, 3]);

        $this->assertCount(3, $set);
        $this->assertSame([1, 2, 3], $set->toArray());
    }

    public function test_removes_duplicates_of_strings(): void
    {
        $set = new SetCollection(['a', 'b', 'a', 'c', 'b']);

        $this->assertSame(['a', 'b', 'c'], $set->toArray());
    }

    public function test_normalizes_items_on_construction(): void
    {
        $email = TestEmailAddress::from('test@example.com');
        $set = new SetCollection([$email]);

        $this->assertCount(1, $set);
        $this->assertSame('test@example.com', $set[0]);
    }

    // ==================== TRANSFORMABLE TESTS ====================

    public function test_from_creates_from_array(): void
    {
        $set = SetCollection::from([1, 2, 3]);

        $this->assertInstanceOf(SetCollection::class, $set);
        $this->assertSame([1, 2, 3], $set->toArray());
    }

    public function test_from_creates_from_object(): void
    {
        $obj = new \stdClass;
        $obj->a = 1;
        $obj->b = 2;

        $set = SetCollection::from($obj);

        $this->assertSame([1, 2], $set->toArray());
    }

    public function test_from_creates_from_transformable_object(): void
    {
        $record = new TestUserRecord(name: 'John', email: $this->testEmail);
        $set = SetCollection::from($record);

        $this->assertCount(1, $set);
        $this->assertIsArray($set[0]);
        $this->assertArrayHasKey('name', $set[0]);
        $this->assertSame('John', $set[0]['name']);
    }

    public function test_from_creates_from_scalar(): void
    {
        $set = SetCollection::from('hello');

        $this->assertSame(['hello'], $set->toArray());
    }

    public function test_from_returns_same_instance_if_already_set(): void
    {
        $original = new SetCollection([1, 2, 3]);
        $result = SetCollection::from($original);

        $this->assertSame($original, $result);
    }

    public function test_from_throws_exception_for_invalid_source(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot create');

        SetCollection::from(new \DateTime);
    }

    public function test_from_json_creates_from_valid_json(): void
    {
        $json = '[1,2,3,4,5]';
        $set = SetCollection::fromJson($json);

        $this->assertSame([1, 2, 3, 4, 5], $set->toArray());
    }

    public function test_from_json_throws_exception_for_invalid_json(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid JSON');

        SetCollection::fromJson('{invalid json}');
    }

    public function test_collect_creates_from_iterable(): void
    {
        $sources = [1, 2, 3, 4, 5];
        $set = SetCollection::collect($sources);

        $this->assertSame([1, 2, 3, 4, 5], $set->toArray());
    }

    public function test_collect_handles_objects(): void
    {
        $record = new TestUserRecord(name: 'John', email: $this->testEmail);
        $set = SetCollection::collect([$record]);

        $this->assertCount(1, $set);
        $this->assertIsArray($set[0]);
        $this->assertArrayHasKey('name', $set[0]);
        $this->assertSame('John', $set[0]['name']);
    }

    // ==================== ADD TESTS ====================

    public function test_add_ignores_duplicates(): void
    {
        $set = new SetCollection([1, 2, 3]);
        $new = $set->add(2);

        $this->assertSame($set, $new);
        $this->assertCount(3, $new);
        $this->assertSame([1, 2, 3], $new->toArray());
    }

    public function test_add_works_with_new_item(): void
    {
        $set = new SetCollection([1, 2, 3]);
        $new = $set->add(4);

        $this->assertNotSame($set, $new);
        $this->assertCount(4, $new);
        $this->assertSame([1, 2, 3, 4], $new->toArray());
    }

    public function test_add_normalizes_item(): void
    {
        $set = new SetCollection([1, 2]);
        $email = TestEmailAddress::from('test@example.com');
        $new = $set->add($email);

        $this->assertCount(3, $new);
        $this->assertSame('test@example.com', $new[2]);
    }

    public function test_add_all_adds_multiple_items_without_duplicates(): void
    {
        $set = new SetCollection([1, 2, 3]);

        $new = $set->addAll([2, 3, 4, 5]);

        $this->assertCount(5, $new);
        $this->assertSame([1, 2, 3, 4, 5], $new->toArray());
    }

    public function test_add_chaining_works(): void
    {
        $set = new SetCollection;
        $result = $set->add(1)->add(2)->add(2)->add(3);

        $this->assertSame([1, 2, 3], $result->toArray());
    }

    // ==================== CONTAINS TESTS ====================

    public function test_contains_returns_true_if_item_exists(): void
    {
        $set = new SetCollection([1, 2, 3]);

        $this->assertTrue($set->contains(1));
        $this->assertTrue($set->contains(2));
        $this->assertTrue($set->contains(3));
    }

    public function test_contains_returns_false_if_item_not_found(): void
    {
        $set = new SetCollection([1, 2, 3]);

        $this->assertFalse($set->contains(99));
    }

    public function test_contains_is_case_sensitive(): void
    {
        $set = new SetCollection(['Apple', 'Banana']);

        $this->assertTrue($set->contains('Apple'));
        $this->assertFalse($set->contains('apple'));
    }

    // ==================== REMOVE TESTS ====================

    public function test_remove_removes_item(): void
    {
        $set = new SetCollection([1, 2, 3]);
        $new = $set->remove(2);

        $this->assertSame([1, 3], $new->toArray());
    }

    public function test_remove_does_nothing_if_not_found(): void
    {
        $set = new SetCollection([1, 2, 3]);
        $new = $set->remove(99);

        $this->assertSame($set, $new);
    }

    // ==================== FILTER / MAP / REDUCE TESTS ====================

    public function test_filter_keeps_items_satisfying_callback(): void
    {
        $set = new SetCollection([1, 2, 3, 4, 5, 6, 7, 8, 9, 10]);

        $filtered = $set->filter(fn ($item) => $item % 2 === 0);

        $this->assertSame([2, 4, 6, 8, 10], $filtered->toArray());
    }

    public function test_map_transforms_each_item(): void
    {
        $set = new SetCollection([1, 2, 3, 4, 5]);

        $mapped = $set->map(fn ($item) => $item * 2);

        $this->assertSame([2, 4, 6, 8, 10], $mapped->toArray());
    }

    public function test_map_removes_duplicates(): void
    {
        $set = new SetCollection([-1, 1, -2, 2]);

        $mapped = $set->map(fn ($item) => abs($item));

        $this->assertSame([1, 2], $mapped->toArray());
    }

    public function test_reduce_aggregates_values(): void
    {
        $set = new SetCollection([1, 2, 3, 4, 5]);

        $sum = $set->reduce(fn ($carry, $item) => $carry + $item, 0);

        $this->assertSame(15, $sum);
    }

    // ==================== UNION TESTS ====================

    public function test_union_merges_two_sets(): void
    {
        $set1 = new SetCollection([1, 2, 3]);
        $set2 = new SetCollection([3, 4, 5]);

        $union = $set1->union($set2);

        $this->assertSame([1, 2, 3, 4, 5], $union->toArray());
    }

    public function test_union_with_overlapping_elements(): void
    {
        $set1 = new SetCollection(['a', 'b', 'c']);
        $set2 = new SetCollection(['c', 'd', 'e']);

        $union = $set1->union($set2);

        $this->assertSame(['a', 'b', 'c', 'd', 'e'], $union->toArray());
    }

    // ==================== INTERSECT TESTS ====================

    public function test_intersect_returns_common_elements(): void
    {
        $set1 = new SetCollection([1, 2, 3, 4]);
        $set2 = new SetCollection([3, 4, 5, 6]);

        $intersect = $set1->intersect($set2);

        $this->assertSame([3, 4], $intersect->toArray());
    }

    public function test_intersect_returns_empty_if_no_common(): void
    {
        $set1 = new SetCollection([1, 2, 3]);
        $set2 = new SetCollection([4, 5, 6]);

        $intersect = $set1->intersect($set2);

        $this->assertTrue($intersect->isEmpty());
    }

    // ==================== DIFF TESTS ====================

    public function test_diff_returns_elements_not_in_other(): void
    {
        $set1 = new SetCollection([1, 2, 3, 4]);
        $set2 = new SetCollection([3, 4, 5, 6]);

        $diff = $set1->diff($set2);

        $this->assertSame([1, 2], $diff->toArray());
    }

    public function test_diff_returns_empty_if_all_elements_in_other(): void
    {
        $set1 = new SetCollection([1, 2, 3]);
        $set2 = new SetCollection([1, 2, 3, 4, 5]);

        $diff = $set1->diff($set2);

        $this->assertTrue($diff->isEmpty());
    }

    // ==================== ARRAY ACCESS TESTS ====================

    public function test_array_access_works(): void
    {
        $set = new SetCollection([1, 2, 3]);

        $this->assertTrue(isset($set[0]));
        $this->assertSame(1, $set[0]);
        $this->assertFalse(isset($set[3]));
    }

    public function test_array_access_offset_set_throws_exception(): void
    {
        $set = new SetCollection([1, 2, 3]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('immutable');

        $set[0] = 99;
    }

    public function test_array_access_offset_unset_throws_exception(): void
    {
        $set = new SetCollection([1, 2, 3]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('immutable');

        unset($set[0]);
    }

    // ==================== ITERATOR TESTS ====================

    public function test_is_iterable(): void
    {
        $set = new SetCollection([1, 2, 3, 4, 5]);
        $items = [];

        foreach ($set as $item) {
            $items[] = $item;
        }

        $this->assertSame([1, 2, 3, 4, 5], $items);
    }

    // ==================== JSON TESTS ====================

    public function test_to_json_returns_json_string(): void
    {
        $set = new SetCollection([1, 2, 3]);

        $this->assertSame('[1,2,3]', $set->toJson());
    }

    public function test_to_string_returns_json_string(): void
    {
        $set = new SetCollection([1, 2, 3]);

        $this->assertSame('[1,2,3]', (string) $set);
    }

    public function test_json_serialize_works(): void
    {
        $set = new SetCollection([1, 2, 3]);

        $serialized = json_encode($set);

        $this->assertSame('[1,2,3]', $serialized);
    }

    // ==================== IMMUTABILITY TESTS ====================

    public function test_immutability(): void
    {
        $set = new SetCollection([1, 2, 3]);
        $original = $set->toArray();

        $set->add(4);
        $set->remove(1);

        $this->assertSame([1, 2, 3], $original);
        $this->assertSame([1, 2, 3], $set->toArray());
    }
}
