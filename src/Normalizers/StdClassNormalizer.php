<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Normalizers;

use AndyDefer\DomainStructures\Enums\NormalizeMode;
use stdClass;

final class StdClassNormalizer extends AbstractNormalizer
{
    public function supports(mixed $value): bool
    {
        return $value instanceof stdClass;
    }

    public function normalize(mixed $value, NormalizeMode $mode = NormalizeMode::ARRAY, bool $includeNulls = true): mixed
    {
        if (! $value instanceof stdClass) {
            return $this->next($value, $mode, $includeNulls);
        }

        $result = [];
        foreach (get_object_vars($value) as $key => $propValue) {
            $normalized = $this->next($propValue, $mode, $includeNulls);
            if (! $includeNulls && $normalized === null) {
                continue;
            }
            $result[$key] = $normalized;
        }

        return $result;
    }
}
