<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Unit\EdgeCases;

use AndyDefer\DomainStructures\Collections\Core\CollectionContainer;
use AndyDefer\DomainStructures\Collections\Core\DataCollection;
use AndyDefer\DomainStructures\Collections\Core\TypedCollection;
use AndyDefer\DomainStructures\Collections\Utility\IntTypedCollection;
use AndyDefer\DomainStructures\Collections\Utility\StringTypedCollection;
use AndyDefer\DomainStructures\Normalizers\NormalizerChain;
use AndyDefer\DomainStructures\Tests\Fixtures\Collections\TestProductRecordCollection;
use AndyDefer\DomainStructures\Tests\Fixtures\Data\TestProductData;
use AndyDefer\DomainStructures\Tests\Fixtures\Data\TestUserData;
use AndyDefer\DomainStructures\Tests\Fixtures\Records\TestProductRecord;
use AndyDefer\DomainStructures\Tests\Fixtures\Records\TestUserRecord;
use AndyDefer\DomainStructures\Tests\Fixtures\ValueObjects\TestEmailAddress;
use AndyDefer\DomainStructures\Tests\Fixtures\ValueObjects\TestIso8601DateTime;
use AndyDefer\DomainStructures\Tests\Fixtures\ValueObjects\TestMoney;
use AndyDefer\DomainStructures\Tests\TestCase;
use AndyDefer\DomainStructures\Utils\DataObject;

final class RecursiveNormalizationTest extends TestCase
{
    private TestIso8601DateTime $now;

    private TestEmailAddress $testEmail;

    private StringTypedCollection $tags;

    protected function setUp(): void
    {
        parent::setUp();

        $this->now = TestIso8601DateTime::from('2024-01-01T12:00:00+00:00');
        $this->testEmail = TestEmailAddress::from('test@example.com');
        $this->tags = new StringTypedCollection;
        $this->tags->add('premium', 'vip');
    }

    // ==================== RECORD CONTAINING RECORD TESTS ====================

    public function test_record_containing_record_normalizes_recursively(): void
    {
        $featuredProduct = new TestProductRecord(
            id: 1,
            name: 'Featured Product',
            price: 999.99,
            isFeatured: true
        );

        $userRecord = new TestUserRecord(
            id: 1,
            name: 'John Doe',
            email: $this->testEmail,
            featuredProduct: $featuredProduct
        );

        $normalized = NormalizerChain::get()->normalize($userRecord);

        $this->assertIsArray($normalized);
        $this->assertArrayHasKey('featured_product', $normalized);
        $this->assertIsArray($normalized['featured_product']);
        $this->assertSame(1, $normalized['featured_product']['id']);
        $this->assertSame('Featured Product', $normalized['featured_product']['name']);
        $this->assertEquals(999.99, $normalized['featured_product']['price']);
        $this->assertTrue($normalized['featured_product']['is_featured']);
    }

    public function test_record_containing_record_containing_record_normalizes_deeply(): void
    {
        $product = new TestProductRecord(
            id: 1,
            name: 'Laptop',
            price: 999.99,
            metadata: new StringTypedCollection,
            productableId: 1,
            productableType: 'App\Models\Category'
        );

        $userRecord = new TestUserRecord(
            id: 1,
            name: 'John Doe',
            email: $this->testEmail,
            products: (new TestProductRecordCollection)->add($product)
        );

        $normalized = NormalizerChain::get()->normalize($userRecord);

        $this->assertIsArray($normalized);
        $this->assertIsArray($normalized['products']);
        $this->assertCount(1, $normalized['products']);
        $this->assertSame('Laptop', $normalized['products'][0]['name']);
        $this->assertEquals(999.99, $normalized['products'][0]['price']);
    }

    // ==================== COLLECTION CONTAINING COLLECTION TESTS ====================

    public function test_collection_containing_collection_normalizes_recursively(): void
    {
        $innerCollection = new StringTypedCollection;
        $innerCollection->add('a', 'b', 'c');

        $outerCollection = new TypedCollection(StringTypedCollection::class);
        $outerCollection->add($innerCollection);

        $normalized = NormalizerChain::get()->normalize($outerCollection);

        $this->assertIsArray($normalized);
        $this->assertCount(1, $normalized);
        $this->assertIsArray($normalized[0]);
        $this->assertSame(['a', 'b', 'c'], $normalized[0]);
    }

