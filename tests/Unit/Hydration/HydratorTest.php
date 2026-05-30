<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Unit\Hydration;

use AndyDefer\DomainStructures\Hydration\Hydrator;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestLifeStage;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestUserGrade;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestUserRole;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestUserStatus;
use AndyDefer\DomainStructures\Tests\Fixtures\Records\TestFullUserRecord;
use AndyDefer\DomainStructures\Tests\Fixtures\Records\TestProductRecord;
use AndyDefer\DomainStructures\Tests\Fixtures\Records\TestRequiredRecord;
use AndyDefer\DomainStructures\Tests\Fixtures\Records\TestRequiredUserRecord;
use AndyDefer\DomainStructures\Tests\Fixtures\Records\TestUserCreateRecord;
use AndyDefer\DomainStructures\Tests\Fixtures\Records\TestUserCriteriaRecord;
use AndyDefer\DomainStructures\Tests\Fixtures\Records\TestUserFiltersRecord;
use AndyDefer\DomainStructures\Tests\Fixtures\Records\TestUserNullableRecord;
use AndyDefer\DomainStructures\Tests\Fixtures\Records\TestUserRecord;
use AndyDefer\DomainStructures\Tests\Fixtures\Records\TestUserUpdateRecord;
use AndyDefer\DomainStructures\Tests\Fixtures\ValueObjects\TestEmailAddress;
use AndyDefer\DomainStructures\Tests\Fixtures\ValueObjects\TestIso8601DateTime;
use AndyDefer\DomainStructures\Tests\TestCase;
use AndyDefer\DomainStructures\Utils\DataObject;
use InvalidArgumentException;

final class HydratorTest extends TestCase
{
    private TestIso8601DateTime $now;

    protected function setUp(): void
    {
        parent::setUp();
        $this->now = TestIso8601DateTime::from('2024-01-01T12:00:00+00:00');
    }

    // ==================== TEST USER RECORD TESTS ====================

    public function test_hydrate_test_user_record_with_all_parameters(): void
    {
        $record = Hydrator::hydrate(TestUserRecord::class, [
            'id' => 1,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'status' => 'active',
            'role' => 'admin',
            'grade' => 2,
            'emailVerifiedAt' => '2024-01-01T12:00:00+00:00',
            'createdAt' => '2024-01-01T12:00:00+00:00',
        ]);

        $this->assertInstanceOf(TestUserRecord::class, $record);
        $this->assertSame(1, $record->id);
        $this->assertSame('John Doe', $record->name);
        $this->assertInstanceOf(TestEmailAddress::class, $record->email);
        $this->assertSame('john@example.com', $record->email->getValue());
        $this->assertSame(TestUserStatus::ACTIVE, $record->status);
        $this->assertSame(TestUserRole::ADMIN, $record->role);
        $this->assertSame(TestUserGrade::SILVER, $record->grade);
        $this->assertInstanceOf(TestIso8601DateTime::class, $record->emailVerifiedAt);
        $this->assertInstanceOf(TestIso8601DateTime::class, $record->createdAt);
    }

