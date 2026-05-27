<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Unit\Normalizers;

use AndyDefer\DomainStructures\Abstracts\AbstractData;
use AndyDefer\DomainStructures\Collections\Core\DataCollection;
use AndyDefer\DomainStructures\Collections\Utility\StringTypedCollection;
use AndyDefer\DomainStructures\Normalizers\DataNormalizer;
use AndyDefer\DomainStructures\Normalizers\RootNormalizer;
use AndyDefer\DomainStructures\Tests\Fixtures\Data\TestProductData;
use AndyDefer\DomainStructures\Tests\Fixtures\Data\TestUserData;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestUserGrade;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestUserRole;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestUserStatus;
use AndyDefer\DomainStructures\Tests\Fixtures\Records\TestUserRecord;
use AndyDefer\DomainStructures\Tests\Fixtures\ValueObjects\TestEmailAddress;
use AndyDefer\DomainStructures\Tests\Fixtures\ValueObjects\TestIso8601DateTime;
use AndyDefer\DomainStructures\Tests\TestCase;
use AndyDefer\DomainStructures\Utils\EmptyData;

final class DataNormalizerTest extends TestCase
{
    private DataNormalizer $normalizer;
    private RootNormalizer $rootNormalizer;
    private TestIso8601DateTime $now;
    private TestEmailAddress $testEmail;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rootNormalizer = new RootNormalizer();

        $this->normalizer = new DataNormalizer();
        $this->normalizer->setRecursiveNormalizer($this->rootNormalizer);

