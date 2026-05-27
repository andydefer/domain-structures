<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Unit\Traits;

use AndyDefer\DomainStructures\Tests\Fixtures\Data\TestSimpleUserData;
use AndyDefer\DomainStructures\Utils\DataObject;
use AndyDefer\DomainStructures\Tests\Fixtures\Data\TestUserData;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestUserGrade;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestUserRole;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestUserStatus;
use AndyDefer\DomainStructures\Tests\Fixtures\Records\TestRequiredRecord;
use AndyDefer\DomainStructures\Tests\Fixtures\Records\TestUserRecord;
use AndyDefer\DomainStructures\Tests\Fixtures\Mixed\UserWithGetters;
use AndyDefer\DomainStructures\Tests\Fixtures\ValueObjects\TestEmailAddress;
use AndyDefer\DomainStructures\Tests\Fixtures\ValueObjects\TestIso8601DateTime;
use AndyDefer\DomainStructures\Tests\TestCase;
use RuntimeException;

final class HydratableTest extends TestCase
{
    private TestIso8601DateTime $now;
    private TestEmailAddress $testEmail;

    protected function setUp(): void
    {
        parent::setUp();
        $this->now = TestIso8601DateTime::from('2024-01-01T12:00:00+00:00');
        $this->testEmail = TestEmailAddress::from('test@example.com');
    }

    // ==================== BASIC HYDRATION TESTS ====================

    public function test_hydrates_from_array(): void
    {
        $source = [
            'id' => 1,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'status' => 'active',
            'role' => 'user',
            'grade' => 1,
        ];

        $result = TestUserData::from($source);

        $this->assertSame(1, $result->id);
        $this->assertSame('John Doe', $result->name);
        $this->assertSame('john@example.com', $result->email->getValue());
        $this->assertSame(TestUserStatus::ACTIVE, $result->status);
        $this->assertSame(TestUserRole::USER, $result->role);
        $this->assertSame(TestUserGrade::BRONZE, $result->grade);
    }

    public function test_hydrates_from_data_object(): void
    {
        $source = DataObject::from([
            'id' => 1,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'status' => 'active',
            'role' => 'user',
            'grade' => 1,
        ]);

        $result = TestUserData::from($source);

        $this->assertSame(1, $result->id);
        $this->assertSame('John Doe', $result->name);
        $this->assertSame('john@example.com', $result->email->getValue());
        $this->assertSame(TestUserStatus::ACTIVE, $result->status);
        $this->assertSame(TestUserRole::USER, $result->role);
        $this->assertSame(TestUserGrade::BRONZE, $result->grade);
    }

    // ==================== CAMELCASE / SNAKE_CASE TESTS ====================

    public function test_converts_snake_case_to_camel_case(): void
    {
        $source = DataObject::from([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com'
        ]);

        $result = TestSimpleUserData::from($source);

        $this->assertSame('John', $result->firstName);
        $this->assertSame('john@example.com', $result->email->getValue());
    }

    // ==================== DEFAULT VALUES TESTS ====================

    public function test_uses_default_values_when_property_absent(): void
    {
        $source = DataObject::from([
            'name' => 'John Doe',
            'email' => $this->testEmail
        ]);

        $result = TestUserRecord::from($source);

        $this->assertSame('John Doe', $result->name);
        $this->assertSame(TestUserStatus::ACTIVE, $result->status);
        $this->assertSame(TestUserRole::USER, $result->role);
        $this->assertSame(TestUserGrade::BRONZE, $result->grade);
    }

    public function test_explicit_null_overrides_default_value(): void
    {
        $source = DataObject::from([
            'name' => 'John Doe',
            'email' => $this->testEmail,
            'status' => null,
            'role' => null,
            'grade' => null
        ]);

        $result = TestUserRecord::from($source);

        $this->assertNull($result->status);
        $this->assertNull($result->role);
        $this->assertNull($result->grade);
    }

    // ==================== COLLECTION METHOD TESTS ====================

    public function test_collect_creates_array_of_instances(): void
    {
        $sources = [
            ['id' => 1, 'name' => 'User 1', 'email' => 'user1@example.com', 'status' => 'active', 'role' => 'user', 'grade' => 1],
            ['id' => 2, 'name' => 'User 2', 'email' => 'user2@example.com', 'status' => 'active', 'role' => 'user', 'grade' => 1],
            ['id' => 3, 'name' => 'User 3', 'email' => 'user3@example.com', 'status' => 'active', 'role' => 'user', 'grade' => 1],
        ];

        $results = TestUserData::collect($sources);

        $this->assertCount(3, $results);
        $this->assertInstanceOf(TestUserData::class, $results[0]);
        $this->assertSame(1, $results[0]->id);
        $this->assertSame('User 2', $results[1]->name);
        $this->assertSame('user3@example.com', $results[2]->email->getValue());
    }

    public function test_collect_returns_empty_array_for_empty_iterable(): void
    {
        $results = TestUserData::collect([]);

        $this->assertIsArray($results);
        $this->assertEmpty($results);
    }

    // ==================== RECURSIVE HYDRATION TESTS ====================

    public function test_hydrates_nested_value_objects(): void
    {
        $source = DataObject::from([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'status' => 'active',
            'role' => 'user',
            'grade' => 1
        ]);

        $result = TestUserData::from($source);

        $this->assertInstanceOf(TestEmailAddress::class, $result->email);
        $this->assertSame('john@example.com', $result->email->getValue());
    }

    // ==================== ERROR HANDLING TESTS ====================

    public function test_throws_exception_for_missing_required_parameter(): void
    {
        $source = DataObject::from(['email' => 'john@example.com']);
        // 'name' is required

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Missing required parameter "$name"');

        TestRequiredRecord::from($source);
    }

    public function test_throws_exception_for_invalid_enum_value(): void
    {
        $source = DataObject::from([
            'name' => 'John Doe',
            'email' => $this->testEmail,
            'status' => 'invalid_status'
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid value "invalid_status" for enum');

        TestUserRecord::from($source);
    }

    // ==================== IDEMPOTENCY TESTS ====================

    public function test_from_is_idempotent(): void
    {
        $source = ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com', 'status' => 'active', 'role' => 'user', 'grade' => 1];

        $first = TestUserData::from($source);
        $second = TestUserData::from($source);
        $third = TestUserData::from($source);

        $this->assertSame($first->id, $second->id);
        $this->assertSame($second->id, $third->id);
    }

    // ==================== NULL HANDLING TESTS ====================

    public function test_handles_null_values(): void
    {
        $source = DataObject::from([
            'id' => null,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'status' => 'active',
            'role' => 'user',
            'grade' => 1
        ]);

        $result = TestUserData::from($source);

        $this->assertNull($result->id);
        $this->assertSame('John Doe', $result->name);
    }
}
