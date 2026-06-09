<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Hydration\Strategy;

use BackedEnum;
use InvalidArgumentException;
use ReflectionEnum;
use UnitEnum;

/**
 * Hydration strategy for PHP 8.1+ enums.
 *
 * Converts scalars, arrays, or objects into enum instances.
 * Supports both backed enums (int/string) and pure enums.
 *
 * @example
 * $strategy = new EnumStrategy();
 * $status = $strategy->hydrate(Status::class, 'active'); // Status::active
 * $role = $strategy->hydrate(Role::class, 1); // Role::Admin (if int-backed)
 */
final class EnumStrategy implements HydrationStrategyInterface
{
    /**
     * Checks if the strategy supports the given class name.
     *
     * @param  string  $className  Fully qualified class name to check
     * @param  mixed  $source  The source data (not used for support check)
     * @return bool True if the class is an enum
     */
    public function supports(string $className, mixed $source): bool
    {
        return enum_exists($className);
    }

    /**
     * Hydrates an enum from various source types.
     *
     * @param  string  $className  Fully qualified enum class name
     * @param  mixed  $source  Source data (scalar, array, object, or existing enum)
     * @return object The hydrated enum instance
     *
     * @throws InvalidArgumentException When hydration fails
     */
    public function hydrate(string $className, mixed $source): object
    {
        if ($this->isAlreadyEnumInstance($className, $source)) {
            return $source;
        }

        return match (true) {
            is_scalar($source) => $this->hydrateFromScalar($className, $source),
            is_array($source) => $this->hydrateFromArray($className, $source),
            is_object($source) => $this->hydrateFromObject($className, $source),
            default => throw new InvalidArgumentException(sprintf(
                'Cannot hydrate enum %s from source type: %s',
                $className,
                gettype($source)
            )),
        };
    }

    /**
     * Checks if the source is already the correct enum instance.
     */
    private function isAlreadyEnumInstance(string $className, mixed $source): bool
    {
        return is_object($source) && $source instanceof $className;
    }

    /**
     * Hydrates an enum from a scalar value (int or string).
     */
    private function hydrateFromScalar(string $className, int|string $source): UnitEnum|BackedEnum
    {
        if (is_subclass_of($className, BackedEnum::class)) {
            return $this->hydrateBackedEnum($className, $source);
        }

        return $this->hydratePureEnum($className, (string) $source);
    }

    /**
     * Hydrates a backed enum (int or string backed).
     */
    private function hydrateBackedEnum(string $className, int|string $source): BackedEnum
    {
        $normalizedValue = $this->normalizeBackingValue($className, $source);
        $enum = $className::tryFrom($normalizedValue);

        if ($enum !== null) {
            return $enum;
        }

        throw new InvalidArgumentException(sprintf(
            'Invalid value "%s" for enum %s',
            $source,
            $className
        ));
    }

    /**
     * Normalizes the source value to match the enum's backing type.
     */
    private function normalizeBackingValue(string $className, int|string $source): int|string
    {
        $reflection = new ReflectionEnum($className);
        $backingType = $reflection->getBackingType();

        if ($backingType === null) {
            return $source;
        }

        $expectedType = $backingType->getName();

        if ($expectedType === 'int' && is_string($source) && is_numeric($source)) {
            return (int) $source;
        }

        return $source;
    }

    /**
     * Hydrates a pure enum (no backing type, uses case names).
     */
    private function hydratePureEnum(string $className, string $caseName): UnitEnum
    {
        $constantName = $className . '::' . $caseName;

        if (defined($constantName)) {
            return constant($constantName);
        }

        throw new InvalidArgumentException(sprintf(
            'Invalid value "%s" for enum %s',
            $caseName,
            $className
        ));
    }

    /**
     * Hydrates an enum from an array structure.
     *
     * Expects array with either 'value' (for backed enums) or 'name' (for pure enums) key.
     */
    private function hydrateFromArray(string $className, array $source): UnitEnum|BackedEnum
    {
        if (array_key_exists('value', $source)) {
            return $this->hydrate($className, $source['value']);
        }

        if (array_key_exists('name', $source)) {
            return $this->hydrate($className, $source['name']);
        }

        throw new InvalidArgumentException(sprintf(
            'Cannot hydrate enum %s from array without "value" or "name" key',
            $className
        ));
    }

    /**
     * Hydrates an enum from an object structure.
     *
     * Expects object with either 'value' (for backed enums) or 'name' (for pure enums) property.
     */
    private function hydrateFromObject(string $className, object $source): UnitEnum|BackedEnum
    {
        if (property_exists($source, 'value')) {
            return $this->hydrate($className, $source->value);
        }

        if (property_exists($source, 'name')) {
            return $this->hydrate($className, $source->name);
        }

        throw new InvalidArgumentException(sprintf(
            'Cannot hydrate enum %s from object without "value" or "name" property',
            $className
        ));
    }
}
