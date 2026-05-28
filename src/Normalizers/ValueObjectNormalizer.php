<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Normalizers;

use AndyDefer\DomainStructures\Abstracts\AbstractValueObject;
use AndyDefer\DomainStructures\Normalizers\Core\AbstractNormalizer;

final class ValueObjectNormalizer extends AbstractNormalizer
{
    public function supports(mixed $value): bool
    {
        return $value instanceof AbstractValueObject;
    }

    public function normalize(mixed $value): mixed
    {
        if (! $value instanceof AbstractValueObject) {
            return $this->next($value);
        }

        $voValue = $value->getValue();

        return $this->next($voValue);
    }
}
