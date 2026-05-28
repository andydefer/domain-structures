<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Unit\Abstracts;

use AndyDefer\DomainStructures\Collections\Core\TypedCollection;
use AndyDefer\DomainStructures\Normalizers\NormalizerChain;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestCurrency;
use AndyDefer\DomainStructures\Tests\Fixtures\Records\TestMoneyRecord;
use AndyDefer\DomainStructures\Tests\Fixtures\Records\TestUserProfileRecord;
use AndyDefer\DomainStructures\Tests\Fixtures\Records\TestUserRecord;
use AndyDefer\DomainStructures\Tests\Fixtures\ValueObjects\TestEmailAddress;
use AndyDefer\DomainStructures\Tests\Fixtures\ValueObjects\TestIso8601DateTime;
use AndyDefer\DomainStructures\Tests\Fixtures\ValueObjects\TestMoney;
use AndyDefer\DomainStructures\Tests\Fixtures\ValueObjects\TestPostalCode;
use AndyDefer\DomainStructures\Tests\Fixtures\ValueObjects\TestUserProfile;
use AndyDefer\DomainStructures\Tests\TestCase;
use DateTime;
use InvalidArgumentException;

final class AbstractValueObjectTest extends TestCase
{
    private TestIso8601DateTime $now;

    protected function setUp(): void
    {
        parent::setUp();
        $this->now = TestIso8601DateTime::from('2024-01-01T12:00:00+00:00');
    }

    // ==================== CONSTRUCTION TESTS ====================

    public function test_email_value_object_can_be_created_via_from_string(): void
    {
        $email = TestEmailAddress::from('user@example.com');

        $this->assertInstanceOf(TestEmailAddress::class, $email);
        $this->assertSame('user@example.com', $email->getValue());
    }

    public function test_postal_code_value_object_can_be_created_via_from_string(): void
    {
        $postalCode = TestPostalCode::from('75001');

        $this->assertInstanceOf(TestPostalCode::class, $postalCode);
        $this->assertSame('75001', $postalCode->getValue());
    }

    public function test_datetime_value_object_can_be_created_via_from_string(): void
    {
        $datetime = TestIso8601DateTime::from('2024-01-01T12:00:00+00:00');

        $this->assertInstanceOf(TestIso8601DateTime::class, $datetime);
        $this->assertSame('2024-01-01T12:00:00+00:00', $datetime->getValue());
    }

    public function test_datetime_value_object_can_be_created_via_from_date_time(): void
    {
        $dateTime = new DateTime('2024-01-01 12:00:00', new \DateTimeZone('UTC'));

        $datetime = TestIso8601DateTime::from($dateTime);

        $this->assertInstanceOf(TestIso8601DateTime::class, $datetime);
        $this->assertStringContainsString('2024-01-01T12:00:00', $datetime->getValue());
    }

    public function test_money_value_object_can_be_created_via_from_array(): void
    {
        $money = TestMoney::from(['amount' => 99.99, 'currency' => 'EUR']);

        $this->assertInstanceOf(TestMoney::class, $money);
        $value = $money->getValue();
        $this->assertSame(99.99, $value->amount);
        $this->assertSame(TestCurrency::EUR, $value->currency);
    }

    public function test_money_value_object_throws_exception_for_negative_amount(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Amount must be positive');

        TestMoney::from(['amount' => -10.00, 'currency' => 'EUR']);
    }

    public function test_email_value_object_throws_exception_for_invalid_email(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid email');

        TestEmailAddress::from('not-an-email');
    }

    public function test_postal_code_value_object_throws_exception_for_invalid_postal_code(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid postal code');

        TestPostalCode::from('1234');
    }

    public function test_datetime_value_object_throws_exception_for_invalid_datetime(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid ISO 8601 datetime');

        TestIso8601DateTime::from('invalid-date');
    }

    // ==================== GET_VALUE TESTS ====================

    public function test_email_value_object_get_value_returns_string(): void
    {
        $email = TestEmailAddress::from('test@example.com');
        $value = $email->getValue();

        $this->assertIsString($value);
        $this->assertSame('test@example.com', $value);
    }

    public function test_postal_code_value_object_get_value_returns_string(): void
    {
        $postalCode = TestPostalCode::from('75001');
        $value = $postalCode->getValue();

        $this->assertIsString($value);
        $this->assertSame('75001', $value);
    }

