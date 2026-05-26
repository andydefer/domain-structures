<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Normalizers;

use AndyDefer\DomainStructures\Abstracts\AbstractRecord;
use AndyDefer\DomainStructures\Enums\NormalizeMode;

final class RecordNormalizer extends AbstractNormalizer
{
    public function supports(mixed $value): bool
    {
        return $value instanceof AbstractRecord;
    }

    public function normalize(mixed $value, NormalizeMode $mode = NormalizeMode::ARRAY, bool $includeNulls = true): mixed
    {
        if (! $value instanceof AbstractRecord) {
            return $this->next($value, $mode, $includeNulls);
        }

        return $value->normalize($includeNulls, NormalizeMode::ARRAY);
    }
}
