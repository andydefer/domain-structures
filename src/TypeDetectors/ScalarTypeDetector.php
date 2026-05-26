<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\TypeDetectors;

final class ScalarTypeDetector implements TypeDetectorInterface
{
    private const TYPE_MAPPING = [
        'integer' => 'int',
        'double' => 'float',
        'string' => 'string',
        'boolean' => 'bool',
        'NULL' => 'null',
    ];

    public function supports(mixed $value): bool
    {
        return is_scalar($value) || $value === null;
    }

    public function getTypeName(mixed $value): string
    {
        return self::TYPE_MAPPING[gettype($value)] ?? gettype($value);
    }

    public function getClassString(mixed $value): string
    {
        return $this->getTypeName($value);
    }
}
