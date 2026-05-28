<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Traits;

use AndyDefer\DomainStructures\Enums\PhpType;
use AndyDefer\DomainStructures\Interfaces\Transformable;
use AndyDefer\DomainStructures\Utils\DataObject;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionUnionType;
use RuntimeException;

/**
 * Trait for automatic hydration of objects.
 *
 * Provides the from() method which analyzes the constructor and hydrates
 * the object automatically.
 */
trait Hydratable
{
    private const VALUE_ABSENT = '__ABSENT__';

    /**
     * Creates an instance from a source.
     *
     * @param  mixed  $source  The source data (array, object, DataObject)
     *
     * @throws RuntimeException
     */
    public static function from(mixed $source): static
    {
        // Normalize source to DataObject (handles camelCase/snake_case)
        $dataObject = DataObject::from($source);

        // Analyze the constructor
        $reflection = new ReflectionClass(static::class);
        $constructor = $reflection->getConstructor();

        if (! $constructor) {
            throw new RuntimeException(sprintf('%s must have a constructor', static::class));
        }

        $parameters = [];

        foreach ($constructor->getParameters() as $parameter) {
            $paramName = $parameter->getName();
            $paramType = $parameter->getType();

            // Extract raw value from DataObject (or VALUE_ABSENT if key doesn't exist)
            $rawValue = self::getValueFromDataObject($dataObject, $paramName);
            $isAbsent = $rawValue === self::VALUE_ABSENT;

            // Convert value only if present (null is a valid value)
            $value = $isAbsent ? null : self::convertToType($rawValue, $paramType, $paramName);

            // CASE 1: Value is ABSENT (source doesn't have the key) -> use default value
            if ($isAbsent && $parameter->isDefaultValueAvailable()) {
                $parameters[] = $parameter->getDefaultValue();

                continue;
            }

            // CASE 2: Explicit NULL (source has key with null value) -> keep null
            if ($value === null && $parameter->allowsNull()) {
                $parameters[] = null;

                continue;
            }

            // CASE 3: Normal value (not null) -> use it
            if ($value !== null) {
                $parameters[] = $value;

                continue;
            }

            // CASE 4: Missing required parameter (no default, not nullable, and value is null)
            throw new RuntimeException(sprintf(
                'Missing required parameter "$%s" for %s::__construct',
                $paramName,
                static::class
            ));
        }

        return new static(...$parameters);
    }

    /**
     * Creates an instance from a JSON string.
     *
     * @param  string  $json  JSON string
     *
     * @throws RuntimeException If JSON is invalid
     */
    public static function fromJson(string $json): static
    {
        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException(sprintf(
                'Invalid JSON: %s',
                json_last_error_msg()
            ));
        }

        return static::from($data);
    }

    /**
     * Hydrates a collection of sources.
     *
     * @param  iterable<mixed>  $sources
     * @return array<static>
     */
    public static function collect(iterable $sources): array
    {
        $results = [];
        foreach ($sources as $source) {
            $results[] = static::from($source);
        }

        return $results;
    }

    /**
     * Gets a value from DataObject.
     * Returns VALUE_ABSENT sentinel if the key doesn't exist.
     */
    private static function getValueFromDataObject(DataObject $dataObject, string $paramName): mixed
    {
        if ($dataObject->has($paramName)) {
            return $dataObject->get($paramName);
        }

        return self::VALUE_ABSENT;
    }

    /**
     * Converts raw value to expected parameter type.
     *
     * @throws RuntimeException
     */
    private static function convertToType(
        mixed $rawValue,
        ?\ReflectionType $paramType,
        string $paramName
    ): mixed {
        if ($paramType === null || $rawValue === null) {
            return $rawValue;
        }

        // Union types
        if ($paramType instanceof ReflectionUnionType) {
            foreach ($paramType->getTypes() as $type) {
                if ($type instanceof ReflectionNamedType) {
                    try {
                        return self::convertToNamedType($rawValue, $type, $paramName);
                    } catch (RuntimeException) {
                        continue;
                    }
                }
            }
            throw new RuntimeException(sprintf(
                'Unable to convert value for parameter $%s: no matching union type',
                $paramName
            ));
        }

        // Named types
        if ($paramType instanceof ReflectionNamedType) {
            return self::convertToNamedType($rawValue, $paramType, $paramName);
        }

        return $rawValue;
    }

    /**
     * Converts to a named type using PhpType.
     *
     * @throws RuntimeException
     */
    private static function convertToNamedType(
        mixed $rawValue,
        ReflectionNamedType $type,
        string $paramName
    ): mixed {
        $typeName = $type->getName();
        $phpType = PhpType::fromTypeString($typeName);

        // If value is already of the correct type, return it directly
        if ($rawValue instanceof $typeName) {
            return $rawValue;
        }

        // Scalar types - explicit conversion
        if ($phpType->isScalar()) {
            return match ($typeName) {
                'int' => self::toInt($rawValue, $paramName),
                'float' => self::toFloat($rawValue, $paramName),
                'string' => self::toString($rawValue, $paramName),
                'bool' => self::toBool($rawValue, $paramName),
                'null' => null,
                default => $rawValue,
            };
        }

        // Enums (BackedEnum) - native conversion via tryFrom()
        if ($phpType->isEnum()) {
            $enum = $typeName::tryFrom($rawValue);
            if ($enum !== null) {
                return $enum;
            }
            throw new RuntimeException(sprintf(
                'Invalid value "%s" for enum %s (parameter $%s)',
                $rawValue,
                $typeName,
                $paramName
            ));
        }

        // Transformable - call from() recursively
        if (is_subclass_of($typeName, Transformable::class)) {
            return $typeName::from($rawValue);
        }

        throw new RuntimeException(sprintf(
            'Cannot convert value for parameter $%s: type %s does not implement Transformable',
            $paramName,
            $typeName
        ));
    }

    /**
     * Converts a value to integer.
     *
     * @throws RuntimeException
     */
    private static function toInt(mixed $rawValue, string $paramName): int
    {
        if (is_numeric($rawValue)) {
            return (int) $rawValue;
        }
        throw new RuntimeException(sprintf(
            'Cannot convert value to int for parameter $%s',
            $paramName
        ));
    }

    /**
     * Converts a value to float.
     *
     * @throws RuntimeException
     */
    private static function toFloat(mixed $rawValue, string $paramName): float
    {
        if (is_numeric($rawValue)) {
            return (float) $rawValue;
        }
        throw new RuntimeException(sprintf(
            'Cannot convert value to float for parameter $%s',
            $paramName
        ));
    }

    /**
     * Converts a value to string.
     *
     * @throws RuntimeException
     */
    private static function toString(mixed $rawValue, string $paramName): string
    {
        if (is_scalar($rawValue) || method_exists($rawValue, '__toString')) {
            return (string) $rawValue;
        }
        throw new RuntimeException(sprintf(
            'Cannot convert value to string for parameter $%s',
            $paramName
        ));
    }

    /**
     * Converts a value to boolean.
     */
    private static function toBool(mixed $rawValue, string $paramName): bool
    {
        return filter_var($rawValue, FILTER_VALIDATE_BOOLEAN);
    }
}
