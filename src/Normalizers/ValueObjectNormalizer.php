<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Normalizers;

use AndyDefer\DomainStructures\Abstracts\AbstractValueObject;
use AndyDefer\DomainStructures\Enums\NormalizeMode;

final class ValueObjectNormalizer extends AbstractNormalizer
{
    public function supports(mixed $value): bool
    {
        return $value instanceof AbstractValueObject;
    }

    public function normalize(mixed $value, NormalizeMode $mode = NormalizeMode::ARRAY, bool $includeNulls = true): mixed
    {
        if (! $value instanceof AbstractValueObject) {
            return $this->next($value, $mode, $includeNulls);
        }

        $voValue = $value->getValue();

        return $this->next($voValue, $mode, $includeNulls);
    }
}
