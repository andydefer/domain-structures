<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Normalizers;

use AndyDefer\DomainStructures\Abstracts\AbstractRecord;
use AndyDefer\DomainStructures\Normalizers\Core\AbstractNormalizer;

final class RecordNormalizer extends AbstractNormalizer
{
    public function supports(mixed $value): bool
    {
        return $value instanceof AbstractRecord;
    }

    public function normalize(mixed $value): mixed
    {
        if (! $value instanceof AbstractRecord) {
            return $this->next($value);
        }

        $reflection = new \ReflectionClass($value);
        $properties = $reflection->getProperties(\ReflectionProperty::IS_PUBLIC);
        $result = [];

        foreach ($properties as $property) {
            $propValue = $property->getValue($value);
            $key = $this->convertCamelToSnake($property->getName());
            $result[$key] = $this->next($propValue);
        }

        return $result;
    }

    private function convertCamelToSnake(string $input): string
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $input));
    }
}
