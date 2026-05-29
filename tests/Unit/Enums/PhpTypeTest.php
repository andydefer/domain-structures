<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Unit\Enums;

use AndyDefer\DomainStructures\Abstracts\AbstractData;
use AndyDefer\DomainStructures\Abstracts\AbstractRecord;
use AndyDefer\DomainStructures\Abstracts\AbstractTypedCollection;
use AndyDefer\DomainStructures\Abstracts\AbstractValueObject;
use AndyDefer\DomainStructures\Collections\Utility\StringTypedCollection;
use AndyDefer\DomainStructures\Enums\PhpType;
use AndyDefer\DomainStructures\Normalizers\NormalizerChain;
use AndyDefer\DomainStructures\Tests\Fixtures\Data\TestUserData;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestBackedIntEnum;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestBackedStringEnum;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestPureEnum;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestUserGrade;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestUserRole;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestUserStatus;
use AndyDefer\DomainStructures\Tests\Fixtures\Records\TestUserRecord;
use AndyDefer\DomainStructures\Tests\Fixtures\ValueObjects\TestEmailAddress;
use AndyDefer\DomainStructures\Tests\Fixtures\ValueObjects\TestIso8601DateTime;
use AndyDefer\DomainStructures\Tests\TestCase;
use AndyDefer\DomainStructures\Utils\DataObject;
use Carbon\Carbon;
use DateTime;
use DateTimeInterface;
use UnitEnum;

/**
 * Unit tests for PhpType enum.
 *
 * This test suite validates the PhpType enumeration which provides
 * standardized representation of PHP data types and domain-specific types.
 *
 * The tests follow the AAA pattern (Arrange, Act, Assert).
 */
final class PhpTypeTest extends TestCase
{
    // ==================== FROM_VALUE TESTS ====================

    public function test_from_value_returns_integer_for_int(): void
    {
        $value = 42;
        $type = PhpType::fromValue($value);

        $this->assertSame(PhpType::INTEGER, $type);
        $this->assertTrue($type->isInt());
        $this->assertTrue($type->isNumeric());
        $this->assertTrue($type->isScalar());
        $this->assertFalse($type->isString());
        $this->assertFalse($type->isBool());
        $this->assertFalse($type->isFloat());
    }

    public function test_from_value_returns_integer_for_negative_int(): void
    {
        $value = -42;
        $type = PhpType::fromValue($value);

        $this->assertSame(PhpType::INTEGER, $type);
        $this->assertTrue($type->isInt());
    }

    public function test_from_value_returns_integer_for_zero(): void
    {
        $value = 0;
        $type = PhpType::fromValue($value);

        $this->assertSame(PhpType::INTEGER, $type);
        $this->assertTrue($type->isInt());
    }

    public function test_from_value_returns_double_for_float(): void
    {
        $value = 3.14;
        $type = PhpType::fromValue($value);

        $this->assertSame(PhpType::DOUBLE, $type);
        $this->assertTrue($type->isFloat());
        $this->assertTrue($type->isNumeric());
        $this->assertTrue($type->isScalar());
        $this->assertFalse($type->isInt());
    }

    public function test_from_value_returns_string_for_string(): void
    {
        $value = 'hello world';
        $type = PhpType::fromValue($value);

        $this->assertSame(PhpType::STRING, $type);
        $this->assertTrue($type->isString());
        $this->assertTrue($type->isScalar());
        $this->assertFalse($type->isInt());
        $this->assertFalse($type->isFloat());
        $this->assertFalse($type->isBool());
    }

    public function test_from_value_returns_string_for_empty_string(): void
    {
        $value = '';
        $type = PhpType::fromValue($value);

        $this->assertSame(PhpType::STRING, $type);
        $this->assertTrue($type->isString());
    }

    public function test_from_value_returns_boolean_for_true(): void
    {
        $value = true;
        $type = PhpType::fromValue($value);

        $this->assertSame(PhpType::BOOLEAN, $type);
        $this->assertTrue($type->isBool());
        $this->assertTrue($type->isScalar());
        $this->assertFalse($type->isInt());
        $this->assertFalse($type->isString());
    }

