<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Hydration\Converter;

interface TypeConverterInterface
{
    public function supports(string $typeName): bool;
    public function convert(mixed $value, string $typeName, string $paramName): mixed;
}
