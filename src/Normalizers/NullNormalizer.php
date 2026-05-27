<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Normalizers;

use AndyDefer\DomainStructures\Normalizers\Core\AbstractNormalizer;

final class NullNormalizer extends AbstractNormalizer
{
    public function supports(mixed $value): bool
    {
        return $value === null;
    }

    public function normalize(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        return $this->next($value);
    }
}
