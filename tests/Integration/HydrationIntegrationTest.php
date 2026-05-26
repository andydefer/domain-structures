<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Integration;

use AndyDefer\DomainStructures\Collections\Core\DataCollection;
use AndyDefer\DomainStructures\Collections\Core\RecordCollection;
use AndyDefer\DomainStructures\Collections\Core\TypedCollection;
use AndyDefer\DomainStructures\Collections\Utility\StringTypedCollection;
use AndyDefer\DomainStructures\Tests\Fixtures\Collections\ProductRecordCollection;
use AndyDefer\DomainStructures\Tests\Fixtures\Collections\TestUserRoleCollection;
use AndyDefer\DomainStructures\Tests\Fixtures\Data\TestUserData;
use AndyDefer\DomainStructures\Tests\Fixtures\Data\TestUserWithRolesData;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestBackedIntEnum;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestBackedStringEnum;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestCurrency;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestPureEnum;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestUserGrade;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestUserRole;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestUserStatus;
use AndyDefer\DomainStructures\Tests\Fixtures\Mixed\ObjectWithNullableProperties;
use AndyDefer\DomainStructures\Tests\Fixtures\Records\TestProductRecord;
use AndyDefer\DomainStructures\Tests\Fixtures\Records\TestUserRecord;
use AndyDefer\DomainStructures\Tests\Fixtures\Records\TestUserUpdateRecord;
use AndyDefer\DomainStructures\Tests\Fixtures\ValueObjects\TestEmailAddress;
use AndyDefer\DomainStructures\Tests\Fixtures\ValueObjects\TestIso8601DateTime;
use AndyDefer\DomainStructures\Tests\Fixtures\ValueObjects\TestMoney;
use AndyDefer\DomainStructures\Tests\Fixtures\ValueObjects\TestPostalCode;
use AndyDefer\DomainStructures\Tests\TestCase;
use RuntimeException;
use stdClass;

/**
 * Integration test for the Hydratable trait.
 *
 * This test suite validates the hydration system that allows creating
 * Record/Data instances from various source objects:
 * - From stdClass objects
 * - From other Records
 * - From Value Objects
 * - From Data DTOs
 * - From objects with getters
 * - From arrays (via toArray method)
 * - From objects with nested collections
 * - With type mismatches and error handling
 *
 * The tests follow the AAA pattern (Arrange, Act, Assert).
 */
final class HydrationIntegrationTest extends TestCase
{
    private TestIso8601DateTime $now;
    private TestEmailAddress $testEmail;
    private StringTypedCollection $tags;

    protected function setUp(): void
    {
        parent::setUp();

        $this->now = TestIso8601DateTime::now();
        $this->testEmail = TestEmailAddress::fromString('john@example.com');
        $this->tags = new StringTypedCollection;
        $this->tags->add('premium', 'vip');
    }

    // ==================== HYDRATION FROM STDCLASS TESTS ====================

    /**
     * Test that a Record can be hydrated from a stdClass object.
     */
    public function test_record_hydrates_from_stdclass_with_matching_properties(): void
    {
        // Arrange
        $source = new stdClass;
        $source->id = 1;
        $source->name = 'John Doe';
        $source->email = 'john@example.com';
        $source->status = 'active';
        $source->role = 'user';
        $source->grade = 1;
        $source->tags = ['premium', 'vip'];

        // Act
        $userRecord = TestUserRecord::from($source);

        // Assert
        $this->assertInstanceOf(TestUserRecord::class, $userRecord);
        $this->assertSame(1, $userRecord->id);
        $this->assertSame('John Doe', $userRecord->name);
        $this->assertInstanceOf(TestEmailAddress::class, $userRecord->email);
        $this->assertSame('john@example.com', $userRecord->email->getValue());
        $this->assertSame(TestUserStatus::ACTIVE, $userRecord->status);
        $this->assertSame(TestUserRole::USER, $userRecord->role);
        $this->assertSame(TestUserGrade::BRONZE, $userRecord->grade);
    }

    /**
     * Test that a Record can be hydrated from a stdClass with nested objects.
     */
    public function test_record_hydrates_from_stdclass_with_nested_email_object(): void
    {
        // Arrange
        $source = new stdClass;
        $source->id = 1;
        $source->name = 'John Doe';
        $source->email = new stdClass;
        $source->email->value = 'john@example.com';
        $source->status = 'active';

        // Act
        $userRecord = TestUserRecord::from($source);

        // Assert
        $this->assertInstanceOf(TestUserRecord::class, $userRecord);
        $this->assertInstanceOf(TestEmailAddress::class, $userRecord->email);
        $this->assertSame('john@example.com', $userRecord->email->getValue());
    }

