<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Unit\Normalizers;

use AndyDefer\DomainStructures\Normalizers\RecordNormalizer;
use AndyDefer\DomainStructures\Normalizers\RootNormalizer;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestUserGrade;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestUserRole;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestUserStatus;
use AndyDefer\DomainStructures\Tests\Fixtures\Records\TestProductRecord;
use AndyDefer\DomainStructures\Tests\Fixtures\Records\TestUserRecord;
use AndyDefer\DomainStructures\Tests\Fixtures\ValueObjects\TestEmailAddress;
use AndyDefer\DomainStructures\Tests\TestCase;
use AndyDefer\DomainStructures\Utils\DataObject;

final class RecordNormalizerTest extends TestCase
{
    private RecordNormalizer $normalizer;

    private RootNormalizer $rootNormalizer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rootNormalizer = new RootNormalizer;

        $this->normalizer = new RecordNormalizer;
        $this->normalizer->setRecursiveNormalizer($this->rootNormalizer);
    }

    private function createTestUserRecord(): TestUserRecord
    {
        $email = TestEmailAddress::from('test@example.com');

        return new TestUserRecord(
            id: 1,
            name: 'John Doe',
            email: $email,
            status: TestUserStatus::ACTIVE,
            role: TestUserRole::USER,
            grade: TestUserGrade::BRONZE
        );
    }

    // ==================== SUPPORTS METHOD TESTS ====================

    public function test_supports_returns_true_for_abstract_record_instance(): void
    {
        $record = $this->createTestUserRecord();
        $result = $this->normalizer->supports($record);

        $this->assertTrue($result);
    }

    public function test_supports_returns_true_for_any_record_subclass(): void
    {
        $records = [
            $this->createTestUserRecord(),
            new TestProductRecord(id: 1, name: 'Product', price: 100),
        ];

        foreach ($records as $record) {
            $result = $this->normalizer->supports($record);
            $this->assertTrue($result);
        }
    }

    public function test_supports_returns_false_for_non_record_values(): void
    {
        $values = [
            42,
            'string',
            3.14,
            true,
            null,
            new DataObject([]),
            TestEmailAddress::from('test@example.com'),
            [],
        ];

        foreach ($values as $value) {
            $result = $this->normalizer->supports($value);
            $this->assertFalse($result, 'Failed for value type: ' . (is_object($value) ? $value::class : gettype($value)));
        }
    }

    // ==================== NORMALIZE METHOD TESTS ====================

    public function test_normalize_returns_array_representation_of_record(): void
    {
        $record = $this->createTestUserRecord();
        $normalized = $this->normalizer->normalize($record);

        $this->assertIsArray($normalized);
        $this->assertArrayHasKey('id', $normalized);
        $this->assertArrayHasKey('name', $normalized);
        $this->assertArrayHasKey('email', $normalized);
        $this->assertSame(1, $normalized['id']);
        $this->assertSame('John Doe', $normalized['name']);
        $this->assertSame('test@example.com', $normalized['email']);
    }

    public function test_normalize_returns_snake_case_keys_for_database(): void
    {
        $record = $this->createTestUserRecord();
        $normalized = $this->normalizer->normalize($record);

        $this->assertArrayHasKey('email_verified_at', $normalized);
        $this->assertArrayNotHasKey('emailVerifiedAt', $normalized);
        $this->assertArrayHasKey('created_at', $normalized);
        $this->assertArrayNotHasKey('createdAt', $normalized);
    }

    public function test_normalize_excludes_null_values_when_include_nulls_false(): void
    {
        $record = new TestUserRecord(
            id: null,
            name: 'John Doe',
            email: TestEmailAddress::from('john@example.com'),
            emailVerifiedAt: null,
            featuredProduct: null
        );

        $normalized = $this->normalizer->normalize($record);

        $this->assertArrayNotHasKey('id', $normalized);
        $this->assertArrayNotHasKey('email_verified_at', $normalized);
        $this->assertArrayNotHasKey('featured_product', $normalized);
        $this->assertArrayHasKey('name', $normalized);
        $this->assertArrayHasKey('email', $normalized);
    }

    public function test_normalize_handles_nested_value_objects_inside_record(): void
    {
        $email = TestEmailAddress::from('nested@example.com');
        $record = new TestUserRecord(name: 'John', email: $email);
        $normalized = $this->normalizer->normalize($record);

        $this->assertIsString($normalized['email']);
        $this->assertSame('nested@example.com', $normalized['email']);
    }

    public function test_normalize_handles_nested_enums_inside_record(): void
    {
        $record = $this->createTestUserRecord();
        $normalized = $this->normalizer->normalize($record);

        $this->assertSame('active', $normalized['status']);
        $this->assertSame('user', $normalized['role']);
        $this->assertSame(1, $normalized['grade']);
    }

    public function test_normalize_forwards_to_next_normalizer_when_not_record(): void
    {
        $value = 'not a record';
        $normalized = $this->normalizer->normalize($value);

        $this->assertSame('not a record', $normalized);
    }

    public function test_normalize_is_idempotent(): void
    {
        $record = $this->createTestUserRecord();
        $first = $this->normalizer->normalize($record);
        $second = $this->normalizer->normalize($record);
        $third = $this->normalizer->normalize($record);

        $this->assertSame($first, $second);
        $this->assertSame($second, $third);
    }

    public function test_normalize_on_empty_record_works(): void
    {
        $record = new TestUserRecord(
            name: '',
            email: TestEmailAddress::from('empty@example.com')
        );
        $normalized = $this->normalizer->normalize($record);

        $this->assertIsArray($normalized);
        $this->assertSame('', $normalized['name']);
        $this->assertSame('empty@example.com', $normalized['email']);
    }
}