    public function test_from_value_returns_boolean_for_false(): void
    {
        $value = false;
        $type = PhpType::fromValue($value);

        $this->assertSame(PhpType::BOOLEAN, $type);
        $this->assertTrue($type->isBool());
    }

    public function test_from_value_returns_null_for_null(): void
    {
        $value = null;
        $type = PhpType::fromValue($value);

        $this->assertSame(PhpType::NULL, $type);
        $this->assertTrue($type->isNull());
        $this->assertTrue($type->isScalar());
        $this->assertFalse($type->isInt());
        $this->assertFalse($type->isString());
    }

    public function test_from_value_throws_exception_for_array(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported PHP type "array"');

        PhpType::fromValue([]);
    }

    public function test_from_value_returns_data_object_for_data_object(): void
    {
        $value = new DataObject(['name' => 'John']);
        $type = PhpType::fromValue($value);

        $this->assertSame(PhpType::DATA_OBJECT, $type);
        $this->assertTrue($type->isDataObject());
        $this->assertTrue($type->isObject());
        $this->assertFalse($type->isEnum());
        $this->assertFalse($type->isRecord());
    }

    public function test_from_value_returns_unit_enum_for_enum(): void
    {
        $value = TestUserRole::ADMIN;
        $type = PhpType::fromValue($value);

        $this->assertSame(PhpType::UNIT_ENUM, $type);
        $this->assertTrue($type->isEnum());
        $this->assertTrue($type->isObject());
        $this->assertFalse($type->isDataObject());
        $this->assertFalse($type->isRecord());
    }

    public function test_from_value_returns_unit_enum_for_backed_string_enum(): void
    {
        $value = TestBackedStringEnum::VALUE_ONE;
        $type = PhpType::fromValue($value);

        $this->assertSame(PhpType::UNIT_ENUM, $type);
        $this->assertTrue($type->isEnum());
        $this->assertTrue($type->isObject());
    }

    public function test_from_value_returns_unit_enum_for_backed_int_enum(): void
    {
        $value = TestBackedIntEnum::VALUE_ONE;
        $type = PhpType::fromValue($value);

        $this->assertSame(PhpType::UNIT_ENUM, $type);
        $this->assertTrue($type->isEnum());
        $this->assertTrue($type->isObject());
    }

    public function test_from_value_returns_unit_enum_for_pure_enum(): void
    {
        $value = TestPureEnum::VALUE_ONE;
        $type = PhpType::fromValue($value);

        $this->assertSame(PhpType::UNIT_ENUM, $type);
        $this->assertTrue($type->isEnum());
        $this->assertTrue($type->isObject());
    }

    public function test_from_value_returns_abstract_record_for_record(): void
    {
        $email = TestEmailAddress::from('test@example.com');
        $value = new TestUserRecord(name: 'Test', email: $email);
        $type = PhpType::fromValue($value);

        $this->assertSame(PhpType::ABSTRACT_RECORD, $type);
        $this->assertTrue($type->isRecord());
        $this->assertTrue($type->isDomainAbstractType());
        $this->assertTrue($type->isObject());
        $this->assertFalse($type->isEnum());
        $this->assertFalse($type->isValueObject());
        $this->assertFalse($type->isData());
        $this->assertFalse($type->isCollection());
    }

    public function test_from_value_returns_abstract_value_object_for_vo(): void
    {
        $value = TestEmailAddress::from('test@example.com');
        $type = PhpType::fromValue($value);

        $this->assertSame(PhpType::ABSTRACT_VALUE_OBJECT, $type);
        $this->assertTrue($type->isValueObject());
        $this->assertTrue($type->isDomainAbstractType());
        $this->assertTrue($type->isObject());
        $this->assertFalse($type->isEnum());
        $this->assertFalse($type->isRecord());
        $this->assertFalse($type->isData());
    }

    public function test_from_value_returns_abstract_data_for_data(): void
    {
        $email = TestEmailAddress::from('test@example.com');
        $value = new TestUserData(
            id: 1,
            name: 'Test',
            email: $email,
            status: TestUserStatus::ACTIVE,
            role: TestUserRole::USER,
            grade: TestUserGrade::BRONZE,
            emailVerifiedAt: null,
            tags: new StringTypedCollection,
            createdAt: TestIso8601DateTime::from(new DateTime)
        );
        $type = PhpType::fromValue($value);

        $this->assertSame(PhpType::ABSTRACT_DATA, $type);
        $this->assertTrue($type->isData());
        $this->assertTrue($type->isDomainAbstractType());
        $this->assertTrue($type->isObject());
    }

