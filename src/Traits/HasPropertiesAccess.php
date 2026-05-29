<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Traits;

use AndyDefer\DomainStructures\Normalizers\NormalizerChain;
use AndyDefer\DomainStructures\Utils\DataObject;
use AndyDefer\DomainStructures\Enums\PhpType;
use AndyDefer\DomainStructures\Interfaces\Transformable;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionProperty;
use RuntimeException;

/**
 * Trait for Value Objects that want to expose properties via magic __get().
 * 
 * Uses reflection to properly reconstruct properties (scalars, enums, Transformable objects).
 * 
 * @example
 * final class Money extends AbstractValueObject
 * {
 *     use HasPropertiesAccess;
 *     
 *     private function __construct(Amount $amount, Currency $currency)
 *     {
 *         $this->amount = $amount;
 *         $this->currency = $currency;
 *     }
 * }
 * 
 * // Usage
 * $money = Money::from(['amount' => 100, 'currency' => 'EUR']);
 * echo $money->amount->getValue(); // "100.00"
 * echo $money->currency->getSymbol(); // "€"
 */
trait HasPropertiesAccess
{
    /**
     * Magic getter for accessing properties.
     * Reconstructs the original object from flattened data.
     * 
     * @param string $name Property name
     * @return mixed
     * @throws InvalidArgumentException
     */
    public function __get(string $name): mixed
    {
        // Get flattened data
        $flatData = NormalizerChain::get()->normalize($this);

        // Check if property exists in flattened data
        if (!isset($flatData[$name])) {
            throw new InvalidArgumentException(
                sprintf('Property "%s" does not exist in %s', $name, static::class)
            );
        }

        $rawValue = $flatData[$name];

        // Get property type via reflection
        $property = $this->getPropertyType($name);

        if ($property === null) {
            return $rawValue;
        }

        return $this->reconstructValue($rawValue, $property, $name);
    }

    /**
     * Check if a property exists.
     * 
     * @param string $name
     * @return bool
     */
    public function __isset(string $name): bool
    {
        $flatData = NormalizerChain::get()->normalize($this);

        return isset($flatData[$name]);
    }

    /**
     * Get property type using reflection.
     */
    private function getPropertyType(string $propertyName): ?ReflectionNamedType
    {
        try {
            $reflection = new ReflectionClass($this);

            if (!$reflection->hasProperty($propertyName)) {
                return null;
            }

            $property = $reflection->getProperty($propertyName);
            $type = $property->getType();

            if ($type instanceof ReflectionNamedType) {
                return $type;
            }

            return null;
        } catch (RuntimeException) {
            return null;
        }
    }

    /**
     * Reconstruct value based on its type.
     */
    private function reconstructValue(mixed $rawValue, ReflectionNamedType $type, string $propertyName): mixed
    {
        $typeName = $type->getName();

        // If value is already of the correct type
        if ($rawValue instanceof $typeName) {
            return $rawValue;
        }

        // Scalar types - return as is
        if (PhpType::fromTypeString($typeName)->isScalar()) {
            return $rawValue;
        }

        // Enum - use ::from()
        if (PhpType::fromTypeString($typeName)->isEnum()) {
            if (method_exists($typeName, 'from')) {
                return $typeName::from($rawValue);
            }
            throw new InvalidArgumentException(
                sprintf('Cannot convert value to enum %s for property $%s', $typeName, $propertyName)
            );
        }

        // Transformable - call ::from()
        if (is_subclass_of($typeName, Transformable::class)) {
            return $typeName::from($rawValue);
        }

        // Unknown type - return raw value
        return $rawValue;
    }
}