    /**
     * Test that a Record can be hydrated from a stdClass with enum string values.
     */
    public function test_record_hydrates_from_stdclass_with_enum_string_values(): void
    {
        // Arrange
        $source = new stdClass;
        $source->name = 'John Doe';
        $source->email = 'john@example.com';
        $source->status = 'suspended';
        $source->role = 'admin';
        $source->grade = 4;

        // Act
        $userRecord = TestUserRecord::from($source);

        // Assert
        $this->assertSame(TestUserStatus::SUSPENDED, $userRecord->status);
        $this->assertSame(TestUserRole::ADMIN, $userRecord->role);
        $this->assertSame(TestUserGrade::PLATINUM, $userRecord->grade);
    }

    /**
     * Test that a Record can be hydrated from a stdClass with collection.
     */
    public function test_record_hydrates_from_stdclass_with_collection(): void
    {
        // Arrange
        $source = new stdClass;
        $source->name = 'John Doe';
        $source->email = 'john@example.com';
        $source->tags = ['tag1', 'tag2', 'tag3'];

        // Act
        $userRecord = TestUserRecord::from($source);

        // Assert
        $this->assertInstanceOf(StringTypedCollection::class, $userRecord->tags);
        $this->assertCount(3, $userRecord->tags);
        $this->assertSame(['tag1', 'tag2', 'tag3'], $userRecord->tags->toArray());
    }

    // ==================== HYDRATION FROM OTHER RECORDS TESTS ====================

    /**
     * Test that a Record can be created from another Record.
     */
    public function test_record_hydrates_from_another_record(): void
    {
        // Arrange
        $sourceRecord = new TestUserRecord(
            id: 1,
            name: 'Jane Doe',
            email: TestEmailAddress::fromString('jane@example.com'),
            status: TestUserStatus::ACTIVE,
            role: TestUserRole::ADMIN,
            grade: TestUserGrade::GOLD,
            tags: $this->tags
        );

        // Act
        $newRecord = TestUserRecord::from($sourceRecord);

        // Assert
        $this->assertNotSame($sourceRecord, $newRecord);
        $this->assertSame(1, $newRecord->id);
        $this->assertSame('Jane Doe', $newRecord->name);
        $this->assertSame('jane@example.com', $newRecord->email->getValue());
        $this->assertSame(TestUserStatus::ACTIVE, $newRecord->status);
        $this->assertSame(TestUserRole::ADMIN, $newRecord->role);
        $this->assertSame(TestUserGrade::GOLD, $newRecord->grade);
    }

    /**
     * Test that a Record can be hydrated from a partial Record (only some properties).
     */
    public function test_record_hydrates_from_partial_record(): void
    {
        // Arrange
        $sourceRecord = new TestUserUpdateRecord(
            name: 'Updated Name',
            lifeStage: null
        );

        // Act
        $targetRecord = TestUserRecord::from($sourceRecord);

        // Assert
        $this->assertSame('Updated Name', $targetRecord->name);
        $this->assertNull($targetRecord->id);
        $this->assertNull($targetRecord->email);
        $this->assertSame(TestUserStatus::ACTIVE, $targetRecord->status); // Default value
    }

    // ==================== HYDRATION FROM VALUE OBJECTS TESTS ====================

    /**
     * Test that a Record can be hydrated from a Value Object.
     */
    public function test_record_hydrates_from_value_object(): void
    {
        // Arrange
        $emailVO = TestEmailAddress::fromString('vo@example.com');
        $postalCodeVO = TestPostalCode::fromString('75001');

        // Act
        $record = TestUserRecord::from($emailVO);

        // Assert - Value Object hydrates to Record
        $this->assertInstanceOf(TestUserRecord::class, $record);
    }

    /**
     * Test that a Record can be hydrated from a complex Value Object.
     */
    public function test_record_hydrates_from_complex_value_object(): void
    {
        // Arrange
        $moneyVO = TestMoney::fromFloat(99.99, TestCurrency::EUR);

        // Act
        $record = TestUserRecord::from($moneyVO);

        // Assert
        $this->assertInstanceOf(TestUserRecord::class, $record);
    }

    // ==================== HYDRATION FROM DATA DTOS TESTS ====================