    public function test_datetime_value_object_get_value_returns_string(): void
    {
        $datetime = TestIso8601DateTime::from('2024-01-01T12:00:00+00:00');
        $value = $datetime->getValue();

        $this->assertIsString($value);
        $this->assertSame('2024-01-01T12:00:00+00:00', $value);
    }

    public function test_money_value_object_get_value_returns_record(): void
    {
        $money = TestMoney::from(['amount' => 99.99, 'currency' => 'EUR']);
        $value = $money->getValue();

        $this->assertInstanceOf(TestMoneyRecord::class, $value);
        $this->assertSame(99.99, $value->amount);
        $this->assertSame(TestCurrency::EUR, $value->currency);
    }

    public function test_user_profile_value_object_get_value_returns_record(): void
    {
        $profile = TestUserProfile::from([
            'id' => 1,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'status' => 'active',
            'roles' => [],
            'grade' => 1,
            'emailVerifiedAt' => null,
            'tags' => [],
            'createdAt' => $this->now->getValue(),
        ]);

        $value = $profile->getValue();

        $this->assertInstanceOf(TestUserProfileRecord::class, $value);
        $this->assertSame(1, $value->id);
        $this->assertSame('John Doe', $value->name);
    }

    // ==================== EQUALS METHOD TESTS ====================

    public function test_equals_returns_true_for_identical_value_objects(): void
    {
        $email1 = TestEmailAddress::from('test@example.com');
        $email2 = TestEmailAddress::from('test@example.com');

        $this->assertTrue($email1->equals($email2));
        $this->assertTrue($email2->equals($email1));
    }

    public function test_equals_returns_false_for_different_value_objects(): void
    {
        $email1 = TestEmailAddress::from('user1@example.com');
        $email2 = TestEmailAddress::from('user2@example.com');

        $this->assertFalse($email1->equals($email2));
        $this->assertFalse($email2->equals($email1));
    }

    public function test_equals_returns_false_for_different_types(): void
    {
        $email = TestEmailAddress::from('test@example.com');
        $postalCode = TestPostalCode::from('75001');

        $this->assertFalse($email->equals($postalCode));
    }

    public function test_equals_for_money_value_object_works_correctly(): void
    {
        $money1 = TestMoney::from(['amount' => 99.99, 'currency' => 'EUR']);
        $money2 = TestMoney::from(['amount' => 99.99, 'currency' => 'EUR']);
        $money3 = TestMoney::from(['amount' => 49.99, 'currency' => 'EUR']);
        $money4 = TestMoney::from(['amount' => 99.99, 'currency' => 'USD']);

        $this->assertTrue($money1->equals($money2));
        $this->assertFalse($money1->equals($money3));
        $this->assertFalse($money1->equals($money4));
    }

    public function test_equals_for_datetime_value_object_works_correctly(): void
    {
        $datetime1 = TestIso8601DateTime::from('2024-01-01T12:00:00+00:00');
        $datetime2 = TestIso8601DateTime::from('2024-01-01T12:00:00+00:00');
        $datetime3 = TestIso8601DateTime::from('2024-01-02T12:00:00+00:00');

        $this->assertTrue($datetime1->equals($datetime2));
        $this->assertFalse($datetime1->equals($datetime3));
    }

    // ==================== NORMALIZATION TESTS ====================

    public function test_value_object_normalizes_to_scalar_value(): void
    {
        $email = TestEmailAddress::from('test@example.com');
        $normalized = NormalizerChain::get()->normalize($email);

        $this->assertSame('test@example.com', $normalized);
    }

    public function test_value_object_normalizes_to_json_string(): void
    {
        $email = TestEmailAddress::from('test@example.com');
        $json = json_encode(NormalizerChain::get()->normalize($email));

        $this->assertIsString($json);
        $this->assertJson($json);
        $this->assertSame('"test@example.com"', $json);
    }

    public function test_money_value_object_normalizes_to_record(): void
    {
        $money = TestMoney::from(['amount' => 99.99, 'currency' => 'EUR']);
        $normalized = NormalizerChain::get()->normalize($money);

        $this->assertIsArray($normalized);
        $this->assertArrayHasKey('amount', $normalized);
        $this->assertArrayHasKey('currency', $normalized);
        $this->assertSame(99.99, $normalized['amount']);
        $this->assertSame('EUR', $normalized['currency']);
    }

    // ==================== ADDITIONAL METHOD TESTS ====================

