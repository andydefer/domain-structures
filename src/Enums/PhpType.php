<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Enums;

use AndyDefer\DomainStructures\Abstracts\AbstractData;
use AndyDefer\DomainStructures\Abstracts\AbstractDataObject;
use AndyDefer\DomainStructures\Abstracts\AbstractRecord;
use AndyDefer\DomainStructures\Abstracts\AbstractTypedCollection;
use AndyDefer\DomainStructures\Abstracts\AbstractValueObject;
use AndyDefer\DomainStructures\Utils\DataObject;
use UnitEnum;

/**
 * PHP Type enumeration.
 *
 * Provides a standardized representation of PHP data types returned by gettype()
 * and extended types for domain-specific classes.
 */
enum PhpType: string
{
    // PHP native types (scalars only)
    case INTEGER = 'integer';
    case DOUBLE = 'double';
    case STRING = 'string';
    case BOOLEAN = 'boolean';
    case NULL = 'NULL';

        // Domain-specific abstract types
    case UNIT_ENUM = UnitEnum::class;
    case ABSTRACT_VALUE_OBJECT = AbstractValueObject::class;
    case ABSTRACT_TYPED_COLLECTION = AbstractTypedCollection::class;
    case ABSTRACT_DATA = AbstractData::class;
    case ABSTRACT_RECORD = AbstractRecord::class;
    case ABSTRACT_DATA_OBJECT = AbstractDataObject::class;

    /**
     * Get the normalized short name for the PHP type.
     */
    public function getNormalizedName(): string
    {
        return match ($this) {
            self::INTEGER => 'int',
            self::DOUBLE => 'float',
            self::BOOLEAN => 'bool',
            self::NULL => 'null',
            self::STRING => 'string',
            self::UNIT_ENUM => UnitEnum::class,
            self::ABSTRACT_RECORD => AbstractRecord::class,
            self::ABSTRACT_VALUE_OBJECT => AbstractValueObject::class,
            self::ABSTRACT_DATA => AbstractData::class,
            self::ABSTRACT_TYPED_COLLECTION => AbstractTypedCollection::class,
            self::ABSTRACT_DATA_OBJECT => AbstractDataObject::class,
        };
    }

    /**
     * Get the concrete name for display (full class name for objects).
     */
    public function getDisplayName(mixed $value): string
    {
        if (is_object($value)) {
            return $value::class;
        }

        return $this->getNormalizedName();
    }

    /**
     * Get the class string representation.
     * For scalars, returns the normalized type name.
     * For objects, returns the concrete class name when an object is provided.
     * For abstract types without an object, returns the abstract class name.
     *
     * @param  mixed  $object  Optional object to get the concrete class name from
     */
    public function getClassString(mixed $object = null): string
    {
        if ($this->isScalar()) {
            return $this->getNormalizedName();
        }

        return is_object($object) ? $object::class : $this->getNormalizedName();
    }

    public function isTransformable(): bool
    {
        return $this->isRecord() || $this->isValueObject() || $this->isData() || $this->isCollection();
    }

    /**
     * Check if the type is a scalar type.
     */
    public function isScalar(): bool
    {
        return in_array($this, [self::INTEGER, self::DOUBLE, self::STRING, self::BOOLEAN, self::NULL]);
    }

    /**
     * Check if the type is a numeric type.
     */
    public function isNumeric(): bool
    {
        return in_array($this, [self::INTEGER, self::DOUBLE]);
    }

    public function isInt(): bool
    {
        return $this === self::INTEGER;
    }

    public function isFloat(): bool
    {
        return $this === self::DOUBLE;
    }

    public function isString(): bool
    {
        return $this === self::STRING;
    }

    public function isBool(): bool
    {
        return $this === self::BOOLEAN;
    }

    public function isNull(): bool
    {
        return $this === self::NULL;
    }

    /**
     * Check if the type is an object (including domain objects).
     */
    public function isObject(): bool
    {
        return in_array($this, [
            self::UNIT_ENUM,
            self::ABSTRACT_RECORD,
            self::ABSTRACT_VALUE_OBJECT,
            self::ABSTRACT_DATA,
            self::ABSTRACT_TYPED_COLLECTION,
            self::ABSTRACT_DATA_OBJECT,
        ]);
    }

    public function isEnum(): bool
    {
        return $this === self::UNIT_ENUM;
    }

    public function isRecord(): bool
    {
        return $this === self::ABSTRACT_RECORD;
    }

    public function isValueObject(): bool
    {
        return $this === self::ABSTRACT_VALUE_OBJECT;
    }

    public function isData(): bool
    {
        return $this === self::ABSTRACT_DATA;
    }

    public function isCollection(): bool
    {
        return $this === self::ABSTRACT_TYPED_COLLECTION;
    }

    public function isDataObject(): bool
    {
        return $this === self::ABSTRACT_DATA_OBJECT;
    }

    public function isDomainAbstractType(): bool
    {
        return in_array($this, [
            self::ABSTRACT_RECORD,
            self::ABSTRACT_VALUE_OBJECT,
            self::ABSTRACT_DATA,
            self::ABSTRACT_TYPED_COLLECTION,
        ]);
    }

