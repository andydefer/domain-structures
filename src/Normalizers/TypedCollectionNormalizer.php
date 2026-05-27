<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Normalizers;

use AndyDefer\DomainStructures\Abstracts\AbstractTypedCollection;
use AndyDefer\DomainStructures\Normalizers\Core\AbstractNormalizer;

final class TypedCollectionNormalizer extends AbstractNormalizer
{
    public function supports(mixed $value): bool
    {
        return $value instanceof AbstractTypedCollection;
    }

    public function normalize(mixed $value): mixed
    {
        if (!$value instanceof AbstractTypedCollection) {
            return $this->next($value);
        }

        $result = [];
        foreach ($value->all() as $item) {
            $result[] = $this->next($item);
        }

        return $result;
    }
}
