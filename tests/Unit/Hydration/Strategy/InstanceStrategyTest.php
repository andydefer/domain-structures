<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Unit\Hydration\Strategy;

use AndyDefer\DomainStructures\Collections\Utility\StringTypedCollection;
use AndyDefer\DomainStructures\Hydration\Strategy\InstanceStrategy;
use AndyDefer\DomainStructures\Tests\Fixtures\Data\TestUserData;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestUserGrade;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestUserRole;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestUserStatus;
use AndyDefer\DomainStructures\Tests\Fixtures\Records\TestUserRecord;
use AndyDefer\DomainStructures\Tests\Fixtures\ValueObjects\TestEmailAddress;
use AndyDefer\DomainStructures\Tests\Fixtures\ValueObjects\TestIso8601DateTime;
use AndyDefer\DomainStructures\Tests\TestCase;

final class InstanceStrategyTest extends TestCase
{
    private InstanceStrategy $strategy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->strategy = new InstanceStrategy;
    }

    // ==================== SUPPORTS METHOD TESTS ====================

    public function test_supports_returns_true_when_source_is_instance_of_class(): void
    {
        $record = new TestUserRecord(name: 'John', email: TestEmailAddress::from('john@example.com'));

        $result = $this->strategy->supports(TestUserRecord::class, $record);
        $this->assertTrue($result);
    }

    public function test_supports_returns_false_when_source_is_not_instance_of_class(): void
    {
        $result = $this->strategy->supports(TestUserRecord::class, ['not', 'an', 'instance']);
        $this->assertFalse($result);
    }

    public function test_supports_returns_false_for_abstract_data_subclass(): void
    {
        $data = new TestUserData(
            name: 'John',
            email: TestEmailAddress::from('john@example.com'),
            status: TestUserStatus::ACTIVE,
            role: TestUserRole::USER,
            grade: TestUserGrade::BRONZE,
            id: 1,
            emailVerifiedAt: null,
            tags: new StringTypedCollection,
            createdAt: TestIso8601DateTime::from('2024-01-01T00:00:00+00:00')
        );

        $result = $this->strategy->supports(TestUserData::class, $data);
        $this->assertFalse($result);
    }

    public function test_supports_returns_false_when_source_is_not_object(): void
    {
        $nonObjects = [null, 'string', 42, 3.14, true, []];

        foreach ($nonObjects as $source) {
            $result = $this->strategy->supports(TestUserRecord::class, $source);
            $this->assertFalse($result, 'Failed for source type: '.gettype($source));
        }
    }

    // ==================== HYDRATE METHOD TESTS ====================

    public function test_hydrate_returns_same_instance(): void
    {
        $original = new TestUserRecord(name: 'John', email: TestEmailAddress::from('john@example.com'));

        $result = $this->strategy->hydrate(TestUserRecord::class, $original);

        $this->assertSame($original, $result);
    }

    public function test_hydrate_preserves_object_state(): void
    {
        $original = new TestUserRecord(
            name: 'Jane Doe',
            email: TestEmailAddress::from('jane@example.com')
        );

        $result = $this->strategy->hydrate(TestUserRecord::class, $original);

        $this->assertSame('Jane Doe', $result->name);
        $this->assertSame('jane@example.com', $result->email->getValue());
    }

    // ==================== EDGE CASES TESTS ====================

    public function test_hydrate_works_with_different_record_types(): void
    {
        $record = new TestUserRecord(name: 'Test', email: TestEmailAddress::from('test@example.com'));

        $result = $this->strategy->hydrate(TestUserRecord::class, $record);

        $this->assertSame($record, $result);
    }

    public function test_strategy_is_idempotent(): void
    {
        $record = new TestUserRecord(name: 'Idempotent', email: TestEmailAddress::from('idemp@example.com'));

        $first = $this->strategy->hydrate(TestUserRecord::class, $record);
        $second = $this->strategy->hydrate(TestUserRecord::class, $first);

        $this->assertSame($record, $first);
        $this->assertSame($first, $second);
    }
}