    /**
     * Test that a Record can be hydrated from a Data DTO.
     */
    public function test_record_hydrates_from_data_dto(): void
    {
        // Arrange
        $sourceData = new TestUserData(
            id: '1',
            name: 'John Doe',
            email: $this->testEmail,
            status: TestUserStatus::ACTIVE,
            role: TestUserRole::USER,
            grade: TestUserGrade::BRONZE,
            emailVerifiedAt: null,
            tags: $this->tags,
            createdAt: $this->now
        );

        // Act
        $record = TestUserRecord::from($sourceData);

        // Assert
        $this->assertInstanceOf(TestUserRecord::class, $record);
        $this->assertSame(1, $record->id); // String '1' converts to int 1
        $this->assertSame('John Doe', $record->name);
        $this->assertSame('john@example.com', $record->email->getValue());
        $this->assertSame(TestUserStatus::ACTIVE, $record->status);
    }

    /**
     * Test that a Data DTO can be hydrated from a Record.
     */
    public function test_data_dto_hydrates_from_record(): void
    {
        // Arrange
        $sourceRecord = new TestUserRecord(
            id: 1,
            name: 'Jane Doe',
            email: TestEmailAddress::fromString('jane@example.com'),
            status: TestUserStatus::ACTIVE,
            role: TestUserRole::ADMIN,
            grade: TestUserGrade::GOLD,
            tags: $this->tags,
            createdAt: $this->now
        );

        // Act
        $data = TestUserData::from($sourceRecord);

        // Assert
        $this->assertInstanceOf(TestUserData::class, $data);
        $this->assertSame('1', $data->id); // Int converts to string
        $this->assertSame('Jane Doe', $data->name);
        $this->assertSame('jane@example.com', $data->email->getValue());
        $this->assertSame(TestUserStatus::ACTIVE, $data->status);
        $this->assertSame(TestUserRole::ADMIN, $data->role);
        $this->assertSame(TestUserGrade::GOLD, $data->grade);
    }

    // ==================== HYDRATION USING GETTERS TESTS ====================

    /**
     * Test that Record hydrates from object with getters instead of public properties.
     */
    public function test_record_hydrates_from_object_with_getters(): void
    {
        // Arrange
        $objectWithGetters = new class {
            private string $name = 'Getter Name';
            private string $email = 'getter@example.com';

            public function getName(): string
            {
                return $this->name;
            }

            public function getEmail(): string
            {
                return $this->email;
            }
        };

        // Act
        $record = TestUserRecord::from($objectWithGetters);

        // Assert
        $this->assertSame('Getter Name', $record->name);
        $this->assertSame('getter@example.com', $record->email->getValue());
    }

    /**
     * Test that Record hydrates from object with is/has getters.
     */
    public function test_record_hydrates_from_object_with_is_has_getters(): void
    {
        // Arrange
        $objectWithBooleanGetters = new class {
            private bool $active = true;
            private bool $verified = false;

            public function isActive(): bool
            {
                return $this->active;
            }

            public function hasVerified(): bool
            {
                return $this->verified;
            }
        };

        // Act
        $record = TestUserRecord::from($objectWithBooleanGetters);

        // Assert
        // Note: TestUserRecord doesn't have boolean fields, this tests the getter resolution
        $this->assertInstanceOf(TestUserRecord::class, $record);
    }

    // ==================== HYDRATION WITH DEFAULT VALUES TESTS ====================

    /**
     * Test that Record uses default values when source lacks properties.
     */
    public function test_record_uses_default_values_when_source_lacks_properties(): void
    {
        // Arrange
        $source = new stdClass;
        $source->name = 'John Doe';
        $source->email = 'john@example.com';

        // Act
        $record = TestUserRecord::from($source);

        // Assert
        $this->assertSame('John Doe', $record->name);
        $this->assertNull($record->id); // No default, explicit null
        $this->assertSame(TestUserStatus::ACTIVE, $record->status); // Has default
        $this->assertSame(TestUserRole::USER, $record->role); // Has default
        $this->assertSame(TestUserGrade::BRONZE, $record->grade); // Has default
    }

    /**
     * Test that Record respects provided values over defaults.
     */
    public function test_record_respects_provided_values_over_defaults(): void
    {
        // Arrange
        $source = new stdClass;
        $source->status = 'suspended';
        $source->role = 'admin';
        $source->grade = 4;

        // Act
        $record = TestUserRecord::from($source);

        // Assert
        $this->assertSame(TestUserStatus::SUSPENDED, $record->status);
        $this->assertSame(TestUserRole::ADMIN, $record->role);
        $this->assertSame(TestUserGrade::PLATINUM, $record->grade);
    }