    // ==================== GET_NORMALIZED_TYPE_NAME TESTS ====================

    public function test_get_normalized_type_name_returns_int_for_integer(): void
    {
        $value = 42;
        $typeName = PhpType::getNormalizedTypeName($value);

        $this->assertSame('int', $typeName);
    }

    public function test_get_normalized_type_name_returns_float_for_float(): void
    {
        $value = 3.14;
        $typeName = PhpType::getNormalizedTypeName($value);

        $this->assertSame('float', $typeName);
    }

    public function test_get_normalized_type_name_returns_string_for_string(): void
    {
        $value = 'hello';
        $typeName = PhpType::getNormalizedTypeName($value);

        $this->assertSame('string', $typeName);
    }

    public function test_get_normalized_type_name_returns_bool_for_boolean(): void
    {
        $value = true;
        $typeName = PhpType::getNormalizedTypeName($value);

        $this->assertSame('bool', $typeName);
    }

    public function test_get_normalized_type_name_returns_null_for_null(): void
    {
        $value = null;
        $typeName = PhpType::getNormalizedTypeName($value);

        $this->assertSame('null', $typeName);
    }

    public function test_get_normalized_type_name_returns_class_name_for_data_object(): void
    {
        $value = new DataObject;
        $typeName = PhpType::getNormalizedTypeName($value);

        $this->assertSame(DataObject::class, $typeName);
    }

    public function test_get_normalized_type_name_returns_unit_enum_class_for_enum(): void
    {
        $value = TestUserRole::ADMIN;
        $typeName = PhpType::getNormalizedTypeName($value);

        $this->assertSame(UnitEnum::class, $typeName);
    }

    // ==================== GET_NORMALIZED_NAME METHOD TESTS ====================

    public function test_get_normalized_name_returns_int_for_integer_case(): void
    {
        $result = PhpType::INTEGER->getNormalizedName();
        $this->assertSame('int', $result);
    }

    public function test_get_normalized_name_returns_float_for_double_case(): void
    {
        $result = PhpType::DOUBLE->getNormalizedName();
        $this->assertSame('float', $result);
    }

    public function test_get_normalized_name_returns_bool_for_boolean_case(): void
    {
        $result = PhpType::BOOLEAN->getNormalizedName();
        $this->assertSame('bool', $result);
    }

    public function test_get_normalized_name_returns_null_for_null_case(): void
    {
        $result = PhpType::NULL->getNormalizedName();
        $this->assertSame('null', $result);
    }

    public function test_get_normalized_name_returns_class_name_for_domain_types(): void
    {
        $this->assertSame(UnitEnum::class, PhpType::UNIT_ENUM->getNormalizedName());
        $this->assertSame(AbstractRecord::class, PhpType::ABSTRACT_RECORD->getNormalizedName());
        $this->assertSame(AbstractValueObject::class, PhpType::ABSTRACT_VALUE_OBJECT->getNormalizedName());
        $this->assertSame(AbstractData::class, PhpType::ABSTRACT_DATA->getNormalizedName());
        $this->assertSame(AbstractTypedCollection::class, PhpType::ABSTRACT_TYPED_COLLECTION->getNormalizedName());
        $this->assertSame(DataObject::class, PhpType::DATA_OBJECT->getNormalizedName());
    }

    // ==================== GET_CLASS_STRING TESTS ====================

    public function test_get_class_string_returns_correct_class_name(): void
    {
        $this->assertSame(UnitEnum::class, PhpType::UNIT_ENUM->getClassString());
        $this->assertSame(AbstractRecord::class, PhpType::ABSTRACT_RECORD->getClassString());
        $this->assertSame(AbstractValueObject::class, PhpType::ABSTRACT_VALUE_OBJECT->getClassString());
        $this->assertSame(AbstractData::class, PhpType::ABSTRACT_DATA->getClassString());
        $this->assertSame(AbstractTypedCollection::class, PhpType::ABSTRACT_TYPED_COLLECTION->getClassString());
        $this->assertSame(DataObject::class, PhpType::DATA_OBJECT->getClassString());
        $this->assertSame('int', PhpType::INTEGER->getClassString());
        $this->assertSame('string', PhpType::STRING->getClassString());
    }