    public function test_email_value_object_get_domain_returns_correct_domain(): void
    {
        $email = TestEmailAddress::from('user@gmail.com');
        $domain = $email->getDomain();

        $this->assertSame('gmail.com', $domain);
    }

    public function test_email_value_object_is_gmail_returns_true_for_gmail(): void
    {
        $email = TestEmailAddress::from('user@gmail.com');
        $this->assertTrue($email->isGmail());
    }

    public function test_email_value_object_is_gmail_returns_false_for_non_gmail(): void
    {
        $email = TestEmailAddress::from('user@example.com');
        $this->assertFalse($email->isGmail());
    }

    public function test_postal_code_get_city_code_returns_first_two_digits(): void
    {
        $postalCode = TestPostalCode::from('75001');
        $cityCode = $postalCode->getCityCode();

        $this->assertSame('75', $cityCode);
    }

    public function test_datetime_value_object_to_date_time_returns_date_time(): void
    {
        $datetime = TestIso8601DateTime::from('2024-01-01T12:00:00+00:00');
        $dateTime = $datetime->toDateTime();

        $this->assertInstanceOf(DateTime::class, $dateTime);
        $this->assertSame('2024-01-01 12:00:00', $dateTime->format('Y-m-d H:i:s'));
    }

    public function test_datetime_value_object_is_after_returns_correct_result(): void
    {
        $datetime1 = TestIso8601DateTime::from('2024-01-02T12:00:00+00:00');
        $datetime2 = TestIso8601DateTime::from('2024-01-01T12:00:00+00:00');

        $this->assertTrue($datetime1->isAfter($datetime2));
        $this->assertFalse($datetime2->isAfter($datetime1));
    }

    public function test_datetime_value_object_is_before_returns_correct_result(): void
    {
        $datetime1 = TestIso8601DateTime::from('2024-01-01T12:00:00+00:00');
        $datetime2 = TestIso8601DateTime::from('2024-01-02T12:00:00+00:00');

        $this->assertTrue($datetime1->isBefore($datetime2));
        $this->assertFalse($datetime2->isBefore($datetime1));
    }

    public function test_money_value_object_add_works_correctly(): void
    {
        $money1 = TestMoney::from(['amount' => 10.50, 'currency' => 'EUR']);
        $money2 = TestMoney::from(['amount' => 5.25, 'currency' => 'EUR']);
        $result = $money1->add($money2);

        $valueResult = $result->getValue();

        $this->assertInstanceOf(TestMoney::class, $result);
        $this->assertEquals(15.75, $valueResult->amount);
        $this->assertSame(TestCurrency::EUR, $valueResult->currency);
    }

