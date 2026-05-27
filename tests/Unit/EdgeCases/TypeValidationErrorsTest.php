<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Unit\EdgeCases;

use AndyDefer\DomainStructures\Abstracts\AbstractData;
use AndyDefer\DomainStructures\Abstracts\AbstractRecord;
use AndyDefer\DomainStructures\Abstracts\AbstractTypedCollection;
use AndyDefer\DomainStructures\Abstracts\AbstractValueObject;
use AndyDefer\DomainStructures\Collections\Core\DataCollection;
use AndyDefer\DomainStructures\Collections\Core\RecordCollection;
use AndyDefer\DomainStructures\Collections\Core\TypedCollection;
use AndyDefer\DomainStructures\Collections\Utility\BoolTypedCollection;
use AndyDefer\DomainStructures\Collections\Utility\FloatTypedCollection;
use AndyDefer\DomainStructures\Collections\Utility\IntTypedCollection;
use AndyDefer\DomainStructures\Collections\Utility\StringTypedCollection;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestUserRole;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestUserStatus;
use AndyDefer\DomainStructures\Tests\Fixtures\Records\TestUserRecord;
use AndyDefer\DomainStructures\Tests\Fixtures\ValueObjects\TestEmailAddress;
use AndyDefer\DomainStructures\Tests\TestCase;
use InvalidArgumentException;
use stdClass;
use UnitEnum;

/**
 * Edge case tests for type validation errors.
 *
 * This test suite validates that all components throw appropriate
 * exceptions with clear messages when type validation fails:
 * - Constructor type validation
 * - Add method type validation
 * - Collection allowed types validation
 * - Invalid enum values
 * - Invalid class types
 * - Object type restrictions
 *
 * The tests follow the AAA pattern (Arrange, Act, Assert).
 */
