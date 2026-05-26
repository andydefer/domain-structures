<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Integration;

use AndyDefer\DomainStructures\Abstracts\AbstractData;
use AndyDefer\DomainStructures\Abstracts\AbstractRecord;
use AndyDefer\DomainStructures\Abstracts\AbstractTypedCollection;
use AndyDefer\DomainStructures\Abstracts\AbstractValueObject;
use AndyDefer\DomainStructures\Collections\Core\TypedCollection;
use AndyDefer\DomainStructures\Collections\Utility\IntTypedCollection;
use AndyDefer\DomainStructures\Collections\Utility\StringTypedCollection;
use AndyDefer\DomainStructures\Tests\Fixtures\Collections\ProductRecordCollection;
use AndyDefer\DomainStructures\Tests\Fixtures\Collections\TestUserRoleCollection;
use AndyDefer\DomainStructures\Tests\Fixtures\Data\TestUserData;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestBackedIntEnum;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestBackedStringEnum;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestCurrency;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestPureEnum;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestUserGrade;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestUserRole;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestUserStatus;
use AndyDefer\DomainStructures\Tests\Fixtures\Records\TestProductRecord;
use AndyDefer\DomainStructures\Tests\Fixtures\Records\TestUserRecord;
use AndyDefer\DomainStructures\Tests\Fixtures\ValueObjects\TestEmailAddress;
use AndyDefer\DomainStructures\Tests\Fixtures\ValueObjects\TestIso8601DateTime;
use AndyDefer\DomainStructures\Tests\Fixtures\ValueObjects\TestMoney;
use AndyDefer\DomainStructures\Tests\TestCase;
use AndyDefer\DomainStructures\TypeDetectors\TypeDetectorChain;
use stdClass;
use UnitEnum;

/**
 * Integration test for the type detection system.
 *
 * This test suite validates the TypeDetectorChain and all individual detectors:
 * - ScalarTypeDetector (int, string, float, bool, null)
 * - EnumTypeDetector (UnitEnum, BackedEnum)
 * - RecordTypeDetector (AbstractRecord)
 * - ValueObjectTypeDetector (AbstractValueObject)
 * - DataTypeDetector (AbstractData)
 * - TypedCollectionTypeDetector (AbstractTypedCollection)
 * - StdClassTypeDetector (stdClass)
 * - DefaultTypeDetector (fallback for unknown types)
 *
 * The tests verify that each detector correctly:
 * - Supports the appropriate types
 * - Returns the correct type name
 * - Returns the correct class string for collections
 * - Chains correctly to the next detector when not supported
 *
 * The tests follow the AAA pattern (Arrange, Act, Assert).
 */
final class TypeDetectionIntegrationTest extends TestCase
{
    private TypeDetectorChain $detectorChain;

    protected function setUp(): void
    {
        parent::setUp();
        $this->detectorChain = TypeDetectorChain::get();
    }

    // ==================== SCALAR TYPE DETECTION TESTS ====================

    /**
     * Test that integer type is correctly detected.
     */
    public function test_integer_type_is_correctly_detected(): void
    {
        // Arrange
        $value = 42;

        // Act
        $typeName = $this->detectorChain->getTypeName($value);
        $classString = $this->detectorChain->getClassString($value);

        // Assert
        $this->assertSame('int', $typeName);
        $this->assertSame('int', $classString);
    }

    /**
     * Test that negative integer type is correctly detected.
     */
    public function test_negative_integer_type_is_correctly_detected(): void
    {
        // Arrange
        $value = -42;

        // Act
        $typeName = $this->detectorChain->getTypeName($value);
        $classString = $this->detectorChain->getClassString($value);

        // Assert
        $this->assertSame('int', $typeName);
        $this->assertSame('int', $classString);
    }

    /**
     * Test that zero integer type is correctly detected.
     */
    public function test_zero_integer_type_is_correctly_detected(): void
    {
        // Arrange
        $value = 0;

        // Act
        $typeName = $this->detectorChain->getTypeName($value);
        $classString = $this->detectorChain->getClassString($value);

        // Assert
        $this->assertSame('int', $typeName);
        $this->assertSame('int', $classString);
    }

