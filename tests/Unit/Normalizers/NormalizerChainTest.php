<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Unit\Normalizers;

use AndyDefer\DomainStructures\Normalizers\Core\NormalizerInterface;
use AndyDefer\DomainStructures\Normalizers\NormalizerChain;
use AndyDefer\DomainStructures\Tests\Fixtures\Collections\ProductRecordCollection;
use AndyDefer\DomainStructures\Tests\Fixtures\Data\TestProductData;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestBackedStringEnum;
use AndyDefer\DomainStructures\Tests\Fixtures\Records\TestProductRecord;
use AndyDefer\DomainStructures\Tests\Fixtures\Records\TestUserRecord;
use AndyDefer\DomainStructures\Tests\Fixtures\ValueObjects\TestEmailAddress;
use AndyDefer\DomainStructures\Tests\TestCase;

final class NormalizerChainTest extends TestCase
{
    private NormalizerInterface $chain;

    protected function setUp(): void
    {
        parent::setUp();
        $this->chain = NormalizerChain::get();
    }

    // ==================== SINGLETON TESTS ====================

    public function test_get_returns_same_instance(): void
    {
        $first = NormalizerChain::get();
        $second = NormalizerChain::get();
        $third = NormalizerChain::get();

        $this->assertSame($first, $second);
        $this->assertSame($second, $third);
    }

    public function test_multiple_calls_return_same_normalizer_interface_instance(): void
    {
        $first = NormalizerChain::get();
        $second = NormalizerChain::get();

        $this->assertSame($first, $second);
    }

    // ==================== NORMALIZATION THROUGH CHAIN TESTS ====================

    public function test_null_value_is_normalized_correctly_through_chain(): void
    {
        $value = null;
        $normalized = $this->chain->normalize($value);

        $this->assertNull($normalized);
    }

    public function test_integer_is_normalized_correctly_through_chain(): void
    {
        $value = 42;
        $normalized = $this->chain->normalize($value);

        $this->assertSame(42, $normalized);
    }

    public function test_string_is_normalized_correctly_through_chain(): void
    {
        $value = 'hello world';
        $normalized = $this->chain->normalize($value);

        $this->assertSame('hello world', $normalized);
    }

    public function test_float_is_normalized_correctly_through_chain(): void
    {
        $value = 3.14;
        $normalized = $this->chain->normalize($value);

        $this->assertSame(3.14, $normalized);
    }

    public function test_boolean_is_normalized_correctly_through_chain(): void
    {
        $value = true;
        $normalized = $this->chain->normalize($value);

        $this->assertTrue($normalized);
    }

    public function test_enum_is_normalized_correctly_through_chain(): void
    {
        $value = TestBackedStringEnum::VALUE_ONE;
        $normalized = $this->chain->normalize($value);

        $this->assertSame('one', $normalized);
    }

    public function test_record_is_normalized_correctly_through_chain(): void
    {
        $email = TestEmailAddress::from('test@example.com');
        $record = new TestUserRecord(name: 'John Doe', email: $email);
        $normalized = $this->chain->normalize($record);

        $this->assertIsArray($normalized);
        $this->assertArrayHasKey('name', $normalized);
        $this->assertArrayHasKey('email', $normalized);
        $this->assertSame('John Doe', $normalized['name']);
        $this->assertSame('test@example.com', $normalized['email']);
    }

    public function test_value_object_is_normalized_correctly_through_chain(): void
    {
        $email = TestEmailAddress::from('test@example.com');
        $normalized = $this->chain->normalize($email);

        $this->assertSame('test@example.com', $normalized);
    }

    public function test_data_object_is_normalized_correctly_through_chain(): void
    {
        $data = new TestProductData(id: 1, name: 'Product', price: 99.99);
        $normalized = $this->chain->normalize($data);

        $this->assertIsArray($normalized);
        $this->assertSame(1, $normalized['id']);
        $this->assertSame('Product', $normalized['name']);
        $this->assertSame(99.99, $normalized['price']);
    }

    public function test_collection_is_normalized_correctly_through_chain(): void
    {
        $collection = new ProductRecordCollection;
        $collection->add(
            new TestProductRecord(id: 1, name: 'Product 1', price: 100),
            new TestProductRecord(id: 2, name: 'Product 2', price: 200)
        );
        $normalized = $this->chain->normalize($collection);

        $this->assertIsArray($normalized);
        $this->assertCount(2, $normalized);
        $this->assertSame('Product 1', $normalized[0]['name']);
        $this->assertSame('Product 2', $normalized[1]['name']);
    }

    public function test_array_is_normalized_correctly_through_chain(): void
    {
        $array = [1, 2, 3, 4, 5];
        $normalized = $this->chain->normalize($array);

        $this->assertSame([1, 2, 3, 4, 5], $normalized);
    }

    // ==================== NESTED NORMALIZATION THROUGH CHAIN TESTS ====================

    public function test_nested_array_with_objects_is_normalized_correctly(): void
    {
        $email = TestEmailAddress::from('nested@example.com');
        $array = [
            'user' => new TestUserRecord(name: 'Nested User', email: $email),
            'tags' => ['a', 'b', 'c'],
        ];
        $normalized = $this->chain->normalize($array);

        $this->assertIsArray($normalized);
        $this->assertIsArray($normalized['user']);
        $this->assertSame('Nested User', $normalized['user']['name']);
        $this->assertSame('nested@example.com', $normalized['user']['email']);
        $this->assertSame(['a', 'b', 'c'], $normalized['tags']);
    }

    public function test_deeply_nested_structure_is_normalized_correctly(): void
    {
        $innerRecord = new TestUserRecord(name: 'Inner', email: TestEmailAddress::from('inner@example.com'));
        $outerArray = ['record' => $innerRecord, 'level' => 1];
        $normalized = $this->chain->normalize($outerArray);

        $this->assertIsArray($normalized);
        $this->assertIsArray($normalized['record']);
        $this->assertSame('Inner', $normalized['record']['name']);
        $this->assertSame('inner@example.com', $normalized['record']['email']);
    }

    // ==================== ERROR HANDLING TESTS ====================

    public function test_normalize_throws_exception_for_unsupported_type_at_end_of_chain(): void
    {
        $resource = fopen('php://memory', 'r');

        $this->expectException(\InvalidArgumentException::class);
        $this->chain->normalize($resource);

        fclose($resource);
    }

    // ==================== IDEMPOTENCY TESTS ====================

    public function test_normalize_returns_consistent_results_for_same_input(): void
    {
        $email = TestEmailAddress::from('test@example.com');
        $record = new TestUserRecord(name: 'John', email: $email);

        $first = $this->chain->normalize($record);
        $second = $this->chain->normalize($record);
        $third = $this->chain->normalize($record);

        $this->assertSame($first, $second);
        $this->assertSame($second, $third);
    }
}
