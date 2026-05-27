<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Normalizers;

use AndyDefer\DomainStructures\Normalizers\Core\AbstractNormalizer;
use UnitEnum;

final class EnumNormalizer extends AbstractNormalizer
{
    public function supports(mixed $value): bool
    {
        return $value instanceof UnitEnum;
    }

    public function normalize(mixed $value): mixed
    {
        if (!$value instanceof UnitEnum) {
            return $this->next($value);
        }

        // Pour les backed enums, retourner la valeur
        if ($value instanceof \BackedEnum) {
            return $value->value;
        }

        // Pour les pure enums, retourner le nom
        return $value->name;
    }
}