    /**
     * Test that string type is correctly detected.
     */
    public function test_string_type_is_correctly_detected(): void
    {
        // Arrange
        $value = 'hello world';

        // Act
        $typeName = $this->detectorChain->getTypeName($value);
        $classString = $this->detectorChain->getClassString($value);

        // Assert
        $this->assertSame('string', $typeName);
        $this->assertSame('string', $classString);
    }

    /**
     * Test that empty string type is correctly detected.
     */
    public function test_empty_string_type_is_correctly_detected(): void
    {
        // Arrange
        $value = '';

        // Act
        $typeName = $this->detectorChain->getTypeName($value);
        $classString = $this->detectorChain->getClassString($value);

        // Assert
        $this->assertSame('string', $typeName);
        $this->assertSame('string', $classString);
    }

    /**
     * Test that float type is correctly detected.
     */
    public function test_float_type_is_correctly_detected(): void
    {
        // Arrange
        $value = 3.14;

        // Act
        $typeName = $this->detectorChain->getTypeName($value);
        $classString = $this->detectorChain->getClassString($value);

        // Assert
        $this->assertSame('float', $typeName);
        $this->assertSame('float', $classString);
    }

    /**
     * Test that boolean true type is correctly detected.
     */
    public function test_boolean_true_type_is_correctly_detected(): void
    {
        // Arrange
        $value = true;

        // Act
        $typeName = $this->detectorChain->getTypeName($value);
        $classString = $this->detectorChain->getClassString($value);

        // Assert
        $this->assertSame('bool', $typeName);
        $this->assertSame('bool', $classString);
    }

    /**
     * Test that boolean false type is correctly detected.
     */
    public function test_boolean_false_type_is_correctly_detected(): void
    {
        // Arrange
        $value = false;

        // Act
        $typeName = $this->detectorChain->getTypeName($value);
        $classString = $this->detectorChain->getClassString($value);

        // Assert
        $this->assertSame('bool', $typeName);
        $this->assertSame('bool', $classString);
    }

    /**
     * Test that null type is correctly detected.
     */
    public function test_null_type_is_correctly_detected(): void
    {
        // Arrange
        $value = null;

        // Act
        $typeName = $this->detectorChain->getTypeName($value);
        $classString = $this->detectorChain->getClassString($value);

        // Assert
        $this->assertSame('null', $typeName);
        $this->assertSame('null', $classString);
    }

    // ==================== ENUM TYPE DETECTION TESTS ====================

    /**
     * Test that backed string enum is correctly detected.
     */
    public function test_backed_string_enum_is_correctly_detected(): void
    {
        // Arrange
        $value = TestBackedStringEnum::VALUE_ONE;

        // Act
        $typeName = $this->detectorChain->getTypeName($value);
        $classString = $this->detectorChain->getClassString($value);

        // Assert
        $this->assertSame(TestBackedStringEnum::class, $typeName);
        $this->assertSame(UnitEnum::class, $classString);
    }

    /**
     * Test that backed int enum is correctly detected.
     */
    public function test_backed_int_enum_is_correctly_detected(): void
    {
        // Arrange
        $value = TestBackedIntEnum::VALUE_ONE;

        // Act
        $typeName = $this->detectorChain->getTypeName($value);
        $classString = $this->detectorChain->getClassString($value);

        // Assert
        $this->assertSame(TestBackedIntEnum::class, $typeName);
        $this->assertSame(UnitEnum::class, $classString);
    }

    /**
     * Test that pure enum is correctly detected.
     */
    public function test_pure_enum_is_correctly_detected(): void
    {
        // Arrange
        $value = TestPureEnum::VALUE_ONE;

        // Act
        $typeName = $this->detectorChain->getTypeName($value);
        $classString = $this->detectorChain->getClassString($value);

        // Assert
        $this->assertSame(TestPureEnum::class, $typeName);
        $this->assertSame(UnitEnum::class, $classString);
    }

