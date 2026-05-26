<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\TypeDetectors;

use AndyDefer\DomainStructures\Abstracts\AbstractRecord;

final class RecordTypeDetector implements TypeDetectorInterface
{
    public function supports(mixed $value): bool
    {
        return $value instanceof AbstractRecord;
    }

    public function getTypeName(mixed $value): string
    {
        return $value::class;
    }

    public function getClassString(mixed $value): string
    {
        return AbstractRecord::class;
    }
}