    // ==================== HYDRATION FROM ARRAY (VIA TOARRAY) TESTS ====================

    /**
     * Test that Record hydrates from object with toArray method.
     */
    public function test_record_hydrates_from_object_with_to_array_method(): void
    {
        // Arrange
        $objectWithToArray = new class {
            public function toArray(): array
            {
                return [
                    'name' => 'ToArray Name',
                    'email' => 'toarray@example.com',
                    'status' => 'active',
                ];
            }
        };

        // Act
        $record = TestUserRecord::from($objectWithToArray);

        // Assert
        $this->assertSame('ToArray Name', $record->name);
        $this->assertSame('toarray@example.com', $record->email->getValue());
        $this->assertSame(TestUserStatus::ACTIVE, $record->status);
    }

    // ==================== HYDRATION WITH COLLECTION PROPERTIES TESTS ====================

    /**
     * Test that Record hydrates with nested product collection.
     */
    public function test_record_hydrates_with_nested_product_collection(): void
    {
        // Arrange
        $source = new stdClass;
        $source->name = 'John Doe';
        $source->email = 'john@example.com';
        $source->products = [
            ['id' => 1, 'name' => 'Laptop', 'price' => 999],
            ['id' => 2, 'name' => 'Mouse', 'price' => 29],
        ];

        // Act
        $record = TestUserRecord::from($source);

        // Assert
        $this->assertInstanceOf(ProductRecordCollection::class, $record->products);
        $this->assertCount(2, $record->products);

        $products = $record->products->toArray();
        $this->assertSame('Laptop', $products[0]->name);
        $this->assertSame(999, $products[0]->price);
        $this->assertSame('Mouse', $products[1]->name);
        $this->assertSame(29, $products[1]->price);
    }

    /**
     * Test that Record hydrates with roles collection from array.
     */
    public function test_record_hydrates_with_roles_collection_from_array(): void
    {
        // Arrange
        $source = new stdClass;
        $source->roles = ['admin', 'user'];

        // Act
        $data = TestUserWithRolesData::from($source);

        // Assert
        $this->assertInstanceOf(TestUserRoleCollection::class, $data->roles);
        $this->assertCount(2, $data->roles);
        $this->assertTrue($data->roles->contains(TestUserRole::ADMIN));
        $this->assertTrue($data->roles->contains(TestUserRole::USER));
    }

    // ==================== TYPE CONVERSION AND CASTING TESTS ====================

    /**
     * Test that string to int conversion works during hydration.
     */
    public function test_string_to_int_conversion_during_hydration(): void
    {
        // Arrange
        $source = new stdClass;
        $source->id = '123';

        // Act
        $record = TestUserRecord::from($source);

        // Assert
        $this->assertSame(123, $record->id);
        $this->assertIsInt($record->id);
    }

    /**
     * Test that int to string conversion works during hydration.
     */
    public function test_int_to_string_conversion_during_hydration(): void
    {
        // Arrange
        $source = new stdClass;
        $source->id = 456;

        // Act
        $data = TestUserData::from($source);

        // Assert
        $this->assertSame('456', $data->id);
        $this->assertIsString($data->id);
    }

    /**
     * Test that string to enum conversion works during hydration.
     */
    public function test_string_to_enum_conversion_during_hydration(): void
    {
        // Arrange
        $source = new stdClass;
        $source->status = 'inactive';
        $source->role = 'admin';

        // Act
        $record = TestUserRecord::from($source);

        // Assert
        $this->assertSame(TestUserStatus::INACTIVE, $record->status);
        $this->assertSame(TestUserRole::ADMIN, $record->role);
    }

    /**
     * Test that int to backed enum conversion works during hydration.
     */
    public function test_int_to_backed_enum_conversion_during_hydration(): void
    {
        // Arrange
        $source = new stdClass;
        $source->grade = 3;

        // Act
        $record = TestUserRecord::from($source);

        // Assert
        $this->assertSame(TestUserGrade::GOLD, $record->grade);
    }

    // ==================== COLLECTION HYDRATION TESTS ====================

