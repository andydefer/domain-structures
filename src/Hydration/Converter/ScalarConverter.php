<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Hydration\Converter;

use AndyDefer\DomainStructures\Enums\PhpType;
use InvalidArgumentException;

final class ScalarConverter implements TypeConverterInterface
{
    public function supports(string $typeName): bool
    {
        return PhpType::fromTypeString($typeName)->isScalar();
    }

    public function convert(mixed $value, string $typeName, string $paramName): mixed
    {
        return match ($typeName) {
            'int', 'integer' => $this->toInt($value, $paramName),
            'float', 'double' => $this->toFloat($value, $paramName),
            'string' => $this->toString($value, $paramName),
            'bool', 'boolean' => $this->toBool($value, $paramName),
            default => throw new InvalidArgumentException(
                sprintf('Cannot cast to scalar type %s for parameter $%s', $typeName, $paramName)
            ),
        };
    }

    private function toInt(mixed $value, string $paramName): int
    {
        if (is_numeric($value)) {
            return (int)$value;
        }
        throw new InvalidArgumentException(
            sprintf('Cannot convert value to int for parameter $%s', $paramName)
        );
    }

    private function toFloat(mixed $value, string $paramName): float
    {
        if (is_numeric($value)) {
            return (float)$value;
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

        if (is_scalar($value) || method_exists($value, '__toString')) {
            return (string)$value;
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
            return (bool)$value;
        }
        if (is_string($value)) {
            return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
        }
        throw new InvalidArgumentException(
            sprintf('Cannot convert value to bool for parameter $%s', $paramName)
        );
    }
}
