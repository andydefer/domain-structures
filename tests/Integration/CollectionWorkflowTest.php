<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Integration;

use AndyDefer\DomainStructures\Collections\Core\TypedCollection;
use AndyDefer\DomainStructures\Collections\Utility\IntTypedCollection;
use AndyDefer\DomainStructures\Collections\Utility\StringTypedCollection;
use AndyDefer\DomainStructures\Normalizers\NormalizerChain;
use AndyDefer\DomainStructures\Tests\Fixtures\Collections\ProductRecordCollection;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestBackedIntEnum;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestBackedStringEnum;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestUserStatus;
use AndyDefer\DomainStructures\Tests\Fixtures\Records\TestProductRecord;
use AndyDefer\DomainStructures\Tests\Fixtures\Records\TestUserRecord;
use AndyDefer\DomainStructures\Tests\Fixtures\ValueObjects\TestEmailAddress;
use AndyDefer\DomainStructures\Tests\TestCase;
use InvalidArgumentException;

final class CollectionWorkflowTest extends TestCase
{
    private TypedCollection $mixedCollection;

    private IntTypedCollection $intCollection;

    private StringTypedCollection $stringCollection;

    private ProductRecordCollection $productCollection;

    protected function setUp(): void
    {
        parent::setUp();

        // ✅ CORRECTION: TypedCollection nécessite des types explicites
        $this->mixedCollection = new TypedCollection('int', 'string', 'float', 'bool', 'null', TestUserStatus::class, TestUserRecord::class);
        $this->intCollection = new IntTypedCollection;
        $this->stringCollection = new StringTypedCollection;
        $this->productCollection = new ProductRecordCollection;
    }

    // ==================== CREATION AND TYPE VALIDATION TESTS ====================

    public function test_constructor_accepts_valid_types(): void
    {
        $collection = new TypedCollection('int', 'string');
        $this->assertSame(['int', 'string'], $collection->getAllowedTypes());
    }

    public function test_constructor_throws_exception_when_no_types_provided(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('At least one allowed type must be provided');

        new TypedCollection;
    }

    public function test_constructor_throws_exception_for_invalid_type(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Type "NonExistentClass" is not allowed');

        new TypedCollection('NonExistentClass');
    }

    public function test_mixed_collection_accepts_all_types_by_default(): void
    {
        // ✅ CORRECTION: On doit spécifier les types qu'on veut accepter
        $collection = new TypedCollection('int', 'string', 'float', 'bool', 'null', TestUserStatus::class, TestUserRecord::class);
        $collection->add(42, 3.14, 'string', true, null, TestUserStatus::ACTIVE, new TestUserRecord(name: 'Test', email: TestEmailAddress::from('test@test.com')));

        $this->assertCount(7, $collection);
    }

    // ==================== ADD AND VALIDATION TESTS ====================

    public function test_add_appends_valid_items_to_collection(): void
    {
        $collection = new IntTypedCollection;
        $collection->add(1, 2, 3);
        $collection->add(4, 5);

        $this->assertCount(5, $collection);
        $this->assertSame([1, 2, 3, 4, 5], $collection->toArray());
    }

    public function test_add_throws_exception_for_invalid_type(): void
    {
        $collection = new IntTypedCollection;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected type(s) int');

        $collection->add('not an integer');
    }

