<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Hydration\Converter;

use AndyDefer\DomainStructures\Enums\PhpType;
use AndyDefer\DomainStructures\Interfaces\Transformable;
use AndyDefer\DomainStructures\Normalizers\NormalizerChain;
use InvalidArgumentException;

final class TransformableConverter implements TypeConverterInterface
{
    public function supports(string $typeName): bool
    {
        return is_subclass_of($typeName, Transformable::class);
    }

    public function convert(mixed $value, string $typeName, string $paramName): mixed
    {
        if (is_object($value)) {
            $flattened = NormalizerChain::get()->normalize($value);
            return $typeName::from($flattened);
        }

        return $typeName::from($value);
    }
}
