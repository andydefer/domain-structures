<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Unit\Abstracts;

use AndyDefer\DomainStructures\Abstracts\AbstractData;
use AndyDefer\DomainStructures\Abstracts\AbstractRecord;
use AndyDefer\DomainStructures\Abstracts\AbstractTypedCollection;
use AndyDefer\DomainStructures\Abstracts\AbstractValueObject;
use AndyDefer\DomainStructures\Collections\Core\DataCollection;
use AndyDefer\DomainStructures\Collections\Core\RecordCollection;
use AndyDefer\DomainStructures\Collections\Core\TypedCollection;
use AndyDefer\DomainStructures\Collections\Utility\IntTypedCollection;
use AndyDefer\DomainStructures\Collections\Utility\StringTypedCollection;
use AndyDefer\DomainStructures\Normalizers\NormalizerChain;
use AndyDefer\DomainStructures\Tests\Fixtures\Data\TestProductData;
use AndyDefer\DomainStructures\Tests\Fixtures\Data\TestUserData;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestBackedIntEnum;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestBackedStringEnum;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestPureEnum;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestUserGrade;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestUserRole;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestUserStatus;
use AndyDefer\DomainStructures\Tests\Fixtures\Records\TestProductRecord;
use AndyDefer\DomainStructures\Tests\Fixtures\Records\TestUserRecord;
use AndyDefer\DomainStructures\Tests\Fixtures\ValueObjects\TestEmailAddress;
use AndyDefer\DomainStructures\Tests\Fixtures\ValueObjects\TestIso8601DateTime;
use AndyDefer\DomainStructures\Tests\TestCase;
use AndyDefer\DomainStructures\Utils\DataObject;
use InvalidArgumentException;
use UnitEnum;

final class AbstractTypedCollectionTest extends TestCase
{
    private TestEmailAddress $testEmail;

