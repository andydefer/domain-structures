<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Collections;

use AndyDefer\DomainStructures\Collections\TypedCollection;
use AndyDefer\DomainStructures\Tests\Fixtures\Data\TestUserData;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestUserGrade;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestUserRole;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestUserStatus;
use AndyDefer\DomainStructures\Tests\Fixtures\Records\TestUserRecord;
use AndyDefer\DomainStructures\Tests\Fixtures\ValueObjects\TestEmailAddress;
use AndyDefer\DomainStructures\Tests\Fixtures\ValueObjects\TestMoney;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use stdClass;

#[AllowMockObjectsWithoutExpectations]
final class TypedCollectionTest extends TestCase
{
    /**
     * Crée une collection avec TOUS les types mélangés.
     */
    private function createMixedCollection(): TypedCollection
    {
        $collection = new TypedCollection(
            'int',
            'string',
            'float',
            'bool',
            'null',
            TestUserStatus::class,
            TestUserGrade::class,
            TestUserRole::class,
            TestUserRecord::class,
            TestEmailAddress::class,
            TestUserData::class,
            stdClass::class,
            TypedCollection::class
        );

        // Scalaires
        $collection->add(42, 'hello', 3.14, true, null);
        // Enums
        $collection->add(TestUserStatus::ACTIVE, TestUserGrade::GOLD, TestUserRole::ADMIN);
        // Record
        $collection->add(new TestUserRecord(name: 'John', email: 'john@example.com'));
        // Value Object
        $collection->add(TestEmailAddress::fromString('test@example.com'));
        // Data
        $record = new TestUserRecord(name: 'Jane', email: 'jane@example.com');
        $collection->add(TestUserData::fromRecord($record));
        // stdClass
        $obj = new stdClass;
        $obj->name = 'test';
        $collection->add($obj);
        // Nested collection
        $nested = new TypedCollection('int');
        $nested->add(1, 2, 3);
        $collection->add($nested);

        return $collection;
    }

    // ==================== CONSTRUCTOR TESTS ====================

    public function test_constructor_requires_at_least_one_type(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new TypedCollection;
    }

    public function test_constructor_accepts_all_types_together(): void
    {
        $collection = $this->createMixedCollection();
        $types = $collection->getAllowedTypes();

        $this->assertContains('int', $types);
        $this->assertContains('string', $types);
        $this->assertContains('float', $types);
        $this->assertContains('bool', $types);
        $this->assertContains('null', $types);
        $this->assertContains(TestUserStatus::class, $types);
        $this->assertContains(TestUserGrade::class, $types);
        $this->assertContains(TestUserRole::class, $types);
        $this->assertContains(TestUserRecord::class, $types);
        $this->assertContains(TestEmailAddress::class, $types);
        $this->assertContains(TestUserData::class, $types);
        $this->assertContains(stdClass::class, $types);
        $this->assertContains(TypedCollection::class, $types);
    }

    // ==================== ADD TESTS ====================

    public function test_add_all_types_together(): void
    {
        $collection = $this->createMixedCollection();
        $this->assertCount(13, $collection);
    }

    public function test_add_throws_for_disallowed_type(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $collection = new TypedCollection('int');
        $collection->add('not an int');
    }

    // ==================== ALL TESTS ====================

    public function test_all_preserves_all_types(): void
    {
        $original = $this->createMixedCollection();
        $copied = $original->all();

        $this->assertNotSame($original, $copied);
        $this->assertSame($original->toArray(), $copied->toArray());
        $this->assertSame($original->getAllowedTypes(), $copied->getAllowedTypes());
    }

    // ==================== TO_ARRAY TESTS ====================

    public function test_to_array_returns_all_items_with_correct_types(): void
    {
        $collection = $this->createMixedCollection();
        $array = $collection->toArray();

        $this->assertCount(13, $array);
        $this->assertIsInt($array[0]);
        $this->assertIsString($array[1]);
        $this->assertIsFloat($array[2]);
        $this->assertIsBool($array[3]);
        $this->assertNull($array[4]);
        $this->assertInstanceOf(TestUserStatus::class, $array[5]);
        $this->assertInstanceOf(TestUserGrade::class, $array[6]);
        $this->assertInstanceOf(TestUserRole::class, $array[7]);
        $this->assertInstanceOf(TestUserRecord::class, $array[8]);
        $this->assertInstanceOf(TestEmailAddress::class, $array[9]);
        $this->assertInstanceOf(TestUserData::class, $array[10]);
        $this->assertInstanceOf(stdClass::class, $array[11]);
        $this->assertInstanceOf(TypedCollection::class, $array[12]);
    }

