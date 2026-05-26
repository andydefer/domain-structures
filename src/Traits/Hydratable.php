<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Traits;

use AndyDefer\DomainStructures\Abstracts\AbstractData;
use AndyDefer\DomainStructures\Abstracts\AbstractRecord;
use AndyDefer\DomainStructures\Abstracts\AbstractTypedCollection;
use AndyDefer\DomainStructures\Abstracts\AbstractValueObject;
use AndyDefer\DomainStructures\Normalizers\NormalizerChain;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionUnionType;
use RuntimeException;

/**
 * Trait for Hydratable objects to enable creation from any source.
 *
 * Allows creating instances from:
 * - AbstractRecord
 * - AbstractValueObject
 * - AbstractData
 * - stdClass
 * - Any object with matching properties
 */
trait Hydratable
{
    /**
     * Create an instance from any source object.
     *
     * @param  object  $source  The source object (Record, ValueObject, Data, stdClass, etc.)
     *
     * @throws InvalidArgumentException If source is invalid
     * @throws RuntimeException If a required property is missing or type mismatch
     */
    public static function from(object $source): static
    {
        if (! is_object($source)) {
            throw new InvalidArgumentException('Source must be an object');
        }

        $reflection = new ReflectionClass(static::class);
        $constructor = $reflection->getConstructor();

        if (! $constructor) {
            throw new RuntimeException(sprintf('%s must have a constructor', static::class));
        }

        $parameters = [];
        $missingProperties = [];
        $typeMismatches = [];

        foreach ($constructor->getParameters() as $parameter) {
            $paramName = $parameter->getName();
            $paramType = $parameter->getType();
            $isNullable = $parameter->allowsNull();
            $hasDefault = $parameter->isDefaultValueAvailable();

            try {
                $value = self::extractValueFromSource($source, $paramName, $paramType);

                if ($value === null && ! $isNullable && ! $hasDefault) {
                    $missingProperties[] = $paramName;

                    continue;
                }

                if ($value !== null && ! self::isCompatibleType($value, $paramType)) {
                    $expectedType = self::getTypeName($paramType);
                    $actualType = is_object($value) ? $value::class : gettype($value);
                    $typeMismatches[] = sprintf(
                        '%s: expected %s, got %s',
                        $paramName,
                        $expectedType,
                        $actualType
                    );

                    continue;
                }

                if ($value === null && $hasDefault) {
                    $value = $parameter->getDefaultValue();
                }

                $parameters[$paramName] = $value;
            } catch (\Exception $e) {
                throw new RuntimeException(
                    sprintf('Failed to extract property "%s": %s', $paramName, $e->getMessage()),
                    0,
                    $e
                );
            }
        }

        if (! empty($missingProperties)) {
            throw new RuntimeException(sprintf(
                'Missing required properties: %s. Source type: %s',
                implode(', ', $missingProperties),
                $source::class
            ));
        }

        if (! empty($typeMismatches)) {
            throw new RuntimeException(sprintf(
                'Type mismatches: %s. Source type: %s',
                implode('; ', $typeMismatches),
                $source::class
            ));
        }

        return new static(...$parameters);
    }

    /**
     * Create an array of instances from a TypedCollection.
     *
     * @param  AbstractTypedCollection<object>  $collection
     * @return array<int, static>
     */
    public static function collect(AbstractTypedCollection $collection): array
    {
        $result = [];

        foreach ($collection->all() as $item) {
            $result[] = static::from($item);
        }

        return $result;
    }

    /**
     * Extract a value from the source object using the NormalizerChain.
     *
     * @param  object  $source  The source object
     * @param  string  $paramName  The parameter name to look for
     * @param  \ReflectionType|null  $paramType  The expected parameter type
     */
    private static function extractValueFromSource(object $source, string $paramName, ?\ReflectionType $paramType): mixed
    {
        $reflection = new ReflectionClass($source);

        // Cas 1: Propriété directe
        if ($reflection->hasProperty($paramName)) {
            $property = $reflection->getProperty($paramName);
            $property->setAccessible(true);

            return $property->getValue($source);
        }

        // Cas 2: Getter
        $getterCandidates = [
            'get'.ucfirst($paramName),
            'is'.ucfirst($paramName),
            'has'.ucfirst($paramName),
        ];

        foreach ($getterCandidates as $getter) {
            if ($reflection->hasMethod($getter)) {
                $method = $reflection->getMethod($getter);
                if ($method->isPublic()) {
                    return $method->invoke($source);
                }
            }
        }

        // Cas 3: Value Object -> getValue()
        if ($source instanceof AbstractValueObject) {
            try {
                return $source->getValue();
            } catch (RuntimeException) {
                // VO multi-propriétés, continuer
            }
        }

        // Cas 4: Record/Data -> normalize() pour obtenir un array
        if ($source instanceof AbstractRecord || $source instanceof AbstractData) {
            $normalized = $source->normalize();
            if (is_array($normalized) && array_key_exists($paramName, $normalized)) {
                return $normalized[$paramName];
            }
        }

        // Cas 5: toArray() method
        if (method_exists($source, 'toArray')) {
            $array = $source->toArray();
            if (array_key_exists($paramName, $array)) {
                return $array[$paramName];
            }
        }

        // Cas 6: stdClass property
        if ($source instanceof \stdClass && property_exists($source, $paramName)) {
            return $source->$paramName;
        }

        return null;
    }

    /**
     * Check if a value is compatible with a given type.
     *
     * @param  mixed  $value  The value to check
     * @param  \ReflectionType|null  $paramType  The expected parameter type
     */
    private static function isCompatibleType(mixed $value, ?\ReflectionType $paramType): bool
    {
        if ($paramType === null) {
            return true;
        }

        if ($paramType instanceof ReflectionNamedType) {
            $typeName = $paramType->getName();

            // Enum handling
            if (is_subclass_of($typeName, \UnitEnum::class)) {
                if ($value instanceof $typeName) {
                    return true;
                }
                if ($value instanceof \BackedEnum && method_exists($typeName, 'tryFrom')) {
                    return $typeName::tryFrom($value->value) !== null;
                }

                return false;
            }

            // Scalar and object type checking
            return match ($typeName) {
                'int' => is_int($value),
                'string' => is_string($value),
                'float' => is_float($value),
                'bool' => is_bool($value),
                'array' => is_array($value),
                'null' => $value === null,
                'mixed' => true,
                default => $value instanceof $typeName,
            };
        }

        if ($paramType instanceof ReflectionUnionType) {
            foreach ($paramType->getTypes() as $type) {
                if (self::isCompatibleType($value, $type)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Get the string representation of a type.
     *
     * @param  \ReflectionType|null  $type  The reflection type
     */
    private static function getTypeName(?\ReflectionType $type): string
    {
        if ($type === null) {
            return 'mixed';
        }

        if ($type instanceof ReflectionNamedType) {
            return $type->getName();
        }

        if ($type instanceof ReflectionUnionType) {
            $names = array_map(fn ($t) => self::getTypeName($t), $type->getTypes());

            return implode('|', $names);
        }

        return 'unknown';
    }
}
