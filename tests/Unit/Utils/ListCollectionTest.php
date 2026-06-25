<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Unit\Collections\Utility;

use AndyDefer\DomainStructures\Tests\Fixtures\Records\TestUserRecord;
use AndyDefer\DomainStructures\Tests\Fixtures\ValueObjects\TestEmailAddress;
use AndyDefer\DomainStructures\Tests\Fixtures\ValueObjects\TestIntVO;
use AndyDefer\DomainStructures\Tests\Fixtures\ValueObjects\TestStringVO;
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

    // ==================== TYPE PRESERVATION TESTS ====================

    public function test_preserves_types_of_value_objects(): void
    {
        $intVO = TestIntVO::from(42);
        $stringVO = TestStringVO::from('Hello');

        $list = new ListCollection([$intVO, $stringVO]);

        $this->assertInstanceOf(TestIntVO::class, $list->first());
        $this->assertInstanceOf(TestStringVO::class, $list->last());
        $this->assertSame(42, $list->first()->getValue());
        $this->assertSame('Hello', $list->last()->getValue());
    }

    public function test_preserves_types_in_to_array(): void
    {
        $intVO = TestIntVO::from(42);
        $stringVO = TestStringVO::from('Hello');

        $list = new ListCollection([$intVO, $stringVO]);
        $array = $list->toArray();

        $this->assertInstanceOf(TestIntVO::class, $array[0]);
        $this->assertInstanceOf(TestStringVO::class, $array[1]);
    }

    public function test_preserves_types_after_add(): void
    {
        $list = new ListCollection;
        $intVO = TestIntVO::from(42);
        $stringVO = TestStringVO::from('Hello');

        $new = $list->add($intVO)->add($stringVO);

        $this->assertInstanceOf(TestIntVO::class, $new->get(0));
        $this->assertInstanceOf(TestStringVO::class, $new->get(1));
    }

    public function test_preserves_types_after_prepend(): void
    {
        $list = new ListCollection([TestStringVO::from('World')]);
        $intVO = TestIntVO::from(42);

        $new = $list->prepend($intVO);

        $this->assertInstanceOf(TestIntVO::class, $new->first());
        $this->assertInstanceOf(TestStringVO::class, $new->last());
    }

    public function test_preserves_types_after_insert(): void
    {
        $list = new ListCollection([
            TestIntVO::from(1),
            TestIntVO::from(3),
        ]);
        $intVO = TestIntVO::from(2);

        $new = $list->insert(1, $intVO);

        $this->assertInstanceOf(TestIntVO::class, $new->get(0));
        $this->assertInstanceOf(TestIntVO::class, $new->get(1));
        $this->assertInstanceOf(TestIntVO::class, $new->get(2));
        $this->assertEquals(2, $new->get(1)->getValue());
    }

    public function test_preserves_types_after_replace(): void
    {
        $list = new ListCollection([
            TestIntVO::from(1),
            TestIntVO::from(2),
        ]);
        $intVO = TestIntVO::from(99);

        $new = $list->replace(1, $intVO);

        $this->assertInstanceOf(TestIntVO::class, $new->get(1));
        $this->assertEquals(99, $new->get(1)->getValue());
    }

    public function test_preserves_types_after_filter(): void
    {
        $list = new ListCollection([
            TestIntVO::from(1),
            TestStringVO::from('Hello'),
            TestIntVO::from(2),
        ]);

        $filtered = $list->filter(fn ($item) => $item instanceof TestIntVO);

        $this->assertCount(2, $filtered);
        $this->assertInstanceOf(TestIntVO::class, $filtered->get(0));
        $this->assertInstanceOf(TestIntVO::class, $filtered->get(1));
        $this->assertEquals(1, $filtered->get(0)->getValue());
        $this->assertEquals(2, $filtered->get(1)->getValue());
    }

    public function test_preserves_types_after_map_when_returning_same_type(): void
    {
        $list = new ListCollection([
            TestIntVO::from(1),
            TestIntVO::from(2),
            TestIntVO::from(3),
        ]);

        $mapped = $list->map(fn ($item) => $item->multiply(2));

        $this->assertCount(3, $mapped);
        $this->assertInstanceOf(TestIntVO::class, $mapped->get(0));
        $this->assertInstanceOf(TestIntVO::class, $mapped->get(1));
        $this->assertInstanceOf(TestIntVO::class, $mapped->get(2));
        $this->assertEquals(2, $mapped->get(0)->getValue());
        $this->assertEquals(4, $mapped->get(1)->getValue());
        $this->assertEquals(6, $mapped->get(2)->getValue());
    }

    public function test_preserves_types_after_map_when_changing_type(): void
    {
        $list = new ListCollection([
            TestIntVO::from(1),
            TestIntVO::from(2),
        ]);

        $mapped = $list->map(fn ($item) => TestStringVO::from((string) $item->getValue()));

        $this->assertCount(2, $mapped);
        $this->assertInstanceOf(TestStringVO::class, $mapped->get(0));
        $this->assertInstanceOf(TestStringVO::class, $mapped->get(1));
    }

    public function test_preserves_types_after_reduce_with_hydration(): void
    {
        $list = new ListCollection([
            TestIntVO::from(1),
            TestIntVO::from(2),
            TestIntVO::from(3),
        ]);

        $sum = $list->reduce(fn ($carry, $item) => $carry + $item->getValue(), 0);

        $this->assertEquals(6, $sum);
    }

    public function test_preserves_types_in_iterator(): void
    {
        $list = new ListCollection([
            TestIntVO::from(1),
            TestStringVO::from('Hello'),
            TestIntVO::from(2),
        ]);

        $types = [];
        foreach ($list as $item) {
            $types[] = get_class($item);
        }

        $this->assertEquals([
            TestIntVO::class,
            TestStringVO::class,
            TestIntVO::class,
        ], $types);
    }

    public function test_preserves_types_in_array_access(): void
    {
        $list = new ListCollection([
            TestIntVO::from(1),
            TestStringVO::from('Hello'),
        ]);

        $this->assertInstanceOf(TestIntVO::class, $list[0]);
        $this->assertInstanceOf(TestStringVO::class, $list[1]);
        $this->assertEquals(1, $list[0]->getValue());
        $this->assertEquals('Hello', $list[1]->getValue());
    }

    public function test_to_raw_array_returns_raw_data(): void
    {
        $intVO = TestIntVO::from(42);
        $stringVO = TestStringVO::from('Hello');

        $list = new ListCollection([$intVO, $stringVO]);
        $raw = $list->toRawArray();

        $this->assertIsInt($raw[0]);
        $this->assertIsString($raw[1]);
        $this->assertEquals(42, $raw[0]);
        $this->assertEquals('Hello', $raw[1]);
    }

    public function test_first_returns_hydrated_object(): void
    {
        $intVO = TestIntVO::from(42);
        $list = new ListCollection([$intVO]);

        $this->assertInstanceOf(TestIntVO::class, $list->first());
        $this->assertEquals(42, $list->first()->getValue());
    }

    public function test_last_returns_hydrated_object(): void
    {
        $stringVO = TestStringVO::from('Hello');
        $list = new ListCollection([$stringVO]);

        $this->assertInstanceOf(TestStringVO::class, $list->last());
        $this->assertEquals('Hello', $list->last()->getValue());
    }

    public function test_get_returns_hydrated_object(): void
    {
        $intVO = TestIntVO::from(42);
        $list = new ListCollection([$intVO]);

        $this->assertInstanceOf(TestIntVO::class, $list->get(0));
        $this->assertEquals(42, $list->get(0)->getValue());
    }

    public function test_reverse_preserves_types(): void
    {
        $list = new ListCollection([
            TestIntVO::from(1),
            TestStringVO::from('Hello'),
            TestIntVO::from(2),
        ]);

        $reversed = $list->reverse();

        $this->assertInstanceOf(TestIntVO::class, $reversed->get(0));
        $this->assertInstanceOf(TestStringVO::class, $reversed->get(1));
        $this->assertInstanceOf(TestIntVO::class, $reversed->get(2));
        $this->assertEquals(2, $reversed->get(0)->getValue());
        $this->assertEquals('Hello', $reversed->get(1)->getValue());
        $this->assertEquals(1, $reversed->get(2)->getValue());
    }

    public function test_sort_preserves_types(): void
    {
        $list = new ListCollection([
            TestIntVO::from(5),
            TestIntVO::from(2),
            TestIntVO::from(8),
        ]);

        $sorted = $list->sort(fn ($a, $b) => $a->getValue() <=> $b->getValue());

        $this->assertInstanceOf(TestIntVO::class, $sorted->get(0));
        $this->assertInstanceOf(TestIntVO::class, $sorted->get(1));
        $this->assertInstanceOf(TestIntVO::class, $sorted->get(2));
        $this->assertEquals(2, $sorted->get(0)->getValue());
        $this->assertEquals(5, $sorted->get(1)->getValue());
        $this->assertEquals(8, $sorted->get(2)->getValue());
    }

    public function test_slice_preserves_types(): void
    {
        $list = new ListCollection([
            TestIntVO::from(1),
            TestStringVO::from('Hello'),
            TestIntVO::from(2),
        ]);

        $slice = $list->slice(1, 2);

        $this->assertInstanceOf(TestStringVO::class, $slice->get(0));
        $this->assertInstanceOf(TestIntVO::class, $slice->get(1));
    }

    public function test_take_preserves_types(): void
    {
        $list = new ListCollection([
            TestIntVO::from(1),
            TestStringVO::from('Hello'),
            TestIntVO::from(2),
        ]);

        $taken = $list->take(2);

        $this->assertInstanceOf(TestIntVO::class, $taken->get(0));
        $this->assertInstanceOf(TestStringVO::class, $taken->get(1));
    }

    public function test_skip_preserves_types(): void
    {
        $list = new ListCollection([
            TestIntVO::from(1),
            TestStringVO::from('Hello'),
            TestIntVO::from(2),
        ]);

        $skipped = $list->skip(1);

        $this->assertInstanceOf(TestStringVO::class, $skipped->get(0));
        $this->assertInstanceOf(TestIntVO::class, $skipped->get(1));
    }

    public function test_merge_preserves_types(): void
    {
        $list1 = new ListCollection([TestIntVO::from(1)]);
        $list2 = new ListCollection([TestStringVO::from('Hello')]);

        $merged = $list1->merge($list2);

        $this->assertInstanceOf(TestIntVO::class, $merged->get(0));
        $this->assertInstanceOf(TestStringVO::class, $merged->get(1));
    }

    public function test_merge_array_preserves_types(): void
    {
        $list = new ListCollection([TestIntVO::from(1)]);
        $array = [TestStringVO::from('Hello')];

        $merged = $list->mergeArray($array);

        $this->assertInstanceOf(TestIntVO::class, $merged->get(0));
        $this->assertInstanceOf(TestStringVO::class, $merged->get(1));
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

    // ==================== INDEX OF / CONTAINS TESTS ====================

    public function test_index_of_returns_correct_index(): void
    {
        $list = new ListCollection(['a', 'b', 'c', 'b']);

        $this->assertSame(0, $list->indexOf('a'));
        $this->assertSame(1, $list->indexOf('b')); // Première occurrence
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

    // ==================== JSON TESTS ====================

    public function test_to_json_returns_json_string(): void
    {
        $list = new ListCollection([1, 2, 3]);

        $this->assertSame('[1,2,3]', $list->toJson());
    }

    public function test_to_json_normalizes_value_objects(): void
    {
        $intVO = TestIntVO::from(42);
        $stringVO = TestStringVO::from('Hello');

        $list = new ListCollection([$intVO, $stringVO]);

        $this->assertSame('[42,"Hello"]', $list->toJson());
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

    public function test_immutability_with_value_objects(): void
    {
        $intVO = TestIntVO::from(42);
        $list = new ListCollection([$intVO]);
        $original = $list->toArray();

        $new = $list->add(TestIntVO::from(99));

        $this->assertCount(1, $list);
        $this->assertCount(2, $new);
        $this->assertInstanceOf(TestIntVO::class, $list->first());
        $this->assertSame(42, $list->first()->getValue());
    }
}
