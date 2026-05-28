<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Unit\Collections\Core;

use AndyDefer\DomainStructures\Collections\Core\CollectionContainer;
use AndyDefer\DomainStructures\Collections\Utility\IntTypedCollection;
use AndyDefer\DomainStructures\Collections\Utility\StringTypedCollection;
use AndyDefer\DomainStructures\Normalizers\NormalizerChain;
use AndyDefer\DomainStructures\Tests\TestCase;
use InvalidArgumentException;

final class CollectionContainerTest extends TestCase
{
    // ==================== CONSTRUCTOR TESTS ====================

    public function test_constructor_requires_at_least_one_collection_type(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('At least one concrete Collection class must be provided');

        new CollectionContainer;
    }

    public function test_constructor_accepts_valid_collection_types(): void
    {
        $container = new CollectionContainer(StringTypedCollection::class, IntTypedCollection::class);

        $this->assertSame([StringTypedCollection::class, IntTypedCollection::class], $container->getAllowedTypes());
    }

    public function test_constructor_rejects_non_collection_type(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Type "stdClass" must be a subclass of AndyDefer\DomainStructures\Abstracts\AbstractTypedCollection');

        new CollectionContainer(\stdClass::class);
    }

    public function test_constructor_rejects_scalar_type(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Type "string" must be a subclass of AndyDefer\DomainStructures\Abstracts\AbstractTypedCollection');

        new CollectionContainer('string');
    }

    // ==================== ADD METHOD TESTS ====================

    public function test_add_accepts_valid_collection(): void
    {
        $container = new CollectionContainer(StringTypedCollection::class);
        $stringCollection = new StringTypedCollection;
        $stringCollection->add('a', 'b', 'c');

        $container->add($stringCollection);

        $this->assertCount(1, $container);
        $this->assertSame($stringCollection, $container[0]);
    }

    public function test_add_accepts_multiple_collections(): void
    {
        $container = new CollectionContainer(StringTypedCollection::class, IntTypedCollection::class);
        $stringCollection = new StringTypedCollection;
        $stringCollection->add('a', 'b');
        $intCollection = new IntTypedCollection;
        $intCollection->add(1, 2, 3);

        $container->add($stringCollection, $intCollection);

        $this->assertCount(2, $container);
        $this->assertSame($stringCollection, $container[0]);
        $this->assertSame($intCollection, $container[1]);
    }

    public function test_add_rejects_wrong_collection_type(): void
    {
        $container = new CollectionContainer(StringTypedCollection::class);
        $intCollection = new IntTypedCollection;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected type(s) '.StringTypedCollection::class);

        $container->add($intCollection);
    }

    public function test_add_rejects_non_collection(): void
    {
        $container = new CollectionContainer(StringTypedCollection::class);

        $this->expectException(InvalidArgumentException::class);

        $container->add('not a collection');
    }

    // ==================== FLATTEN METHOD TESTS ====================

    public function test_flatten_returns_all_items_from_contained_collections(): void
    {
        $container = new CollectionContainer(StringTypedCollection::class);

        $collection1 = new StringTypedCollection;
        $collection1->add('a', 'b', 'c');

        $collection2 = new StringTypedCollection;
        $collection2->add('d', 'e', 'f');

        $container->add($collection1, $collection2);

        $flattened = $container->flatten();

        $this->assertSame(['a', 'b', 'c', 'd', 'e', 'f'], $flattened);
    }

    public function test_flatten_on_empty_container_returns_empty_array(): void
    {
        $container = new CollectionContainer(StringTypedCollection::class);

        $flattened = $container->flatten();

        $this->assertEmpty($flattened);
    }

    public function test_flatten_maintains_order(): void
    {
        $container = new CollectionContainer(StringTypedCollection::class);

        $collection1 = new StringTypedCollection;
        $collection1->add('first', 'second');

        $collection2 = new StringTypedCollection;
        $collection2->add('third', 'fourth');

        $container->add($collection1, $collection2);

        $flattened = $container->flatten();

        $this->assertSame(['first', 'second', 'third', 'fourth'], $flattened);
    }

    // ==================== FLATTEN_DEEP METHOD TESTS ====================

    public function test_flatten_deep_handles_nested_containers(): void
    {
        $container = new CollectionContainer(CollectionContainer::class, StringTypedCollection::class);

        $innerContainer = new CollectionContainer(StringTypedCollection::class);
        $innerCollection = new StringTypedCollection;
        $innerCollection->add('a', 'b', 'c');
        $innerContainer->add($innerCollection);

        $outerCollection = new StringTypedCollection;
        $outerCollection->add('x', 'y', 'z');

        $container->add($innerContainer, $outerCollection);

        $flattened = $container->flattenDeep();

        $this->assertSame(['a', 'b', 'c', 'x', 'y', 'z'], $flattened);
    }

    public function test_flatten_deep_on_empty_container_returns_empty_array(): void
    {
        $container = new CollectionContainer(StringTypedCollection::class);

        $flattened = $container->flattenDeep();

        $this->assertEmpty($flattened);
    }

    public function test_flatten_deep_handles_multiple_levels(): void
    {
        $level3 = new CollectionContainer(StringTypedCollection::class);
        $level3Collection = new StringTypedCollection;
        $level3Collection->add('deep');
        $level3->add($level3Collection);

        $level2 = new CollectionContainer(CollectionContainer::class);
        $level2->add($level3);

        $level1 = new CollectionContainer(CollectionContainer::class);
        $level1->add($level2);

        $flattened = $level1->flattenDeep();

        $this->assertSame(['deep'], $flattened);
    }

    // ==================== NORMALIZATION TESTS ====================

    public function test_normalize_returns_nested_structure(): void
    {
        $container = new CollectionContainer(StringTypedCollection::class);

        $collection1 = new StringTypedCollection;
        $collection1->add('a', 'b');
        $collection2 = new StringTypedCollection;
        $collection2->add('c', 'd');

        $container->add($collection1, $collection2);

        $normalized = NormalizerChain::get()->normalize($container);

        $this->assertIsArray($normalized);
        $this->assertCount(2, $normalized);
        $this->assertSame(['a', 'b'], $normalized[0]);
        $this->assertSame(['c', 'd'], $normalized[1]);
    }

    // ==================== CHAINING TESTS ====================

    public function test_add_returns_self_for_chaining(): void
    {
        $container = new CollectionContainer(StringTypedCollection::class);
        $collection = new StringTypedCollection;

        $result = $container->add($collection);

        $this->assertSame($container, $result);
    }

    // ==================== ARRAY ACCESS TESTS ====================

    public function test_array_access_works_with_container(): void
    {
        $container = new CollectionContainer(StringTypedCollection::class);
        $collection = new StringTypedCollection;
        $collection->add('test');

        $container[] = $collection;

        $this->assertTrue(isset($container[0]));
        $this->assertSame($collection, $container[0]);
    }

    // ==================== ITERATOR TESTS ====================

    public function test_container_is_iterable(): void
    {
        $container = new CollectionContainer(StringTypedCollection::class);
        $collection1 = new StringTypedCollection;
        $collection2 = new StringTypedCollection;
        $container->add($collection1, $collection2);

        $items = [];
        foreach ($container as $item) {
            $items[] = $item;
        }

        $this->assertCount(2, $items);
        $this->assertSame($collection1, $items[0]);
        $this->assertSame($collection2, $items[1]);
    }
}