    /**
     * Test that different enum instances are correctly detected.
     */
    public function test_different_enum_instances_are_correctly_detected(): void
    {
        // Arrange
        $values = [
            TestUserStatus::ACTIVE,
            TestUserRole::ADMIN,
            TestUserGrade::GOLD,
        ];

        // Act & Assert
        foreach ($values as $value) {
            $typeName = $this->detectorChain->getTypeName($value);
            $classString = $this->detectorChain->getClassString($value);

            $this->assertStringContainsString('TestUser', $typeName);
            $this->assertSame(UnitEnum::class, $classString);
        }
    }

    // ==================== RECORD TYPE DETECTION TESTS ====================

    /**
     * Test that AbstractRecord instance is correctly detected.
     */
    public function test_abstract_record_instance_is_correctly_detected(): void
    {
        // Arrange
        $value = new TestUserRecord(
            name: 'John Doe',
            email: TestEmailAddress::fromString('john@example.com')
        );

        // Act
        $typeName = $this->detectorChain->getTypeName($value);
        $classString = $this->detectorChain->getClassString($value);

        // Assert
        $this->assertSame(TestUserRecord::class, $typeName);
        $this->assertSame(AbstractRecord::class, $classString);
    }

    /**
     * Test that different Record types are correctly detected.
     */
    public function test_different_record_types_are_correctly_detected(): void
    {
        // Arrange
        $values = [
            new TestUserRecord(name: 'User', email: TestEmailAddress::fromString('user@example.com')),
            new TestProductRecord(id: 1, name: 'Product'),
        ];

        // Act & Assert
        foreach ($values as $value) {
            $typeName = $this->detectorChain->getTypeName($value);
            $classString = $this->detectorChain->getClassString($value);

            $this->assertStringContainsString('Test', $typeName);
            $this->assertSame(AbstractRecord::class, $classString);
        }
    }

    // ==================== VALUE OBJECT TYPE DETECTION TESTS ====================

    /**
     * Test that AbstractValueObject instance is correctly detected.
     */
    public function test_abstract_value_object_instance_is_correctly_detected(): void
    {
        // Arrange
        $value = TestEmailAddress::fromString('test@example.com');

        // Act
        $typeName = $this->detectorChain->getTypeName($value);
        $classString = $this->detectorChain->getClassString($value);

        // Assert
        $this->assertSame(TestEmailAddress::class, $typeName);
        $this->assertSame(AbstractValueObject::class, $classString);
    }

    /**
     * Test that different Value Object types are correctly detected.
     */
    public function test_different_value_object_types_are_correctly_detected(): void
    {
        // Arrange
        $values = [
            TestEmailAddress::fromString('test@example.com'),
            TestIso8601DateTime::now(),
            TestMoney::fromFloat(100.50, TestCurrency::USD),
        ];

        // Act & Assert
        foreach ($values as $value) {
            $typeName = $this->detectorChain->getTypeName($value);
            $classString = $this->detectorChain->getClassString($value);

            $this->assertStringContainsString('Test', $typeName);
            $this->assertSame(AbstractValueObject::class, $classString);
        }
    }

    // ==================== DATA TYPE DETECTION TESTS ====================

    /**
     * Test that AbstractData instance is correctly detected.
     */
    public function test_abstract_data_instance_is_correctly_detected(): void
    {
        // Arrange
        $value = new TestUserData(
            id: '1',
            name: 'John Doe',
            email: TestEmailAddress::fromString('john@example.com'),
            status: TestUserStatus::ACTIVE,
            role: TestUserRole::USER,
            grade: TestUserGrade::BRONZE,
            emailVerifiedAt: null,
            tags: new StringTypedCollection,
            createdAt: TestIso8601DateTime::now()
        );

        // Act
        $typeName = $this->detectorChain->getTypeName($value);
        $classString = $this->detectorChain->getClassString($value);

        // Assert
        $this->assertSame(TestUserData::class, $typeName);
        $this->assertSame(AbstractData::class, $classString);
    }

    // ==================== TYPED COLLECTION TYPE DETECTION TESTS ====================

    /**
     * Test that AbstractTypedCollection instance is correctly detected.
     */
    public function test_abstract_typed_collection_instance_is_correctly_detected(): void
    {
        // Arrange
        $value = new StringTypedCollection;

        // Act
        $typeName = $this->detectorChain->getTypeName($value);
        $classString = $this->detectorChain->getClassString($value);

        // Assert
        $this->assertSame(StringTypedCollection::class, $typeName);
        $this->assertSame(AbstractTypedCollection::class, $classString);
    }