    public function test_add_throws_exception_for_enum_not_in_allowed_types(): void
    {
        $collection = new TypedCollection(TestUserStatus::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Expected type.*TestUserStatus/');

        $collection->add(TestBackedIntEnum::VALUE_ONE);
    }

    public function test_add_accepts_multiple_items_in_one_call(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('apple', 'banana', 'cherry', 'date');

        $this->assertCount(4, $collection);
        $this->assertSame(['apple', 'banana', 'cherry', 'date'], $collection->toArray());
    }

    public function test_add_returns_self_for_chaining(): void
    {
        $result = $this->intCollection->add(1)->add(2)->add(3);

        $this->assertSame($this->intCollection, $result);
        $this->assertCount(3, $this->intCollection);
    }

    // ==================== COUNT AND EMPTINESS TESTS ====================

    public function test_count_returns_correct_number_of_items(): void
    {
        $collection = new IntTypedCollection;

        $this->assertCount(0, $collection);
        $this->assertSame(0, $collection->count());

        $collection->add(1, 2, 3);
        $this->assertCount(3, $collection);
        $this->assertSame(3, $collection->count());

        $collection->add(4);
        $this->assertCount(4, $collection);
    }

    public function test_is_empty_returns_true_when_collection_empty(): void
    {
        $collection = new StringTypedCollection;

        $this->assertTrue($collection->isEmpty());
        $this->assertFalse($collection->isNotEmpty());
    }

    public function test_is_empty_returns_false_when_collection_not_empty(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('test');

        $this->assertFalse($collection->isEmpty());
        $this->assertTrue($collection->isNotEmpty());
    }

    // ==================== MAP TRANSFORMATION TESTS ====================

    public function test_map_transforms_each_item_and_returns_new_collection(): void
    {
        $this->intCollection->add(1, 2, 3, 4, 5);

        $doubled = $this->intCollection->map(fn ($item) => $item * 2);

        $this->assertNotSame($this->intCollection, $doubled);
        $this->assertInstanceOf(TypedCollection::class, $doubled);
        $this->assertSame([2, 4, 6, 8, 10], $doubled->toArray());
        $this->assertSame([1, 2, 3, 4, 5], $this->intCollection->toArray());
    }

    public function test_map_can_change_collection_type_based_on_return_value(): void
    {
        $this->intCollection->add(1, 2, 3);

        $stringCollection = $this->intCollection->map(fn ($item) => "Number: {$item}");

        $this->assertInstanceOf(TypedCollection::class, $stringCollection);
        $this->assertSame(['Number: 1', 'Number: 2', 'Number: 3'], $stringCollection->toArray());
    }

    public function test_map_on_empty_collection_returns_empty_collection(): void
    {
        $emptyCollection = new IntTypedCollection;
        $result = $emptyCollection->map(fn ($item) => $item * 2);

        $this->assertCount(0, $result);
        $this->assertEmpty($result->toArray());
    }

    public function test_map_preserves_sequential_order(): void
    {
        $this->stringCollection->add('a', 'b', 'c');

        $uppercase = $this->stringCollection->map(fn ($item) => strtoupper($item));

        $this->assertSame(['A', 'B', 'C'], $uppercase->toArray());
    }

    // ==================== FILTER TESTS ====================

    public function test_filter_keeps_only_items_satisfying_callback(): void
    {
        $this->intCollection->add(1, 2, 3, 4, 5, 6, 7, 8, 9, 10);

        $evenNumbers = $this->intCollection->filter(fn ($item) => $item % 2 === 0);

        $this->assertSame([2, 4, 6, 8, 10], $evenNumbers->toArray());
        $this->assertCount(5, $evenNumbers);
    }

    public function test_filter_returns_empty_collection_when_no_items_match(): void
    {
        $this->intCollection->add(1, 3, 5, 7, 9);

        $evenNumbers = $this->intCollection->filter(fn ($item) => $item % 2 === 0);

        $this->assertCount(0, $evenNumbers);
        $this->assertTrue($evenNumbers->isEmpty());
    }

    public function test_filter_returns_new_collection_instance(): void
    {
        $this->intCollection->add(1, 2, 3);

        $filtered = $this->intCollection->filter(fn ($item) => $item > 1);

        $this->assertNotSame($this->intCollection, $filtered);
        $this->assertSame([1, 2, 3], $this->intCollection->toArray());
        $this->assertSame([2, 3], $filtered->toArray());
    }

    public function test_filter_works_with_string_collection(): void
    {
        $this->stringCollection->add('apple', 'banana', 'cherry', 'date', 'elderberry');

        $longStrings = $this->stringCollection->filter(fn ($item) => strlen($item) > 5);

        $this->assertSame(['banana', 'cherry', 'elderberry'], $longStrings->toArray());
    }

    // ==================== REDUCE TESTS ====================

    public function test_reduce_aggregates_values_correctly(): void
    {
        $this->intCollection->add(1, 2, 3, 4, 5);

        $sum = $this->intCollection->reduce(fn ($carry, $item) => $carry + $item, 0);

        $this->assertSame(15, $sum);
    }

    public function test_reduce_with_string_concatenation(): void
    {
        $this->stringCollection->add('Hello', ' ', 'World', '!');

        $result = $this->stringCollection->reduce(fn ($carry, $item) => $carry.$item, '');

        $this->assertSame('Hello World!', $result);
    }

    public function test_reduce_with_multiplication(): void
    {
        $this->intCollection->add(1, 2, 3, 4);

        $product = $this->intCollection->reduce(fn ($carry, $item) => $carry * $item, 1);

        $this->assertSame(24, $product);
    }

    public function test_reduce_on_empty_collection_returns_initial_value(): void
    {
        $emptyCollection = new IntTypedCollection;
        $result = $emptyCollection->reduce(fn ($carry, $item) => $carry + $item, 100);

        $this->assertSame(100, $result);
    }

    // ==================== FIND TESTS ====================

    public function test_find_returns_first_item_matching_predicate(): void
    {
        $this->intCollection->add(1, 3, 5, 6, 7, 8);

        $firstEven = $this->intCollection->find(fn ($item) => $item % 2 === 0);

        $this->assertSame(6, $firstEven);
    }

    public function test_find_returns_null_when_no_item_matches(): void
    {
        $this->intCollection->add(1, 3, 5, 7);

        $result = $this->intCollection->find(fn ($item) => $item % 2 === 0);

        $this->assertNull($result);
    }

    public function test_find_returns_first_match_only(): void
    {
        $this->stringCollection->add('apple', 'banana', 'avocado', 'apricot');

        $firstA = $this->stringCollection->find(fn ($item) => str_starts_with($item, 'a'));

        $this->assertSame('apple', $firstA);
    }

    public function test_find_on_empty_collection_returns_null(): void
    {
        $emptyCollection = new StringTypedCollection;
        $result = $emptyCollection->find(fn ($item) => true);

        $this->assertNull($result);
    }

    // ==================== EVERY AND SOME TESTS ====================

    public function test_every_returns_true_when_all_items_satisfy_predicate(): void
    {
        $this->intCollection->add(2, 4, 6, 8, 10);

        $allEven = $this->intCollection->every(fn ($item) => $item % 2 === 0);

        $this->assertTrue($allEven);
    }

    public function test_every_returns_false_when_any_item_fails_predicate(): void
    {
        $this->intCollection->add(2, 4, 5, 6, 8);

        $allEven = $this->intCollection->every(fn ($item) => $item % 2 === 0);

        $this->assertFalse($allEven);
    }

    public function test_every_on_empty_collection_returns_true(): void
    {
        $emptyCollection = new IntTypedCollection;
        $result = $emptyCollection->every(fn ($item) => $item > 100);

        $this->assertTrue($result);
    }

    public function test_some_returns_true_when_at_least_one_item_satisfies_predicate(): void
    {
        $this->intCollection->add(1, 3, 5, 6, 7, 9);

        $hasEven = $this->intCollection->some(fn ($item) => $item % 2 === 0);

        $this->assertTrue($hasEven);
    }

    public function test_some_returns_false_when_no_items_satisfy_predicate(): void
    {
        $this->intCollection->add(1, 3, 5, 7, 9);

        $hasEven = $this->intCollection->some(fn ($item) => $item % 2 === 0);

        $this->assertFalse($hasEven);
    }

    public function test_some_on_empty_collection_returns_false(): void
    {
        $emptyCollection = new IntTypedCollection;
        $result = $emptyCollection->some(fn ($item) => true);

        $this->assertFalse($result);
    }

    // ==================== SORT TESTS ====================

    public function test_sort_orders_items_in_ascending_order(): void
    {
        $this->intCollection->add(5, 2, 8, 1, 9, 3);

        $sorted = $this->intCollection->sort();

        $this->assertSame([1, 2, 3, 5, 8, 9], $sorted->toArray());
    }

    public function test_sort_returns_new_collection_instance(): void
    {
        $this->intCollection->add(3, 1, 2);

        $sorted = $this->intCollection->sort();

        $this->assertNotSame($this->intCollection, $sorted);
        $this->assertSame([1, 2, 3], $sorted->toArray());
        $this->assertSame([3, 1, 2], $this->intCollection->toArray());
    }

    public function test_sort_with_numeric_flag_works(): void
    {
        $collection = new TypedCollection('int', 'float');
        $collection->add(5, 2.5, 8, 1.2, 9, 3.7);

        $sorted = $collection->sort(SORT_NUMERIC);

        $this->assertSame([1.2, 2.5, 3.7, 5, 8, 9], $sorted->toArray());
    }

    public function test_sort_on_empty_collection_returns_empty_collection(): void
    {
        $emptyCollection = new IntTypedCollection;
        $sorted = $emptyCollection->sort();

        $this->assertCount(0, $sorted);
        $this->assertEmpty($sorted->toArray());
    }

    // ==================== REVERSE TESTS ====================

    public function test_reverse_reverses_order_of_items(): void
    {
        $this->intCollection->add(1, 2, 3, 4, 5);

        $reversed = $this->intCollection->reverse();

        $this->assertSame([5, 4, 3, 2, 1], $reversed->toArray());
    }

    public function test_reverse_returns_new_collection_instance(): void
    {
        $this->stringCollection->add('a', 'b', 'c');

        $reversed = $this->stringCollection->reverse();

        $this->assertNotSame($this->stringCollection, $reversed);
        $this->assertSame(['c', 'b', 'a'], $reversed->toArray());
        $this->assertSame(['a', 'b', 'c'], $this->stringCollection->toArray());
    }

    public function test_reverse_on_single_item_returns_single_item(): void
    {
        $this->intCollection->add(42);

        $reversed = $this->intCollection->reverse();

        $this->assertSame([42], $reversed->toArray());
    }

    public function test_reverse_on_empty_collection_returns_empty_collection(): void
    {
        $emptyCollection = new IntTypedCollection;
        $reversed = $emptyCollection->reverse();

        $this->assertCount(0, $reversed);
    }

    // ==================== MERGE TESTS ====================

    public function test_merge_combines_two_collections(): void
    {
        $collection1 = new IntTypedCollection;
        $collection2 = new IntTypedCollection;
        $collection1->add(1, 2, 3);
        $collection2->add(4, 5, 6);

        $merged = $collection1->merge($collection2);

        $this->assertSame([1, 2, 3, 4, 5, 6], $merged->toArray());
        $this->assertCount(6, $merged);
    }

    public function test_merge_returns_new_collection_instance(): void
    {
        $collection1 = new IntTypedCollection;
        $collection2 = new IntTypedCollection;
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
        $collection1 = new IntTypedCollection;
        $emptyCollection = new IntTypedCollection;
        $collection1->add(1, 2, 3);

        $merged = $collection1->merge($emptyCollection);

        $this->assertSame([1, 2, 3], $merged->toArray());
    }

    public function test_merge_preserves_allowed_types(): void
    {
        $collection1 = new TypedCollection('int', 'string');
        $collection2 = new TypedCollection('int', 'string');
        $collection1->add(1, 'two');
        $collection2->add(3, 'four');

        $merged = $collection1->merge($collection2);

        $this->assertSame(['int', 'string'], $merged->getAllowedTypes());
        $this->assertSame([1, 'two', 3, 'four'], $merged->toArray());
    }

    // ==================== CONTAINS TESTS ====================

    public function test_contains_returns_true_when_value_exists(): void
    {
        $this->stringCollection->add('apple', 'banana', 'cherry');

        $this->assertTrue($this->stringCollection->contains('banana'));
        $this->assertTrue($this->stringCollection->contains('apple'));
        $this->assertTrue($this->stringCollection->contains('cherry'));
    }

    public function test_contains_returns_false_when_value_does_not_exist(): void
    {
        $this->stringCollection->add('apple', 'banana', 'cherry');

        $this->assertFalse($this->stringCollection->contains('grape'));
        $this->assertFalse($this->stringCollection->contains('orange'));
    }

    public function test_contains_with_enum_values_works(): void
    {
        $collection = new TypedCollection(TestUserStatus::class);
        $collection->add(TestUserStatus::ACTIVE, TestUserStatus::INACTIVE);

        $this->assertTrue($collection->contains(TestUserStatus::ACTIVE));
        $this->assertTrue($collection->contains(TestUserStatus::INACTIVE));
        $this->assertFalse($collection->contains(TestUserStatus::SUSPENDED));
    }

    public function test_contains_with_objects_works(): void
    {
        $user1 = new TestUserRecord(name: 'John', email: TestEmailAddress::from('john@test.com'));
        $user2 = new TestUserRecord(name: 'Jane', email: TestEmailAddress::from('jane@test.com'));
        $collection = new TypedCollection(TestUserRecord::class);
        $collection->add($user1, $user2);

        $this->assertTrue($collection->contains($user1));
        $this->assertTrue($collection->contains($user2));

        $newUser = new TestUserRecord(name: 'John', email: TestEmailAddress::from('john@test.com'));
        $this->assertFalse($collection->contains($newUser));
    }

    // ==================== EACH (ITERATION) TESTS ====================

    public function test_each_executes_callback_for_each_item(): void
    {
        $this->intCollection->add(1, 2, 3);
        $sum = 0;

        $result = $this->intCollection->each(function ($item) use (&$sum) {
            $sum += $item;
        });

        $this->assertSame(6, $sum);
        $this->assertSame($this->intCollection, $result);
    }

    public function test_each_returns_self_for_chaining(): void
    {
        $this->intCollection->add(1, 2, 3);
        $logs = [];

        $result = $this->intCollection->each(function ($item) use (&$logs) {
            $logs[] = $item;
        })->add(4, 5);

        $this->assertSame($this->intCollection, $result);
        $this->assertSame([1, 2, 3, 4, 5], $this->intCollection->toArray());
    }

    public function test_each_on_empty_collection_does_nothing(): void
    {
        $emptyCollection = new IntTypedCollection;
        $counter = 0;

        $emptyCollection->each(function () use (&$counter) {
            $counter++;
        });

        $this->assertSame(0, $counter);
    }

    // ==================== ARRAY ACCESS TESTS ====================

    public function test_array_access_offset_get_returns_item_at_index(): void
    {
        $this->stringCollection->add('apple', 'banana', 'cherry');

        $this->assertSame('apple', $this->stringCollection[0]);
        $this->assertSame('banana', $this->stringCollection[1]);
        $this->assertSame('cherry', $this->stringCollection[2]);
    }

    public function test_array_access_offset_get_returns_null_for_invalid_index(): void
    {
        $this->intCollection->add(1, 2, 3);

        $this->assertNull($this->intCollection[10]);
        $this->assertNull($this->intCollection[-1]);
    }

    public function test_array_access_offset_exists_checks_index_existence(): void
    {
        $this->intCollection->add(1, 2, 3);

        $this->assertTrue(isset($this->intCollection[0]));
        $this->assertTrue(isset($this->intCollection[1]));
        $this->assertTrue(isset($this->intCollection[2]));
        $this->assertFalse(isset($this->intCollection[3]));
    }

    public function test_array_access_offset_set_adds_item_at_specified_index(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('first', 'second');

        $collection[2] = 'third';

        $this->assertSame('third', $collection[2]);
        $this->assertCount(3, $collection);
    }

    public function test_array_access_offset_set_with_null_index_appends(): void
    {
        $collection = new StringTypedCollection;
        $collection[] = 'first';
        $collection[] = 'second';
        $collection[] = 'third';

        $this->assertSame(['first', 'second', 'third'], $collection->toArray());
    }

    public function test_array_access_offset_set_validates_type(): void
    {
        $collection = new IntTypedCollection;

        $this->expectException(InvalidArgumentException::class);
        $collection[0] = 'not an integer';
    }

    public function test_array_access_offset_unset_removes_item(): void
    {
        $this->stringCollection->add('apple', 'banana', 'cherry');

        unset($this->stringCollection[1]);

        $this->assertCount(2, $this->stringCollection);
        $this->assertSame('apple', $this->stringCollection[0]);
        $this->assertSame('cherry', $this->stringCollection[2]);
    }

    // ==================== ITERATOR TESTS ====================

    public function test_collection_is_iterable_with_foreach(): void
    {
        $this->intCollection->add(1, 2, 3, 4, 5);
        $items = [];

        foreach ($this->intCollection as $item) {
            $items[] = $item;
        }

        $this->assertSame([1, 2, 3, 4, 5], $items);
    }

    public function test_get_iterator_returns_array_iterator(): void
    {
        $this->intCollection->add(1, 2, 3);

        $iterator = $this->intCollection->getIterator();

        $this->assertInstanceOf(\ArrayIterator::class, $iterator);
        $this->assertSame([1, 2, 3], iterator_to_array($iterator));
    }

    // ==================== TO_ARRAY TESTS ====================

    public function test_to_array_returns_plain_array(): void
    {
        $this->stringCollection->add('a', 'b', 'c');

        $array = $this->stringCollection->toArray();

        $this->assertIsArray($array);
        $this->assertSame(['a', 'b', 'c'], $array);
    }

    public function test_to_array_on_empty_collection_returns_empty_array(): void
    {
        $emptyCollection = new StringTypedCollection;
        $array = $emptyCollection->toArray();

        $this->assertIsArray($array);
        $this->assertEmpty($array);
    }

    // ==================== JSON SERIALIZATION TESTS ====================

    public function test_collection_implements_json_serializable(): void
    {
        $this->assertInstanceOf(\JsonSerializable::class, $this->intCollection);
    }

    public function test_json_serialize_returns_items_array(): void
    {
        $this->intCollection->add(1, 2, 3);

        $serialized = $this->intCollection->jsonSerialize();

        $this->assertSame([1, 2, 3], $serialized);
    }

    public function test_collection_can_be_json_encoded(): void
    {
        $this->intCollection->add(1, 2, 3);

        $json = json_encode($this->intCollection);

        $this->assertSame('[1,2,3]', $json);
    }

    // ==================== NORMALIZATION TESTS ====================

    public function test_normalize_returns_array_by_default(): void
    {
        $this->intCollection->add(1, 2, 3);

        $result = NormalizerChain::get()->normalize($this->intCollection);

        $this->assertIsArray($result);
        $this->assertSame([1, 2, 3], $result);
    }

    public function test_normalize_handles_nested_objects(): void
    {
        $product1 = new TestProductRecord(id: 1, name: 'Product 1', price: 100);
        $product2 = new TestProductRecord(id: 2, name: 'Product 2', price: 200);
        $this->productCollection->add($product1, $product2);

        $result = NormalizerChain::get()->normalize($this->productCollection);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals(1, $result[0]['id']);
        $this->assertSame('Product 1', $result[0]['name']);
        $this->assertEquals(100, $result[0]['price']);
    }

    // ==================== TO_STRING TESTS ====================

    public function test_to_string_returns_string_representation(): void
    {
        $collection = new TypedCollection('int', 'string');
        $collection->add(1, 'two', 3);

        $string = (string) $collection;

        $this->assertIsString($string);
        $this->assertJson($string);
        $decoded = json_decode($string, true);
        $this->assertSame([1, 'two', 3], $decoded);
    }

    // ==================== CLONING TESTS ====================

    public function test_clone_creates_deep_copy_of_collection(): void
    {
        $original = new IntTypedCollection;
        $original->add(1, 2, 3);

        $cloned = clone $original;
        $cloned->add(4, 5);

        $this->assertSame([1, 2, 3], $original->toArray());
        $this->assertSame([1, 2, 3, 4, 5], $cloned->toArray());
    }

    public function test_clone_creates_deep_copy_of_object_items(): void
    {
        $original = new TypedCollection(TestUserRecord::class);
        $user = new TestUserRecord(name: 'John', email: TestEmailAddress::from('john@test.com'));
        $original->add($user);

        $cloned = clone $original;
        $firstOriginal = $original[0];
        $firstCloned = $cloned[0];

        $this->assertNotSame($firstOriginal, $firstCloned);
        $this->assertEquals($firstOriginal, $firstCloned);
    }

    // ==================== COMPLEX WORKFLOW TESTS ====================

    public function test_complete_workflow_with_chaining_operations(): void
    {
        $collection = new IntTypedCollection;
        $collection->add(5, 2, 8, 1, 9, 3, 6, 4, 7);

        $result = $collection
            ->filter(fn ($n) => $n % 2 === 0)
            ->map(fn ($n) => $n * 10)
            ->sort()
            ->reverse();

        $this->assertSame([80, 60, 40, 20], $result->toArray());
        $this->assertCount(4, $result);
    }

    public function test_complex_workflow_with_custom_objects(): void
    {
        $products = new ProductRecordCollection;
        $products->add(
            new TestProductRecord(id: 1, name: 'Laptop', price: 999, isFeatured: true),
            new TestProductRecord(id: 2, name: 'Mouse', price: 29, isFeatured: false),
            new TestProductRecord(id: 3, name: 'Keyboard', price: 89, isFeatured: true),
            new TestProductRecord(id: 4, name: 'Monitor', price: 299, isFeatured: false),
            new TestProductRecord(id: 5, name: 'Desk', price: 499, isFeatured: true),
        );

        $featuredProductNames = $products
            ->filter(fn ($product) => $product->isFeatured === true)
            ->filter(fn ($product) => ($product->price ?? 0) > 100)
            ->map(fn ($product) => $product->name)
            ->sort();

        $this->assertCount(2, $featuredProductNames);
        $this->assertSame(['Desk', 'Laptop'], $featuredProductNames->toArray());
    }

    public function test_collection_with_enums_and_string_operations(): void
    {
        $enums = new TypedCollection(TestBackedStringEnum::class);
        $enums->add(
            TestBackedStringEnum::VALUE_ONE,
            TestBackedStringEnum::VALUE_TWO,
            TestBackedStringEnum::VALUE_THREE
        );

        $values = $enums->map(fn ($enum) => $enum->value)->toArray();

        $this->assertSame(['one', 'two', 'three'], $values);
    }

    public function test_nested_collections_work_correctly(): void
    {
        $innerCollection = new StringTypedCollection;
        $innerCollection->add('a', 'b', 'c');

        $outerCollection = new TypedCollection(StringTypedCollection::class);
        $outerCollection->add($innerCollection);

        $innerArray = $outerCollection[0]->toArray();

        $this->assertSame(['a', 'b', 'c'], $innerArray);
    }

    public function test_collection_handles_null_values_correctly(): void
    {
        $collection = new TypedCollection('int', 'null');
        $collection->add(1, null, 2, null, 3);

        $withoutNulls = $collection->filter(fn ($item) => $item !== null);

        $this->assertCount(5, $collection);
        $this->assertCount(3, $withoutNulls);
        $this->assertSame([1, 2, 3], $withoutNulls->toArray());
    }

    public function test_performance_workflow_with_reduce_and_map(): void
    {
        $collection = new IntTypedCollection;
        $collection->add(1, 2, 3, 4, 5, 6, 7, 8, 9, 10);

        $result = $collection
            ->filter(fn ($n) => $n % 2 === 0)
            ->map(fn ($n) => $n * $n)
            ->reduce(fn ($carry, $n) => $carry + $n, 0);

        $this->assertSame(220, $result);
    }

    public function test_all_returns_new_collection_with_same_items(): void
    {
        $this->intCollection->add(1, 2, 3);

        $newCollection = $this->intCollection->all();

        $this->assertNotSame($this->intCollection, $newCollection);
        $this->assertEquals($this->intCollection->toArray(), $newCollection->toArray());
    }

    public function test_get_allowed_types_returns_configured_types(): void
    {
        $collection = new TypedCollection('int', 'string', 'float');
        $types = $collection->getAllowedTypes();

        $this->assertSame(['int', 'string', 'float'], $types);
    }
}