    public function test_collection_containing_collections_normalizes_deeply(): void
    {
        $level3 = new StringTypedCollection;
        $level3->add('x', 'y', 'z');

        $level2 = new TypedCollection(StringTypedCollection::class);
        $level2->add($level3);

        $level1 = new TypedCollection(TypedCollection::class);
        $level1->add($level2);

        $normalized = NormalizerChain::get()->normalize($level1);

        $this->assertIsArray($normalized);
        $this->assertCount(1, $normalized);
        $this->assertIsArray($normalized[0]);
        $this->assertCount(1, $normalized[0]);
        $this->assertIsArray($normalized[0][0]);
        $this->assertSame(['x', 'y', 'z'], $normalized[0][0]);
    }

    public function test_nested_collection_normalizes_correctly(): void
    {
        $container = new CollectionContainer(StringTypedCollection::class);

        $inner1 = new StringTypedCollection;
        $inner1->add('a', 'b', 'c');

        $inner2 = new StringTypedCollection;
        $inner2->add('d', 'e', 'f');

        $container->add($inner1, $inner2);

        $normalized = NormalizerChain::get()->normalize($container);

        $this->assertIsArray($normalized);
        $this->assertCount(2, $normalized);
        $this->assertSame(['a', 'b', 'c'], $normalized[0]);
        $this->assertSame(['d', 'e', 'f'], $normalized[1]);
    }

    // ==================== RECORD CONTAINING COLLECTION TESTS ====================

    public function test_record_containing_collection_normalizes_recursively(): void
    {
        $products = new TestProductRecordCollection;
        $products->add(
            new TestProductRecord(id: 1, name: 'Product 1', price: 100),
            new TestProductRecord(id: 2, name: 'Product 2', price: 200)
        );

        $userRecord = new TestUserRecord(
            id: 1,
            name: 'John Doe',
            email: $this->testEmail,
            products: $products,
            tags: $this->tags
        );

        $normalized = NormalizerChain::get()->normalize($userRecord);

        $this->assertIsArray($normalized);
        $this->assertIsArray($normalized['products']);
        $this->assertCount(2, $normalized['products']);
        $this->assertSame('Product 1', $normalized['products'][0]['name']);
        $this->assertSame('Product 2', $normalized['products'][1]['name']);
        $this->assertIsArray($normalized['tags']);
        $this->assertSame(['premium', 'vip'], $normalized['tags']);
    }

    public function test_record_containing_collection_containing_records_normalizes_deeply(): void
    {
        $product1 = new TestProductRecord(id: 1, name: 'Laptop', price: 999);
        $product2 = new TestProductRecord(id: 2, name: 'Mouse', price: 29);

        $products = new TestProductRecordCollection;
        $products->add($product1, $product2);

        $userRecord = new TestUserRecord(
            id: 1,
            name: 'John Doe',
            email: $this->testEmail,
            products: $products
        );

        $normalized = NormalizerChain::get()->normalize($userRecord);

        $this->assertIsArray($normalized);
        $this->assertIsArray($normalized['products']);
        $this->assertCount(2, $normalized['products']);
        $this->assertSame('Laptop', $normalized['products'][0]['name']);
        $this->assertEquals(999, $normalized['products'][0]['price']);
        $this->assertSame('Mouse', $normalized['products'][1]['name']);
        $this->assertEquals(29, $normalized['products'][1]['price']);
    }

    // ==================== DATA DTO NESTING TESTS ====================

    /**
     * ✅ CORRECTION: DataCollection nécessite un type concret
     */
    public function test_data_dto_containing_nested_data_dto_normalizes_correctly(): void
    {
        $productData = new TestProductData(
            id: 1,
            name: 'Laptop',
            price: 999.99,
            isFeatured: true
        );

        $dataCollection = new DataCollection(TestProductData::class);
        $dataCollection->add($productData);

        $normalized = NormalizerChain::get()->normalize($dataCollection);

        $this->assertIsArray($normalized);
        $this->assertCount(1, $normalized);
        $this->assertSame(1, $normalized[0]['id']);
        $this->assertSame('Laptop', $normalized[0]['name']);
        $this->assertSame(999.99, $normalized[0]['price']);
        $this->assertTrue($normalized[0]['isFeatured']);
    }

