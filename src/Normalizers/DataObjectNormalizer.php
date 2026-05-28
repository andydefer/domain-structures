<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Normalizers;

use AndyDefer\DomainStructures\Normalizers\Core\AbstractNormalizer;
use AndyDefer\DomainStructures\Utils\DataObject;

final class DataObjectNormalizer extends AbstractNormalizer
{
    public function supports(mixed $value): bool
    {
        return $value instanceof DataObject;
    }

    public function normalize(mixed $value): mixed
    {
        if (! $value instanceof DataObject) {
            return $this->next($value);
        }

        $result = [];
        foreach ($value->toArray() as $key => $propValue) {
            $result[$key] = $this->next($propValue);
        }

        return $result;
    }
}
