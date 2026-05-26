<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Normalizers;

use AndyDefer\DomainStructures\Abstracts\AbstractTypedCollection;
use AndyDefer\DomainStructures\Enums\NormalizeMode;

final class TypedCollectionNormalizer extends AbstractNormalizer
{
    public function supports(mixed $value): bool
    {
        return $value instanceof AbstractTypedCollection;
    }

    public function normalize(mixed $value, NormalizeMode $mode = NormalizeMode::ARRAY, bool $includeNulls = true): mixed
    {
        if (! $value instanceof AbstractTypedCollection) {
            return $this->next($value, $mode, $includeNulls);
        }

        $result = [];
        foreach ($value->all() as $item) {
            $normalized = $this->next($item, $mode, $includeNulls);
            if (! $includeNulls && $normalized === null) {
                continue;
            }
            $result[] = $normalized;
        }

        return $result;
    }
}