    /**
     * Test that different TypedCollection types are correctly detected.
     */
    public function test_different_typed_collection_types_are_correctly_detected(): void
    {
        // Arrange
        $values = [
            new StringTypedCollection,
            new IntTypedCollection,
            new ProductRecordCollection,
            new TestUserRoleCollection,
            new TypedCollection,
        ];

        // Act & Assert
        foreach ($values as $value) {
            $typeName = $this->detectorChain->getTypeName($value);
            $classString = $this->detectorChain->getClassString($value);

            $this->assertStringContainsString('Collection', $typeName);
            $this->assertSame(AbstractTypedCollection::class, $classString);
        }
    }

    // ==================== STDCLASS TYPE DETECTION TESTS ====================

    /**
     * Test that stdClass instance is correctly detected.
     */
    public function test_stdclass_instance_is_correctly_detected(): void
    {
        // Arrange
        $value = new stdClass;

        // Act
        $typeName = $this->detectorChain->getTypeName($value);
        $classString = $this->detectorChain->getClassString($value);

        // Assert
        $this->assertSame(stdClass::class, $typeName);
        $this->assertSame(stdClass::class, $classString);
    }

    /**
     * Test that stdClass with properties is correctly detected.
     */
    public function test_stdclass_with_properties_is_correctly_detected(): void
    {
        // Arrange
        $value = new stdClass;
        $value->name = 'John';
        $value->age = 30;

        // Act
        $typeName = $this->detectorChain->getTypeName($value);
        $classString = $this->detectorChain->getClassString($value);

        // Assert
        $this->assertSame(stdClass::class, $typeName);
        $this->assertSame(stdClass::class, $classString);
    }

    // ==================== DEFAULT DETECTOR TESTS (UNKNOWN TYPES) ====================

    /**
     * Test that unknown class instances fall back to DefaultTypeDetector.
     */
    public function test_unknown_class_instance_falls_back_to_default_detector(): void
    {
        // Arrange
        $value = new class {
            public string $property = 'value';
        };

        // Act
        $typeName = $this->detectorChain->getTypeName($value);
        $classString = $this->detectorChain->getClassString($value);

        // Assert
        $this->assertStringContainsString('object@anonymous', $typeName);
        $this->assertStringContainsString('object@anonymous', $classString);
    }

    // ==================== TYPE NAME CONSISTENCY TESTS ====================

    /**
     * Test that multiple calls for same value return consistent type name.
     */
    public function test_multiple_calls_for_same_value_return_consistent_type_name(): void
    {
        // Arrange
        $value = 42;

        // Act
        $first = $this->detectorChain->getTypeName($value);
        $second = $this->detectorChain->getTypeName($value);
        $third = $this->detectorChain->getTypeName($value);

        // Assert
        $this->assertSame($first, $second);
        $this->assertSame($second, $third);
        $this->assertSame('int', $first);
    }

    /**
     * Test that multiple calls for same enum return consistent type name.
     */
    public function test_multiple_calls_for_same_enum_return_consistent_type_name(): void
    {
        // Arrange
        $value = TestUserStatus::ACTIVE;

        // Act
        $first = $this->detectorChain->getTypeName($value);
        $second = $this->detectorChain->getTypeName($value);

        // Assert
        $this->assertSame($first, $second);
        $this->assertSame(TestUserStatus::class, $first);
    }

    // ==================== COLLECTION DETECTION FOR MIXED VALUES TESTS ====================

    /**
     * Test that detection works for all values in a mixed collection.
     */
    public function test_detection_works_for_all_values_in_mixed_collection(): void
    {
        // Arrange
        $mixedValues = [
            'int' => 42,
            'string' => 'hello',
            'float' => 3.14,
            'bool' => true,
            'null' => null,
            'enum' => TestUserStatus::ACTIVE,
            'record' => new TestUserRecord(name: 'User', email: TestEmailAddress::fromString('user@example.com')),
            'value_object' => TestEmailAddress::fromString('test@example.com'),
            'collection' => new StringTypedCollection,
            'stdclass' => new stdClass,
        ];

        // Act & Assert
        foreach ($mixedValues as $key => $value) {
            $typeName = $this->detectorChain->getTypeName($value);
            $classString = $this->detectorChain->getClassString($value);

            $this->assertIsString($typeName, "Type name for {$key} should be string");
            $this->assertIsString($classString, "Class string for {$key} should be string");
            $this->assertNotEmpty($typeName, "Type name for {$key} should not be empty");
        }
    }

