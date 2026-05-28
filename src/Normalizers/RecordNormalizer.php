<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Normalizers;

use AndyDefer\DomainStructures\Abstracts\AbstractRecord;
use AndyDefer\DomainStructures\Normalizers\Core\AbstractNormalizer;

final class RecordNormalizer extends AbstractNormalizer
{
    public function supports(mixed $value): bool
    {
        return $value instanceof AbstractRecord;
    }

    public function normalize(mixed $value): mixed
    {
        if (! $value instanceof AbstractRecord) {
            return $this->next($value);
        }

        // AbstractRecord::normalize() retourne TOUJOURS un tableau
        // Le paramètre includeNulls est géré par le Record lui-même
        return $value->normalize(true);
    }
}
