<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\TypeDetectors;

final class DefaultTypeDetector implements TypeDetectorInterface
{
    public function supports(mixed $value): bool
    {
        return true;
    }

    public function getTypeName(mixed $value): string
    {
        if (is_object($value)) {
            return 'object('.$value::class.')';
        }

        return gettype($value);
    }

    public function getClassString(mixed $value): string
    {
        return $this->getTypeName($value);
    }
}