    // ==================== COUNT TESTS ====================

    public function test_count_works_with_mixed_types(): void
    {
        $collection = $this->createMixedCollection();
        $this->assertSame(13, $collection->count());
    }

    // ==================== IS_EMPTY / IS_NOT_EMPTY TESTS ====================

    public function test_is_empty_false_for_mixed_collection(): void
    {
        $collection = $this->createMixedCollection();
        $this->assertFalse($collection->isEmpty());
        $this->assertTrue($collection->isNotEmpty());
    }

    public function test_is_empty_true_for_new_collection(): void
    {
        $collection = new TypedCollection('int');
        $this->assertTrue($collection->isEmpty());
        $this->assertFalse($collection->isNotEmpty());
    }

    // ==================== MAP TESTS ====================

    public function test_map_transforms_all_types_to_strings(): void
    {
        $collection = $this->createMixedCollection();

        $mapped = $collection->map(function ($item): string {
            return match (true) {
                $item === null => 'null_value',
                is_int($item) => "int:{$item}",
                is_string($item) => "str:{$item}",
                is_float($item) => "float:{$item}",
                is_bool($item) => $item ? 'bool:true' : 'bool:false',
                $item instanceof TestUserStatus => 'enum:status',
                $item instanceof TestUserGrade => 'enum:grade',
                $item instanceof TestUserRole => 'enum:role',
                $item instanceof TestUserRecord => 'record:user',
                $item instanceof TestEmailAddress => 'vo:email',
                $item instanceof TestUserData => 'data:user',
                $item instanceof stdClass => 'stdclass',
                $item instanceof TypedCollection => 'collection',
                default => 'unknown'
            };
        });

        $this->assertCount(13, $mapped);
        $this->assertSame(['int:42', 'str:hello', 'float:3.14', 'bool:true', 'null_value'], array_slice($mapped->toArray(), 0, 5));
        $this->assertSame(['enum:status', 'enum:grade', 'enum:role'], array_slice($mapped->toArray(), 5, 3));
        $this->assertSame(['record:user', 'vo:email', 'data:user', 'stdclass', 'collection'], array_slice($mapped->toArray(), 8, 5));
    }

    public function test_map_extracts_property_from_all_object_types(): void
    {
        $collection = $this->createMixedCollection();

        $mapped = $collection->map(function ($item) {
            return match (true) {
                $item instanceof TestUserRecord => $item->name,
                $item instanceof TestEmailAddress => $item->value,
                $item instanceof TestUserData => $item->name,
                $item instanceof stdClass && property_exists($item, 'name') => $item->name,
                $item instanceof TypedCollection => $item->count(),
                default => null
            };
        });

        $this->assertEquals('John', $mapped->toArray()[8]);
        $this->assertEquals('test@example.com', $mapped->toArray()[9]);
        $this->assertEquals('Jane', $mapped->toArray()[10]);
        $this->assertEquals('test', $mapped->toArray()[11]);
        $this->assertEquals(3, $mapped->toArray()[12]);
    }

    // ==================== FILTER TESTS ====================

    public function test_filter_keeps_only_records(): void
    {
        $collection = $this->createMixedCollection();

        $filtered = $collection->filter(fn ($item) => $item instanceof TestUserRecord);

        $this->assertCount(1, $filtered);
        $this->assertInstanceOf(TestUserRecord::class, $filtered->toArray()[0]);
    }

    public function test_filter_keeps_only_scalars(): void
    {
        $collection = $this->createMixedCollection();

        $filtered = $collection->filter(fn ($item) => is_scalar($item) || $item === null);

        $this->assertCount(5, $filtered); // 42, 'hello', 3.14, true, null
    }

    public function test_filter_keeps_only_enums(): void
    {
        $collection = $this->createMixedCollection();

        $filtered = $collection->filter(fn ($item) => $item instanceof \UnitEnum);

        $this->assertCount(3, $filtered);
    }

    public function test_filter_keeps_only_data(): void
    {
        $collection = $this->createMixedCollection();

        $filtered = $collection->filter(fn ($item) => $item instanceof TestUserData);

        $this->assertCount(1, $filtered);
    }

    // ==================== EACH TESTS ====================

