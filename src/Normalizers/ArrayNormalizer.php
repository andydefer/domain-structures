<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Normalizers;

use AndyDefer\DomainStructures\Enums\NormalizeMode;

final class ArrayNormalizer extends AbstractNormalizer
{
    public function supports(mixed $value): bool
    {
        return is_array($value);
    }

    public function normalize(mixed $value, NormalizeMode $mode = NormalizeMode::ARRAY, bool $includeNulls = true): mixed
    {
        if (! is_array($value)) {
            return $this->next($value, $mode, $includeNulls);
        }

        $result = [];
        foreach ($value as $key => $item) {
            $normalized = $this->next($item, $mode, $includeNulls);
            if (! $includeNulls && $normalized === null) {
                continue;
            }
            $result[$key] = $normalized;
        }

        return $result;
    }
}