    public function test_hydrate_test_user_record_with_minimal_parameters_uses_defaults(): void
    {
        $record = Hydrator::hydrate(TestUserRecord::class, [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $this->assertSame('John Doe', $record->name);
        $this->assertSame('john@example.com', $record->email->getValue());
        $this->assertSame(TestUserStatus::ACTIVE, $record->status);
        $this->assertSame(TestUserRole::USER, $record->role);
        $this->assertSame(TestUserGrade::BRONZE, $record->grade);
        $this->assertNull($record->id);
        $this->assertNull($record->emailVerifiedAt);
        $this->assertNull($record->createdAt);
    }

    public function test_hydrate_test_user_record_with_explicit_null_overrides_defaults(): void
    {
        $record = Hydrator::hydrate(TestUserRecord::class, [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'status' => null,
            'role' => null,
            'grade' => null,
        ]);

        $this->assertNull($record->status);
        $this->assertNull($record->role);
        $this->assertNull($record->grade);
    }

    // ==================== TEST USER NULLABLE RECORD TESTS ====================

    public function test_hydrate_test_user_nullable_record_with_partial_data(): void
    {
        $record = Hydrator::hydrate(TestUserNullableRecord::class, [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
        ]);

        $this->assertNull($record->id);
        $this->assertSame('Jane Doe', $record->name);
        $this->assertSame('jane@example.com', $record->email->getValue());
        $this->assertNull($record->status);
        $this->assertNull($record->role);
        $this->assertNull($record->grade);
    }

    public function test_hydrate_test_user_nullable_record_with_all_nullables(): void
    {
        $record = Hydrator::hydrate(TestUserNullableRecord::class, [
            'id' => null,
            'name' => null,
            'email' => null,
            'status' => null,
            'role' => null,
            'grade' => null,
        ]);

        $this->assertNull($record->id);
        $this->assertNull($record->name);
        $this->assertNull($record->email);
        $this->assertNull($record->status);
        $this->assertNull($record->role);
        $this->assertNull($record->grade);
    }

    // ==================== TEST REQUIRED RECORD TESTS ====================

    public function test_hydrate_test_required_record_with_all_parameters(): void
    {
        $record = Hydrator::hydrate(TestRequiredRecord::class, [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'optional' => 'some value',
        ]);

        $this->assertSame('John Doe', $record->name);
        $this->assertSame('john@example.com', $record->email);
        $this->assertSame('some value', $record->optional);
    }

    public function test_hydrate_test_required_record_without_optional_works(): void
    {
        $record = Hydrator::hydrate(TestRequiredRecord::class, [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $this->assertSame('John Doe', $record->name);
        $this->assertSame('john@example.com', $record->email);
        $this->assertNull($record->optional);
    }

    public function test_hydrate_test_required_record_missing_name_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required parameter "$name"');

        Hydrator::hydrate(TestRequiredRecord::class, [
            'email' => 'john@example.com',
        ]);
    }

    public function test_hydrate_test_required_record_missing_email_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required parameter "$email"');

        Hydrator::hydrate(TestRequiredRecord::class, [
            'name' => 'John Doe',
        ]);
    }

    // ==================== TEST REQUIRED USER RECORD TESTS ====================

    public function test_hydrate_test_required_user_record_with_all_parameters(): void
    {
        $record = Hydrator::hydrate(TestRequiredUserRecord::class, [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'id' => 123,
            'status' => 'active',
            'role' => 'admin',
        ]);

        $this->assertSame('John Doe', $record->name);
        $this->assertInstanceOf(TestEmailAddress::class, $record->email);
        $this->assertSame('john@example.com', $record->email->getValue());
        $this->assertSame(123, $record->id);
        $this->assertSame('active', $record->status);
        $this->assertSame('admin', $record->role);
    }

    public function test_hydrate_test_required_user_record_missing_name_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required parameter "$name"');

        Hydrator::hydrate(TestRequiredUserRecord::class, [
            'email' => 'john@example.com',
        ]);
    }

    public function test_hydrate_test_required_user_record_missing_email_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required parameter "$email"');

        Hydrator::hydrate(TestRequiredUserRecord::class, [
            'name' => 'John Doe',
        ]);
    }

    // ==================== TEST USER CRITERIA RECORD TESTS ====================

    public function test_hydrate_test_user_criteria_record(): void
    {
        $record = Hydrator::hydrate(TestUserCriteriaRecord::class, [
            'name' => 'John',
            'email' => 'john@example.com',
            'lifeStage' => 'adult',
        ]);

        $this->assertSame('John', $record->name);
        $this->assertSame('john@example.com', $record->email);
        $this->assertSame(TestLifeStage::ADULT, $record->lifeStage);
    }

    public function test_hydrate_test_user_criteria_record_empty(): void
    {
        $record = Hydrator::hydrate(TestUserCriteriaRecord::class, []);

        $this->assertNull($record->name);
        $this->assertNull($record->email);
        $this->assertNull($record->lifeStage);
    }

    // ==================== TEST USER CREATE RECORD TESTS ====================

