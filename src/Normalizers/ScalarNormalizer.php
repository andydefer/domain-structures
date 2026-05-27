<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Normalizers;

use AndyDefer\DomainStructures\Normalizers\Core\AbstractNormalizer;

final class ScalarNormalizer extends AbstractNormalizer
{
    public function supports(mixed $value): bool
    {
        return is_scalar($value);
    }

    public function normalize(mixed $value): mixed
    {
        if (!is_scalar($value)) {
            return $this->next($value);
        }

        return $value;
    }
}