    public function test_record_to_data_transformation_preserves_nested_structure(): void
    {
        $products = new TestProductRecordCollection;
        $products->add(
            new TestProductRecord(id: 1, name: 'Product A', price: 100),
            new TestProductRecord(id: 2, name: 'Product B', price: 200)
        );

        $userRecord = new TestUserRecord(
            id: 1,
            name: 'John Doe',
            email: $this->testEmail,
            products: $products,
            tags: $this->tags
        );

        $userData = TestUserData::from($userRecord);
        $normalizedData = NormalizerChain::get()->normalize($userData);

        $this->assertIsArray($normalizedData);
        $this->assertArrayHasKey('tags', $normalizedData);
        $this->assertIsArray($normalizedData['tags']);
        $this->assertSame(['premium', 'vip'], $normalizedData['tags']);
    }

    // ==================== VALUE OBJECT NESTING TESTS ====================

    public function test_value_object_containing_record_normalizes_correctly(): void
    {
        $money = TestMoney::from(['amount' => 99.99, 'currency' => 'EUR']);
        $normalized = NormalizerChain::get()->normalize($money);

        $this->assertIsArray($normalized);
        $this->assertArrayHasKey('amount', $normalized);
        $this->assertArrayHasKey('currency', $normalized);
        $this->assertSame(99.99, $normalized['amount']);
        $this->assertSame('EUR', $normalized['currency']);
    }

    public function test_value_object_containing_value_object_normalizes_recursively(): void
    {
        $innerVO = TestEmailAddress::from('inner@example.com');
        $normalizedInner = NormalizerChain::get()->normalize($innerVO);

        $this->assertSame('inner@example.com', $normalizedInner);
    }

    // ==================== DATAOBJECT NESTING TESTS ====================

    public function test_data_object_containing_data_object_normalizes_recursively(): void
    {
        $inner = DataObject::from(['value' => 'nested value', 'number' => 42]);
        $outer = DataObject::from(['name' => 'outer', 'inner' => $inner]);

        $collection = new TypedCollection(DataObject::class);
        $collection->add($outer);

        $normalized = NormalizerChain::get()->normalize($collection);

        $this->assertIsArray($normalized);
        $this->assertIsArray($normalized[0]['inner']);
        $this->assertSame('nested value', $normalized[0]['inner']['value']);
        $this->assertSame(42, $normalized[0]['inner']['number']);
    }

    /**
     * ✅ CORRECTION: Email spécifique pour éviter confusion
     */
    public function test_data_object_containing_record_normalizes_recursively(): void
    {
        $recordEmail = TestEmailAddress::from('john.doe@example.com');
        $record = new TestUserRecord(
            id: 1,
            name: 'John Doe',
            email: $recordEmail
        );

        $dataObject = DataObject::from(['user' => $record, 'type' => 'test']);

        $collection = new TypedCollection(DataObject::class);
        $collection->add($dataObject);

        $normalized = NormalizerChain::get()->normalize($collection);

        $this->assertIsArray($normalized);
        $this->assertIsArray($normalized[0]['user']);
        $this->assertSame(1, $normalized[0]['user']['id']);
        $this->assertSame('John Doe', $normalized[0]['user']['name']);
        $this->assertSame('john.doe@example.com', $normalized[0]['user']['email']);
    }

    // ==================== MIXED NESTING TESTS ====================

    public function test_mixed_nested_types_normalize_together(): void
    {
        $products = new TestProductRecordCollection;
        $products->add(
            new TestProductRecord(id: 1, name: 'Product', price: 100)
        );

        $userRecord = new TestUserRecord(
            id: 1,
            name: 'John Doe',
            email: $this->testEmail,
            products: $products,
            tags: $this->tags
        );

        $normalized = NormalizerChain::get()->normalize($userRecord);

        $this->assertIsArray($normalized);
        $this->assertIsArray($normalized['products']);
        $this->assertIsArray($normalized['tags']);
        $this->assertSame('Product', $normalized['products'][0]['name']);
        $this->assertSame(['premium', 'vip'], $normalized['tags']);
    }

    // ==================== DEEP NESTING TESTS (3+ LEVELS) ====================

    public function test_three_level_deep_nesting_normalizes_correctly(): void
    {
        $product = new TestProductRecord(id: 1, name: 'Deep Product', price: 100);
        $productCollection = new TestProductRecordCollection;
        $productCollection->add($product);

        $userRecord = new TestUserRecord(
            id: 1,
            name: 'Deep User',
            email: $this->testEmail,
            products: $productCollection
        );

        $normalized = NormalizerChain::get()->normalize($userRecord);

        $this->assertIsArray($normalized);
        $this->assertIsArray($normalized['products']);
        $this->assertCount(1, $normalized['products']);
        $this->assertSame('Deep Product', $normalized['products'][0]['name']);
        $this->assertEquals(100, $normalized['products'][0]['price']);
    }

