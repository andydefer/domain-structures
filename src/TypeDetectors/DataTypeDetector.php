<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\TypeDetectors;

use AndyDefer\DomainStructures\Abstracts\AbstractData;

final class DataTypeDetector implements TypeDetectorInterface
{
    public function supports(mixed $value): bool
    {
        return $value instanceof AbstractData;
    }

    public function getTypeName(mixed $value): string
    {
        return $value::class;
    }

    public function getClassString(mixed $value): string
    {
        return AbstractData::class;
    }
}