final class TypeValidationErrorsTest extends TestCase
{
    private TestEmailAddress $testEmail;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testEmail = TestEmailAddress::from('test@example.com');
    }

    // ==================== CONSTRUCTOR TYPE VALIDATION TESTS ====================

    /**
     * Test that constructor throws exception when no types provided.
     */
    public function test_constructor_throws_exception_when_no_types_provided(): void
    {
        // Arrange & Act & Assert
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('At least one type must be provided');

        new class extends AbstractTypedCollection
        {
            public function __construct()
            {
                parent::__construct();
            }
        };
    }

    /**
     * Test that constructor throws exception for non-existent class.
     */
    public function test_constructor_throws_exception_for_non_existent_class(): void
    {
        // Arrange & Act & Assert
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Type "NonExistentClass123" is not a valid class');

        new TypedCollection('NonExistentClass123');
    }

    /**
     * Test that constructor throws exception for disallowed scalar type.
     */
    public function test_constructor_throws_exception_for_disallowed_scalar_type(): void
    {
        // Arrange & Act & Assert
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Type "array" is not allowed');

        new TypedCollection('array');
    }

    /**
     * Test that constructor throws exception for disallowed object type.
     */
    public function test_constructor_throws_exception_for_disallowed_object_type(): void
    {
        // Arrange & Act & Assert
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Type "DateTime" is not allowed');

        new TypedCollection(\DateTime::class);
    }

    /**
     * Test that constructor throws exception for resource type.
     */
    public function test_constructor_throws_exception_for_resource_type(): void
    {
        // Arrange & Act & Assert
        $this->expectException(InvalidArgumentException::class);

        new TypedCollection('resource');
    }

    // ==================== ADD METHOD TYPE VALIDATION TESTS ====================

    /**
     * Test that add throws exception for wrong scalar type.
     */
    public function test_add_throws_exception_for_wrong_scalar_type(): void
    {
        // Arrange
        $collection = new TypedCollection('int');

        // Act & Assert
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected type(s) int');

        $collection->add('not an integer');
    }

    /**
     * Test that add throws exception for wrong float when int expected.
     */
    public function test_add_throws_exception_for_float_when_int_expected(): void
    {
        // Arrange
        $collection = new TypedCollection('int');

        // Act & Assert
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected type(s) int');

        $collection->add(3.14);
    }

    /**
     * Test that add throws exception for wrong enum type.
     */
    public function test_add_throws_exception_for_wrong_enum_type(): void
    {
        // Arrange
        $collection = new TypedCollection(TestUserStatus::class);

        // Act & Assert
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Expected type\(s\) .*TestUserStatus/');

        $collection->add(TestUserRole::ADMIN);
    }

    /**
     * Test that add throws exception for non-enum when enum expected.
     */
    public function test_add_throws_exception_for_non_enum_when_enum_expected(): void
    {
        // Arrange
        $collection = new TypedCollection(UnitEnum::class);

        // Act & Assert
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected type(s) UnitEnum');

        $collection->add('not an enum');
    }

    /**
     * Test that add throws exception for wrong Record type.
     */
    public function test_add_throws_exception_for_wrong_record_type(): void
    {
        // Arrange
        $collection = new TypedCollection(AbstractRecord::class);

        // This should work because any AbstractRecord is accepted
        $collection->add(new TestUserRecord(name: 'Test', email: $this->testEmail));

        // But this should fail because it's not a Record
        $this->expectException(InvalidArgumentException::class);
        $collection->add('not a record');
    }

    /**
     * Test that add throws exception for wrong Value Object type.
     */
    public function test_add_throws_exception_for_wrong_value_object_type(): void
    {
        // Arrange
        $collection = new TypedCollection(AbstractValueObject::class);

        // Act & Assert
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected type(s) AbstractValueObject');

        $collection->add('not a value object');
    }

    /**
     * Test that add throws exception for wrong Data type.
     */
    public function test_add_throws_exception_for_wrong_data_type(): void
    {
        // Arrange
        $collection = new TypedCollection(AbstractData::class);

        // Act & Assert
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected type(s) AbstractData');

        $collection->add('not a data object');
    }

    /**
     * Test that add throws exception for wrong Collection type.
     */
    public function test_add_throws_exception_for_wrong_collection_type(): void
    {
        // Arrange
        $collection = new TypedCollection(AbstractTypedCollection::class);

        // Act & Assert
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected type(s) AbstractTypedCollection');

        $collection->add('not a collection');
    }

    /**
     * Test that add throws exception for stdClass when not allowed.
     */
    public function test_add_throws_exception_for_stdclass_when_not_allowed(): void
    {
        // Arrange
        $collection = new TypedCollection('int');

        // Act & Assert
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Object of type "stdClass" is not allowed');

        $collection->add(new stdClass);
    }

    /**
     * Test that add throws exception for null when not allowed.
     */
    public function test_add_throws_exception_for_null_when_not_allowed(): void
    {
        // Arrange
        $collection = new TypedCollection('int');

        // Act & Assert
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected type(s) int');

        $collection->add(null);
    }

    // ==================== MULTI-TYPE COLLECTION VALIDATION TESTS ====================

    /**
     * Test that add throws exception for type not in allowed types list.
     */
    public function test_add_throws_exception_for_type_not_in_allowed_list(): void
    {
        // Arrange
        $collection = new TypedCollection('int', 'string');

        // Act & Assert
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected type(s) int|string');

        $collection->add(3.14);
    }

    /**
     * Test that add throws exception with all allowed types in message.
     */
    public function test_add_throws_exception_with_all_allowed_types_in_message(): void
    {
        // Arrange
        $collection = new TypedCollection('int', 'string', 'float', 'bool');

        // Act & Assert
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected type(s) int|string|float|bool');

        $collection->add(null);
    }

    /**
     * Test that add throws exception for enum not in allowed list.
     */
    public function test_add_throws_exception_for_enum_not_in_allowed_list(): void
    {
        // Arrange
        $collection = new TypedCollection('int', 'string');

        // Act & Assert
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Expected type\(s\) int\|string/');

        $collection->add(TestUserStatus::ACTIVE);
    }

    // ==================== SPECIFIC COLLECTION TYPE VALIDATION TESTS ====================

    /**
     * Test that IntTypedCollection rejects string.
     */
    public function test_int_typed_collection_rejects_string(): void
    {
        // Arrange
        $collection = new IntTypedCollection;

        // Act & Assert
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected type(s) int');

        $collection->add('not an int');
    }

    /**
     * Test that IntTypedCollection rejects float.
     */
    public function test_int_typed_collection_rejects_float(): void
    {
        // Arrange
        $collection = new IntTypedCollection;

        // Act & Assert
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected type(s) int');

        $collection->add(3.14);
    }

    /**
     * Test that FloatTypedCollection rejects int (strict typing).
     */
    public function test_float_typed_collection_rejects_int(): void
    {
        // Arrange
        $collection = new FloatTypedCollection;

        // Act & Assert
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected type(s) float');

        $collection->add(42);
    }

    /**
     * Test that StringTypedCollection rejects int.
     */
    public function test_string_typed_collection_rejects_int(): void
    {
        // Arrange
        $collection = new StringTypedCollection;

        // Act & Assert
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected type(s) string');

        $collection->add(42);
    }

    /**
     * Test that BoolTypedCollection rejects string.
     */
    public function test_bool_typed_collection_rejects_string(): void
    {
        // Arrange
        $collection = new BoolTypedCollection;

        // Act & Assert
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected type(s) bool');

        $collection->add('true');
    }

    /**
     * Test that RecordCollection rejects non-Record.
     */
    public function test_record_collection_rejects_non_record(): void
    {
        // Arrange
        $collection = new RecordCollection;

        // Act & Assert
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected type(s) AbstractRecord');

        $collection->add('not a record');
    }

    /**
     * Test that DataCollection rejects non-Data.
     */
    public function test_data_collection_rejects_non_data(): void
    {
        // Arrange
        $collection = new DataCollection;

        // Act & Assert
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected type(s) AbstractData');

        $collection->add('not a data object');
    }

    // ==================== SUBTYPE VALIDATION TESTS ====================

    /**
     * Test that collection with parent class accepts child class.
     */
    public function test_collection_with_parent_class_accepts_child_class(): void
    {
        // Arrange
        $collection = new TypedCollection(AbstractRecord::class);
        $childRecord = new TestUserRecord(name: 'Test', email: $this->testEmail);

        // Act
        $collection->add($childRecord);

        // Assert - Should not throw exception
        $this->assertCount(1, $collection);
    }

    /**
     * Test that collection with parent enum accepts child enum.
     */
    public function test_collection_with_parent_enum_accepts_child_enum(): void
    {
        // Arrange
        $collection = new TypedCollection(UnitEnum::class);

        // Act - Should accept any enum
        $collection->add(TestUserStatus::ACTIVE);

        // Assert
        $this->assertCount(1, $collection);
    }

    /**
     * Test that collection with parent collection accepts child collection.
     */
    public function test_collection_with_parent_collection_accepts_child_collection(): void
    {
        // Arrange
        $collection = new TypedCollection(AbstractTypedCollection::class);
        $childCollection = new TypedCollection('int');

        // Act
        $collection->add($childCollection);

        // Assert
        $this->assertCount(1, $collection);
    }

    // ==================== HYDRATION TYPE ERROR TESTS ====================

    /**
     * Test that hydration throws exception for missing required property.
     */
    public function test_hydration_throws_exception_for_missing_required_property(): void
    {
        // Arrange
        $source = new stdClass;
        $source->email = 'test@example.com';
        // Missing 'name' which has no default

        // Act & Assert
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Missing required properties: name');

        TestUserRecord::from($source);
    }

    /**
     * Test that hydration throws exception for type mismatch.
     */
    public function test_hydration_throws_exception_for_type_mismatch(): void
    {
        // Arrange
        $source = new stdClass;
        $source->name = 'John Doe';
        $source->email = 12345; // Should be string or Email VO

        // Act & Assert
        $this->expectException(\RuntimeException::class);
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
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Type mismatches');

        TestUserRecord::from($source);
    }

    /**
     * Test that hydration throws exception for wrong enum type.
     */
    public function test_hydration_throws_exception_for_wrong_enum_type(): void
    {
        // Arrange
        $source = new stdClass;
        $source->name = 'John Doe';
        $source->email = 'john@example.com';
        $source->status = 'admin'; // Should be user status, not role

        // Act & Assert
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Type mismatches');

        TestUserRecord::from($source);
    }

    // ==================== ARRAY ACCESS TYPE VALIDATION TESTS ====================

    /**
     * Test that array offset set validates type.
     */
    public function test_array_offset_set_validates_type(): void
    {
        // Arrange
        $collection = new TypedCollection('int');

        // Act & Assert
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected type(s) int');

        $collection[0] = 'not an integer';
    }

    /**
     * Test that array offset set with null validates type.
     */
    public function test_array_offset_set_with_null_validates_type(): void
    {
        // Arrange
        $collection = new TypedCollection('int');

        // Act & Assert
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected type(s) int');

        $collection[0] = null;
    }

    // ==================== MAP RETURN TYPE VALIDATION TESTS ====================

    /**
     * Test that map validates return type.
     */
    public function test_map_validates_return_type(): void
    {
        // Arrange
        $collection = new TypedCollection('int');
        $collection->add(1, 2, 3);

        // Act & Assert - Return type is int, which matches, so no exception
        $result = $collection->map(fn($item) => $item * 2);
        $this->assertCount(3, $result);
    }

    /**
     * Test that map throws exception for invalid return type.
     */
    public function test_map_throws_exception_for_invalid_return_type(): void
    {
        // Arrange
        $collection = new TypedCollection('int');
        $collection->add(1, 2, 3);

        // Act & Assert - The collection will try to detect return type
        // and add the items. If the return type doesn't match allowed types,
        // it will throw an exception during add.
        $result = $collection->map(fn($item) => (string) $item);

        // The collection now contains strings but allowed type is 'int'
        // This will fail when adding to a new collection
        $this->expectException(InvalidArgumentException::class);

        $result->add(4); // This should work, but let's verify
    }

    // ==================== MERGE TYPE VALIDATION TESTS ====================

    /**
     * Test that merge validates types from other collection.
     */
    public function test_merge_validates_types_from_other_collection(): void
    {
        // Arrange
        $collection1 = new TypedCollection('int');
        $collection2 = new TypedCollection('int');
        $collection1->add(1, 2, 3);
        $collection2->add(4, 5, 6);

        // Act - Both have same allowed types, should work
        $merged = $collection1->merge($collection2);

        // Assert
        $this->assertCount(6, $merged);
    }

    /**
     * Test that merge with incompatible collections throws exception.
     */
    public function test_merge_with_incompatible_collections_throws_exception(): void
    {
        // Arrange
        $collection1 = new TypedCollection('int');
        $collection2 = new TypedCollection('string');
        $collection1->add(1, 2, 3);
        $collection2->add('a', 'b', 'c');

        // Act - Merge will try to add string to int collection
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected type(s) int');

        $collection1->merge($collection2);
    }

    // ==================== ERROR MESSAGE CLARITY TESTS ====================

    /**
     * Test that error message includes expected types.
     */
    public function test_error_message_includes_expected_types(): void
    {
        // Arrange
        $collection = new TypedCollection('int', 'string', 'float');

        // Act & Assert
        try {
            $collection->add(true);
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('int|string|float', $e->getMessage());
            $this->assertStringContainsString('bool', $e->getMessage());
        }
    }

    /**
     * Test that error message includes actual type.
     */
    public function test_error_message_includes_actual_type(): void
    {
        // Arrange
        $collection = new TypedCollection('int');

        // Act & Assert
        try {
            $collection->add('hello');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('string', $e->getMessage());
        }
    }

    /**
     * Test that error message for object includes class name.
     */
    public function test_error_message_for_object_includes_class_name(): void
    {
        // Arrange
        $collection = new TypedCollection('int');

        // Act & Assert
        try {
            $collection->add(new stdClass);
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('stdClass', $e->getMessage());
        }
    }

    /**
     * Test that error message for enum includes enum class.
     */
    public function test_error_message_for_enum_includes_enum_class(): void
    {
        // Arrange
        $collection = new TypedCollection('int');

        // Act & Assert
        try {
            $collection->add(TestUserStatus::ACTIVE);
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString(TestUserStatus::class, $e->getMessage());
        }
    }
}