    /**
     * Test that collect method creates array of instances from RecordCollection.
     */
    public function test_collect_creates_array_of_instances_from_record_collection(): void
    {
        // Arrange
        $recordCollection = new RecordCollection;
        $recordCollection->add(
            new TestUserRecord(name: 'User 1', email: TestEmailAddress::fromString('user1@example.com')),
            new TestUserRecord(name: 'User 2', email: TestEmailAddress::fromString('user2@example.com')),
            new TestUserRecord(name: 'User 3', email: TestEmailAddress::fromString('user3@example.com'))
        );

        // Act
        $dataArray = TestUserData::collect($recordCollection);

        // Assert
        $this->assertIsArray($dataArray);
        $this->assertCount(3, $dataArray);
        $this->assertInstanceOf(TestUserData::class, $dataArray[0]);
        $this->assertInstanceOf(TestUserData::class, $dataArray[1]);
        $this->assertInstanceOf(TestUserData::class, $dataArray[2]);
        $this->assertSame('User 1', $dataArray[0]->name);
        $this->assertSame('User 2', $dataArray[1]->name);
        $this->assertSame('User 3', $dataArray[2]->name);
    }

    /**
     * Test that collect method works with DataCollection.
     */
    public function test_collect_creates_array_of_instances_from_data_collection(): void
    {
        // Arrange
        $dataCollection = new DataCollection;
        $dataCollection->add(
            new TestUserData(id: '1', name: 'User 1', email: $this->testEmail, status: TestUserStatus::ACTIVE, role: TestUserRole::USER, grade: TestUserGrade::BRONZE, emailVerifiedAt: null, tags: $this->tags, createdAt: $this->now),
            new TestUserData(id: '2', name: 'User 2', email: $this->testEmail, status: TestUserStatus::ACTIVE, role: TestUserRole::USER, grade: TestUserGrade::BRONZE, emailVerifiedAt: null, tags: $this->tags, createdAt: $this->now)
        );

        // Act
        $recordArray = TestUserRecord::collect($dataCollection);

        // Assert
        $this->assertIsArray($recordArray);
        $this->assertCount(2, $recordArray);
        $this->assertInstanceOf(TestUserRecord::class, $recordArray[0]);
        $this->assertInstanceOf(TestUserRecord::class, $recordArray[1]);
    }