    /**
     * Create PhpType from a PHP value.
     */
    public static function fromValue(mixed $value): self
    {
        // Domain-specific types first
        if ($value instanceof UnitEnum) {
            return self::UNIT_ENUM;
        }

        if ($value instanceof AbstractRecord) {
            return self::ABSTRACT_RECORD;
        }

        if ($value instanceof AbstractValueObject) {
            return self::ABSTRACT_VALUE_OBJECT;
        }

        if ($value instanceof AbstractData) {
            return self::ABSTRACT_DATA;
        }

        if ($value instanceof AbstractTypedCollection) {
            return self::ABSTRACT_TYPED_COLLECTION;
        }

        if ($value instanceof DataObject) {
            return self::ABSTRACT_DATA_OBJECT;
        }

        // Scalar types
        $type = gettype($value);

        return match ($type) {
            'integer' => self::INTEGER,
            'double' => self::DOUBLE,
            'string' => self::STRING,
            'boolean' => self::BOOLEAN,
            'NULL' => self::NULL,
            default => throw new \InvalidArgumentException(sprintf(
                'Unsupported PHP type "%s". Only scalars (int, string, float, bool, null) and domain objects are supported.',
                $type
            )),
        };
    }

    /**
     * Get the normalized type name from a PHP value.
     */
    public static function getNormalizedTypeName(mixed $value): string
    {
        return self::fromValue($value)->getNormalizedName();
    }

    /**
     * Get all scalar type enum cases.
     *
     * @return array<self>
     */
    public static function getScalarTypes(): array
    {
        return [self::INTEGER, self::DOUBLE, self::STRING, self::BOOLEAN, self::NULL];
    }

    /**
     * Get all numeric type enum cases.
     *
     * @return array<self>
     */
    public static function getNumericTypes(): array
    {
        return [self::INTEGER, self::DOUBLE];
    }

    /**
     * Get the scalar type names for validation.
     *
     * @return array<string>
     */
    public static function getScalarTypeNames(): array
    {
        return array_map(fn($type) => $type->getNormalizedName(), self::getScalarTypes());
    }

    /**
     * Get all abstract types (for validation).
     *
     * @return array<string>
     */
    public static function getAbstractTypes(): array
    {
        return [
            UnitEnum::class,
            AbstractRecord::class,
            AbstractValueObject::class,
            AbstractData::class,
            AbstractTypedCollection::class,
            AbstractDataObject::class,
        ];
    }

    /**
     * Get a human-readable description of allowed types.
     */
    public static function getAllowedTypeDescription(): string
    {
        return 'scalar (int, string, float, bool, null), Enum, Record, ValueObject, Data, TypedCollection, or DataObject';
    }

    /**
     * Get all allowed types that can be stored in a collection.
     *
     * @return array<string>
     */
    public static function getAllowedTypesList(): array
    {
        return [
            self::INTEGER->getNormalizedName(),
            self::STRING->getNormalizedName(),
            self::DOUBLE->getNormalizedName(),
            self::BOOLEAN->getNormalizedName(),
            self::NULL->getNormalizedName(),
            self::UNIT_ENUM->getNormalizedName(),
            self::ABSTRACT_RECORD->getClassString(),
            self::ABSTRACT_VALUE_OBJECT->getClassString(),
            self::ABSTRACT_DATA->getClassString(),
            self::ABSTRACT_TYPED_COLLECTION->getClassString(),
            self::ABSTRACT_DATA_OBJECT->getClassString(),
        ];
    }

    public static function isValidType(string $type): bool
    {
        // Check scalar types
        if (in_array($type, self::getScalarTypeNames(), true)) {
            return true;
        }

        // Vérifier que la classe existe
        if (!class_exists($type)) {
            return false;
        }

        // REFUSER les classes abstraites
        if ((new \ReflectionClass($type))->isAbstract()) {
            return false;
        }

        // Check subclasses valides (concrètes uniquement)
        return is_subclass_of($type, UnitEnum::class) ||
            is_subclass_of($type, AbstractRecord::class) ||
            is_subclass_of($type, AbstractValueObject::class) ||
            is_subclass_of($type, AbstractData::class) ||
            is_subclass_of($type, AbstractTypedCollection::class) ||
            is_subclass_of($type, AbstractDataObject::class);

        return false;
    }

    /**
     * Get the corresponding PhpType from a type string or class name.
     *
     * @throws \InvalidArgumentException
     */
    public static function fromTypeString(string $type): self
    {
        // Scalar types
        $scalarMapping = [
            'int' => self::INTEGER,
            'string' => self::STRING,
            'float' => self::DOUBLE,
            'bool' => self::BOOLEAN,
            'null' => self::NULL,
        ];

        if (isset($scalarMapping[$type])) {
            return $scalarMapping[$type];
        }

        // Domain abstract classes
        $classMapping = [
            UnitEnum::class => self::UNIT_ENUM,
            AbstractRecord::class => self::ABSTRACT_RECORD,
            AbstractValueObject::class => self::ABSTRACT_VALUE_OBJECT,
            AbstractData::class => self::ABSTRACT_DATA,
            AbstractTypedCollection::class => self::ABSTRACT_TYPED_COLLECTION,
            AbstractDataObject::class => self::ABSTRACT_DATA_OBJECT,
        ];

        if (isset($classMapping[$type])) {
            return $classMapping[$type];
        }

        // Subclasses
        if (class_exists($type)) {
            if (is_subclass_of($type, UnitEnum::class)) {
                return self::UNIT_ENUM;
            }
            if (is_subclass_of($type, AbstractRecord::class)) {
                return self::ABSTRACT_RECORD;
            }
            if (is_subclass_of($type, AbstractValueObject::class)) {
                return self::ABSTRACT_VALUE_OBJECT;
            }
            if (is_subclass_of($type, AbstractData::class)) {
                return self::ABSTRACT_DATA;
            }
            if (is_subclass_of($type, AbstractTypedCollection::class)) {
                return self::ABSTRACT_TYPED_COLLECTION;
            }
        }

        throw new \InvalidArgumentException(sprintf('Unknown type: %s', $type));
    }
}