    public function test_each_iterates_over_all_mixed_items(): void
    {
        $collection = $this->createMixedCollection();
        $counts = [
            'scalars' => 0,
            'enums' => 0,
            'records' => 0,
            'vos' => 0,
            'data' => 0,
            'stdclass' => 0,
            'collections' => 0,
        ];

        $collection->each(function ($item) use (&$counts) {
            match (true) {
                is_scalar($item) || $item === null => $counts['scalars']++,
                $item instanceof \UnitEnum => $counts['enums']++,
                $item instanceof TestUserRecord => $counts['records']++,
                $item instanceof TestEmailAddress => $counts['vos']++,
                $item instanceof TestUserData => $counts['data']++,
                $item instanceof stdClass => $counts['stdclass']++,
                $item instanceof TypedCollection => $counts['collections']++,
                default => null
            };
        });

        $this->assertSame(5, $counts['scalars']);
        $this->assertSame(3, $counts['enums']);
        $this->assertSame(1, $counts['records']);
        $this->assertSame(1, $counts['vos']);
        $this->assertSame(1, $counts['data']);
        $this->assertSame(1, $counts['stdclass']);
        $this->assertSame(1, $counts['collections']);
    }

    // ==================== MERGE TESTS ====================

    public function test_merge_combines_two_mixed_collections(): void
    {
        $col1 = $this->createMixedCollection();
        $col2 = $this->createMixedCollection();

        $merged = $col1->merge($col2);

        $this->assertCount(26, $merged);
    }

    public function test_merge_throws_for_incompatible_types(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $col1 = new TypedCollection('int');
        $col1->add(1);
        $col2 = new TypedCollection('string');
        $col2->add('a');
        $col1->merge($col2);
    }

    // ==================== REDUCE TESTS ====================

    public function test_reduce_collects_all_info(): void
    {
        $collection = $this->createMixedCollection();

        $result = $collection->reduce(function ($carry, $item): array {
            $carry['total']++;

            if (is_int($item)) {
                $carry['int_sum'] += $item;
            }
            if ($item instanceof TestUserRecord) {
                $carry['record_names'][] = $item->name;
            }
            if ($item instanceof TestEmailAddress) {
                $carry['emails'][] = $item->value;
            }

            return $carry;
        }, [
            'total' => 0,
            'int_sum' => 0,
            'record_names' => [],
            'emails' => [],
        ]);

        $this->assertSame(13, $result['total']);
        $this->assertSame(42, $result['int_sum']);
        $this->assertContains('John', $result['record_names']);
        $this->assertContains('test@example.com', $result['emails']);
    }

    // ==================== CONTAINS TESTS ====================

    public function test_contains_returns_true_for_scalar_int(): void
    {
        $collection = new TypedCollection('int');
        $collection->add(42);
        $this->assertTrue($collection->contains(42));
        $this->assertFalse($collection->contains(99));
    }

    public function test_contains_returns_true_for_scalar_string(): void
    {
        $collection = new TypedCollection('string');
        $collection->add('hello');
        $this->assertTrue($collection->contains('hello'));
        $this->assertFalse($collection->contains('world'));
    }

    public function test_contains_returns_true_for_scalar_float(): void
    {
        $collection = new TypedCollection('float');
        $collection->add(3.14);
        $this->assertTrue($collection->contains(3.14));
        $this->assertFalse($collection->contains(2.71));
    }

    public function test_contains_returns_true_for_scalar_bool(): void
    {
        $collection = new TypedCollection('bool');
        $collection->add(true);
        $this->assertTrue($collection->contains(true));
        $this->assertFalse($collection->contains(false));
    }

    public function test_contains_returns_true_for_null(): void
    {
        $collection = new TypedCollection('null');
        $collection->add(null);
        $this->assertTrue($collection->contains(null));
    }

    public function test_contains_returns_true_for_enum(): void
    {
        $collection = new TypedCollection(TestUserStatus::class);
        $collection->add(TestUserStatus::ACTIVE);
        $this->assertTrue($collection->contains(TestUserStatus::ACTIVE));
        $this->assertFalse($collection->contains(TestUserStatus::INACTIVE));
    }

    public function test_contains_returns_true_for_record(): void
    {
        $collection = new TypedCollection(TestUserRecord::class);
        $record = new TestUserRecord(name: 'John');
        $collection->add($record);
        $this->assertTrue($collection->contains($record));

        $otherRecord = new TestUserRecord(name: 'Jane');
        $this->assertFalse($collection->contains($otherRecord));
    }

