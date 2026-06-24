<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Unit\Collections\Utility;

use AndyDefer\DomainStructures\Tests\Fixtures\Records\TestUserRecord;
use AndyDefer\DomainStructures\Tests\Fixtures\ValueObjects\TestEmailAddress;
use AndyDefer\DomainStructures\Tests\TestCase;
use AndyDefer\DomainStructures\Utils\ListCollection;
use AndyDefer\DomainStructures\Utils\MapCollection;

final class MapCollectionTest extends TestCase
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
        $map = new MapCollection;

        $this->assertTrue($map->isEmpty());
        $this->assertCount(0, $map);
        $this->assertSame([], $map->toArray());
    }

    public function test_can_be_created_with_items(): void
    {
        $map = new MapCollection([
            'name' => 'John',
            'age' => 30,
            'city' => 'Paris',
        ]);

        $this->assertCount(3, $map);
        $this->assertSame([
            'name' => 'John',
            'age' => 30,
            'city' => 'Paris',
        ], $map->toArray());
    }

    public function test_normalizes_values_on_construction(): void
    {
        $email = TestEmailAddress::from('test@example.com');
        $map = new MapCollection(['email' => $email]);

        $this->assertCount(1, $map);
        $this->assertSame('test@example.com', $map['email']);
    }

    // ==================== TRANSFORMABLE TESTS ====================

    public function test_from_creates_from_array(): void
    {
        $map = MapCollection::from(['a' => 1, 'b' => 2]);

        $this->assertInstanceOf(MapCollection::class, $map);
        $this->assertSame(['a' => 1, 'b' => 2], $map->toArray());
    }

    public function test_from_creates_from_object(): void
    {
        $obj = new \stdClass;
        $obj->name = 'John';
        $obj->age = 30;

        $map = MapCollection::from($obj);

        $this->assertSame(['name' => 'John', 'age' => 30], $map->toArray());
    }

    public function test_from_creates_from_transformable_object(): void
    {
        $record = new TestUserRecord(name: 'John', email: $this->testEmail);
        $map = MapCollection::from($record);

        $this->assertIsArray($map->toArray());
        $this->assertArrayHasKey('name', $map->toArray());
        $this->assertSame('John', $map['name']);
    }

    public function test_from_returns_same_instance_if_already_map(): void
    {
        $original = new MapCollection(['a' => 1]);
        $result = MapCollection::from($original);

        $this->assertSame($original, $result);
    }

    public function test_from_throws_exception_for_invalid_source(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot create');

        MapCollection::from(new \DateTime);
    }

    public function test_from_json_creates_from_valid_json(): void
    {
        $json = '{"a":1,"b":2,"c":3}';
        $map = MapCollection::fromJson($json);

        $this->assertSame(['a' => 1, 'b' => 2, 'c' => 3], $map->toArray());
    }

    public function test_from_json_throws_exception_for_invalid_json(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid JSON');

        MapCollection::fromJson('{invalid json}');
    }

    public function test_collect_creates_from_iterable(): void
    {
        $sources = [['a' => 1], ['b' => 2]];
        $map = MapCollection::collect($sources);

        $this->assertSame(['a' => 1, 'b' => 2], $map->toArray());
    }

    public function test_collect_handles_objects(): void
    {
        $record = new TestUserRecord(name: 'John', email: $this->testEmail);
        $map = MapCollection::collect([$record]);

        $this->assertArrayHasKey('name', $map->toArray());
        $this->assertSame('John', $map['name']);
    }

    // ==================== PUT TESTS ====================

    public function test_put_adds_new_key_value_pair(): void
    {
        $map = new MapCollection(['name' => 'John']);
        $new = $map->put('age', 30);

        $this->assertNotSame($map, $new);
        $this->assertSame([
            'name' => 'John',
            'age' => 30,
        ], $new->toArray());
    }

    public function test_put_replaces_existing_value(): void
    {
        $map = new MapCollection(['name' => 'John', 'age' => 25]);
        $new = $map->put('age', 30);

        $this->assertSame([
            'name' => 'John',
            'age' => 30,
        ], $new->toArray());
    }

    public function test_put_normalizes_value(): void
    {
        $map = new MapCollection;
        $email = TestEmailAddress::from('test@example.com');
        $new = $map->put('email', $email);

        $this->assertSame('test@example.com', $new['email']);
    }

    public function test_put_chaining_works(): void
    {
        $map = new MapCollection;
        $result = $map
            ->put('name', 'John')
            ->put('age', 30)
            ->put('city', 'Paris');

        $this->assertSame([
            'name' => 'John',
            'age' => 30,
            'city' => 'Paris',
        ], $result->toArray());
    }

    public function test_put_all_adds_multiple_pairs(): void
    {
        $map = new MapCollection(['name' => 'John']);
        $new = $map->putAll([
            'age' => 30,
            'city' => 'Paris',
        ]);

        $this->assertSame([
            'name' => 'John',
            'age' => 30,
            'city' => 'Paris',
        ], $new->toArray());
    }

    public function test_put_all_overwrites_existing_keys(): void
    {
        $map = new MapCollection(['name' => 'John', 'age' => 25]);
        $new = $map->putAll([
            'age' => 30,
            'city' => 'Paris',
        ]);

        $this->assertSame([
            'name' => 'John',
            'age' => 30,
            'city' => 'Paris',
        ], $new->toArray());
    }

    // ==================== GET TESTS ====================

    public function test_get_returns_value_for_key(): void
    {
        $map = new MapCollection([
            'name' => 'John',
            'age' => 30,
        ]);

        $this->assertSame('John', $map->get('name'));
        $this->assertSame(30, $map->get('age'));
    }

    public function test_get_returns_null_if_key_not_found(): void
    {
        $map = new MapCollection(['name' => 'John']);

        $this->assertNull($map->get('unknown'));
    }

    // ==================== HAS KEY / HAS VALUE TESTS ====================

    public function test_has_key_returns_true_if_key_exists(): void
    {
        $map = new MapCollection(['name' => 'John', 'age' => 30]);

        $this->assertTrue($map->hasKey('name'));
        $this->assertTrue($map->hasKey('age'));
    }

    public function test_has_key_returns_false_if_key_not_found(): void
    {
        $map = new MapCollection(['name' => 'John']);

        $this->assertFalse($map->hasKey('unknown'));
    }

    public function test_has_value_returns_true_if_value_exists(): void
    {
        $map = new MapCollection(['name' => 'John', 'age' => 30]);

        $this->assertTrue($map->hasValue('John'));
        $this->assertTrue($map->hasValue(30));
    }

    public function test_has_value_returns_false_if_value_not_found(): void
    {
        $map = new MapCollection(['name' => 'John']);

        $this->assertFalse($map->hasValue('Jane'));
    }

    // ==================== REMOVE TESTS ====================

    public function test_remove_removes_key_value_pair(): void
    {
        $map = new MapCollection(['name' => 'John', 'age' => 30]);
        $new = $map->remove('age');

        $this->assertSame(['name' => 'John'], $new->toArray());
    }

    public function test_remove_does_nothing_if_key_not_found(): void
    {
        $map = new MapCollection(['name' => 'John']);
        $new = $map->remove('unknown');

        $this->assertSame($map, $new);
    }

    // ==================== KEYS / VALUES TESTS ====================

    public function test_keys_returns_list_of_keys(): void
    {
        $map = new MapCollection([
            'name' => 'John',
            'age' => 30,
            'city' => 'Paris',
        ]);

        $keys = $map->keys();

        $this->assertInstanceOf(ListCollection::class, $keys);
        $this->assertSame(['name', 'age', 'city'], $keys->toArray());
    }

    public function test_values_returns_list_of_values(): void
    {
        $map = new MapCollection([
            'name' => 'John',
            'age' => 30,
            'city' => 'Paris',
        ]);

        $values = $map->values();

        $this->assertInstanceOf(ListCollection::class, $values);
        $this->assertSame(['John', 30, 'Paris'], $values->toArray());
    }

    // ==================== FILTER / MAP / REDUCE TESTS ====================

    public function test_filter_keeps_pairs_satisfying_callback(): void
    {
        $map = new MapCollection([
            'name' => 'John',
            'age' => 30,
            'city' => 'Paris',
        ]);

        $filtered = $map->filter(fn ($value, $key) => is_string($value));

        $this->assertSame([
            'name' => 'John',
            'city' => 'Paris',
        ], $filtered->toArray());
    }

    public function test_filter_with_key_filter(): void
    {
        $map = new MapCollection([
            'name' => 'John',
            'age' => 30,
            'city' => 'Paris',
        ]);

        $filtered = $map->filter(fn ($value, $key) => $key !== 'age');

        $this->assertSame([
            'name' => 'John',
            'city' => 'Paris',
        ], $filtered->toArray());
    }

    public function test_map_transforms_values(): void
    {
        $map = new MapCollection([
            'name' => 'john',
            'city' => 'paris',
        ]);

        $mapped = $map->map(fn ($value, $key) => strtoupper($value));

        $this->assertSame([
            'name' => 'JOHN',
            'city' => 'PARIS',
        ], $mapped->toArray());
    }

    public function test_map_with_key_access(): void
    {
        $map = new MapCollection([
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        $mapped = $map->map(fn ($value, $key) => $key.': '.$value);

        $this->assertSame([
            'first_name' => 'first_name: John',
            'last_name' => 'last_name: Doe',
        ], $mapped->toArray());
    }

    public function test_reduce_aggregates_values(): void
    {
        $map = new MapCollection([
            'a' => 1,
            'b' => 2,
            'c' => 3,
        ]);

        $sum = $map->reduce(fn ($carry, $value, $key) => $carry + $value, 0);

        $this->assertSame(6, $sum);
    }

    public function test_reduce_with_key_access(): void
    {
        $map = new MapCollection([
            'x' => 1,
            'y' => 2,
        ]);

        $result = $map->reduce(function ($carry, $value, $key) {
            $carry[$key] = $value * 2;

            return $carry;
        }, []);

        $this->assertSame(['x' => 2, 'y' => 4], $result);
    }

    // ==================== MERGE TESTS ====================

    public function test_merge_combines_two_maps(): void
    {
        $map1 = new MapCollection(['a' => 1, 'b' => 2]);
        $map2 = new MapCollection(['c' => 3, 'd' => 4]);

        $merged = $map1->merge($map2);

        $this->assertSame(['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4], $merged->toArray());
    }

    public function test_merge_overwrites_existing_keys(): void
    {
        $map1 = new MapCollection(['a' => 1, 'b' => 2]);
        $map2 = new MapCollection(['b' => 99, 'c' => 3]);

        $merged = $map1->merge($map2);

        $this->assertSame(['a' => 1, 'b' => 99, 'c' => 3], $merged->toArray());
    }

    public function test_merge_array_combines_with_array(): void
    {
        $map = new MapCollection(['a' => 1, 'b' => 2]);

        $merged = $map->mergeArray(['c' => 3, 'd' => 4]);

        $this->assertSame(['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4], $merged->toArray());
    }

    // ==================== ARRAY ACCESS TESTS ====================

    public function test_array_access_works(): void
    {
        $map = new MapCollection(['a' => 1, 'b' => 2]);

        $this->assertTrue(isset($map['a']));
        $this->assertSame(1, $map['a']);
        $this->assertFalse(isset($map['z']));
    }

    public function test_array_access_offset_set_throws_exception(): void
    {
        $map = new MapCollection(['a' => 1]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('immutable');

        $map['a'] = 99;
    }

    public function test_array_access_offset_unset_throws_exception(): void
    {
        $map = new MapCollection(['a' => 1]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('immutable');

        unset($map['a']);
    }

    // ==================== ITERATOR TESTS ====================

    public function test_is_iterable(): void
    {
        $map = new MapCollection(['a' => 1, 'b' => 2, 'c' => 3]);
        $items = [];

        foreach ($map as $key => $value) {
            $items[$key] = $value;
        }

        $this->assertSame(['a' => 1, 'b' => 2, 'c' => 3], $items);
    }

    // ==================== JSON TESTS ====================

    public function test_to_json_returns_json_string(): void
    {
        $map = new MapCollection(['a' => 1, 'b' => 2]);

        $this->assertSame('{"a":1,"b":2}', $map->toJson());
    }

    public function test_to_string_returns_json_string(): void
    {
        $map = new MapCollection(['a' => 1, 'b' => 2]);

        $this->assertSame('{"a":1,"b":2}', (string) $map);
    }

    public function test_json_serialize_works(): void
    {
        $map = new MapCollection(['a' => 1, 'b' => 2]);

        $serialized = json_encode($map);

        $this->assertSame('{"a":1,"b":2}', $serialized);
    }

    // ==================== IMMUTABILITY TESTS ====================

    public function test_immutability(): void
    {
        $map = new MapCollection(['a' => 1, 'b' => 2]);
        $original = $map->toArray();

        $map->put('c', 3);
        $map->remove('a');

        $this->assertSame(['a' => 1, 'b' => 2], $original);
        $this->assertSame(['a' => 1, 'b' => 2], $map->toArray());
    }

    // ==================== EDGE CASES ====================

    public function test_works_with_different_key_types(): void
    {
        $map = new MapCollection;
        $map = $map->put('string_key', 'value1');
        $map = $map->put(123, 'value2');
        $map = $map->put(45.67, 'value3');

        $this->assertSame('value1', $map->get('string_key'));
        $this->assertSame('value2', $map->get(123));
        $this->assertSame('value3', $map->get(45.67));
    }

    public function test_works_with_null_values(): void
    {
        $map = new MapCollection(['key' => null]);

        $this->assertTrue($map->hasKey('key'));
        $this->assertNull($map->get('key'));
        $this->assertTrue($map->hasValue(null));
    }
}
