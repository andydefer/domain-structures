<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Normalizers;

use AndyDefer\DomainStructures\Abstracts\AbstractSequential;
use AndyDefer\DomainStructures\Normalizers\Core\AbstractNormalizer;

final class SequentialNormalizer extends AbstractNormalizer
{
    public function supports(mixed $value): bool
    {
        return $value instanceof AbstractSequential;
    }

    public function normalize(mixed $value): mixed
    {
        if (! $value instanceof AbstractSequential) {
            return $this->next($value);
        }

        $result = [];
        foreach ($value->toArray() as $item) {
            $result[] = $this->next($item);
        }

        return $result;
    }
}
