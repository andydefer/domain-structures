<?php
// tests/Unit/Services/ItemHydrationServiceTest.php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Unit\Services;

use AndyDefer\DomainStructures\Services\ItemHydrationService;
use AndyDefer\DomainStructures\Tests\TestCase;
use AndyDefer\DomainStructures\Tests\Fixtures\Records\TestUserRecord;
use AndyDefer\DomainStructures\Tests\Fixtures\Records\TestRequiredRecord;
use AndyDefer\DomainStructures\Tests\Fixtures\Records\TestMoneyRecord;
use AndyDefer\DomainStructures\Tests\Fixtures\Records\TestUserNullableRecord;
use AndyDefer\DomainStructures\Tests\Fixtures\Data\TestUserData;
use AndyDefer\DomainStructures\Tests\Fixtures\Data\TestSimpleUserData;
use AndyDefer\DomainStructures\Tests\Fixtures\Data\TestProductData;
use AndyDefer\DomainStructures\Tests\Fixtures\ValueObjects\TestEmailAddress;
use AndyDefer\DomainStructures\Tests\Fixtures\ValueObjects\TestMoney;
use AndyDefer\DomainStructures\Tests\Fixtures\ValueObjects\TestIso8601DateTime;
use AndyDefer\DomainStructures\Tests\Fixtures\ValueObjects\TestPostalCode;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestUserStatus;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestUserRole;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestUserGrade;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestCurrency;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestBackedStringEnum;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestBackedIntEnum;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestPureEnum;
use AndyDefer\DomainStructures\Utils\DataObject;
use AndyDefer\DomainStructures\Utils\StrictDataObject;
use InvalidArgumentException;
use RuntimeException;

