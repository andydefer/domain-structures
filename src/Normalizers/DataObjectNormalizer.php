<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Normalizers;

use AndyDefer\DomainStructures\Abstracts\AbstractDataObject;
use AndyDefer\DomainStructures\Normalizers\Core\AbstractNormalizer;

final class DataObjectNormalizer extends AbstractNormalizer
{
    public function supports(mixed $value): bool
    {
        return $value instanceof AbstractDataObject;
    }

    public function normalize(mixed $value): mixed
    {
        if (! $value instanceof AbstractDataObject) {
            return $this->next($value);
        }

        $result = [];
        foreach ($value->toArray() as $key => $propValue) {
            $result[$key] = $this->next($propValue);
        }

        return $result;
    }
}