        $this->now = TestIso8601DateTime::from('2024-01-01T12:00:00+00:00');
        $this->testEmail = TestEmailAddress::from('test@example.com');
    }

    private function createTestUserData(): TestUserData
    {
        return new TestUserData(
            id: 1,
            name: 'John Doe',
            email: $this->testEmail,
            status: TestUserStatus::ACTIVE,
            role: TestUserRole::USER,
            grade: TestUserGrade::BRONZE,
            emailVerifiedAt: null,
            tags: new StringTypedCollection,
            createdAt: $this->now
        );
    }

    // ==================== SUPPORTS METHOD TESTS ====================

    public function test_supports_returns_true_for_abstract_data_instance(): void
    {
        $data = $this->createTestUserData();
        $result = $this->normalizer->supports($data);
        $this->assertTrue($result);
    }

    public function test_supports_returns_false_for_non_abstract_data(): void
    {
        $values = [
            42,
            'string',
            3.14,
            true,
            null,
            new \stdClass,
            new TestUserRecord(name: 'Test', email: $this->testEmail),
            TestEmailAddress::from('test@example.com'),
        ];

        foreach ($values as $value) {
            $result = $this->normalizer->supports($value);
            $this->assertFalse($result, 'Failed for value type: ' . (is_object($value) ? $value::class : gettype($value)));
        }
    }

    public function test_supports_returns_true_for_any_abstract_data_subclass(): void
    {
        $dataClasses = [
            new TestUserData(
                id: 1,
                name: 'Test',
                email: $this->testEmail,
                status: TestUserStatus::ACTIVE,
                role: TestUserRole::USER,
                grade: TestUserGrade::BRONZE,
                emailVerifiedAt: null,
                tags: new StringTypedCollection,
                createdAt: $this->now
            ),
            new TestProductData(id: 1, name: 'Product', price: 99.99),
        ];

        foreach ($dataClasses as $data) {
            $result = $this->normalizer->supports($data);
            $this->assertTrue($result);
        }
    }

    // ==================== NORMALIZE METHOD TESTS ====================

    public function test_normalize_returns_array_representation_of_data(): void
    {
        $data = $this->createTestUserData();
        $normalized = $this->normalizer->normalize($data);

        $this->assertIsArray($normalized);
        $this->assertArrayHasKey('id', $normalized);
        $this->assertArrayHasKey('name', $normalized);
        $this->assertArrayHasKey('email', $normalized);
        $this->assertSame(1, $normalized['id']);
        $this->assertSame('John Doe', $normalized['name']);
        $this->assertSame('test@example.com', $normalized['email']);
    }

    public function test_normalize_returns_camel_case_keys_for_api(): void
    {
        $data = $this->createTestUserData();
        $normalized = $this->normalizer->normalize($data);

        $this->assertArrayHasKey('emailVerifiedAt', $normalized);
        $this->assertArrayHasKey('createdAt', $normalized);
        $this->assertArrayNotHasKey('email_verified_at', $normalized);
        $this->assertArrayNotHasKey('created_at', $normalized);
    }

    public function test_normalize_handles_nested_data_objects(): void
    {
        $productData = new TestProductData(
            id: 1,
            name: 'Laptop',
            price: 999.99,
            isFeatured: true
        );

        $normalized = $this->normalizer->normalize($productData);

        $this->assertIsArray($normalized);
        $this->assertSame(1, $normalized['id']);
        $this->assertSame('Laptop', $normalized['name']);
        $this->assertSame(999.99, $normalized['price']);
        $this->assertTrue($normalized['isFeatured']);
    }

    public function test_normalize_preserves_enum_values_as_strings(): void
    {
        $data = $this->createTestUserData();
        $normalized = $this->normalizer->normalize($data);

        $this->assertSame('active', $normalized['status']);
        $this->assertSame('user', $normalized['role']);
        $this->assertSame(1, $normalized['grade']);
        $this->assertIsString($normalized['status']);
        $this->assertIsString($normalized['role']);
        $this->assertIsInt($normalized['grade']);
    }

    public function test_normalize_handles_value_objects_inside_data(): void
    {
        $data = $this->createTestUserData();
        $normalized = $this->normalizer->normalize($data);

        $this->assertIsString($normalized['email']);
        $this->assertSame('test@example.com', $normalized['email']);
        $this->assertIsString($normalized['createdAt']);
    }

    public function test_normalize_handles_collections_inside_data(): void
    {
        $tags = new StringTypedCollection;
        $tags->add('premium', 'vip', 'new');

        $data = new TestUserData(
            id: 1,
            name: 'John Doe',
            email: $this->testEmail,
            status: TestUserStatus::ACTIVE,
            role: TestUserRole::USER,
            grade: TestUserGrade::BRONZE,
            emailVerifiedAt: null,
            tags: $tags,
            createdAt: $this->now
        );

        $normalized = $this->normalizer->normalize($data);

        $this->assertIsArray($normalized['tags']);
        $this->assertSame(['premium', 'vip', 'new'], $normalized['tags']);
    }

    public function test_normalize_handles_null_values_appropriately(): void
    {
        $data = new TestUserData(
            id: 1,
            name: 'John Doe',
            email: $this->testEmail,
            status: TestUserStatus::ACTIVE,
            role: TestUserRole::USER,
            grade: TestUserGrade::BRONZE,
            emailVerifiedAt: null,
            tags: new StringTypedCollection,
            createdAt: $this->now
        );

        $normalized = $this->normalizer->normalize($data);

        $this->assertArrayHasKey('emailVerifiedAt', $normalized);
        $this->assertNull($normalized['emailVerifiedAt']);
    }

    public function test_normalize_forwards_to_next_normalizer_when_not_supported(): void
    {
        $value = 'not a data object';
        $normalized = $this->normalizer->normalize($value);
        $this->assertSame('not a data object', $normalized);
    }

    public function test_normalize_on_empty_data_dto_works(): void
    {
        $emptyData = new EmptyData;
        $normalized = $this->normalizer->normalize($emptyData);

        $this->assertIsArray($normalized);
        $this->assertEmpty($normalized);
    }

    public function test_normalize_preserves_data_types_appropriately(): void
    {
        $data = new TestProductData(
            id: 123,
            name: 'Test Product',
            price: 99.99,
            isFeatured: true
        );

        $normalized = $this->normalizer->normalize($data);

        $this->assertIsInt($normalized['id']);
        $this->assertIsString($normalized['name']);
        $this->assertIsFloat($normalized['price']);
        $this->assertIsBool($normalized['isFeatured']);
        $this->assertSame(123, $normalized['id']);
        $this->assertSame(99.99, $normalized['price']);
        $this->assertTrue($normalized['isFeatured']);
    }

    public function test_normalize_handles_data_with_nested_data_collections(): void
    {
        $product1 = new TestProductData(id: 1, name: 'Product 1', price: 100);
        $product2 = new TestProductData(id: 2, name: 'Product 2', price: 200);

        $collection = new DataCollection;
        $collection->add($product1, $product2);

        $normalized = $this->normalizer->normalize($collection);

        $this->assertIsArray($normalized);
        $this->assertCount(2, $normalized);
        $this->assertSame('Product 1', $normalized[0]['name']);
        $this->assertSame('Product 2', $normalized[1]['name']);
    }

    public function test_normalize_is_idempotent(): void
    {
        $data = $this->createTestUserData();

        $first = $this->normalizer->normalize($data);
        $second = $this->normalizer->normalize($data);
        $third = $this->normalizer->normalize($data);

        $this->assertSame($first, $second);
        $this->assertSame($second, $third);
    }

    public function test_normalize_handles_data_with_deep_nested_structures(): void
    {
        $innerData = new TestProductData(id: 1, name: 'Inner', price: 50);
        $collection = new DataCollection;
        $collection->add($innerData);

        $outerData = new TestUserData(
            id: 1,
            name: 'Outer',
            email: $this->testEmail,
            status: TestUserStatus::ACTIVE,
            role: TestUserRole::USER,
            grade: TestUserGrade::BRONZE,
            emailVerifiedAt: null,
            tags: new StringTypedCollection,
            createdAt: $this->now
        );

        $normalizedOuter = $this->normalizer->normalize($outerData);
        $normalizedInner = $this->normalizer->normalize($collection);

        $this->assertIsArray($normalizedOuter);
        $this->assertIsArray($normalizedInner);
        $this->assertSame('Outer', $normalizedOuter['name']);
        $this->assertSame('Inner', $normalizedInner[0]['name']);
    }
}
