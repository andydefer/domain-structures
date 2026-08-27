<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Hydration\Converter;

use AndyDefer\DomainStructures\Interfaces\Fromable;
use AndyDefer\DomainStructures\Normalizers\NormalizerChain;

final class TransformableConverter implements TypeConverterInterface
{
    public function supports(string $typeName): bool
    {
        return is_subclass_of($typeName, Fromable::class);
    }

    public function convert(mixed $value, string $typeName, string $paramName): mixed
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof $typeName) {
            return $value;
        }

        // Normaliser uniquement si c'est un Transformable
        if ($value instanceof Fromable) {
            $flattened = NormalizerChain::get()->normalize($value);

            return $typeName::from($flattened);
        }

        return $typeName::from($value);
    }
}