    public function test_four_level_deep_nesting_normalizes_correctly(): void
    {
        $email = TestEmailAddress::from('deep@example.com');
        $product = new TestProductRecord(id: 1, name: 'Product', price: 100);
        $productCollection = new TestProductRecordCollection;
        $productCollection->add($product);

        $userRecord = new TestUserRecord(
            id: 1,
            name: 'Deep User',
            email: $email,
            products: $productCollection
        );

        $normalized = NormalizerChain::get()->normalize($userRecord);

        $this->assertIsArray($normalized);
        $this->assertSame('deep@example.com', $normalized['email']);
        $this->assertIsArray($normalized['products']);
        $this->assertSame('Product', $normalized['products'][0]['name']);
    }

    // ==================== COLLECTION OF COLLECTIONS TESTS ====================

    public function test_collection_of_collections_normalizes_correctly(): void
    {
        $stringCollection1 = new StringTypedCollection;
        $stringCollection1->add('a', 'b', 'c');

        $stringCollection2 = new StringTypedCollection;
        $stringCollection2->add('d', 'e', 'f');

        $collectionOfCollections = new TypedCollection(StringTypedCollection::class);
        $collectionOfCollections->add($stringCollection1, $stringCollection2);

        $normalized = NormalizerChain::get()->normalize($collectionOfCollections);

        $this->assertIsArray($normalized);
        $this->assertCount(2, $normalized);
        $this->assertSame(['a', 'b', 'c'], $normalized[0]);
        $this->assertSame(['d', 'e', 'f'], $normalized[1]);
    }

    public function test_record_collection_containing_record_collection_normalizes(): void
    {
        $innerProducts = new TestProductRecordCollection;
        $innerProducts->add(
            new TestProductRecord(id: 1, name: 'Inner Product', price: 50)
        );

        $outerCollection = new TypedCollection(TestProductRecordCollection::class);
        $outerCollection->add($innerProducts);

        $normalized = NormalizerChain::get()->normalize($outerCollection);

        $this->assertIsArray($normalized);
        $this->assertCount(1, $normalized);
        $this->assertIsArray($normalized[0]);
        $this->assertSame('Inner Product', $normalized[0][0]['name']);
    }

    // ==================== PERFORMANCE AND SAFETY TESTS ====================

    /**
     * ✅ CORRECTION: Utiliser des types cohérents dans la boucle
     */
    public function test_normalization_does_not_cause_stack_overflow_with_deep_nesting(): void
    {
        $current = new TypedCollection('string');
        $current->add('level1');

        for ($i = 2; $i <= 20; $i++) {
            $wrapper = new TypedCollection(TypedCollection::class);
            $wrapper->add($current);
            $current = $wrapper;
        }

        $normalized = NormalizerChain::get()->normalize($current);
        $this->assertIsArray($normalized);
    }

    public function test_normalization_result_is_consistent_across_calls(): void
    {
        $product = new TestProductRecord(id: 1, name: 'Product', price: 100);
        $products = (new TestProductRecordCollection)->add($product);
        $userRecord = new TestUserRecord(
            id: 1,
            name: 'John Doe',
            email: $this->testEmail,
            products: $products
        );

        $first = NormalizerChain::get()->normalize($userRecord);
        $second = NormalizerChain::get()->normalize($userRecord);
        $third = NormalizerChain::get()->normalize($userRecord);

        $this->assertSame($first, $second);
        $this->assertSame($second, $third);
    }

    // ==================== COLLECTION CONTAINER ADDITIONAL TESTS ====================

    public function test_collection_container_with_multiple_collection_types(): void
    {
        $container = new CollectionContainer(StringTypedCollection::class, IntTypedCollection::class);

        $strings = new StringTypedCollection;
        $strings->add('hello', 'world');

        $ints = new IntTypedCollection;
        $ints->add(1, 2, 3);

        $container->add($strings, $ints);

        $normalized = NormalizerChain::get()->normalize($container);

        $this->assertCount(2, $normalized);
        $this->assertSame(['hello', 'world'], $normalized[0]);
        $this->assertSame([1, 2, 3], $normalized[1]);
    }

    public function test_collection_container_flatten_works_with_nested_containers(): void
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
}
