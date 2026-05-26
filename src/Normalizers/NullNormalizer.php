<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Normalizers;

use AndyDefer\DomainStructures\Enums\NormalizeMode;

final class NullNormalizer extends AbstractNormalizer
{
    public function supports(mixed $value): bool
    {
        return $value === null;
    }

    public function normalize(mixed $value, NormalizeMode $mode = NormalizeMode::ARRAY, bool $includeNulls = true): mixed
    {
        return null;
    }
}
