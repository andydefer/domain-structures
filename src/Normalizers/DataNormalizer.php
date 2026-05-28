<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Normalizers;

use AndyDefer\DomainStructures\Abstracts\AbstractData;
use AndyDefer\DomainStructures\Normalizers\Core\AbstractNormalizer;

final class DataNormalizer extends AbstractNormalizer
{
    public function supports(mixed $value): bool
    {
        return $value instanceof AbstractData;
    }

    public function normalize(mixed $value): mixed
    {
        if (! $value instanceof AbstractData) {
            return $this->next($value);
        }

        return $value->normalize();
    }
}