    // ==================== IS_* METHOD TESTS ====================

    public function test_is_scalar_returns_correct_values(): void
    {
        $this->assertTrue(PhpType::INTEGER->isScalar());
        $this->assertTrue(PhpType::DOUBLE->isScalar());
        $this->assertTrue(PhpType::STRING->isScalar());
        $this->assertTrue(PhpType::BOOLEAN->isScalar());
        $this->assertTrue(PhpType::NULL->isScalar());

        $this->assertFalse(PhpType::UNIT_ENUM->isScalar());
        $this->assertFalse(PhpType::ABSTRACT_RECORD->isScalar());
    }

    public function test_is_numeric_returns_correct_values(): void
    {
        $this->assertTrue(PhpType::INTEGER->isNumeric());
        $this->assertTrue(PhpType::DOUBLE->isNumeric());

        $this->assertFalse(PhpType::STRING->isNumeric());
        $this->assertFalse(PhpType::BOOLEAN->isNumeric());
        $this->assertFalse(PhpType::NULL->isNumeric());
    }

    public function test_is_int_returns_correct_values(): void
    {
        $this->assertTrue(PhpType::INTEGER->isInt());
        $this->assertFalse(PhpType::DOUBLE->isInt());
        $this->assertFalse(PhpType::STRING->isInt());
    }

    public function test_is_float_returns_correct_values(): void
    {
        $this->assertTrue(PhpType::DOUBLE->isFloat());
        $this->assertFalse(PhpType::INTEGER->isFloat());
        $this->assertFalse(PhpType::STRING->isFloat());
    }

    public function test_is_string_returns_correct_values(): void
    {
        $this->assertTrue(PhpType::STRING->isString());
        $this->assertFalse(PhpType::INTEGER->isString());
        $this->assertFalse(PhpType::DOUBLE->isString());
    }

    public function test_is_bool_returns_correct_values(): void
    {
        $this->assertTrue(PhpType::BOOLEAN->isBool());
        $this->assertFalse(PhpType::INTEGER->isBool());
        $this->assertFalse(PhpType::STRING->isBool());
    }

    public function test_is_null_returns_correct_values(): void
    {
        $this->assertTrue(PhpType::NULL->isNull());
        $this->assertFalse(PhpType::INTEGER->isNull());
        $this->assertFalse(PhpType::STRING->isNull());
    }

    public function test_is_object_returns_correct_values(): void
    {
        $this->assertTrue(PhpType::UNIT_ENUM->isObject());
        $this->assertTrue(PhpType::ABSTRACT_RECORD->isObject());
        $this->assertTrue(PhpType::ABSTRACT_VALUE_OBJECT->isObject());
        $this->assertTrue(PhpType::ABSTRACT_DATA->isObject());
        $this->assertTrue(PhpType::ABSTRACT_TYPED_COLLECTION->isObject());
        $this->assertTrue(PhpType::DATA_OBJECT->isObject());

        $this->assertFalse(PhpType::INTEGER->isObject());
        $this->assertFalse(PhpType::STRING->isObject());
        $this->assertFalse(PhpType::DOUBLE->isObject());
        $this->assertFalse(PhpType::BOOLEAN->isObject());
        $this->assertFalse(PhpType::NULL->isObject());
    }

    public function test_is_enum_returns_correct_values(): void
    {
        $this->assertTrue(PhpType::UNIT_ENUM->isEnum());
        $this->assertFalse(PhpType::INTEGER->isEnum());
        $this->assertFalse(PhpType::ABSTRACT_RECORD->isEnum());
    }

    public function test_is_record_returns_correct_values(): void
    {
        $this->assertTrue(PhpType::ABSTRACT_RECORD->isRecord());
        $this->assertFalse(PhpType::INTEGER->isRecord());
        $this->assertFalse(PhpType::UNIT_ENUM->isRecord());
    }