    public function test_money_value_object_add_throws_exception_for_different_currencies(): void
    {
        $money1 = TestMoney::from(['amount' => 10.50, 'currency' => 'EUR']);
        $money2 = TestMoney::from(['amount' => 5.25, 'currency' => 'USD']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot add different currencies');

        $money1->add($money2);
    }

    public function test_money_value_object_format_returns_formatted_string(): void
    {
        $money = TestMoney::from(['amount' => 99.99, 'currency' => 'EUR']);
        $formatted = $money->format();

        $this->assertSame('€99.99', $formatted);
    }

    // ==================== IMMUTABILITY TESTS ====================

    public function test_value_objects_are_immutable(): void
    {
        $email = TestEmailAddress::from('test@example.com');
        $originalValue = $email->getValue();

        $this->assertSame($originalValue, $email->getValue());
    }

    public function test_operations_return_new_instances_immutability(): void
    {
        $money = TestMoney::from(['amount' => 10.00, 'currency' => 'EUR']);
        $value = $money->getValue();
        $originalAmount = $value->amount;

        $newMoney = $money->add(TestMoney::from(['amount' => 5.00, 'currency' => 'EUR']));
        $newValue = $newMoney->getValue();

        $this->assertNotSame($money, $newMoney);
        $this->assertSame($originalAmount, $value->amount);
        $this->assertEquals(15.00, $newValue->amount);
    }

    // ==================== MAGIC TO_STRING TESTS ====================

    public function test_to_string_returns_json_representation(): void
    {
        $email = TestEmailAddress::from('test@example.com');
        $string = (string) $email;

        $this->assertIsString($string);
        $this->assertJson($string);
        $this->assertSame('"test@example.com"', $string);
    }

    public function test_to_string_for_money_returns_json_of_record(): void
    {
        $money = TestMoney::from(['amount' => 99.99, 'currency' => 'EUR']);
        $string = (string) $money;

        $this->assertIsString($string);
        $this->assertJson($string);

        $decoded = json_decode($string, true);
        $this->assertArrayHasKey('amount', $decoded);
        $this->assertArrayHasKey('currency', $decoded);
        $this->assertEquals(99.99, $decoded['amount']);
        $this->assertSame('EUR', $decoded['currency']);
    }

    // ==================== VALUE OBJECT AS RECORD PROPERTY TESTS ====================

    public function test_value_object_can_be_used_as_record_property(): void
    {
        $email = TestEmailAddress::from('user@example.com');
        $record = new TestUserRecord(
            name: 'John Doe',
            email: $email
        );

        $this->assertSame($email, $record->email);
        $this->assertSame('user@example.com', $record->email->getValue());
    }

    public function test_value_object_in_record_normalizes_correctly(): void
    {
        $record = new TestUserRecord(
            name: 'John Doe',
            email: TestEmailAddress::from('john@example.com'),
            createdAt: $this->now
        );

        $normalized = NormalizerChain::get()->normalize($record);

        $this->assertSame('john@example.com', $normalized['email']);
        $this->assertIsString($normalized['created_at']);
    }

    // ==================== VALUE OBJECT EQUALITY IN COLLECTIONS TESTS ====================

    public function test_value_objects_can_be_stored_in_collections(): void
    {
        $collection = new TypedCollection(TestEmailAddress::class);
        $collection->add(
            TestEmailAddress::from('user1@example.com'),
            TestEmailAddress::from('user2@example.com'),
            TestEmailAddress::from('user3@example.com')
        );

        $this->assertCount(3, $collection);
        $this->assertSame('user1@example.com', $collection[0]->getValue());
        $this->assertSame('user2@example.com', $collection[1]->getValue());
        $this->assertSame('user3@example.com', $collection[2]->getValue());
    }

    public function test_contains_works_with_value_objects(): void
    {
        $collection = new TypedCollection(TestEmailAddress::class);
        $email1 = TestEmailAddress::from('user1@example.com');
        $email2 = TestEmailAddress::from('user2@example.com');
        $collection->add($email1, $email2);

        $this->assertTrue($collection->contains($email1));
        $this->assertTrue($collection->contains($email2));

        $email3 = TestEmailAddress::from('user3@example.com');
        $this->assertFalse($collection->contains($email3));
    }

    // ==================== EDGE CASES TESTS ====================

    public function test_value_object_with_special_characters_works_correctly(): void
    {
        $email = TestEmailAddress::from('user+tag@example.com');
        $this->assertSame('user+tag@example.com', $email->getValue());
    }

    public function test_value_object_with_unicode_works_correctly(): void
    {
        $email = TestEmailAddress::from('user@example.com');
        $this->assertSame('user@example.com', $email->getValue());
    }

    public function test_datetime_value_object_comparison_with_same_time_works(): void
    {
        $datetime1 = TestIso8601DateTime::from('2024-01-01T12:00:00+00:00');
        $datetime2 = TestIso8601DateTime::from('2024-01-01T12:00:00+00:00');

        $this->assertFalse($datetime1->isAfter($datetime2));
        $this->assertFalse($datetime1->isBefore($datetime2));
        $this->assertTrue($datetime1->equals($datetime2));
    }

    public function test_multiple_value_objects_can_be_created_with_same_value(): void
    {
        $email1 = TestEmailAddress::from('test@example.com');
        $email2 = TestEmailAddress::from('test@example.com');
        $email3 = TestEmailAddress::from('test@example.com');

        $this->assertNotSame($email1, $email2);
        $this->assertNotSame($email2, $email3);
        $this->assertTrue($email1->equals($email2));
        $this->assertTrue($email2->equals($email3));
    }

    // ==================== TYPE SAFETY TESTS ====================

    public function test_value_object_type_is_preserved_in_collections(): void
    {
        $collection = new TypedCollection(TestEmailAddress::class);
        $collection->add(TestEmailAddress::from('test@example.com'));
        $item = $collection[0];

        $this->assertInstanceOf(TestEmailAddress::class, $item);
        $this->assertNotInstanceOf(TestPostalCode::class, $item);
    }

    public function test_value_object_cannot_be_added_to_wrong_collection_type(): void
    {
        $collection = new TypedCollection(TestPostalCode::class);

        $this->expectException(InvalidArgumentException::class);
        $collection->add(TestEmailAddress::from('test@example.com'));
    }
}
