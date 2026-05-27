<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Normalizers;

use AndyDefer\DomainStructures\Normalizers\Core\AbstractNormalizer;

final class ArrayNormalizer extends AbstractNormalizer
{
    public function supports(mixed $value): bool
    {
        return is_array($value);
    }

    public function normalize(mixed $value): mixed
    {
        if (!is_array($value)) {
            return $this->next($value);
        }

        $result = [];
        foreach ($value as $key => $item) {
            $result[$key] = $this->next($item);
        }

        return $result;
    }
}