    public function test_contains_returns_true_for_value_object(): void
    {
        $collection = new TypedCollection(TestEmailAddress::class);
        $email = TestEmailAddress::fromString('test@example.com');
        $collection->add($email);
        $this->assertTrue($collection->contains($email));

        $otherEmail = TestEmailAddress::fromString('other@example.com');
        $this->assertFalse($collection->contains($otherEmail));
    }

    public function test_contains_returns_true_for_data(): void
    {
        $collection = new TypedCollection(TestUserData::class);
        $record = new TestUserRecord(name: 'John', email: 'john@example.com');
        $data = TestUserData::fromRecord($record);
        $collection->add($data);
        $this->assertTrue($collection->contains($data));
    }

    public function test_contains_returns_true_for_std_class(): void
    {
        $collection = new TypedCollection(stdClass::class);
        $obj = new stdClass;
        $obj->name = 'test';
        $collection->add($obj);
        $this->assertTrue($collection->contains($obj));
    }

    public function test_contains_returns_true_for_nested_collection(): void
    {
        $collection = new TypedCollection(TypedCollection::class);
        $nested = new TypedCollection('int');
        $nested->add(1, 2, 3);
        $collection->add($nested);
        $this->assertTrue($collection->contains($nested));
    }

    public function test_contains_on_mixed_collection(): void
    {
        $collection = $this->createMixedCollection();

        $this->assertTrue($collection->contains(42));
        $this->assertTrue($collection->contains('hello'));
        $this->assertTrue($collection->contains(3.14));
        $this->assertTrue($collection->contains(true));
        $this->assertTrue($collection->contains(null));
        $this->assertTrue($collection->contains(TestUserStatus::ACTIVE));
        $this->assertTrue($collection->contains(TestUserGrade::GOLD));
        $this->assertTrue($collection->contains(TestUserRole::ADMIN));

        $this->assertFalse($collection->contains(999));
        $this->assertFalse($collection->contains('not present'));
        $this->assertFalse($collection->contains(TestUserStatus::INACTIVE));
        $this->assertFalse($collection->contains(TestUserGrade::BRONZE));
        $this->assertFalse($collection->contains(TestUserRole::GUEST));
    }

    // ==================== FIND TESTS ====================

    public function test_find_returns_first_record(): void
    {
        $collection = $this->createMixedCollection();

        $found = $collection->find(fn ($item) => $item instanceof TestUserRecord);

        $this->assertInstanceOf(TestUserRecord::class, $found);
        $this->assertSame('John', $found->name);
    }

    public function test_find_returns_first_enum(): void
    {
        $collection = $this->createMixedCollection();

        $found = $collection->find(fn ($item) => $item instanceof \UnitEnum);

        $this->assertSame(TestUserStatus::ACTIVE, $found);
    }

    public function test_find_returns_null_when_not_found(): void
    {
        $collection = $this->createMixedCollection();

        $found = $collection->find(fn ($item) => $item instanceof TestMoney);

        $this->assertNull($found);
    }

    // ==================== EVERY TESTS ====================

    public function test_every_returns_true_when_all_items_are_objects(): void
    {
        $collection = new TypedCollection(stdClass::class);
        $collection->add(new stdClass, new stdClass);

        $result = $collection->every(fn ($item) => is_object($item));

        $this->assertTrue($result);
    }

    public function test_every_returns_false_when_one_item_is_not_object(): void
    {
        $collection = $this->createMixedCollection();

        $result = $collection->every(fn ($item) => is_object($item));

        $this->assertFalse($result); // There are scalars!
    }

    public function test_every_on_empty_collection_returns_true(): void
    {
        $collection = new TypedCollection('int');
        $result = $collection->every(fn ($item) => $item > 0);
        $this->assertTrue($result);
    }

    // ==================== SOME TESTS ====================

    public function test_some_returns_true_when_there_is_at_least_one_record(): void
    {
        $collection = $this->createMixedCollection();

        $result = $collection->some(fn ($item) => $item instanceof TestUserRecord);

        $this->assertTrue($result);
    }

    public function test_some_returns_true_when_there_is_at_least_one_scalar(): void
    {
        $collection = $this->createMixedCollection();

        $result = $collection->some(fn ($item) => is_int($item));

        $this->assertTrue($result);
    }

    public function test_some_returns_false_when_no_match(): void
    {
        $collection = $this->createMixedCollection();

        $result = $collection->some(fn ($item) => $item instanceof TestMoney);

        $this->assertFalse($result);
    }

