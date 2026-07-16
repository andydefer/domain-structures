<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Unit\Normalizers;

use AndyDefer\DomainStructures\Abstracts\AbstractRecord;
use AndyDefer\DomainStructures\Normalizers\Core\NormalizerInterface;
use AndyDefer\DomainStructures\Normalizers\NormalizerChain;
use AndyDefer\DomainStructures\Tests\Fixtures\Collections\TestProductRecordCollection;
use AndyDefer\DomainStructures\Tests\Fixtures\Data\TestProductData;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestBackedStringEnum;
use AndyDefer\DomainStructures\Tests\Fixtures\Records\TestProductRecord;
use AndyDefer\DomainStructures\Tests\Fixtures\Records\TestUserRecord;
use AndyDefer\DomainStructures\Tests\Fixtures\ValueObjects\TestEmailAddress;
use AndyDefer\DomainStructures\Tests\TestCase;
use RuntimeException;

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

    // ==================== PRESERVE RECORD CASE TESTS ====================

    public function test_get_with_preserve_record_case_true_returns_different_instance(): void
    {
        $default = NormalizerChain::get();
        $preserve = NormalizerChain::get(true);

        $this->assertNotSame($default, $preserve);
    }

    public function test_get_with_preserve_record_case_false_returns_same_as_default(): void
    {
        $default = NormalizerChain::get();
        $explicitFalse = NormalizerChain::get(false);

        $this->assertSame($default, $explicitFalse);
    }

    public function test_record_normalization_converts_to_snake_case_by_default(): void
    {
        $email = TestEmailAddress::from('test@example.com');
        $record = new TestUserRecord(name: 'John Doe', email: $email);
        $normalized = $this->chain->normalize($record);

        $this->assertIsArray($normalized);
        $this->assertArrayHasKey('name', $normalized);
        $this->assertArrayHasKey('email', $normalized);
        // Les propriétés en camelCase doivent être converties en snake_case
        $this->assertArrayNotHasKey('userName', $normalized);
        $this->assertArrayNotHasKey('EmailAddress', $normalized);
    }

    public function test_record_normalization_preserves_original_case_when_preserve_record_case_is_true(): void
    {
        $chain = NormalizerChain::get(true);
        $email = TestEmailAddress::from('test@example.com');

        // Créer un record avec des propriétés en camelCase
        $record = new class($email) extends AbstractRecord
        {
            public function __construct(
                public readonly TestEmailAddress $emailAddress,
                public readonly string $userName = 'John Doe',
                public readonly int $userId = 123
            ) {}
        };

        $normalized = $chain->normalize($record);

        $this->assertIsArray($normalized);
        // Les propriétés doivent garder leur cas d'origine
        $this->assertArrayHasKey('emailAddress', $normalized);
        $this->assertArrayHasKey('userName', $normalized);
        $this->assertArrayHasKey('userId', $normalized);
        // Elles ne doivent PAS être en snake_case
        $this->assertArrayNotHasKey('email_address', $normalized);
        $this->assertArrayNotHasKey('user_name', $normalized);
        $this->assertArrayNotHasKey('user_id', $normalized);
    }

    public function test_preserve_record_case_works_with_nested_records(): void
    {
        $chain = NormalizerChain::get(true);

        // Record imbriqué avec camelCase
        $nestedRecord = new class extends AbstractRecord
        {
            public function __construct(
                public readonly string $nestedProperty = 'nested value',
                public readonly int $nestedId = 999
            ) {}
        };

        $parentRecord = new class($nestedRecord) extends AbstractRecord
        {
            public function __construct(
                public readonly object $nested,
                public readonly string $parentName = 'parent',
                public readonly bool $isActive = true
            ) {}
        };

        $normalized = $chain->normalize($parentRecord);

        $this->assertIsArray($normalized);
        // Parent garde sa casse
        $this->assertArrayHasKey('parentName', $normalized);
        $this->assertArrayHasKey('isActive', $normalized);
        $this->assertArrayNotHasKey('parent_name', $normalized);
        $this->assertArrayNotHasKey('is_active', $normalized);

        // Nested garde aussi sa casse
        $this->assertIsArray($normalized['nested']);
        $this->assertArrayHasKey('nestedProperty', $normalized['nested']);
        $this->assertArrayHasKey('nestedId', $normalized['nested']);
        $this->assertArrayNotHasKey('nested_property', $normalized['nested']);
        $this->assertArrayNotHasKey('nested_id', $normalized['nested']);
    }

    public function test_preserve_record_case_does_not_affect_other_normalizers(): void
    {
        $chain = NormalizerChain::get(true);

        // Les ValueObjects doivent toujours être normalisés normalement
        $email = TestEmailAddress::from('test@example.com');
        $normalizedEmail = $chain->normalize($email);
        $this->assertSame('test@example.com', $normalizedEmail);

        // Les enums doivent toujours être normalisés normalement
        $enum = TestBackedStringEnum::VALUE_ONE;
        $normalizedEnum = $chain->normalize($enum);
        $this->assertSame('one', $normalizedEnum);

        // Les DataObjects doivent toujours être normalisés normalement
        $data = new TestProductData(id: 1, name: 'Product', price: 99.99);
        $normalizedData = $chain->normalize($data);
        $this->assertIsArray($normalizedData);
        $this->assertArrayHasKey('id', $normalizedData);
        $this->assertArrayHasKey('name', $normalizedData);
        $this->assertArrayHasKey('price', $normalizedData);
    }

    public function test_preserve_record_case_with_multiple_instances_works_independently(): void
    {
        $chainDefault = NormalizerChain::get();
        $chainPreserve = NormalizerChain::get(true);

        // Record avec camelCase
        $record = new class extends AbstractRecord
        {
            public function __construct(
                public readonly string $firstName = 'John',
                public readonly string $lastName = 'Doe'
            ) {}
        };

        $normalizedDefault = $chainDefault->normalize($record);
        $normalizedPreserve = $chainPreserve->normalize($record);

        // Avec preserve = false (défaut) : snake_case
        $this->assertArrayHasKey('first_name', $normalizedDefault);
        $this->assertArrayHasKey('last_name', $normalizedDefault);
        $this->assertArrayNotHasKey('firstName', $normalizedDefault);
        $this->assertArrayNotHasKey('lastName', $normalizedDefault);

        // Avec preserve = true : camelCase conservé
        $this->assertArrayHasKey('firstName', $normalizedPreserve);
        $this->assertArrayHasKey('lastName', $normalizedPreserve);
        $this->assertArrayNotHasKey('first_name', $normalizedPreserve);
        $this->assertArrayNotHasKey('last_name', $normalizedPreserve);
    }

    public function test_preserve_record_case_with_mixed_property_names(): void
    {
        $chain = NormalizerChain::get(true);

        $record = new class extends AbstractRecord
        {
            public function __construct(
                public readonly string $alreadySnakeCase = 'value1',
                public readonly string $camelCaseProperty = 'value2',
                public readonly string $UPPERCase = 'value3',
                public readonly string $PascalCase = 'value4'
            ) {}
        };

        $normalized = $chain->normalize($record);

        // Tous les noms de propriétés sont conservés tels quels
        $this->assertArrayHasKey('alreadySnakeCase', $normalized);
        $this->assertArrayHasKey('camelCaseProperty', $normalized);
        $this->assertArrayHasKey('UPPERCase', $normalized);
        $this->assertArrayHasKey('PascalCase', $normalized);

        // Aucun n'est converti
        $this->assertArrayNotHasKey('already_snake_case', $normalized);
        $this->assertArrayNotHasKey('camel_case_property', $normalized);
        $this->assertArrayNotHasKey('upper_case', $normalized);
        $this->assertArrayNotHasKey('pascal_case', $normalized);
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
        $collection = new TestProductRecordCollection;
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

    public function test_std_class_is_normalized_correctly_through_chain(): void
    {
        $obj = new \stdClass;
        $obj->name = 'John Doe';
        $obj->age = 30;
        $obj->active = true;

        $normalized = $this->chain->normalize($obj);

        $this->assertIsArray($normalized);
        $this->assertSame('John Doe', $normalized['name']);
        $this->assertSame(30, $normalized['age']);
        $this->assertTrue($normalized['active']);
    }

    public function test_nested_std_class_is_normalized_correctly_through_chain(): void
    {
        $nested = new \stdClass;
        $nested->city = 'Paris';
        $nested->country = 'France';

        $obj = new \stdClass;
        $obj->name = 'John';
        $obj->address = $nested;

        $normalized = $this->chain->normalize($obj);

        $this->assertIsArray($normalized);
        $this->assertSame('John', $normalized['name']);
        $this->assertIsArray($normalized['address']);
        $this->assertSame('Paris', $normalized['address']['city']);
        $this->assertSame('France', $normalized['address']['country']);
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

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No normalizer found for type resource');
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

    public function test_preserve_record_case_returns_consistent_results(): void
    {
        $chain = NormalizerChain::get(true);

        $record = new class extends AbstractRecord
        {
            public function __construct(
                public readonly string $camelProperty = 'value'
            ) {}
        };

        $first = $chain->normalize($record);
        $second = $chain->normalize($record);

        $this->assertSame($first, $second);
        $this->assertArrayHasKey('camelProperty', $first);
    }
}