    /**
     * Test that collect on empty collection returns empty array.
     */
    public function test_collect_on_empty_collection_returns_empty_array(): void
    {
        // Arrange
        $emptyCollection = new RecordCollection;

        // Act
        $result = TestUserRecord::collect($emptyCollection);

        // Assert
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    // ==================== ERROR HANDLING TESTS ====================

    /**
     * Test that hydration throws exception when required property is missing.
     */
    public function test_hydration_throws_exception_when_required_property_missing(): void
    {
        // Arrange
        $source = new stdClass;
        // Missing 'name' which is required (no default value in constructor)

        // Act & Assert
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Missing required properties: name');

        TestUserRecord::from($source);
    }

    /**
     * Test that hydration throws exception with clear error message for type mismatch.
     */
    public function test_hydration_throws_exception_for_type_mismatch(): void
    {
        // Arrange
        $source = new stdClass;
        $source->name = 'John Doe';
        $source->email = 12345; // Should be string or Email VO

        // Act & Assert
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Type mismatches');

        TestUserRecord::from($source);
    }

    /**
     * Test that hydration throws exception for invalid enum value.
     */
    public function test_hydration_throws_exception_for_invalid_enum_value(): void
    {
        // Arrange
        $source = new stdClass;
        $source->name = 'John Doe';
        $source->email = 'john@example.com';
        $source->status = 'invalid_status_value';

        // Act & Assert
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Type mismatches');

        TestUserRecord::from($source);
    }

    /**
     * Test that hydration throws exception for invalid integer value.
     */
    public function test_hydration_throws_exception_for_invalid_integer_value(): void
    {
        // Arrange
        $source = new stdClass;
        $source->id = 'not an integer';
        $source->name = 'John Doe';
        $source->email = 'john@example.com';

        // Act & Assert
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Type mismatches');

        TestUserRecord::from($source);
    }

    // ==================== COMPLEX NESTED HYDRATION TESTS ====================

    /**
     * Test complete hydration workflow with deeply nested structures.
     */
    public function test_complete_hydration_workflow_with_deeply_nested_structures(): void
    {
        // Arrange
        $source = new stdClass;
        $source->name = 'John Doe';
        $source->email = 'john@example.com';
        $source->status = 'active';
        $source->products = [
            [
                'id' => 1,
                'name' => 'Laptop',
                'price' => 999,
                'metadata' => ['brand' => 'Apple', 'color' => 'Silver']
            ],
            [
                'id' => 2,
                'name' => 'Mouse',
                'price' => 29,
                'metadata' => ['brand' => 'Logitech', 'color' => 'Black']
            ]
        ];
        $source->tags = ['premium', 'vip', 'early_bird'];

        // Act
        $record = TestUserRecord::from($source);

        // Assert
        $this->assertInstanceOf(TestUserRecord::class, $record);
        $this->assertCount(2, $record->products);
        $this->assertCount(3, $record->tags);

        $products = $record->products->toArray();
        $this->assertSame('Laptop', $products[0]->name);
        $this->assertSame(999, $products[0]->price);
        $this->assertSame('Mouse', $products[1]->name);
        $this->assertSame(29, $products[1]->price);
    }

    /**
     * Test hydration from source that contains extra properties not in target.
     */
    public function test_hydration_ignores_extra_properties_in_source(): void
    {
        // Arrange
        $source = new stdClass;
        $source->name = 'John Doe';
        $source->email = 'john@example.com';
        $source->extraField = 'this should be ignored';
        $source->anotherExtra = 'also ignored';

        // Act
        $record = TestUserRecord::from($source);

        // Assert - No exception, extra fields ignored
        $this->assertInstanceOf(TestUserRecord::class, $record);
        $this->assertSame('John Doe', $record->name);
    }

    /**
     * Test hydration from Value Object that returns Record via getValue().
     */
    public function test_hydration_from_value_object_that_returns_record(): void
    {
        // Arrange
        $userProfileVO = new \AndyDefer\DomainStructures\Tests\Fixtures\ValueObjects\TestUserProfile(
            id: 1,
            name: 'John Doe',
            email: $this->testEmail,
            status: TestUserStatus::ACTIVE,
            roles: new TestUserRoleCollection,
            grade: TestUserGrade::BRONZE,
            emailVerifiedAt: null,
            tags: $this->tags,
            createdAt: $this->now
        );

        // Act
        $record = TestUserRecord::from($userProfileVO);

        // Assert
        $this->assertInstanceOf(TestUserRecord::class, $record);
        $this->assertSame('John Doe', $record->name);
    }

    /**
     * Test hydration preserves immutability (readonly properties).
     */
    public function test_hydration_preserves_immutability(): void
    {
        // Arrange
        $source = new stdClass;
        $source->name = 'John Doe';
        $source->email = 'john@example.com';

        // Act
        $record = TestUserRecord::from($source);

        // Assert
        $this->assertTrue(property_exists($record, 'name'));

        // Verify readonly property cannot be modified (PHP 8.1+)
        $reflection = new \ReflectionProperty($record, 'name');
        $this->assertTrue($reflection->isReadOnly());
    }

    // ==================== HYDRATION WITH NULLABLE PROPERTIES TESTS ====================

    /**
     * Test that hydration with explicit null overrides default values.
     */
    public function test_hydration_with_explicit_null_overrides_defaults(): void
    {
        // Arrange
        $source = new stdClass;
        $source->name = 'John Doe';
        $source->email = 'john@example.com';
        $source->status = null; // Explicit null should override default ACTIVE

        // Act
        $record = TestUserRecord::from($source);

        // Assert
        $this->assertNull($record->status);
        $this->assertNotSame(TestUserStatus::ACTIVE, $record->status);
    }

    // ==================== HYDRATION WITH TYPED COLLECTIONS TESTS ====================

    /**
     * Test that hydration creates TypedCollection from array.
     */
    public function test_hydration_creates_typed_collection_from_array(): void
    {
        // Arrange
        $source = new stdClass;
        $source->name = 'John Doe';
        $source->email = 'john@example.com';
        $source->tags = ['php', 'laravel', 'testing'];

        // Act
        $record = TestUserRecord::from($source);

        // Assert
        $this->assertInstanceOf(StringTypedCollection::class, $record->tags);
        $this->assertCount(3, $record->tags);
        $this->assertContains('php', $record->tags->toArray());
        $this->assertContains('laravel', $record->tags->toArray());
        $this->assertContains('testing', $record->tags->toArray());
    }

    /**
     * Test that hydration with empty array creates empty TypedCollection.
     */
    public function test_hydration_with_empty_array_creates_empty_typed_collection(): void
    {
        // Arrange
        $source = new stdClass;
        $source->name = 'John Doe';
        $source->email = 'john@example.com';
        $source->tags = [];

        // Act
        $record = TestUserRecord::from($source);

        // Assert
        $this->assertInstanceOf(StringTypedCollection::class, $record->tags);
        $this->assertCount(0, $record->tags);
        $this->assertTrue($record->tags->isEmpty());
    }
}