    public function test_is_value_object_returns_correct_values(): void
    {
        $this->assertTrue(PhpType::ABSTRACT_VALUE_OBJECT->isValueObject());
        $this->assertFalse(PhpType::INTEGER->isValueObject());
        $this->assertFalse(PhpType::ABSTRACT_RECORD->isValueObject());
    }

    public function test_is_data_returns_correct_values(): void
    {
        $this->assertTrue(PhpType::ABSTRACT_DATA->isData());
        $this->assertFalse(PhpType::INTEGER->isData());
        $this->assertFalse(PhpType::ABSTRACT_RECORD->isData());
    }

    public function test_is_collection_returns_correct_values(): void
    {
        $this->assertTrue(PhpType::ABSTRACT_TYPED_COLLECTION->isCollection());
        $this->assertFalse(PhpType::INTEGER->isCollection());
        $this->assertFalse(PhpType::ABSTRACT_RECORD->isCollection());
    }

    public function test_is_data_object_returns_correct_values(): void
    {
        $this->assertTrue(PhpType::DATA_OBJECT->isDataObject());
        $this->assertFalse(PhpType::INTEGER->isDataObject());
        $this->assertFalse(PhpType::DATA_OBJECT->isEnum());
    }

    public function test_is_domain_abstract_type_returns_correct_values(): void
    {
        $this->assertTrue(PhpType::ABSTRACT_RECORD->isDomainAbstractType());
        $this->assertTrue(PhpType::ABSTRACT_VALUE_OBJECT->isDomainAbstractType());
        $this->assertTrue(PhpType::ABSTRACT_DATA->isDomainAbstractType());
        $this->assertTrue(PhpType::ABSTRACT_TYPED_COLLECTION->isDomainAbstractType());

        $this->assertFalse(PhpType::INTEGER->isDomainAbstractType());
        $this->assertFalse(PhpType::UNIT_ENUM->isDomainAbstractType());
        $this->assertFalse(PhpType::DATA_OBJECT->isDomainAbstractType());
    }

    // ==================== GET_SCALAR_TYPES TESTS ====================

    public function test_get_scalar_types_returns_correct_array(): void
    {
        $scalarTypes = PhpType::getScalarTypes();

        $this->assertCount(5, $scalarTypes);
        $this->assertContains(PhpType::INTEGER, $scalarTypes);
        $this->assertContains(PhpType::DOUBLE, $scalarTypes);
        $this->assertContains(PhpType::STRING, $scalarTypes);
        $this->assertContains(PhpType::BOOLEAN, $scalarTypes);
        $this->assertContains(PhpType::NULL, $scalarTypes);
    }

    public function test_get_scalar_type_names_returns_correct_array(): void
    {
        $scalarTypeNames = PhpType::getScalarTypeNames();

        $this->assertCount(5, $scalarTypeNames);
        $this->assertContains('int', $scalarTypeNames);
        $this->assertContains('float', $scalarTypeNames);
        $this->assertContains('string', $scalarTypeNames);
        $this->assertContains('bool', $scalarTypeNames);
        $this->assertContains('null', $scalarTypeNames);
    }

    public function test_get_numeric_types_returns_correct_array(): void
    {
        $numericTypes = PhpType::getNumericTypes();

        $this->assertCount(2, $numericTypes);
        $this->assertContains(PhpType::INTEGER, $numericTypes);
        $this->assertContains(PhpType::DOUBLE, $numericTypes);
    }

    // ==================== GET_ALLOWED_TYPES_LIST TESTS ====================

    public function test_get_allowed_types_list_returns_all_allowed_types(): void
    {
        $allowedTypes = PhpType::getAllowedTypesList();

        $this->assertCount(11, $allowedTypes);
        $this->assertContains('int', $allowedTypes);
        $this->assertContains('string', $allowedTypes);
        $this->assertContains('float', $allowedTypes);
        $this->assertContains('bool', $allowedTypes);
        $this->assertContains('null', $allowedTypes);
        $this->assertContains(UnitEnum::class, $allowedTypes);
        $this->assertContains(AbstractRecord::class, $allowedTypes);
        $this->assertContains(AbstractValueObject::class, $allowedTypes);
        $this->assertContains(AbstractData::class, $allowedTypes);
        $this->assertContains(AbstractTypedCollection::class, $allowedTypes);
        $this->assertContains(DataObject::class, $allowedTypes);
    }

