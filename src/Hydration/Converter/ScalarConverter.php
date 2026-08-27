<?php

// src/Hydration/Converter/ScalarConverter.php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Hydration\Converter;

use AndyDefer\DomainStructures\Enums\PhpType;
use InvalidArgumentException;

final class ScalarConverter implements TypeConverterInterface
{
    public function supports(string $typeName): bool
    {
        try {
            $normalizedType = $this->normalizeTypeName($typeName);
            $phpType = PhpType::fromTypeString($normalizedType);

            return $phpType->isScalarOrNull();
        } catch (InvalidArgumentException) {
            return false;
        }
    }

    public function convert(mixed $value, string $typeName, string $paramName): mixed
    {
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

    private function normalizeTypeName(string $typeName): string
    {
        return match ($typeName) {
            'integer' => 'int',
            'double' => 'float',
            'boolean' => 'bool',
            default => $typeName,
        };
    }

    /**
     * Check if the value is an object that has a 'from' method and a 'value' property.
     *
     * @param  mixed  $value  The value to check
     * @return bool True if the object has 'from' method and 'value' property
     */
    private function isFromableWithValue(mixed $value): bool
    {
        if (! is_object($value)) {
            return false;
        }

        if (! property_exists($value, 'value')) {
            return false;
        }

        return method_exists($value, 'from');
    }

    /**
     * Check if the value is numeric (for int/float conversion).
     *
     * @param  mixed  $value  The value to check
     * @return bool True if the value is numeric
     */
    private function isNumericValue(mixed $value): bool
    {
        if (is_numeric($value)) {
            return true;
        }

        if ($this->isFromableWithValue($value) && is_numeric($value->value)) {
            return true;
        }

        return false;
    }

    private function toInt(mixed $value, string $paramName): int
    {
        if (is_bool($value)) {
            return $value ? 1 : 0;
        }

        if ($this->isNumericValue($value)) {
            return (int) ($this->isFromableWithValue($value) ? $value->value : $value);
        }

        throw new InvalidArgumentException(
            sprintf('Cannot convert value to int for parameter $%s', $paramName)
        );
    }

    private function toFloat(mixed $value, string $paramName): float
    {
        if (is_bool($value)) {
            return $value ? 1.0 : 0.0;
        }

        if ($this->isNumericValue($value)) {
            return (float) ($this->isFromableWithValue($value) ? $value->value : $value);
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

        if (is_scalar($value)) {
            return (string) $value;
        }

        if (is_object($value) && method_exists($value, '__toString')) {
            return (string) $value;
        }

        if ($this->isFromableWithValue($value)) {
            return (string) $value->value;
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

        if (is_string($value)) {
            return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
        }

        if ($this->isFromableWithValue($value)) {
            if (is_string($value->value)) {
                return filter_var($value->value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
            }

            if (is_bool($value->value)) {
                return $value->value;
            }

            if (is_numeric($value->value)) {
                return (bool) $value->value;
            }
        }

        if (is_numeric($value)) {
            return (bool) $value;
        }

        throw new InvalidArgumentException(
            sprintf('Cannot convert value to bool for parameter $%s', $paramName)
        );
    }
}
