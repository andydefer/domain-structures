<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Traits;

use AndyDefer\DomainStructures\Hydration\Hydrator;
use AndyDefer\DomainStructures\Normalizers\NormalizerChain;
use InvalidArgumentException;

/**
 * Trait for Value Objects that want to expose properties via magic __get().
 * 
 * Uses Hydrator to properly reconstruct objects from flattened data.
 * 
 * @example
 * final class Money extends AbstractValueObject
 * {
 *     use HasPropertiesAccess;
 *     
 *     public function __construct(
 *         private readonly Amount $amount,
 *         private readonly Currency $currency
 *     ) {
 *         if ($amount->isNegative()) {
 *             throw new InvalidArgumentException("Amount cannot be negative");
 *         }
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
     * Uses Hydrator to reconstruct the property from flattened data.
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

        $typeName = $property->getName();

        // Si la valeur est déjà du bon type
        if ($rawValue instanceof $typeName) {
            return $rawValue;
        }

        // Utiliser Hydrator pour reconstruire l'objet
        if (class_exists($typeName) && method_exists($typeName, 'from')) {
            return Hydrator::hydrate($typeName, $rawValue);
        }

        return $rawValue;
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
    private function getPropertyType(string $propertyName): ?\ReflectionNamedType
    {
        try {
            $reflection = new \ReflectionClass($this);

            if (!$reflection->hasProperty($propertyName)) {
                return null;
            }

            $property = $reflection->getProperty($propertyName);
            $type = $property->getType();

            if ($type instanceof \ReflectionNamedType) {
                return $type;
            }

            return null;
        } catch (\RuntimeException) {
            return null;
        }
    }
}