final class ItemHydrationServiceTest extends TestCase
{
    private ItemHydrationService $service;
    private TestIso8601DateTime $now;
    private TestEmailAddress $testEmail;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ItemHydrationService();
        $this->now = new TestIso8601DateTime('2024-01-01T12:00:00+00:00');
        $this->testEmail = new TestEmailAddress('test@example.com');
    }

    // ==================== RECORD HYDRATION TESTS ====================

    public function test_hydrate_record_from_array(): void
    {
        $data = [
            'id' => 1,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'status' => 'active',
            'price' => 'active',
            'role' => 'user',
            'grade' => 1,
        ];

        $record = $this->service->hydrate(TestUserRecord::class, $data);

        $this->assertInstanceOf(TestUserRecord::class, $record);
        $this->assertSame(1, $record->id);
        $this->assertSame('John Doe', $record->name);
        $this->assertSame('john@example.com', $record->email->getValue());
        $this->assertSame(TestUserStatus::ACTIVE, $record->status);
        $this->assertSame(TestUserRole::USER, $record->role);
        $this->assertSame(TestUserGrade::BRONZE, $record->grade);
    }

    public function test_hydrate_record_from_json(): void
    {
        $json = '{"id":1,"name":"John Doe","email":"john@example.com","status":"active","role":"user","grade":1}';

        $record = $this->service->hydrate(TestUserRecord::class, $json);

        $this->assertInstanceOf(TestUserRecord::class, $record);
        $this->assertSame(1, $record->id);
        $this->assertSame('John Doe', $record->name);
        $this->assertSame('john@example.com', $record->email->getValue());
    }

    public function test_hydrate_record_from_data_object(): void
    {
        $dataObject = DataObject::from([
            'id' => 1,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'status' => 'active',
            'role' => 'user',
            'grade' => 1,
        ]);

        $record = $this->service->hydrate(TestUserRecord::class, $dataObject);

        $this->assertInstanceOf(TestUserRecord::class, $record);
        $this->assertSame(1, $record->id);
        $this->assertSame('John Doe', $record->name);
    }

    public function test_hydrate_record_from_existing_record(): void
    {
        $original = new TestUserRecord(
            id: 1,
            name: 'John Doe',
            email: $this->testEmail,
            status: TestUserStatus::ACTIVE,
            role: TestUserRole::USER,
            grade: TestUserGrade::BRONZE
        );

        $result = $this->service->hydrate(TestUserRecord::class, $original);

        $this->assertSame($original, $result);
    }

    public function test_hydrate_record_with_nullable_properties(): void
    {
        $data = [
            'id' => 1,
            'name' => 'John Doe',
            'email' => null,
            'status' => null,
        ];

        $record = $this->service->hydrate(TestUserNullableRecord::class, $data);

        $this->assertSame(1, $record->id);
        $this->assertSame('John Doe', $record->name);
        $this->assertNull($record->email);
        $this->assertNull($record->status);
    }

    public function test_hydrate_record_with_default_values(): void
    {
        $data = ['name' => 'John Doe', 'email' => 'john@example.com'];

        $record = $this->service->hydrate(TestUserRecord::class, $data);

        $this->assertSame(TestUserStatus::ACTIVE, $record->status);
        $this->assertSame(TestUserRole::USER, $record->role);
        $this->assertSame(TestUserGrade::BRONZE, $record->grade);
    }

    public function test_hydrate_record_missing_required_field_throws_exception(): void
    {
        $data = ['name' => 'John Doe'];

        $this->expectException(InvalidArgumentException::class);
        $this->service->hydrate(TestRequiredRecord::class, $data);
    }

    // ==================== DATA HYDRATION TESTS ====================

    public function test_hydrate_data_from_array(): void
    {
        $data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'status' => 'active',
            'role' => 'user',
            'grade' => 1,
        ];

        $dataObject = $this->service->hydrate(TestUserData::class, $data);

        $this->assertInstanceOf(TestUserData::class, $dataObject);
        $this->assertSame('John Doe', $dataObject->name);
        $this->assertSame('john@example.com', $dataObject->email->getValue());
        $this->assertSame(TestUserStatus::ACTIVE, $dataObject->status);
        $this->assertSame(TestUserRole::USER, $dataObject->role);
        $this->assertSame(TestUserGrade::BRONZE, $dataObject->grade);
    }

    public function test_hydrate_data_from_record(): void
    {
        $record = new TestUserRecord(
            id: 1,
            name: 'John Doe',
            email: $this->testEmail,
            status: TestUserStatus::ACTIVE,
            role: TestUserRole::USER,
            grade: TestUserGrade::BRONZE
        );

        $dataObject = $this->service->hydrate(TestUserData::class, $record);

        $this->assertInstanceOf(TestUserData::class, $dataObject);
        $this->assertSame('John Doe', $dataObject->name);
        $this->assertSame($this->testEmail->getValue(), $dataObject->email->getValue());
    }

    public function test_hydrate_data_from_json(): void
    {
        $json = '{"name":"John Doe","email":"john@example.com","status":"active","role":"user","grade":1}';

        $dataObject = $this->service->hydrate(TestUserData::class, $json);

        $this->assertInstanceOf(TestUserData::class, $dataObject);
        $this->assertSame('John Doe', $dataObject->name);
    }

    public function test_hydrate_data_from_data_object(): void
    {
        $dataObject = DataObject::from([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'status' => 'active',
            'role' => 'user',
            'grade' => 1,
        ]);

        $result = $this->service->hydrate(TestUserData::class, $dataObject);

        $this->assertInstanceOf(TestUserData::class, $result);
        $this->assertSame('John Doe', $result->name);
    }

    // ==================== VALUE OBJECT HYDRATION TESTS ====================

    public function test_hydrate_value_object_from_scalar(): void
    {
        $email = $this->service->hydrate(TestEmailAddress::class, 'test@example.com');

        $this->assertInstanceOf(TestEmailAddress::class, $email);
        $this->assertSame('test@example.com', $email->getValue());
    }

    public function test_hydrate_value_object_from_array(): void
    {
        $data = ['amount' => 99.99, 'currency' => 'EUR'];

        $money = $this->service->hydrate(TestMoney::class, $data);

        $this->assertInstanceOf(TestMoney::class, $money);
    }

    public function test_hydrate_value_object_from_json(): void
    {
        $json = '{"amount":99.99,"currency":"EUR"}';

        $money = $this->service->hydrate(TestMoney::class, $json);

        $this->assertInstanceOf(TestMoney::class, $money);
    }

    public function test_hydrate_value_object_with_validation(): void
    {
        $data = ['value' => '75001'];

        $postalCode = $this->service->hydrate(TestPostalCode::class, $data);

        $this->assertInstanceOf(TestPostalCode::class, $postalCode);
        $this->assertSame('75001', $postalCode->value);
    }

    public function test_hydrate_value_object_with_invalid_data_throws_exception(): void
    {
        $data = ['value' => 'invalid'];

        $this->expectException(InvalidArgumentException::class);
        $this->service->hydrate(TestPostalCode::class, $data);
    }

    // ==================== ENUM HYDRATION TESTS ====================

    public function test_hydrate_backed_string_enum_from_string(): void
    {
        $enum = $this->service->hydrate(TestBackedStringEnum::class, 'one');

        $this->assertInstanceOf(TestBackedStringEnum::class, $enum);
        $this->assertSame(TestBackedStringEnum::VALUE_ONE, $enum);
    }

    public function test_hydrate_backed_string_enum_from_array(): void
    {
        $data = ['value' => 'two'];

        $enum = $this->service->hydrate(TestBackedStringEnum::class, $data);

        $this->assertSame(TestBackedStringEnum::VALUE_TWO, $enum);
    }

    public function test_hydrate_backed_int_enum_from_int(): void
    {
        $enum = $this->service->hydrate(TestBackedIntEnum::class, 1);

        $this->assertInstanceOf(TestBackedIntEnum::class, $enum);
        $this->assertSame(TestBackedIntEnum::VALUE_ONE, $enum);
    }

    public function test_hydrate_backed_int_enum_from_string(): void
    {
        $enum = $this->service->hydrate(TestBackedIntEnum::class, '2');

        $this->assertSame(TestBackedIntEnum::VALUE_TWO, $enum);
    }

    public function test_hydrate_pure_enum_from_string(): void
    {
        $enum = $this->service->hydrate(TestPureEnum::class, 'VALUE_ONE');

        $this->assertInstanceOf(TestPureEnum::class, $enum);
        $this->assertSame(TestPureEnum::VALUE_ONE, $enum);
    }

    public function test_hydrate_enum_from_json(): void
    {
        $json = 'active';

        $enum = $this->service->hydrate(TestUserStatus::class, $json);

        $this->assertSame(TestUserStatus::ACTIVE, $enum);
    }

    public function test_hydrate_enum_from_array_with_type(): void
    {
        $data = ['_type' => TestUserRole::class, 'value' => 'admin'];

        $enum = $this->service->hydrate(TestUserRole::class, $data);

        $this->assertSame(TestUserRole::ADMIN, $enum);
    }

    public function test_hydrate_enum_with_invalid_value_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->hydrate(TestUserStatus::class, 'invalid_status');
    }

    // ==================== DATA OBJECT HYDRATION TESTS ====================

    public function test_hydrate_data_object_from_array_with_camelcase(): void
    {
        $data = ['user_id' => 1, 'user_name' => 'John Doe', 'is_active' => true];

        $dataObject = $this->service->hydrate(DataObject::class, $data);

        $this->assertInstanceOf(DataObject::class, $dataObject);
        $this->assertSame(1, $dataObject->userId);
        $this->assertSame('John Doe', $dataObject->userName);
        $this->assertTrue($dataObject->isActive);
    }

    public function test_hydrate_data_object_from_json(): void
    {
        $json = '{"user_id":1,"user_name":"John Doe"}';

        $dataObject = $this->service->hydrateFromJson(DataObject::class, $json);

        $this->assertInstanceOf(DataObject::class, $dataObject);
        $this->assertSame(1, $dataObject->userId);
        $this->assertSame('John Doe', $dataObject->userName);
    }

    public function test_hydrate_strict_data_object_preserves_case(): void
    {
        $data = ['UserId' => 1, 'UserName' => 'John Doe'];

        $dataObject = $this->service->hydrate(StrictDataObject::class, $data);

        $this->assertInstanceOf(StrictDataObject::class, $dataObject);
        $this->assertSame(1, $dataObject->UserId);
        $this->assertSame('John Doe', $dataObject->UserName);
    }

    // ==================== SCALAR HYDRATION TESTS ====================

    public function test_hydrate_string(): void
    {
        $result = $this->service->hydrate('string', 123);

        $this->assertSame('123', $result);
    }

    public function test_hydrate_int(): void
    {
        $result = $this->service->hydrate('int', '456');

        $this->assertSame(456, $result);
    }

    public function test_hydrate_float(): void
    {
        $result = $this->service->hydrate('float', '78.90');

        $this->assertSame(78.9, $result);
    }

    public function test_hydrate_bool(): void
    {
        $result = $this->service->hydrate('bool', 'true');

        $this->assertTrue($result);
    }

    // ==================== NESTED HYDRATION TESTS ====================

    public function test_hydrate_nested_objects_in_record(): void
    {
        $data = [
            'id' => 1,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'status' => 'active',
            'role' => 'user',
            'grade' => 1,
            'emailVerifiedAt' => '2024-01-15T10:00:00+01:00',
            'createdAt' => '2024-01-01T12:00:00+01:00',
        ];

        $record = $this->service->hydrate(TestUserRecord::class, $data);

        $this->assertInstanceOf(TestIso8601DateTime::class, $record->emailVerifiedAt);
        $this->assertInstanceOf(TestIso8601DateTime::class, $record->createdAt);
    }

    public function test_hydrate_nested_value_objects_in_data(): void
    {
        $data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'status' => 'active',
            'role' => 'user',
            'grade' => 1,
            'emailVerifiedAt' => '2024-01-15T10:00:00+01:00',
        ];

        $dataObject = $this->service->hydrate(TestUserData::class, $data);

        $this->assertInstanceOf(TestIso8601DateTime::class, $dataObject->emailVerifiedAt);
    }

    // ==================== JSON HYDRATION TESTS ====================

    public function test_hydrate_from_json_with_snake_case_keys(): void
    {
        $json = '{"first_name":"John","last_name":"Doe","email":"john@example.com"}';

        $result = $this->service->hydrate(TestSimpleUserData::class, $json);

        $this->assertSame('John', $result->firstName);
        $this->assertSame('john@example.com', $result->email->getValue());
    }

    public function test_hydrate_from_json_with_nested_json(): void
    {
        $json = '{"amount":49.99,"currency":"USD"}';

        $money = $this->service->hydrate(TestMoneyRecord::class, $json);

        $this->assertSame(49.99, $money->amount);
        $this->assertSame(TestCurrency::USD, $money->currency);
    }

    public function test_hydrate_from_json_throws_exception_for_invalid_json(): void
    {
        $this->expectException(RuntimeException::class);
        $this->service->hydrateFromJson(TestUserRecord::class, '{invalid json}');
    }

    // ==================== CONVERSION TESTS ====================

    public function test_hydrate_converts_string_id_to_int(): void
    {
        $data = ['id' => '123', 'name' => 'John Doe', 'email' => 'john@example.com'];

        $record = $this->service->hydrate(TestUserRecord::class, $data);

        $this->assertIsInt($record->id);
        $this->assertSame(123, $record->id);
    }

    public function test_hydrate_converts_string_enum_to_enum(): void
    {
        $data = ['name' => 'John Doe', 'email' => 'john@example.com', 'status' => 'suspended'];

        $record = $this->service->hydrate(TestUserRecord::class, $data);

        $this->assertSame(TestUserStatus::SUSPENDED, $record->status);
    }

    public function test_hydrate_converts_string_grade_to_enum(): void
    {
        $data = ['name' => 'John Doe', 'email' => 'john@example.com', 'grade' => '4'];

        $record = $this->service->hydrate(TestUserRecord::class, $data);

        $this->assertSame(TestUserGrade::PLATINUM, $record->grade);
    }

    // ==================== HYDRATION FROM EXISTING OBJECTS TESTS ====================

    public function test_hydrate_from_data_object_to_record(): void
    {
        $dataObject = new DataObject([
            'id' => 1,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'status' => 'active',
            'role' => 'user',
            'grade' => 1,
        ]);

        $record = $this->service->hydrate(TestUserRecord::class, $dataObject);

        $this->assertInstanceOf(TestUserRecord::class, $record);
        $this->assertSame(1, $record->id);
        $this->assertSame('John Doe', $record->name);
    }

    public function test_hydrate_from_record_to_data(): void
    {
        $record = new TestUserRecord(
            name: 'John Doe',
            email: $this->testEmail,
            status: TestUserStatus::ACTIVE,
            role: TestUserRole::USER,
            grade: TestUserGrade::BRONZE
        );

        $data = $this->service->hydrate(TestUserData::class, $record);

        $this->assertInstanceOf(TestUserData::class, $data);
        $this->assertSame('John Doe', $data->name);
        $this->assertSame(TestUserStatus::ACTIVE, $data->status);
    }

    // ==================== IDEMPOTENCY TESTS ====================

    public function test_hydrate_is_idempotent(): void
    {
        $data = ['name' => 'John Doe', 'email' => 'john@example.com'];

        $first = $this->service->hydrate(TestUserRecord::class, $data);
        $second = $this->service->hydrate(TestUserRecord::class, $data);

        $this->assertEquals($first, $second);
    }

    public function test_hydrate_from_json_is_idempotent(): void
    {
        $json = '{"name":"John Doe","email":"john@example.com"}';

        $first = $this->service->hydrateFromJson(TestUserRecord::class, $json);
        $second = $this->service->hydrateFromJson(TestUserRecord::class, $json);

        $this->assertEquals($first, $second);
    }

    // ==================== ERROR HANDLING TESTS ====================

    public function test_hydrate_with_nonexistent_class_throws_exception(): void
    {
        // ReflectionException est levée car la classe n'existe pas
        $this->expectException(\ReflectionException::class);
        $this->expectExceptionMessage('Class "NonExistentClass" does not exist');

        $this->service->hydrate('NonExistentClass', []);
    }

    public function test_hydrate_from_json_with_empty_string_throws_exception(): void
    {
        $this->expectException(RuntimeException::class);
        $this->service->hydrateFromJson(TestUserRecord::class, '');
    }
}
