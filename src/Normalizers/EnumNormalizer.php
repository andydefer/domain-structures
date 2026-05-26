<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Normalizers;

use AndyDefer\DomainStructures\Enums\NormalizeMode;
use UnitEnum;

final class EnumNormalizer extends AbstractNormalizer
{
    public function supports(mixed $value): bool
    {
        return $value instanceof UnitEnum;
    }

    public function normalize(mixed $value, NormalizeMode $mode = NormalizeMode::ARRAY, bool $includeNulls = true): mixed
    {
        if (! $value instanceof UnitEnum) {
            return $this->next($value, $mode, $includeNulls);
        }

        return $value instanceof \BackedEnum ? $value->value : $value->name;
    }
}