    private TestIso8601DateTime $now;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testEmail = TestEmailAddress::from('test@example.com');
        $this->now = TestIso8601DateTime::from('2024-01-01T12:00:00+00:00');
    }

    // ==================== CONSTRUCTOR AND TYPE VALIDATION TESTS ====================

    public function test_constructor_accepts_valid_scalar_types(): void
    {
        $intCollection = new TypedCollection('int');
        $stringCollection = new TypedCollection('string');
        $floatCollection = new TypedCollection('float');
        $boolCollection = new TypedCollection('bool');
        $nullCollection = new TypedCollection('null');

        $this->assertSame(['int'], $intCollection->getAllowedTypes());
        $this->assertSame(['string'], $stringCollection->getAllowedTypes());
        $this->assertSame(['float'], $floatCollection->getAllowedTypes());
        $this->assertSame(['bool'], $boolCollection->getAllowedTypes());
        $this->assertSame(['null'], $nullCollection->getAllowedTypes());
    }

    public function test_constructor_accepts_enum_types(): void
    {
        $collection1 = new TypedCollection(UnitEnum::class);
        $collection2 = new TypedCollection(TestUserStatus::class);
        $collection3 = new TypedCollection(TestUserRole::class);
        $collection4 = new TypedCollection(TestUserGrade::class);

        $this->assertSame([UnitEnum::class], $collection1->getAllowedTypes());
        $this->assertSame([TestUserStatus::class], $collection2->getAllowedTypes());
        $this->assertSame([TestUserRole::class], $collection3->getAllowedTypes());
        $this->assertSame([TestUserGrade::class], $collection4->getAllowedTypes());
    }

    public function test_constructor_throws_exception_for_abstract_record_type(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('abstract');
        new TypedCollection(AbstractRecord::class);
    }

    public function test_constructor_throws_exception_for_abstract_value_object_type(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('abstract');
        new TypedCollection(AbstractValueObject::class);
    }

    public function test_constructor_throws_exception_for_abstract_data_type(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('abstract');
        new TypedCollection(AbstractData::class);
    }

    public function test_constructor_throws_exception_for_abstract_typed_collection_type(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('abstract');
        new TypedCollection(AbstractTypedCollection::class);
    }

    public function test_constructor_accepts_data_object_type(): void
    {
        $collection = new TypedCollection(DataObject::class);
        $this->assertSame([DataObject::class], $collection->getAllowedTypes());
    }

    public function test_constructor_accepts_multiple_types(): void
    {
        $collection = new TypedCollection('int', 'string', TestUserStatus::class, DataObject::class);

        $this->assertSame(['int', 'string', TestUserStatus::class, DataObject::class], $collection->getAllowedTypes());
    }

    public function test_constructor_throws_exception_for_non_existent_class(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Type "NonExistentClass" is not allowed');

        new TypedCollection('NonExistentClass');
    }

    public function test_constructor_throws_exception_for_disallowed_type(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('is not allowed');

        new TypedCollection('array');
    }

    // ==================== ADD AND VALIDATION TESTS ====================

    public function test_add_accepts_valid_integer(): void
    {
        $collection = new TypedCollection('int');
        $collection->add(42);

        $this->assertCount(1, $collection);
        $this->assertSame(42, $collection[0]);
    }

    public function test_add_accepts_valid_string(): void
    {
        $collection = new TypedCollection('string');
        $collection->add('hello');

        $this->assertCount(1, $collection);
        $this->assertSame('hello', $collection[0]);
    }

    public function test_add_accepts_valid_float(): void
    {
        $collection = new TypedCollection('float');
        $collection->add(3.14);

        $this->assertCount(1, $collection);
        $this->assertSame(3.14, $collection[0]);
    }

    public function test_add_accepts_valid_boolean(): void
    {
        $collection = new TypedCollection('bool');
        $collection->add(true);
        $collection->add(false);

        $this->assertCount(2, $collection);
        $this->assertTrue($collection[0]);
        $this->assertFalse($collection[1]);
    }

    public function test_add_accepts_null(): void
    {
        $collection = new TypedCollection('null');
        $collection->add(null);

        $this->assertCount(1, $collection);
        $this->assertNull($collection[0]);
    }

    public function test_add_accepts_backed_string_enum(): void
    {
        $collection = new TypedCollection(TestBackedStringEnum::class);
        $collection->add(TestBackedStringEnum::VALUE_ONE);

        $this->assertCount(1, $collection);
        $this->assertSame(TestBackedStringEnum::VALUE_ONE, $collection[0]);
    }

    public function test_add_accepts_backed_int_enum(): void
    {
        $collection = new TypedCollection(TestBackedIntEnum::class);
        $collection->add(TestBackedIntEnum::VALUE_ONE);

        $this->assertCount(1, $collection);
        $this->assertSame(TestBackedIntEnum::VALUE_ONE, $collection[0]);
    }

    public function test_add_accepts_pure_enum(): void
    {
        $collection = new TypedCollection(TestPureEnum::class);
        $collection->add(TestPureEnum::VALUE_ONE);

        $this->assertCount(1, $collection);
        $this->assertSame(TestPureEnum::VALUE_ONE, $collection[0]);
    }

    public function test_add_accepts_abstract_record_instance(): void
    {
        $collection = new TypedCollection(TestUserRecord::class);
        $record = new TestUserRecord(name: 'John', email: $this->testEmail);
        $collection->add($record);

        $this->assertCount(1, $collection);
        $this->assertSame($record, $collection[0]);
    }

    public function test_add_accepts_abstract_value_object_instance(): void
    {
        $collection = new TypedCollection(TestEmailAddress::class);
        $vo = TestEmailAddress::from('test@example.com');
        $collection->add($vo);

        $this->assertCount(1, $collection);
        $this->assertSame($vo, $collection[0]);
    }

    public function test_add_accepts_abstract_data_instance(): void
    {
        $collection = new TypedCollection(TestUserData::class);
        $data = new TestUserData(
            name: 'John Doe',
            email: $this->testEmail,
            status: TestUserStatus::ACTIVE,
            role: TestUserRole::USER,
            grade: TestUserGrade::BRONZE,
            id: 1,
            createdAt: $this->now
        );
        $collection->add($data);

        $this->assertCount(1, $collection);
    }

    public function test_add_accepts_typed_collection_instance(): void
    {
        $collection = new TypedCollection(TypedCollection::class);
        $innerCollection = new TypedCollection('int');
        $collection->add($innerCollection);

        $this->assertCount(1, $collection);
        $this->assertSame($innerCollection, $collection[0]);
    }

    public function test_add_accepts_data_object_instance(): void
    {
        $collection = new TypedCollection(DataObject::class);
        $object = new DataObject(['name' => 'test']);
        $collection->add($object);

        $this->assertCount(1, $collection);
        $this->assertSame($object, $collection[0]);
    }

    public function test_add_accepts_multiple_items_at_once(): void
    {
        $collection = new TypedCollection('int');
        $collection->add(1, 2, 3, 4, 5);

        $this->assertCount(5, $collection);
        $this->assertSame(1, $collection[0]);
        $this->assertSame(2, $collection[1]);
        $this->assertSame(3, $collection[2]);
        $this->assertSame(4, $collection[3]);
        $this->assertSame(5, $collection[4]);
    }

    public function test_add_returns_self_for_chaining(): void
    {
        $collection = new TypedCollection('int');
        $result = $collection->add(1)->add(2)->add(3);

        $this->assertSame($collection, $result);
        $this->assertCount(3, $collection);
    }

    public function test_add_throws_exception_for_invalid_type(): void
    {
        $collection = new TypedCollection('int');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected type(s) int');

        $collection->add('not an integer');
    }

    public function test_add_throws_exception_for_invalid_enum(): void
    {
        $collection = new TypedCollection(TestUserStatus::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Expected type\(s\) .*TestUserStatus/');

        $collection->add(TestUserRole::ADMIN);
    }

    public function test_add_throws_exception_for_disallowed_object_type(): void
    {
        $collection = new TypedCollection('int');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected type(s) int');

        $collection->add(new DataObject);
    }

    // ==================== ALL METHOD TESTS ====================

    public function test_all_returns_new_collection_with_same_items(): void
    {
        $original = new TypedCollection('int');
        $original->add(1, 2, 3);

        $newCollection = $original->all();

        $this->assertNotSame($original, $newCollection);
        $this->assertEquals($original->toArray(), $newCollection->toArray());
    }

    public function test_all_on_empty_collection_returns_empty_collection(): void
    {
        $original = new TypedCollection('int');
        $newCollection = $original->all();

        $this->assertNotSame($original, $newCollection);
        $this->assertCount(0, $newCollection);
    }

    // ==================== TO_ARRAY METHOD TESTS ====================

    public function test_to_array_returns_plain_array_of_items(): void
    {
        $collection = new TypedCollection('int');
        $collection->add(1, 2, 3);

        $array = $collection->toArray();

        $this->assertIsArray($array);
        $this->assertSame([1, 2, 3], $array);
    }

    public function test_to_array_on_empty_collection_returns_empty_array(): void
    {
        $collection = new TypedCollection('int');
        $array = $collection->toArray();

        $this->assertIsArray($array);
        $this->assertEmpty($array);
    }

    // ==================== GET_ALLOWED_TYPES METHOD TESTS ====================

    public function test_get_allowed_types_returns_configured_types(): void
    {
        $collection = new TypedCollection('int', 'string', TestUserStatus::class);
        $types = $collection->getAllowedTypes();

        $this->assertSame(['int', 'string', TestUserStatus::class], $types);
    }

    // ==================== COUNT AND EMPTINESS TESTS ====================

    public function test_count_returns_correct_number_of_items(): void
    {
        $collection = new TypedCollection('int');

        $this->assertSame(0, $collection->count());

        $collection->add(1);
        $this->assertSame(1, $collection->count());

        $collection->add(2, 3);
        $this->assertSame(3, $collection->count());
    }

    public function test_is_empty_returns_true_for_empty_collection(): void
    {
        $collection = new TypedCollection('int');

        $this->assertTrue($collection->isEmpty());
        $this->assertFalse($collection->isNotEmpty());
    }

    public function test_is_empty_returns_false_for_non_empty_collection(): void
    {
        $collection = new TypedCollection('int');
        $collection->add(1);

        $this->assertFalse($collection->isEmpty());
        $this->assertTrue($collection->isNotEmpty());
    }

    // ==================== MAP METHOD TESTS ====================

    public function test_map_transforms_each_item_and_returns_new_collection(): void
    {
        $collection = new TypedCollection('int');
        $collection->add(1, 2, 3, 4, 5);

        $doubled = $collection->map(fn ($item) => $item * 2);

        $this->assertNotSame($collection, $doubled);
        $this->assertSame([2, 4, 6, 8, 10], $doubled->toArray());
        $this->assertSame([1, 2, 3, 4, 5], $collection->toArray());
    }

    public function test_map_can_change_collection_type(): void
    {
        $collection = new TypedCollection('int');
        $collection->add(1, 2, 3);

        $stringCollection = $collection->map(fn ($item) => "Number: {$item}");

        $this->assertSame(['string'], $stringCollection->getAllowedTypes());
        $this->assertSame(['Number: 1', 'Number: 2', 'Number: 3'], $stringCollection->toArray());
    }

    public function test_map_on_empty_collection_returns_empty_collection(): void
    {
        $emptyCollection = new TypedCollection('int');
        $result = $emptyCollection->map(fn ($item) => $item * 2);

        $this->assertCount(0, $result);
    }

    // ==================== FILTER METHOD TESTS ====================

    public function test_filter_keeps_only_items_satisfying_callback(): void
    {
        $collection = new TypedCollection('int');
        $collection->add(1, 2, 3, 4, 5, 6, 7, 8, 9, 10);

        $evenNumbers = $collection->filter(fn ($item) => $item % 2 === 0);

        $this->assertSame([2, 4, 6, 8, 10], $evenNumbers->toArray());
    }

    public function test_filter_returns_new_collection_instance(): void
    {
        $collection = new TypedCollection('int');
        $collection->add(1, 2, 3);

        $filtered = $collection->filter(fn ($item) => $item > 1);

        $this->assertNotSame($collection, $filtered);
        $this->assertSame([1, 2, 3], $collection->toArray());
        $this->assertSame([2, 3], $filtered->toArray());
    }

    public function test_filter_on_empty_collection_returns_empty_collection(): void
    {
        $emptyCollection = new TypedCollection('int');
        $result = $emptyCollection->filter(fn ($item) => $item > 0);

        $this->assertCount(0, $result);
    }

    // ==================== REDUCE METHOD TESTS ====================

    public function test_reduce_aggregates_values_correctly(): void
    {
        $collection = new TypedCollection('int');
        $collection->add(1, 2, 3, 4, 5);

        $sum = $collection->reduce(fn ($carry, $item) => $carry + $item, 0);

        $this->assertSame(15, $sum);
    }

    public function test_reduce_with_string_concatenation_works(): void
    {
        $collection = new TypedCollection('string');
        $collection->add('Hello', ' ', 'World', '!');

        $result = $collection->reduce(fn ($carry, $item) => $carry.$item, '');

        $this->assertSame('Hello World!', $result);
    }

    public function test_reduce_on_empty_collection_returns_initial_value(): void
    {
        $emptyCollection = new TypedCollection('int');
        $result = $emptyCollection->reduce(fn ($carry, $item) => $carry + $item, 100);

        $this->assertSame(100, $result);
    }

    // ==================== FIND METHOD TESTS ====================

    public function test_find_returns_first_item_matching_predicate(): void
    {
        $collection = new TypedCollection('int');
        $collection->add(1, 3, 5, 6, 7, 8);

        $firstEven = $collection->find(fn ($item) => $item % 2 === 0);

        $this->assertSame(6, $firstEven);
    }

    public function test_find_returns_null_when_no_item_matches(): void
    {
        $collection = new TypedCollection('int');
        $collection->add(1, 3, 5, 7);

        $result = $collection->find(fn ($item) => $item % 2 === 0);

        $this->assertNull($result);
    }

    public function test_find_on_empty_collection_returns_null(): void
    {
        $emptyCollection = new TypedCollection('int');
        $result = $emptyCollection->find(fn ($item) => true);

        $this->assertNull($result);
    }

    // ==================== EVERY METHOD TESTS ====================

    public function test_every_returns_true_when_all_items_satisfy_predicate(): void
    {
        $collection = new TypedCollection('int');
        $collection->add(2, 4, 6, 8, 10);

        $allEven = $collection->every(fn ($item) => $item % 2 === 0);

        $this->assertTrue($allEven);
    }

    public function test_every_returns_false_when_any_item_fails_predicate(): void
    {
        $collection = new TypedCollection('int');
        $collection->add(2, 4, 5, 6, 8);

        $allEven = $collection->every(fn ($item) => $item % 2 === 0);

        $this->assertFalse($allEven);
    }

    public function test_every_on_empty_collection_returns_true(): void
    {
        $emptyCollection = new TypedCollection('int');
        $result = $emptyCollection->every(fn ($item) => $item > 100);

        $this->assertTrue($result);
    }

    // ==================== SOME METHOD TESTS ====================

    public function test_some_returns_true_when_at_least_one_item_satisfies_predicate(): void
    {
        $collection = new TypedCollection('int');
        $collection->add(1, 3, 5, 6, 7, 9);

        $hasEven = $collection->some(fn ($item) => $item % 2 === 0);

        $this->assertTrue($hasEven);
    }

    public function test_some_returns_false_when_no_items_satisfy_predicate(): void
    {
        $collection = new TypedCollection('int');
        $collection->add(1, 3, 5, 7, 9);

        $hasEven = $collection->some(fn ($item) => $item % 2 === 0);

        $this->assertFalse($hasEven);
    }

    public function test_some_on_empty_collection_returns_false(): void
    {
        $emptyCollection = new TypedCollection('int');
        $result = $emptyCollection->some(fn ($item) => true);

        $this->assertFalse($result);
    }

    // ==================== SORT METHOD TESTS ====================

    public function test_sort_orders_items_in_ascending_order(): void
    {
        $collection = new TypedCollection('int');
        $collection->add(5, 2, 8, 1, 9, 3);

        $sorted = $collection->sort();

        $this->assertSame([1, 2, 3, 5, 8, 9], $sorted->toArray());
    }

    public function test_sort_returns_new_collection_instance(): void
    {
        $collection = new TypedCollection('int');
        $collection->add(3, 1, 2);

        $sorted = $collection->sort();

        $this->assertNotSame($collection, $sorted);
        $this->assertSame([1, 2, 3], $sorted->toArray());
        $this->assertSame([3, 1, 2], $collection->toArray());
    }

    public function test_sort_on_empty_collection_returns_empty_collection(): void
    {
        $emptyCollection = new TypedCollection('int');
        $sorted = $emptyCollection->sort();

        $this->assertCount(0, $sorted);
    }

    // ==================== SORTBY METHOD TESTS ====================

    public function test_sort_by_sorts_by_property_name_in_ascending_order(): void
    {
        $collection = new TypedCollection(TestUserRecord::class);
        $collection->add(
            new TestUserRecord(id: 3, name: 'Charlie', email: $this->testEmail),
            new TestUserRecord(id: 1, name: 'Alice', email: $this->testEmail),
            new TestUserRecord(id: 4, name: 'David', email: $this->testEmail),
            new TestUserRecord(id: 2, name: 'Bob', email: $this->testEmail)
        );

        $sorted = $collection->sortBy('name');

        $this->assertSame('Alice', $sorted[0]->name);
        $this->assertSame('Bob', $sorted[1]->name);
        $this->assertSame('Charlie', $sorted[2]->name);
        $this->assertSame('David', $sorted[3]->name);
    }

    public function test_sort_by_sorts_by_property_name_in_descending_order(): void
    {
        $collection = new TypedCollection(TestUserRecord::class);
        $collection->add(
            new TestUserRecord(id: 3, name: 'Charlie', email: $this->testEmail),
            new TestUserRecord(id: 1, name: 'Alice', email: $this->testEmail),
            new TestUserRecord(id: 4, name: 'David', email: $this->testEmail),
            new TestUserRecord(id: 2, name: 'Bob', email: $this->testEmail)
        );

        $sorted = $collection->sortBy('name', SORT_REGULAR, true);

        $this->assertSame('David', $sorted[0]->name);
        $this->assertSame('Charlie', $sorted[1]->name);
        $this->assertSame('Bob', $sorted[2]->name);
        $this->assertSame('Alice', $sorted[3]->name);
    }

    public function test_sort_by_sorts_by_numeric_property(): void
    {
        $collection = new TypedCollection(TestUserRecord::class);
        $collection->add(
            new TestUserRecord(id: 5, name: 'Eve', email: $this->testEmail),
            new TestUserRecord(id: 2, name: 'Bob', email: $this->testEmail),
            new TestUserRecord(id: 4, name: 'David', email: $this->testEmail),
            new TestUserRecord(id: 1, name: 'Alice', email: $this->testEmail),
            new TestUserRecord(id: 3, name: 'Charlie', email: $this->testEmail)
        );

        $sorted = $collection->sortBy('id', SORT_NUMERIC);

        $this->assertEquals(1, $sorted[0]->id);
        $this->assertEquals(2, $sorted[1]->id);
        $this->assertEquals(3, $sorted[2]->id);
        $this->assertEquals(4, $sorted[3]->id);
        $this->assertEquals(5, $sorted[4]->id);
    }

    public function test_sort_by_with_closure_callback_works(): void
    {
        $collection = new TypedCollection(TestUserRecord::class);
        $collection->add(
            new TestUserRecord(id: 3, name: 'Charlie', email: $this->testEmail),
            new TestUserRecord(id: 1, name: 'Alice', email: $this->testEmail),
            new TestUserRecord(id: 4, name: 'David', email: $this->testEmail),
            new TestUserRecord(id: 2, name: 'Bob', email: $this->testEmail)
        );

        $sorted = $collection->sortBy(fn ($item) => strlen($item->name));

        $this->assertSame('Bob', $sorted[0]->name);
        $this->assertSame('Alice', $sorted[1]->name);
        $this->assertSame('David', $sorted[2]->name);
        $this->assertSame('Charlie', $sorted[3]->name);
    }

    public function test_sort_by_with_numeric_flag_works(): void
    {
        $collection = new TypedCollection('int', 'float');
        $collection->add(5, 2.5, 8, 1.2, 9, 3.7);

        $sorted = $collection->sortBy(fn ($item) => $item, SORT_NUMERIC);

        $this->assertSame([1.2, 2.5, 3.7, 5, 8, 9], $sorted->toArray());
    }

    public function test_sort_by_with_string_flag_works(): void
    {
        $collection = new TypedCollection('string');
        $collection->add('banana', 'Apple', 'cherry', 'date');

        $sorted = $collection->sortBy(fn ($item) => $item, SORT_STRING);

        $this->assertSame('Apple', $sorted[0]);
        $this->assertSame('banana', $sorted[1]);
        $this->assertSame('cherry', $sorted[2]);
        $this->assertSame('date', $sorted[3]);
    }

    public function test_sort_by_returns_new_collection_instance(): void
    {
        $collection = new TypedCollection(TestUserRecord::class);
        $collection->add(
            new TestUserRecord(id: 2, name: 'Bob', email: $this->testEmail),
            new TestUserRecord(id: 1, name: 'Alice', email: $this->testEmail)
        );

        $sorted = $collection->sortBy('name');

        $this->assertNotSame($collection, $sorted);
        $this->assertSame([2, 1], array_column($collection->toArray(), 'id'));
        $this->assertSame([1, 2], array_column($sorted->toArray(), 'id'));
    }

    public function test_sort_by_on_empty_collection_returns_empty_collection(): void
    {
        $emptyCollection = new TypedCollection('int');
        $sorted = $emptyCollection->sortBy(fn ($item) => $item);

        $this->assertCount(0, $sorted);
    }

    // ==================== USORT METHOD TESTS ====================

    public function test_usort_sorts_using_custom_comparison_function(): void
    {
        $collection = new TypedCollection(TestUserRecord::class);
        $collection->add(
            new TestUserRecord(id: 3, name: 'Charlie', email: $this->testEmail),
            new TestUserRecord(id: 1, name: 'Alice', email: $this->testEmail),
            new TestUserRecord(id: 4, name: 'David', email: $this->testEmail),
            new TestUserRecord(id: 2, name: 'Bob', email: $this->testEmail)
        );

        $sorted = $collection->usort(fn ($a, $b) => strcmp($b->name, $a->name));

        $this->assertSame('David', $sorted[0]->name);
        $this->assertSame('Charlie', $sorted[1]->name);
        $this->assertSame('Bob', $sorted[2]->name);
        $this->assertSame('Alice', $sorted[3]->name);
    }

    public function test_usort_sorts_numbers_correctly(): void
    {
        $collection = new TypedCollection('int');
        $collection->add(5, 2, 8, 1, 9, 3, 6, 4, 7);

        $sorted = $collection->usort(fn ($a, $b) => $a <=> $b);

        $this->assertSame([1, 2, 3, 4, 5, 6, 7, 8, 9], $sorted->toArray());
    }

    public function test_usort_sorts_in_descending_order(): void
    {
        $collection = new TypedCollection('int');
        $collection->add(1, 2, 3, 4, 5);

        $sorted = $collection->usort(fn ($a, $b) => $b <=> $a);

        $this->assertSame([5, 4, 3, 2, 1], $sorted->toArray());
    }

    public function test_usort_returns_new_collection_instance(): void
    {
        $collection = new TypedCollection('int');
        $collection->add(3, 1, 2);

        $sorted = $collection->usort(fn ($a, $b) => $a <=> $b);

        $this->assertNotSame($collection, $sorted);
        $this->assertSame([3, 1, 2], $collection->toArray());
        $this->assertSame([1, 2, 3], $sorted->toArray());
    }

    public function test_usort_on_empty_collection_returns_empty_collection(): void
    {
        $emptyCollection = new TypedCollection('int');
        $sorted = $emptyCollection->usort(fn ($a, $b) => $a <=> $b);

        $this->assertCount(0, $sorted);
    }

    public function test_sort_by_and_usort_are_consistent(): void
    {
        $collection = new TypedCollection('int');
        $collection->add(5, 2, 8, 1, 9, 3);

        $sortByResult = $collection->sortBy(fn ($item) => $item, SORT_NUMERIC)->toArray();
        $usortResult = $collection->usort(fn ($a, $b) => $a <=> $b)->toArray();

        $this->assertSame($sortByResult, $usortResult);
        $this->assertSame([1, 2, 3, 5, 8, 9], $sortByResult);
    }

    public function test_sort_by_property_and_usort_property_are_consistent(): void
    {
        $collection = new TypedCollection(TestUserRecord::class);
        $collection->add(
            new TestUserRecord(id: 3, name: 'Charlie', email: $this->testEmail),
            new TestUserRecord(id: 1, name: 'Alice', email: $this->testEmail),
            new TestUserRecord(id: 4, name: 'David', email: $this->testEmail),
            new TestUserRecord(id: 2, name: 'Bob', email: $this->testEmail)
        );

        $sortByResult = $collection->sortBy('name')->toArray();
        $usortResult = $collection->usort(fn ($a, $b) => strcmp($a->name, $b->name))->toArray();

        $this->assertSame('Alice', $sortByResult[0]->name);
        $this->assertSame('Alice', $usortResult[0]->name);
        $this->assertSame('Bob', $sortByResult[1]->name);
        $this->assertSame('Bob', $usortResult[1]->name);
    }

    // ==================== REVERSE METHOD TESTS ====================

    public function test_reverse_reverses_order_of_items(): void
    {
        $collection = new TypedCollection('int');
        $collection->add(1, 2, 3, 4, 5);

        $reversed = $collection->reverse();

        $this->assertSame([5, 4, 3, 2, 1], $reversed->toArray());
    }

    public function test_reverse_returns_new_collection_instance(): void
    {
        $collection = new TypedCollection('int');
        $collection->add(1, 2, 3);

        $reversed = $collection->reverse();

        $this->assertNotSame($collection, $reversed);
        $this->assertSame([1, 2, 3], $collection->toArray());
        $this->assertSame([3, 2, 1], $reversed->toArray());
    }

    public function test_reverse_on_empty_collection_returns_empty_collection(): void
    {
        $emptyCollection = new TypedCollection('int');
        $reversed = $emptyCollection->reverse();

        $this->assertCount(0, $reversed);
    }

    // ==================== MERGE METHOD TESTS ====================

    public function test_merge_combines_two_collections(): void
    {
        $collection1 = new TypedCollection('int');
        $collection2 = new TypedCollection('int');
        $collection1->add(1, 2, 3);
        $collection2->add(4, 5, 6);

        $merged = $collection1->merge($collection2);

        $this->assertSame([1, 2, 3, 4, 5, 6], $merged->toArray());
    }

    public function test_merge_returns_new_collection_instance(): void
    {
        $collection1 = new TypedCollection('int');
        $collection2 = new TypedCollection('int');
        $collection1->add(1, 2);
        $collection2->add(3, 4);

        $merged = $collection1->merge($collection2);

        $this->assertNotSame($collection1, $merged);
        $this->assertNotSame($collection2, $merged);
        $this->assertSame([1, 2], $collection1->toArray());
        $this->assertSame([3, 4], $collection2->toArray());
    }

    public function test_merge_with_empty_collection_returns_original_items(): void
    {
        $collection1 = new TypedCollection('int');
        $emptyCollection = new TypedCollection('int');
        $collection1->add(1, 2, 3);

        $merged = $collection1->merge($emptyCollection);

        $this->assertSame([1, 2, 3], $merged->toArray());
    }

    // ==================== CONTAINS METHOD TESTS ====================

    public function test_contains_returns_true_when_value_exists(): void
    {
        $collection = new TypedCollection('int');
        $collection->add(1, 2, 3, 4, 5);

        $this->assertTrue($collection->contains(3));
        $this->assertTrue($collection->contains(1));
        $this->assertTrue($collection->contains(5));
    }

    public function test_contains_returns_false_when_value_does_not_exist(): void
    {
        $collection = new TypedCollection('int');
        $collection->add(1, 2, 3);

        $this->assertFalse($collection->contains(4));
        $this->assertFalse($collection->contains(0));
    }

    public function test_contains_works_with_objects(): void
    {
        $record1 = new TestUserRecord(name: 'John', email: $this->testEmail);
        $record2 = new TestUserRecord(name: 'Jane', email: $this->testEmail);
        $collection = new TypedCollection(TestUserRecord::class);
        $collection->add($record1, $record2);

        $this->assertTrue($collection->contains($record1));
        $this->assertTrue($collection->contains($record2));

        $newRecord = new TestUserRecord(name: 'John', email: $this->testEmail);
        $this->assertFalse($collection->contains($newRecord));
    }

    // ==================== EACH METHOD TESTS ====================

    public function test_each_executes_callback_for_each_item(): void
    {
        $collection = new TypedCollection('int');
        $collection->add(1, 2, 3);
        $sum = 0;

        $result = $collection->each(function ($item) use (&$sum) {
            $sum += $item;
        });

        $this->assertSame(6, $sum);
        $this->assertSame($collection, $result);
    }

    public function test_each_returns_self_for_chaining(): void
    {
        $collection = new TypedCollection('int');
        $collection->add(1, 2, 3);
        $logs = [];

        $result = $collection->each(function ($item) use (&$logs) {
            $logs[] = $item;
        })->add(4, 5);

        $this->assertSame($collection, $result);
        $this->assertSame([1, 2, 3, 4, 5], $collection->toArray());
    }

    // ==================== NORMALIZATION TESTS ====================

    public function test_normalize_returns_array_by_default(): void
    {
        $collection = new TypedCollection('int');
        $collection->add(1, 2, 3);

        $result = NormalizerChain::get()->normalize($collection);

        $this->assertIsArray($result);
        $this->assertSame([1, 2, 3], $result);
    }

    public function test_normalize_on_empty_collection_returns_empty_array(): void
    {
        $emptyCollection = new TypedCollection('int');
        $result = NormalizerChain::get()->normalize($emptyCollection);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    // ==================== JSON SERIALIZATION TESTS ====================

    public function test_collection_implements_json_serializable(): void
    {
        $collection = new TypedCollection('int');
        $this->assertInstanceOf(\JsonSerializable::class, $collection);
    }

    public function test_json_serialize_returns_items_array(): void
    {
        $collection = new TypedCollection('int');
        $collection->add(1, 2, 3);

        $serialized = $collection->jsonSerialize();

        $this->assertSame([1, 2, 3], $serialized);
    }

    public function test_collection_can_be_json_encoded(): void
    {
        $collection = new TypedCollection('int');
        $collection->add(1, 2, 3);

        $json = json_encode($collection);

        $this->assertSame('[1,2,3]', $json);
    }

    // ==================== MAGIC TO_STRING TESTS ====================

    public function test_to_string_returns_json_representation(): void
    {
        $collection = new TypedCollection('string');
        $collection->add('a', 'b', 'c');

        $string = (string) $collection;

        $this->assertIsString($string);
        $this->assertJson($string);
        $this->assertSame('["a","b","c"]', $string);
    }

    // ==================== CLONING TESTS ====================

    public function test_clone_creates_deep_copy_of_collection(): void
    {
        $original = new TypedCollection('int');
        $original->add(1, 2, 3);

        $cloned = clone $original;
        $cloned->add(4, 5);

        $this->assertSame([1, 2, 3], $original->toArray());
        $this->assertSame([1, 2, 3, 4, 5], $cloned->toArray());
    }

    public function test_clone_creates_deep_copy_of_object_items(): void
    {
        $original = new TypedCollection(TestUserRecord::class);
        $user = new TestUserRecord(name: 'John', email: $this->testEmail);
        $original->add($user);

        $cloned = clone $original;
        $originalItem = $original->toArray()[0];
        $clonedItem = $cloned->toArray()[0];

        $this->assertNotSame($originalItem, $clonedItem);
        $this->assertEquals($originalItem, $clonedItem);
    }

    // ==================== ARRAY ACCESS TESTS ====================

    public function test_array_access_offset_get_returns_item_at_index(): void
    {
        $collection = new TypedCollection('string');
        $collection->add('a', 'b', 'c');

        $this->assertSame('a', $collection[0]);
        $this->assertSame('b', $collection[1]);
        $this->assertSame('c', $collection[2]);
    }

    public function test_array_access_offset_exists_checks_index_existence(): void
    {
        $collection = new TypedCollection('string');
        $collection->add('a', 'b', 'c');

        $this->assertTrue(isset($collection[0]));
        $this->assertTrue(isset($collection[1]));
        $this->assertTrue(isset($collection[2]));
        $this->assertFalse(isset($collection[3]));
    }

    public function test_array_access_offset_set_adds_item_at_specified_index(): void
    {
        $collection = new TypedCollection('string');
        $collection->add('a', 'b');

        $collection[2] = 'c';

        $this->assertSame('c', $collection[2]);
        $this->assertCount(3, $collection);
    }

    public function test_array_access_offset_set_with_null_index_appends(): void
    {
        $collection = new TypedCollection('string');
        $collection[] = 'a';
        $collection[] = 'b';
        $collection[] = 'c';

        $this->assertSame(['a', 'b', 'c'], $collection->toArray());
    }

    public function test_array_access_offset_set_validates_type(): void
    {
        $collection = new TypedCollection('int');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected type(s) int');

        $collection[0] = 'not an integer';
    }

    public function test_array_access_offset_unset_removes_item(): void
    {
        $collection = new TypedCollection('string');
        $collection->add('a', 'b', 'c');

        unset($collection[1]);

        $this->assertCount(2, $collection);
        $this->assertSame('a', $collection[0]);
        $this->assertSame('c', $collection[2]);
    }

    // ==================== ITERATOR TESTS ====================

    public function test_collection_is_traversable_with_foreach(): void
    {
        $collection = new TypedCollection('int');
        $collection->add(1, 2, 3, 4, 5);
        $items = [];

        foreach ($collection as $item) {
            $items[] = $item;
        }

        $this->assertSame([1, 2, 3, 4, 5], $items);
    }

    public function test_get_iterator_returns_array_iterator(): void
    {
        $collection = new TypedCollection('int');
        $collection->add(1, 2, 3);

        $iterator = $collection->getIterator();

        $this->assertInstanceOf(\ArrayIterator::class, $iterator);
        $this->assertSame([1, 2, 3], iterator_to_array($iterator));
    }

    // ==================== FROM METHOD TESTS ====================

    public function test_string_typed_collection_from_array_of_strings(): void
    {
        $source = ['apple', 'banana', 'cherry'];
        $collection = StringTypedCollection::from($source);

        $this->assertCount(3, $collection);
        $this->assertSame(['apple', 'banana', 'cherry'], $collection->toArray());
    }

    public function test_string_typed_collection_from_existing_collection(): void
    {
        $original = new StringTypedCollection;
        $original->add('a', 'b', 'c');

        $collection = StringTypedCollection::from($original);

        $this->assertSame($original, $collection);
    }

    public function test_string_typed_collection_from_non_iterable_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        StringTypedCollection::from('not an iterable');
    }

    public function test_int_typed_collection_from_array_of_ints(): void
    {
        $source = [1, 2, 3, 4, 5];
        $collection = IntTypedCollection::from($source);

        $this->assertSame([1, 2, 3, 4, 5], $collection->toArray());
    }

    // ==================== NESTED FROM METHOD TESTS ====================

    public function test_collection_from_array_of_arrays_with_nested_data(): void
    {
        $source = [
            [
                '_type' => TestUserData::class,
                'id' => 1,
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'status' => 'active',
                'role' => 'admin',
                'grade' => 4,
                'created_at' => '2024-01-01T12:00:00+00:00',
            ],
            [
                '_type' => TestUserData::class,
                'id' => 2,
                'name' => 'Jane Doe',
                'email' => 'jane@example.com',
                'status' => 'active',
                'role' => 'user',
                'grade' => 2,
                'created_at' => '2024-01-02T12:00:00+00:00',
            ],
        ];

        $collection = new DataCollection(TestUserData::class);
        $result = $collection::from($source);

        $this->assertCount(2, $result);
        $this->assertInstanceOf(TestUserData::class, $result[0]);
        $this->assertSame('John Doe', $result[0]->name);
    }

    public function test_collection_from_array_with_invalid_items_throws_exception(): void
    {
        $source = [1, 2, 3];
        $collection = new TypedCollection(TestUserData::class);

        $this->expectException(InvalidArgumentException::class);
        $collection->from($source);
    }

    public function test_collection_from_empty_source_returns_empty_collection(): void
    {
        $collection = new TypedCollection(TestUserData::class);
        $result = $collection->from([]);

        $this->assertCount(0, $result);
        $this->assertTrue($result->isEmpty());
    }

    public function test_scalar_typed_collection_from_array(): void
    {
        $source = [1, 2, 3, 4, 5];
        $collection = IntTypedCollection::from($source);

        $this->assertCount(5, $collection);
        $this->assertSame([1, 2, 3, 4, 5], $collection->toArray());
    }

    // ==================== AMBIGUOUS TYPE DETECTION TESTS ====================

    public function test_ambiguous_item_throws_exception_when_no_type_specified(): void
    {
        $collection = new TypedCollection(TestProductData::class, TestProductRecord::class);

        $source = [
            ['id' => 1, 'name' => 'Laptop', 'price' => 999],
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Ambiguous item #1');

        $collection->from($source);
    }

    public function test_ambiguous_item_works_with_type_specified(): void
    {
        $collection = new TypedCollection(TestProductData::class, TestProductRecord::class);

        $source = [
            ['_type' => TestProductRecord::class, 'id' => 1, 'name' => 'Laptop', 'price' => 999],
        ];

        $result = $collection->from($source);

        $this->assertCount(1, $result);
        $this->assertInstanceOf(TestProductRecord::class, $result[0]);
    }

    public function test_ambiguous_item_with_wrong_type_throws_exception(): void
    {
        $collection = new TypedCollection(TestProductData::class, TestProductRecord::class);

        $source = [
            ['_type' => 'InvalidType', 'id' => 1, 'name' => 'Laptop', 'price' => 999],
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Type "InvalidType" specified in "_type" is not allowed');

        $collection->from($source);
    }

    public function test_non_ambiguous_items_work_without_type(): void
    {
        $collection = new TypedCollection(TestUserData::class, TestProductData::class);

        $source = [
            ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com', 'status' => 'active', 'role' => 'user', 'grade' => 1, 'created_at' => '2024-01-01T12:00:00+00:00'],
            ['id' => 2, 'name' => 'Jane Doe', 'email' => 'jane@example.com', 'status' => 'active', 'role' => 'admin', 'grade' => 4, 'created_at' => '2024-01-02T12:00:00+00:00'],
        ];

        $result = $collection->from($source);

        $this->assertCount(2, $result);
        $this->assertInstanceOf(TestUserData::class, $result[0]);
        $this->assertInstanceOf(TestUserData::class, $result[1]);
    }

    public function test_mixed_types_in_collection_with_explicit_type(): void
    {
        $collection = new TypedCollection(TestUserData::class, TestProductData::class);

        $source = [
            ['_type' => TestUserData::class, 'id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com', 'status' => 'active', 'role' => 'user', 'grade' => 1, 'created_at' => '2024-01-01T12:00:00+00:00'],
            ['_type' => TestProductData::class, 'id' => 2, 'name' => 'Laptop', 'price' => 999, 'isFeatured' => true],
        ];

        $result = $collection->from($source);

        $this->assertCount(2, $result);
        $this->assertInstanceOf(TestUserData::class, $result[0]);
        $this->assertInstanceOf(TestProductData::class, $result[1]);
    }

    // ==================== FROM JSON METHOD TESTS ====================

    public function test_int_typed_collection_from_json(): void
    {
        $array = [10, 20, 30, 40, 50];
        $json = json_encode($array);

        $collection = IntTypedCollection::fromJson($json);

        $this->assertCount(5, $collection);
        $this->assertSame([10, 20, 30, 40, 50], $collection->toArray());
    }

    public function test_string_typed_collection_from_json(): void
    {
        $array = ['apple', 'banana', 'cherry'];
        $json = json_encode($array);

        $collection = StringTypedCollection::fromJson($json);

        $this->assertCount(3, $collection);
        $this->assertSame(['apple', 'banana', 'cherry'], $collection->toArray());
    }

    public function test_data_collection_from_json_with_nested_data(): void
    {
        $array = [
            ['id' => 1, 'name' => 'Product A', 'price' => 100, 'isFeatured' => true],
            ['id' => 2, 'name' => 'Product B', 'price' => 200, 'isFeatured' => false],
        ];
        $json = json_encode($array);

        $collection = new DataCollection(TestProductData::class);
        $result = $collection::fromJson($json);

        $this->assertCount(2, $result);
        $this->assertInstanceOf(TestProductData::class, $result[0]);
        $this->assertSame(1, $result[0]->id);
        $this->assertSame('Product A', $result[0]->name);
        $this->assertTrue($result[0]->isFeatured);
        $this->assertInstanceOf(TestProductData::class, $result[1]);
        $this->assertSame(2, $result[1]->id);
        $this->assertSame('Product B', $result[1]->name);
        $this->assertFalse($result[1]->isFeatured);
    }

    public function test_record_collection_from_json(): void
    {
        $array = [
            ['id' => 1, 'name' => 'User 1', 'email' => 'user1@example.com'],
            ['id' => 2, 'name' => 'User 2', 'email' => 'user2@example.com'],
        ];
        $json = json_encode($array);

        $collection = new RecordCollection(TestUserRecord::class);
        $result = $collection::fromJson($json);

        $this->assertCount(2, $result);
        $this->assertInstanceOf(TestUserRecord::class, $result[0]);
        $this->assertSame(1, $result[0]->id);
        $this->assertSame('User 1', $result[0]->name);
    }

    public function test_collection_from_json_with_explicit_types(): void
    {
        $array = [
            ['_type' => TestUserData::class, 'id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com', 'status' => 'active', 'role' => 'user', 'grade' => 1],
            ['_type' => TestProductData::class, 'id' => 2, 'name' => 'Laptop', 'price' => 999.99],
        ];
        $json = json_encode($array);

        $collection = new TypedCollection(TestUserData::class, TestProductData::class);
        $result = $collection::fromJson($json);

        $this->assertCount(2, $result);
        $this->assertInstanceOf(TestUserData::class, $result[0]);
        $this->assertSame(1, $result[0]->id);
        $this->assertSame('John Doe', $result[0]->name);
        $this->assertInstanceOf(TestProductData::class, $result[1]);
        $this->assertSame(2, $result[1]->id);
        $this->assertSame('Laptop', $result[1]->name);
    }

    public function test_collection_from_json_with_empty_array(): void
    {
        $json = json_encode([]);

        $collection = new TypedCollection(TestUserData::class);
        $result = $collection::fromJson($json);

        $this->assertCount(0, $result);
        $this->assertTrue($result->isEmpty());
    }

    public function test_collection_from_json_throws_exception_for_invalid_json(): void
    {
        $invalidJson = '{invalid json}';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid JSON');

        TypedCollection::fromJson($invalidJson);
    }

    public function test_collection_from_json_throws_exception_for_malformed_json(): void
    {
        $malformedJson = '[1, 2, 3';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid JSON');

        TypedCollection::fromJson($malformedJson);
    }

    public function test_collection_from_json_preserves_order(): void
    {
        $array = ['first', 'second', 'third', 'fourth'];
        $json = json_encode($array);

        $collection = StringTypedCollection::fromJson($json);

        $this->assertSame('first', $collection[0]);
        $this->assertSame('second', $collection[1]);
        $this->assertSame('third', $collection[2]);
        $this->assertSame('fourth', $collection[3]);
    }
}
