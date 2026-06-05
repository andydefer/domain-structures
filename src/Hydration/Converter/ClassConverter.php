<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Hydration\Converter;

final class ClassConverter implements TypeConverterInterface
{
    public function supports(string $typeName): bool
    {
        return class_exists($typeName);
    }

    public function convert(mixed $value, string $typeName, string $paramName): mixed
    {
        return new $typeName($value);
    }
}