    // ==================== DETECTOR CHAIN ORDER TESTS ====================

    /**
     * Test that specific detectors are called before generic ones.
     */
    public function test_specific_detectors_are_called_before_generic_ones(): void
    {
        // Arrange
        $record = new TestUserRecord(name: 'User', email: TestEmailAddress::fromString('user@example.com'));

        // Act
        $typeName = $this->detectorChain->getTypeName($record);

        // Assert - Should be detected by RecordTypeDetector, not DefaultTypeDetector
        $this->assertSame(TestUserRecord::class, $typeName);
        $this->assertNotSame('object(' . TestUserRecord::class . ')', $typeName);
    }

    /**
     * Test that scalar values are detected before object types.
     */
    public function test_scalar_values_are_detected_before_object_types(): void
    {
        // Arrange
        $intValue = 42;
        $stringValue = 'test';

        // Act
        $intType = $this->detectorChain->getTypeName($intValue);
        $stringType = $this->detectorChain->getTypeName($stringValue);

        // Assert
        $this->assertSame('int', $intType);
        $this->assertSame('string', $stringType);
    }

    // ==================== NULL HANDLING TESTS ====================

    /**
     * Test that null value detection works consistently.
     */
    public function test_null_value_detection_works_consistently(): void
    {
        // Arrange
        $values = [null, null, null];

        // Act & Assert
        foreach ($values as $value) {
            $typeName = $this->detectorChain->getTypeName($value);
            $this->assertSame('null', $typeName);
        }
    }

    /**
     * Test that null inside collection is correctly detected.
     */
    public function test_null_inside_collection_is_correctly_detected(): void
    {
        // Arrange
        $collection = new TypedCollection('int', 'null', 'string');
        $collection->add(1, null, 'text', 2, null);

        // Act
        $types = [];
        foreach ($collection->all() as $item) {
            $types[] = $this->detectorChain->getTypeName($item);
        }

        // Assert
        $this->assertSame(['int', 'null', 'string', 'int', 'null'], $types);
    }

    // ==================== COMPLEX NESTED DETECTION TESTS ====================

    /**
     * Test that nested collections maintain correct type detection.
     */
    public function test_nested_collections_maintain_correct_type_detection(): void
    {
        // Arrange
        $innerCollection = new StringTypedCollection;
        $innerCollection->add('a', 'b', 'c');

        $outerCollection = new TypedCollection(StringTypedCollection::class);
        $outerCollection->add($innerCollection);

        // Act
        $outerType = $this->detectorChain->getTypeName($outerCollection);
        $innerType = $this->detectorChain->getTypeName($innerCollection);

        // Assert
        $this->assertSame(TypedCollection::class, $outerType);
        $this->assertSame(StringTypedCollection::class, $innerType);
    }

    /**
     * Test that Record with nested Value Object is correctly detected.
     */
    public function test_record_with_nested_value_object_is_correctly_detected(): void
    {
        // Arrange
        $record = new TestUserRecord(
            name: 'John Doe',
            email: TestEmailAddress::fromString('john@example.com'),
            createdAt: TestIso8601DateTime::now()
        );

        // Act
        $recordType = $this->detectorChain->getTypeName($record);
        $emailType = $this->detectorChain->getTypeName($record->email);
        $dateType = $this->detectorChain->getTypeName($record->createdAt);

        // Assert
        $this->assertSame(TestUserRecord::class, $recordType);
        $this->assertSame(TestEmailAddress::class, $emailType);
        $this->assertSame(TestIso8601DateTime::class, $dateType);
    }

