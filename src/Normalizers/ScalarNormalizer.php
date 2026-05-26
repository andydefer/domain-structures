<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Normalizers;

use AndyDefer\DomainStructures\Enums\NormalizeMode;

final class ScalarNormalizer extends AbstractNormalizer
{
    public function supports(mixed $value): bool
    {
        return is_scalar($value);
    }

    public function normalize(mixed $value, NormalizeMode $mode = NormalizeMode::ARRAY, bool $includeNulls = true): mixed
    {
        return $value;
    }
}
