<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\TypeDetectors;

use stdClass;

final class StdClassTypeDetector implements TypeDetectorInterface
{
    public function supports(mixed $value): bool
    {
        return $value instanceof stdClass;
    }

    public function getTypeName(mixed $value): string
    {
        return stdClass::class;
    }

    public function getClassString(mixed $value): string
    {
        return stdClass::class;
    }
}