    /**
     * Test that Record with nested collection is correctly detected.
     */
    public function test_record_with_nested_collection_is_correctly_detected(): void
    {
        // Arrange
        $tags = new StringTypedCollection;
        $tags->add('premium', 'vip');

        $record = new TestUserRecord(
            name: 'John Doe',
            email: TestEmailAddress::fromString('john@example.com'),
            tags: $tags
        );

        // Act
        $recordType = $this->detectorChain->getTypeName($record);
        $tagsType = $this->detectorChain->getTypeName($record->tags);

        // Assert
        $this->assertSame(TestUserRecord::class, $recordType);
        $this->assertSame(StringTypedCollection::class, $tagsType);
    }

    // ==================== PERFORMANCE AND CONSISTENCY TESTS ====================

    /**
     * Test that singleton instance returns the same detector chain.
     */
    public function test_singleton_instance_returns_same_detector_chain(): void
    {
        // Arrange
        $first = TypeDetectorChain::get();
        $second = TypeDetectorChain::get();

        // Assert
        $this->assertSame($first, $second);
    }

    /**
     * Test that detection is deterministic (same input always same output).
     */
    public function test_detection_is_deterministic(): void
    {
        // Arrange
        $testCases = [
            'int' => 42,
            'string' => 'test',
            'float' => 3.14,
            'bool' => true,
            'null' => null,
            'enum' => TestUserStatus::ACTIVE,
            'record' => new TestUserRecord(name: 'User', email: TestEmailAddress::fromString('user@example.com')),
            'value_object' => TestEmailAddress::fromString('test@example.com'),
            'collection' => new StringTypedCollection,
            'stdclass' => new stdClass,
        ];

        // Act & Assert
        foreach ($testCases as $key => $value) {
            $firstResult = $this->detectorChain->getTypeName($value);
            $secondResult = $this->detectorChain->getTypeName($value);
            $thirdResult = $this->detectorChain->getTypeName($value);

            $this->assertSame($firstResult, $secondResult, "Detection for {$key} is not deterministic");
            $this->assertSame($secondResult, $thirdResult, "Detection for {$key} is not deterministic");
        }
    }

    // ==================== EDGE CASES TESTS ====================

    /**
     * Test that empty string is correctly detected (not as null).
     */
    public function test_empty_string_is_correctly_detected_not_as_null(): void
    {
        // Arrange
        $value = '';

        // Act
        $typeName = $this->detectorChain->getTypeName($value);

        // Assert
        $this->assertSame('string', $typeName);
        $this->assertNotSame('null', $typeName);
    }

    /**
     * Test that zero is correctly detected as int, not as null or bool.
     */
    public function test_zero_is_correctly_detected_as_int(): void
    {
        // Arrange
        $value = 0;

        // Act
        $typeName = $this->detectorChain->getTypeName($value);

        // Assert
        $this->assertSame('int', $typeName);
        $this->assertNotSame('null', $typeName);
        $this->assertNotSame('bool', $typeName);
    }

    /**
     * Test that false is correctly detected as bool, not as null or int.
     */
    public function test_false_is_correctly_detected_as_bool(): void
    {
        // Arrange
        $value = false;

        // Act
        $typeName = $this->detectorChain->getTypeName($value);

        // Assert
        $this->assertSame('bool', $typeName);
        $this->assertNotSame('null', $typeName);
        $this->assertNotSame('int', $typeName);
    }

    /**
     * Test that large numbers are correctly detected.
     */
    public function test_large_numbers_are_correctly_detected(): void
    {
        // Arrange
        $intValue = PHP_INT_MAX;
        $floatValue = PHP_FLOAT_MAX;

        // Act
        $intType = $this->detectorChain->getTypeName($intValue);
        $floatType = $this->detectorChain->getTypeName($floatValue);

        // Assert
        $this->assertSame('int', $intType);
        $this->assertSame('float', $floatType);
    }

    /**
     * Test that special float values are correctly detected.
     */
    public function test_special_float_values_are_correctly_detected(): void
    {
        // Arrange
        $values = [NAN, INF, -INF];

        // Act & Assert
        foreach ($values as $value) {
            $typeName = $this->detectorChain->getTypeName($value);
            $this->assertSame('float', $typeName);
        }
    }
}
