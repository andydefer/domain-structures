<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Unit\Collections\Utility;

use AndyDefer\DomainStructures\Collections\Utility\ScalarTypedCollection;
use AndyDefer\DomainStructures\Tests\TestCase;

final class ScalarTypedCollectionTest extends TestCase
{
    // ==================== GET_STRINGS METHOD TESTS ====================

    public function test_get_strings_returns_only_string_values(): void
    {
        $collection = new ScalarTypedCollection;
        $collection->add('hello', 42, 'world', true, 'test', null, 'foo');

        $strings = $collection->getStrings();

        $this->assertInstanceOf(ScalarTypedCollection::class, $strings);
        $this->assertSame(['hello', 'world', 'test', 'foo'], $strings->toArray());
    }

    public function test_get_strings_returns_empty_collection_when_no_strings(): void
    {
        $collection = new ScalarTypedCollection;
        $collection->add(1, 2, 3, true, false, null);

        $strings = $collection->getStrings();

        $this->assertInstanceOf(ScalarTypedCollection::class, $strings);
        $this->assertTrue($strings->isEmpty());
    }

    // ==================== GET_INTEGERS METHOD TESTS ====================

    public function test_get_integers_returns_only_integer_values(): void
    {
        $collection = new ScalarTypedCollection;
        $collection->add(1, 'hello', 42, 'world', 100, true, -5);

        $integers = $collection->getIntegers();

        $this->assertInstanceOf(ScalarTypedCollection::class, $integers);
        $this->assertSame([1, 42, 100, -5], $integers->toArray());
    }

    public function test_get_integers_returns_empty_collection_when_no_integers(): void
    {
        $collection = new ScalarTypedCollection;
        $collection->add('hello', 'world', true, false, null);

        $integers = $collection->getIntegers();

        $this->assertInstanceOf(ScalarTypedCollection::class, $integers);
        $this->assertTrue($integers->isEmpty());
    }

    // ==================== GET_BOOLEANS METHOD TESTS ====================

    public function test_get_booleans_returns_only_boolean_values(): void
    {
        $collection = new ScalarTypedCollection;
        $collection->add(true, 'hello', false, 42, true, 'world', false);

        $booleans = $collection->getBooleans();

        $this->assertInstanceOf(ScalarTypedCollection::class, $booleans);
        $this->assertSame([true, false, true, false], $booleans->toArray());
    }

    public function test_get_booleans_returns_empty_collection_when_no_booleans(): void
    {
        $collection = new ScalarTypedCollection;
        $collection->add('hello', 'world', 1, 2, 3, null);

        $booleans = $collection->getBooleans();

        $this->assertInstanceOf(ScalarTypedCollection::class, $booleans);
        $this->assertTrue($booleans->isEmpty());
    }

    // ==================== MAP PRESERVE TYPE TESTS ====================

    public function test_map_preserve_type_keeps_same_collection_class(): void
    {
        $collection = new ScalarTypedCollection;
        $collection->add(1, 2, 3, 4);

        $mapped = $collection->mapPreserveType(fn ($item) => $item * 2);

        $this->assertInstanceOf(ScalarTypedCollection::class, $mapped);
        $this->assertSame([2, 4, 6, 8], $mapped->toArray());
    }

    public function test_map_preserve_type_throws_exception_for_incompatible_types(): void
    {
        $collection = new ScalarTypedCollection;
        $collection->add(1, 2, 3);

        $this->expectException(\TypeError::class);
        // TypeError message: "array given"

        $collection->mapPreserveType(fn ($item) => ['array']);
    }
}
