<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests;

use AndyDefer\DomainStructures\Collections\TypedCollection;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestCurrency;
use AndyDefer\DomainStructures\Tests\Fixtures\ValueObjects\TestEmailAddress;
use AndyDefer\DomainStructures\Tests\Fixtures\ValueObjects\TestMoney;
use AndyDefer\DomainStructures\Tests\Fixtures\ValueObjects\TestPostalCode;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class AbstractValueObjectTest extends TestCase
{
    // ==================== TESTS POUR toArray() ====================

    public function test_to_array_returns_array_with_correct_keys(): void
    {
        $email = TestEmailAddress::fromString('john@example.com');
        $array = $email->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('value', $array);
        $this->assertSame('john@example.com', $array['value']);
    }

    public function test_to_array_with_composite_vo(): void
    {
        $money = TestMoney::fromFloat(99.99, TestCurrency::EUR);
        $array = $money->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('amount', $array);
        $this->assertArrayHasKey('currency', $array);
        $this->assertSame(99.99, $array['amount']);
        $this->assertSame('EUR', $array['currency']);
    }

    // ==================== TESTS POUR toJson() ====================

    public function test_to_json_returns_valid_json(): void
    {
        $email = TestEmailAddress::fromString('john@example.com');
        $json = $email->toJson();

        $this->assertJson($json);
        $this->assertStringContainsString('john@example.com', $json);
    }

    // ==================== TESTS POUR equals() ====================

    public function test_equals_returns_true_for_same_values(): void
    {
        $email1 = TestEmailAddress::fromString('john@example.com');
        $email2 = TestEmailAddress::fromString('john@example.com');

        $this->assertTrue($email1->equals($email2));
    }

    public function test_equals_returns_false_for_different_values(): void
    {
        $email1 = TestEmailAddress::fromString('john@example.com');
        $email2 = TestEmailAddress::fromString('jane@example.com');

        $this->assertFalse($email1->equals($email2));
    }

    public function test_equals_returns_false_for_different_classes(): void
    {
        $email = TestEmailAddress::fromString('john@example.com');
        $postalCode = TestPostalCode::fromString('75001');

        $this->assertFalse($email->equals($postalCode));
    }

    public function test_equals_with_composite_vo(): void
    {
        $money1 = TestMoney::fromFloat(99.99, TestCurrency::EUR);
        $money2 = TestMoney::fromFloat(99.99, TestCurrency::EUR);
        $money3 = TestMoney::fromFloat(149.99, TestCurrency::EUR);

        $this->assertTrue($money1->equals($money2));
        $this->assertFalse($money1->equals($money3));
    }

    // ==================== TESTS POUR isEmpty() ====================

    public function test_is_empty_returns_false_for_non_empty_vo(): void
    {
        $email = TestEmailAddress::fromString('john@example.com');
        $this->assertFalse($email->isEmpty());
    }

    // ==================== TESTS POUR collectFromCollection() ====================

    public function test_collect_from_collection_converts_scalars_to_vos(): void
    {
        $scalars = new TypedCollection('string');
        $scalars->add('john@example.com', 'jane@example.com');

        $emails = TestEmailAddress::collectFromCollection($scalars);

        $this->assertInstanceOf(TypedCollection::class, $emails);
        $this->assertCount(2, $emails);
        $this->assertInstanceOf(TestEmailAddress::class, $emails->firstItem());
        $this->assertSame('john@example.com', $emails->firstItem()->value);
    }

    public function test_collect_from_collection_preserves_existing_vos(): void
    {
        $existingVos = new TypedCollection(TestEmailAddress::class);
        $existingVos->add(
            TestEmailAddress::fromString('john@example.com'),
            TestEmailAddress::fromString('jane@example.com')
        );

        $result = TestEmailAddress::collectFromCollection($existingVos);

        $this->assertCount(2, $result);
        $this->assertSame('john@example.com', $result->firstItem()->value);
    }

    public function test_collect_from_collection_throws_exception_for_invalid_items(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot convert item of type int');

        $invalidItems = new TypedCollection('int');
        $invalidItems->add(123);

        TestEmailAddress::collectFromCollection($invalidItems);
    }

    // ==================== TESTS POUR fromArray() ====================

    public function test_from_array_creates_vo_from_array(): void
    {
        $email = TestEmailAddress::fromArray(['value' => 'john@example.com']);

        $this->assertInstanceOf(TestEmailAddress::class, $email);
        $this->assertSame('john@example.com', $email->value);
    }

    public function test_from_array_with_composite_vo(): void
    {
        $money = TestMoney::fromArray([
            'amount' => 99.99,
            'currency' => 'EUR',
        ]);

        $this->assertInstanceOf(TestMoney::class, $money);
        $this->assertSame(99.99, $money->amount);
        $this->assertSame(TestCurrency::EUR, $money->currency);
    }

    // ==================== TESTS POUR with() ====================

    public function test_with_creates_new_instance_with_modified_property(): void
    {
        $original = TestEmailAddress::fromString('john@example.com');
        $modified = $original->with('value', 'jane@example.com');

        $this->assertNotSame($original, $modified);
        $this->assertSame('john@example.com', $original->value);
        $this->assertSame('jane@example.com', $modified->value);
    }

    public function test_with_on_composite_vo(): void
    {
        $original = TestMoney::fromFloat(99.99, TestCurrency::EUR);
        $modified = $original->with('amount', 149.99);

        $this->assertNotSame($original, $modified);
        $this->assertSame(99.99, $original->amount);
        $this->assertSame(149.99, $modified->amount);
        $this->assertSame(TestCurrency::EUR, $modified->currency);
    }

    public function test_with_chaining_multiple_properties(): void
    {
        $original = TestMoney::fromFloat(99.99, TestCurrency::EUR);
        $modified = $original
            ->with('amount', 149.99)
            ->with('currency', TestCurrency::USD);

        $this->assertSame(99.99, $original->amount);
        $this->assertSame(TestCurrency::EUR, $original->currency);
        $this->assertSame(149.99, $modified->amount);
        $this->assertSame(TestCurrency::USD, $modified->currency);
    }

    public function test_with_throws_exception_for_non_existent_property(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Property "nonExistent" does not exist');

        $email = TestEmailAddress::fromString('john@example.com');
        $email->with('nonExistent', 'value');
    }

    public function test_with_throws_exception_for_non_public_property(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('is not public');

        // Création d'une classe avec une propriété non publique pour tester
        $reflection = new \ReflectionClass(TestEmailAddress::class);
        $email = $reflection->newInstance('test@example.com');

        $email->with('value', 'new@example.com');
    }

    // ==================== TESTS POUR assertNotEmpty() ====================

    public function test_assert_not_empty_returns_self_for_non_empty_vo(): void
    {
        $email = TestEmailAddress::fromString('john@example.com');
        $result = $email->assertNotEmpty();

        $this->assertSame($email, $result);
    }

    // ==================== TESTS POUR __toString() ====================

    public function test_to_string_returns_json_representation(): void
    {
        $email = TestEmailAddress::fromString('john@example.com');
        $string = (string) $email;

        $this->assertJson($string);
        $this->assertStringContainsString('john@example.com', $string);
    }

    // ==================== TESTS POUR normalizeValue() ====================

    public function test_normalize_value_handles_null(): void
    {
        $reflection = new \ReflectionClass(TestEmailAddress::class);
        $method = $reflection->getMethod('normalizeValue');
        $method->setAccessible(true);

        $vo = TestEmailAddress::fromString('test@example.com');
        $result = $method->invoke($vo, null);

        $this->assertNull($result);
    }

    public function test_normalize_value_handles_nested_vo(): void
    {
        $reflection = new \ReflectionClass(TestMoney::class);
        $method = $reflection->getMethod('normalizeValue');
        $method->setAccessible(true);

        $money = TestMoney::fromFloat(99.99, TestCurrency::EUR);
        $result = $method->invoke($money, $money);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('amount', $result);
        $this->assertArrayHasKey('currency', $result);
    }

    public function test_normalize_value_handles_typed_collection(): void
    {
        $reflection = new \ReflectionClass(TestEmailAddress::class);
        $method = $reflection->getMethod('normalizeValue');
        $method->setAccessible(true);

        $collection = new TypedCollection('string');
        $collection->add('tag1', 'tag2');

        $vo = TestEmailAddress::fromString('test@example.com');
        $result = $method->invoke($vo, $collection);

        $this->assertIsArray($result);
        $this->assertSame(['tag1', 'tag2'], $result);
    }

    public function test_normalize_value_handles_backed_enum(): void
    {
        $reflection = new \ReflectionClass(TestMoney::class);
        $method = $reflection->getMethod('normalizeValue');
        $method->setAccessible(true);

        $money = TestMoney::fromFloat(99.99, TestCurrency::EUR);
        $result = $method->invoke($money, TestCurrency::EUR);

        $this->assertSame('EUR', $result);
    }

    // ==================== TESTS POUR fromArray() avec données invalides ====================

    public function test_from_array_throws_exception_for_missing_required_field(): void
    {
        $this->expectException(InvalidArgumentException::class);

        // TestPostalCode nécessite 'value'
        TestPostalCode::fromArray([]);
    }
}
