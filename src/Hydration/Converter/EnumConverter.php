<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Hydration\Converter;

use AndyDefer\DomainStructures\Enums\PhpType;
use AndyDefer\DomainStructures\Hydration\Hydrator;
use InvalidArgumentException;

final class EnumConverter implements TypeConverterInterface
{
    public function supports(string $typeName): bool
    {
        try {
            return PhpType::fromTypeString($typeName)->isEnum();
        } catch (InvalidArgumentException) {
            return false;
        }
    }

    public function convert(mixed $value, string $typeName, string $paramName): mixed
    {
        return Hydrator::hydrate($typeName, $value);
    }
}