    public function test_some_on_empty_collection_returns_false(): void
    {
        $collection = new TypedCollection('int');
        $result = $collection->some(fn ($item) => $item > 0);
        $this->assertFalse($result);
    }

    // ==================== REVERSE TESTS ====================

    public function test_reverse_reverses_order_of_all_types(): void
    {
        $collection = $this->createMixedCollection();
        $originalArray = $collection->toArray();

        $reversed = $collection->reverse();

        $this->assertSame(array_reverse($originalArray), $reversed->toArray());
    }

    public function test_reverse_preserves_allowed_types(): void
    {
        $collection = $this->createMixedCollection();
        $reversed = $collection->reverse();

        $this->assertSame($collection->getAllowedTypes(), $reversed->getAllowedTypes());
    }

    // ==================== GET_ITERATOR TESTS ====================

    public function test_foreach_iterates_over_all_mixed_items(): void
    {
        $collection = $this->createMixedCollection();
        $items = [];

        foreach ($collection as $item) {
            $items[] = $item;
        }

        $this->assertCount(13, $items);
        $this->assertSame($collection->toArray(), $items);
    }

    // ==================== JSON_SERIALIZE TESTS ====================

    public function test_json_serialize_returns_array(): void
    {
        $collection = $this->createMixedCollection();
        $json = json_encode($collection);

        $this->assertIsString($json);
        $this->assertJson($json);
    }

    // ==================== TO_STRING TESTS ====================

    public function test_to_string_returns_json(): void
    {
        $collection = $this->createMixedCollection();
        $string = (string) $collection;

        $this->assertIsString($string);
        $this->assertJson($string);
    }

    // ==================== CLONE TESTS ====================

    public function test_clone_creates_deep_copy(): void
    {
        $collection = $this->createMixedCollection();
        $cloned = clone $collection;

        $this->assertNotSame($collection, $cloned);
        $this->assertSame($collection->toArray(), $cloned->toArray());
    }

    // ==================== INTEGRATION TESTS ====================

    public function test_chaining_multiple_operations_on_mixed_collection(): void
    {
        $collection = $this->createMixedCollection();

        $result = $collection
            ->filter(fn ($item) => is_object($item))
            ->filter(fn ($item) => ! ($item instanceof \UnitEnum))
            ->map(function ($item) {
                return match (true) {
                    $item instanceof TestUserRecord => $item->name,
                    $item instanceof TestEmailAddress => $item->value,
                    $item instanceof TestUserData => $item->name,
                    $item instanceof stdClass => 'stdClass',
                    $item instanceof TypedCollection => 'collection',
                    default => 'unknown'
                };
            })
            ->reverse()
            ->toArray();

        // Should contain: collection, stdClass, Jane, test@example.com, John
        $this->assertCount(5, $result);
        $this->assertSame('collection', $result[0]);
        $this->assertSame('stdClass', $result[1]);
        $this->assertSame('Jane', $result[2]);
        $this->assertSame('test@example.com', $result[3]);
        $this->assertSame('John', $result[4]);
    }

    public function test_complex_reduce_on_mixed_collection(): void
    {
        $collection = $this->createMixedCollection();

        $summary = $collection->reduce(function ($carry, $item) {
            $carry['count']++;

            if (is_int($item)) {
                $carry['integers'][] = $item;
            }
            if (is_string($item)) {
                $carry['strings'][] = $item;
            }
            if ($item instanceof \UnitEnum) {
                $carry['enums'][] = $item::class;
            }
            if ($item instanceof TestUserRecord) {
                $carry['has_record'] = true;
            }
            if ($item instanceof TestEmailAddress) {
                $carry['has_vo'] = true;
            }
            if ($item instanceof TestUserData) {
                $carry['has_data'] = true;
            }

            return $carry;
        }, [
            'count' => 0,
            'integers' => [],
            'strings' => [],
            'enums' => [],
            'has_record' => false,
            'has_vo' => false,
            'has_data' => false,
        ]);

        $this->assertSame(13, $summary['count']);
        $this->assertContains(42, $summary['integers']);
        $this->assertContains('hello', $summary['strings']);
        $this->assertCount(3, $summary['enums']);
        $this->assertTrue($summary['has_record']);
        $this->assertTrue($summary['has_vo']);
        $this->assertTrue($summary['has_data']);
    }
}
