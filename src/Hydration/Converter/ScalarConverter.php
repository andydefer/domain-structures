<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Hydration\Converter;

use AndyDefer\DomainStructures\Enums\PhpType;
use InvalidArgumentException;

final class ScalarConverter implements TypeConverterInterface
{
    public function supports(string $typeName): bool
    {
        try {
            // Normaliser les alias de types
            $normalizedType = $this->normalizeTypeName($typeName);
            $phpType = PhpType::fromTypeString($normalizedType);

            return $phpType->isScalarOrNull();
        } catch (InvalidArgumentException) {
            // Le type n'est pas reconnu par PhpType (ex: 'array', 'object', 'resource', 'callable')
            return false;
        }
    }

    public function convert(mixed $value, string $typeName, string $paramName): mixed
    {
        // Normaliser le type cible
        $normalizedType = $this->normalizeTypeName($typeName);

        return match ($normalizedType) {
            'int' => $this->toInt($value, $paramName),
            'float' => $this->toFloat($value, $paramName),
            'string' => $this->toString($value, $paramName),
            'bool' => $this->toBool($value, $paramName),
            'null' => null,
            default => throw new InvalidArgumentException(
                sprintf('Cannot cast to scalar type %s for parameter $%s', $typeName, $paramName)
            ),
        };
    }

    /**
     * Normalize type aliases to their short form.
     */
    private function normalizeTypeName(string $typeName): string
    {
        return match ($typeName) {
            'integer' => 'int',
            'double' => 'float',
            'boolean' => 'bool',
            default => $typeName,
        };
    }

    private function toInt(mixed $value, string $paramName): int
    {
        // Gérer les booléens
        if (is_bool($value)) {
            return $value ? 1 : 0;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        throw new InvalidArgumentException(
            sprintf('Cannot convert value to int for parameter $%s', $paramName)
        );
    }

    private function toFloat(mixed $value, string $paramName): float
    {
        // Gérer les booléens
        if (is_bool($value)) {
            return $value ? 1.0 : 0.0;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        throw new InvalidArgumentException(
            sprintf('Cannot convert value to float for parameter $%s', $paramName)
        );
    }

    private function toString(mixed $value, string $paramName): string
    {
        if ($value === null) {
            throw new InvalidArgumentException(
                sprintf('Cannot convert null to string for parameter $%s', $paramName)
            );
        }

        // Vérifier d'abord si c'est un scalaire
        if (is_scalar($value)) {
            return (string) $value;
        }

        // Ensuite vérifier si c'est un objet avec __toString
        if (is_object($value) && method_exists($value, '__toString')) {
            return (string) $value;
        }

        throw new InvalidArgumentException(
            sprintf('Cannot convert value to string for parameter $%s', $paramName)
        );
    }

    private function toBool(mixed $value, string $paramName): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (bool) $value;
        }

        if (is_string($value)) {
            return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
        }

        throw new InvalidArgumentException(
            sprintf('Cannot convert value to bool for parameter $%s', $paramName)
        );
    }
}
