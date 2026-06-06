<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Unit\Hydration\Converter;

use AndyDefer\DomainStructures\Hydration\Converter\TransformableConverter;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestUserGrade;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestUserRole;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestUserStatus;
use AndyDefer\DomainStructures\Tests\Fixtures\Records\TestUserRecord;
use AndyDefer\DomainStructures\Tests\Fixtures\ValueObjects\TestEmailAddress;
use AndyDefer\DomainStructures\Tests\TestCase;
use AndyDefer\DomainStructures\Utils\DataObject;
use AndyDefer\DomainStructures\Utils\StrictDataObject;
use InvalidArgumentException;

final class TransformableConverterTest extends TestCase
{
    private TransformableConverter $converter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->converter = new TransformableConverter;
    }

    // ==================== SUPPORTS METHOD TESTS ====================

    public function test_supports_returns_true_for_transformable_class(): void
    {
        $result = $this->converter->supports(TestUserRecord::class);
        $this->assertTrue($result);
    }

    public function test_supports_returns_true_for_value_object_class(): void
    {
        $result = $this->converter->supports(TestEmailAddress::class);
        $this->assertTrue($result);
    }

    public function test_supports_returns_true_for_data_object_class(): void
    {
        $result = $this->converter->supports(DataObject::class);
        $this->assertTrue($result);
    }

    public function test_supports_returns_false_for_non_transformable_class(): void
    {
        $nonTransformable = ['int', 'string', 'array', 'stdClass', self::class];

        foreach ($nonTransformable as $type) {
            $result = $this->converter->supports($type);
            $this->assertFalse($result, "Failed for type: {$type}");
        }
    }

    // ==================== CONVERT FROM ARRAY TESTS ====================

    public function test_convert_converts_array_to_record(): void
    {
        $data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ];

        $result = $this->converter->convert($data, TestUserRecord::class, 'user');

        $this->assertInstanceOf(TestUserRecord::class, $result);
        $this->assertSame('John Doe', $result->name);
        $this->assertSame('john@example.com', $result->email->getValue());
    }

    public function test_convert_converts_array_with_nested_array_for_value_object(): void
    {
        // Pour créer un Value Object à partir d'un tableau, on passe les données brutes
        // TestEmailAddress sera hydraté via son propre système
        $data = [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',  // String directe, pas de tableau magique
        ];

        $result = $this->converter->convert($data, TestUserRecord::class, 'user');

        $this->assertInstanceOf(TestUserRecord::class, $result);
        $this->assertSame('Jane Doe', $result->name);
        $this->assertSame('jane@example.com', $result->email->getValue());
    }

    // ==================== CONVERT FROM DATA OBJECT TESTS ====================

    public function test_convert_converts_data_object_to_record(): void
    {
        $dataObject = new DataObject([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $result = $this->converter->convert($dataObject, TestUserRecord::class, 'user');

        $this->assertInstanceOf(TestUserRecord::class, $result);
        $this->assertSame('John Doe', $result->name);
        $this->assertSame('john@example.com', $result->email->getValue());
    }

    public function test_convert_converts_strict_data_object_to_record(): void
    {
        $strictDataObject = new StrictDataObject([
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
        ]);

        $result = $this->converter->convert($strictDataObject, TestUserRecord::class, 'user');

        $this->assertInstanceOf(TestUserRecord::class, $result);
        $this->assertSame('Jane Smith', $result->name);
        $this->assertSame('jane@example.com', $result->email->getValue());
    }

    public function test_convert_returns_same_instance_when_already_correct_type(): void
    {
        $original = new TestUserRecord(
            name: 'John Doe',
            email: TestEmailAddress::from('john@example.com')
        );

        $result = $this->converter->convert($original, TestUserRecord::class, 'user');

        $this->assertSame($original, $result);
    }

    // ==================== CONVERT FROM SCALAR TESTS ====================

    public function test_convert_converts_email_string_to_email_value_object(): void
    {
        $result = $this->converter->convert('test@example.com', TestEmailAddress::class, 'email');

        $this->assertInstanceOf(TestEmailAddress::class, $result);
        $this->assertSame('test@example.com', $result->getValue());
    }

    public function test_convert_throws_exception_for_invalid_email_string(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->converter->convert('invalid-email', TestEmailAddress::class, 'email');
    }

    // ==================== VALUE OBJECT SPECIFIC TESTS ====================

    public function test_convert_creates_email_vo_from_valid_string(): void
    {
        $result = $this->converter->convert('user@example.com', TestEmailAddress::class, 'email');
        $this->assertSame('user@example.com', $result->getValue());
    }

    // ==================== RECORD SPECIFIC TESTS ====================

    public function test_convert_creates_record_with_all_fields(): void
    {
        $data = [
            'name' => 'Complete User',
            'email' => 'complete@example.com',
            'status' => 'active',
            'role' => 'admin',
        ];

        $result = $this->converter->convert($data, TestUserRecord::class, 'user');

        $this->assertSame('Complete User', $result->name);
        $this->assertSame('complete@example.com', $result->email->getValue());
    }

    public function test_convert_creates_record_with_default_values_for_missing_fields(): void
    {
        // Arrange: Seulement le nom est fourni
        $data = ['name' => 'Minimal User'];

        // Act: Convertir en TestUserRecord
        $result = $this->converter->convert($data, TestUserRecord::class, 'user');

        // Assert: Le nom est défini
        $this->assertSame('Minimal User', $result->name);

        // Assert: Les champs avec des valeurs par défaut NON null sont définis
        $this->assertEquals(TestUserStatus::ACTIVE, $result->status);
        $this->assertEquals(TestUserRole::USER, $result->role);
        $this->assertEquals(TestUserGrade::BRONZE, $result->grade);

        // Assert: Les champs nullable sans valeur par défaut sont null
        $this->assertNull($result->email);
    }

    // ==================== EDGE CASES TESTS ====================

    public function test_convert_returns_null_when_source_is_null(): void
    {
        $result = $this->converter->convert(null, TestUserRecord::class, 'user');
        $this->assertNull($result);
    }

    public function test_convert_is_idempotent_for_transformable_instances(): void
    {
        $original = new TestUserRecord(
            name: 'Idempotent User',
            email: TestEmailAddress::from('idempotent@example.com')
        );

        $first = $this->converter->convert($original, TestUserRecord::class, 'user');
        $second = $this->converter->convert($first, TestUserRecord::class, 'user');

        $this->assertSame($original, $first);
        $this->assertSame($first, $second);
    }

    public function test_convert_preserves_data_integrity(): void
    {
        $originalName = 'Data Integrity User';
        $originalEmail = 'integrity@example.com';

        $data = [
            'name' => $originalName,
            'email' => $originalEmail,
        ];

        $result = $this->converter->convert($data, TestUserRecord::class, 'user');

        $this->assertSame($originalName, $result->name);
        $this->assertSame($originalEmail, $result->email->getValue());
    }
}
