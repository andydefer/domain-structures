<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\TypeDetectors;

use UnitEnum;

final class EnumTypeDetector implements TypeDetectorInterface
{
    public function supports(mixed $value): bool
    {
        return $value instanceof UnitEnum;
    }

    public function getTypeName(mixed $value): string
    {
        return $value::class;
    }

    public function getClassString(mixed $value): string
    {
        return UnitEnum::class;
    }
}