    // ==================== IS_VALID_TYPE TESTS ====================

    public function test_is_valid_type_returns_true_for_valid_scalar_types(): void
    {
        $this->assertTrue(PhpType::isValidType('int'));
        $this->assertTrue(PhpType::isValidType('string'));
        $this->assertTrue(PhpType::isValidType('float'));
        $this->assertTrue(PhpType::isValidType('bool'));
        $this->assertTrue(PhpType::isValidType('null'));
    }

    public function test_is_valid_type_returns_true_for_valid_domain_abstract_types(): void
    {
        $this->assertTrue(PhpType::isValidType(UnitEnum::class));
        $this->assertTrue(PhpType::isValidType(AbstractRecord::class));
        $this->assertTrue(PhpType::isValidType(AbstractValueObject::class));
        $this->assertTrue(PhpType::isValidType(AbstractData::class));
        $this->assertTrue(PhpType::isValidType(AbstractTypedCollection::class));
        $this->assertTrue(PhpType::isValidType(DataObject::class));
    }

    public function test_is_valid_type_returns_true_for_enum_subclasses(): void
    {
        $this->assertTrue(PhpType::isValidType(TestUserRole::class));
        $this->assertTrue(PhpType::isValidType(TestBackedStringEnum::class));
        $this->assertTrue(PhpType::isValidType(TestBackedIntEnum::class));
        $this->assertTrue(PhpType::isValidType(TestPureEnum::class));
    }

    public function test_is_valid_type_returns_true_for_record_subclasses(): void
    {
        $this->assertTrue(PhpType::isValidType(TestUserRecord::class));
    }

    public function test_is_valid_type_returns_true_for_value_object_subclasses(): void
    {
        $this->assertTrue(PhpType::isValidType(TestEmailAddress::class));
        $this->assertTrue(PhpType::isValidType(TestIso8601DateTime::class));
    }

    public function test_is_valid_type_returns_false_for_invalid_types(): void
    {
        $this->assertFalse(PhpType::isValidType('invalid_type'));
        $this->assertFalse(PhpType::isValidType('NonExistentClass'));
        $this->assertFalse(PhpType::isValidType('array'));
    }

    // ==================== FROM_TYPE_STRING TESTS ====================

    public function test_from_type_string_returns_correct_type_for_scalars(): void
    {
        $this->assertSame(PhpType::INTEGER, PhpType::fromTypeString('int'));
        $this->assertSame(PhpType::STRING, PhpType::fromTypeString('string'));
        $this->assertSame(PhpType::DOUBLE, PhpType::fromTypeString('float'));
        $this->assertSame(PhpType::BOOLEAN, PhpType::fromTypeString('bool'));
        $this->assertSame(PhpType::NULL, PhpType::fromTypeString('null'));
    }

    public function test_from_type_string_returns_correct_type_for_domain_abstract_types(): void
    {
        $this->assertSame(PhpType::UNIT_ENUM, PhpType::fromTypeString(UnitEnum::class));
        $this->assertSame(PhpType::ABSTRACT_RECORD, PhpType::fromTypeString(AbstractRecord::class));
        $this->assertSame(PhpType::ABSTRACT_VALUE_OBJECT, PhpType::fromTypeString(AbstractValueObject::class));
        $this->assertSame(PhpType::ABSTRACT_DATA, PhpType::fromTypeString(AbstractData::class));
        $this->assertSame(PhpType::ABSTRACT_TYPED_COLLECTION, PhpType::fromTypeString(AbstractTypedCollection::class));
        $this->assertSame(PhpType::DATA_OBJECT, PhpType::fromTypeString(DataObject::class));
    }

    public function test_from_type_string_returns_correct_type_for_enum_subclasses(): void
    {
        $this->assertSame(PhpType::UNIT_ENUM, PhpType::fromTypeString(TestUserRole::class));
        $this->assertSame(PhpType::UNIT_ENUM, PhpType::fromTypeString(TestBackedStringEnum::class));
    }

    public function test_from_type_string_returns_correct_type_for_record_subclasses(): void
    {
        $this->assertSame(PhpType::ABSTRACT_RECORD, PhpType::fromTypeString(TestUserRecord::class));
    }