    public function test_hydrate_test_user_create_record(): void
    {
        $record = Hydrator::hydrate(TestUserCreateRecord::class, [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $this->assertSame('John Doe', $record->name);
        $this->assertSame('john@example.com', $record->email);
        $this->assertSame(TestLifeStage::UNKNOWN, $record->lifeStage);
    }

    public function test_hydrate_test_user_create_record_with_life_stage(): void
    {
        $record = Hydrator::hydrate(TestUserCreateRecord::class, [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'lifeStage' => 'adult',
        ]);

        $this->assertSame(TestLifeStage::ADULT, $record->lifeStage);
    }

    // ==================== TEST USER UPDATE RECORD TESTS ====================

    public function test_hydrate_test_user_update_record_with_partial_data(): void
    {
        $record = Hydrator::hydrate(TestUserUpdateRecord::class, [
            'name' => 'John Doe Updated',
        ]);

        $this->assertSame('John Doe Updated', $record->name);
        $this->assertNull($record->email);
        $this->assertNull($record->lifeStage);
    }

    public function test_hydrate_test_user_update_record_with_all_data(): void
    {
        $record = Hydrator::hydrate(TestUserUpdateRecord::class, [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'lifeStage' => 'senior',
        ]);

        $this->assertSame('John Doe', $record->name);
        $this->assertInstanceOf(TestEmailAddress::class, $record->email);
        $this->assertSame('john@example.com', $record->email->getValue());
        $this->assertSame(TestLifeStage::SENIOR, $record->lifeStage);
    }

    // ==================== TEST USER FILTERS RECORD TESTS ====================

    public function test_hydrate_test_user_filters_record(): void
    {
        $record = Hydrator::hydrate(TestUserFiltersRecord::class, [
            'name' => 'John',
            'email' => 'john@example.com',
            'status' => 'active',
            'role' => 'admin',
            'grade' => 3,
        ]);

        $this->assertSame('John', $record->name);
        $this->assertSame('john@example.com', $record->email->getValue());
        $this->assertSame(TestUserStatus::ACTIVE, $record->status);
        $this->assertSame(TestUserRole::ADMIN, $record->role);
        $this->assertSame(TestUserGrade::GOLD, $record->grade);
    }

    // ==================== TEST FULL USER RECORD TESTS ====================

    public function test_hydrate_test_full_user_record(): void
    {
        $record = Hydrator::hydrate(TestFullUserRecord::class, [
            'id' => 1,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'status' => 'active',
            'role' => 'admin',
            'grade' => 3,
            'emailVerifiedAt' => '2024-01-01T12:00:00+00:00',
            'tags' => ['vip', 'premium'],
            'products' => [],
            'featuredProduct' => null,
            'createdAt' => '2024-01-01T12:00:00+00:00',
        ]);

        $this->assertSame(1, $record->id);
        $this->assertSame('John Doe', $record->name);
        $this->assertInstanceOf(TestEmailAddress::class, $record->email);
        $this->assertSame(TestUserStatus::ACTIVE, $record->status);
        $this->assertSame(TestUserRole::ADMIN, $record->role);
        $this->assertSame(TestUserGrade::GOLD, $record->grade);
        $this->assertNotNull($record->emailVerifiedAt);
        $this->assertNotNull($record->createdAt);
    }

    // ==================== TEST PRODUCT RECORD TESTS ====================

    public function test_hydrate_test_product_record(): void
    {
        $record = Hydrator::hydrate(TestProductRecord::class, [
            'id' => 1,
            'name' => 'Laptop',
            'price' => 999.99,
            'isFeatured' => true,
        ]);

        $this->assertSame(1, $record->id);
        $this->assertSame('Laptop', $record->name);
        $this->assertSame(999.99, $record->price);
        $this->assertTrue($record->isFeatured);
    }

    // ==================== DATA OBJECT SOURCE TESTS ====================

    public function test_hydrate_from_data_object(): void
    {
        $data = DataObject::from([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $record = Hydrator::hydrate(TestUserRecord::class, $data);

        $this->assertSame('John Doe', $record->name);
        $this->assertSame('john@example.com', $record->email->getValue());
    }

    public function test_hydrate_from_existing_record_returns_same_instance(): void
    {
        $original = new TestUserRecord(
            name: 'John Doe',
            email: TestEmailAddress::from('john@example.com')
        );

        $result = Hydrator::hydrate(TestUserRecord::class, $original);

        $this->assertSame($original, $result);
    }

    // ==================== EDGE CASES TESTS ====================

    public function test_hydrate_with_empty_array_uses_all_defaults(): void
    {
        $record = Hydrator::hydrate(TestUserRecord::class, []);

        $this->assertNull($record->id);
        $this->assertNull($record->name);
        $this->assertNull($record->email);
        $this->assertSame(TestUserStatus::ACTIVE, $record->status);
        $this->assertSame(TestUserRole::USER, $record->role);
        $this->assertSame(TestUserGrade::BRONZE, $record->grade);
    }

    public function test_hydrate_with_json_string(): void
    {
        $json = '{"name":"John Doe","email":"john@example.com","status":"active"}';
        $record = Hydrator::hydrate(TestUserRecord::class, $json);

        $this->assertSame('John Doe', $record->name);
        $this->assertSame('john@example.com', $record->email->getValue());
        $this->assertSame(TestUserStatus::ACTIVE, $record->status);
    }

    public function test_hydrate_with_invalid_json_string_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $invalidJson = 'invalid json';
        Hydrator::hydrate(TestUserRecord::class, $invalidJson);
    }
}