    public function test_from_type_string_returns_correct_type_for_value_object_subclasses(): void
    {
        $this->assertSame(PhpType::ABSTRACT_VALUE_OBJECT, PhpType::fromTypeString(TestEmailAddress::class));
    }

    public function test_from_type_string_throws_exception_for_invalid_type(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown type: invalid_type');
        PhpType::fromTypeString('invalid_type');
    }

    public function test_from_type_string_throws_exception_for_non_existent_class(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown type: NonExistentClass');
        PhpType::fromTypeString('NonExistentClass');
    }

    public function test_from_type_string_throws_exception_for_array(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown type: array');
        PhpType::fromTypeString('array');
    }

    // ==================== GET_DISPLAY_NAME TESTS ====================

    public function test_get_display_name_returns_int_for_integer(): void
    {
        $value = 42;
        $type = PhpType::fromValue($value);

        $displayName = $type->getDisplayName($value);

        $this->assertSame('int', $displayName);
    }

    public function test_get_display_name_returns_float_for_float(): void
    {
        $value = 3.14;
        $type = PhpType::fromValue($value);

        $displayName = $type->getDisplayName($value);

        $this->assertSame('float', $displayName);
    }

    public function test_get_display_name_returns_string_for_string(): void
    {
        $value = 'hello';
        $type = PhpType::fromValue($value);

        $displayName = $type->getDisplayName($value);

        $this->assertSame('string', $displayName);
    }

    public function test_get_display_name_returns_bool_for_boolean(): void
    {
        $value = true;
        $type = PhpType::fromValue($value);

        $displayName = $type->getDisplayName($value);

        $this->assertSame('bool', $displayName);
    }

    public function test_get_display_name_returns_null_for_null(): void
    {
        $value = null;
        $type = PhpType::fromValue($value);

        $displayName = $type->getDisplayName($value);

        $this->assertSame('null', $displayName);
    }

    public function test_get_display_name_returns_concrete_class_name_for_data_object(): void
    {
        $value = new DataObject(['name' => 'John']);
        $type = PhpType::fromValue($value);

        $displayName = $type->getDisplayName($value);

        $this->assertSame(DataObject::class, $displayName);
    }

    public function test_get_display_name_returns_concrete_class_name_for_enum(): void
    {
        $value = TestUserRole::ADMIN;
        $type = PhpType::fromValue($value);

        $displayName = $type->getDisplayName($value);

        $this->assertSame(TestUserRole::class, $displayName);
    }

    public function test_get_display_name_returns_concrete_class_name_for_record(): void
    {
        $email = TestEmailAddress::from('test@example.com');
        $value = new TestUserRecord(name: 'Test', email: $email);
        $type = PhpType::fromValue($value);

        $displayName = $type->getDisplayName($value);

        $this->assertSame(TestUserRecord::class, $displayName);
    }

    public function test_get_display_name_returns_concrete_class_name_for_value_object(): void
    {
        $value = TestEmailAddress::from('test@example.com');
        $type = PhpType::fromValue($value);

        $displayName = $type->getDisplayName($value);

        $this->assertSame(TestEmailAddress::class, $displayName);
    }

    public function test_get_display_name_returns_concrete_class_name_for_data(): void
    {
        $email = TestEmailAddress::from('test@example.com');
        $value = new TestUserData(
            id: 1,
            name: 'Test',
            email: $email,
            status: TestUserStatus::ACTIVE,
            role: TestUserRole::USER,
            grade: TestUserGrade::BRONZE,
            emailVerifiedAt: null,
            tags: new StringTypedCollection,
            createdAt: TestIso8601DateTime::from(new DateTime)
        );
        $type = PhpType::fromValue($value);

        $displayName = $type->getDisplayName($value);

        $this->assertSame(TestUserData::class, $displayName);
    }

    /**
     * Test that get_display_name returns the class name for an anonymous class.
     * Note: PhpType::fromValue() will throw an exception for anonymous classes
     * because they are not supported. This test verifies that behavior.
     */
    public function test_get_display_name_throws_exception_for_anonymous_class(): void
    {
        $value = new class
        {
            public string $name = 'anonymous';
        };

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported PHP type "object"');

        $type = PhpType::fromValue($value);
        $type->getDisplayName($value);
    }
}
