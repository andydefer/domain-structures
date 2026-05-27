<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Abstracts;

use AndyDefer\DomainStructures\Enums\NormalizeMode;
use AndyDefer\DomainStructures\Interfaces\RecordInterface;
use AndyDefer\DomainStructures\Interfaces\Transformable;
use AndyDefer\DomainStructures\Normalizers\NormalizerChain;
use AndyDefer\DomainStructures\Traits\Hydratable;
use ReflectionClass;
use ReflectionProperty;

abstract class AbstractRecord implements RecordInterface, Transformable
{
    use Hydratable;

    public function normalize(bool $includeNulls = true): array
    {
        $reflection = new ReflectionClass($this);
        $properties = $reflection->getProperties(ReflectionProperty::IS_PUBLIC);
        $result = [];

        foreach ($properties as $property) {
            $value = $property->getValue($this);
            $key = $this->convertCamelToSnake($property->getName());

            if (! $includeNulls && $value === null) {
                continue;
            }

            // 🔥 Toujours normaliser en ARRAY
            $normalizedValue = NormalizerChain::get()->normalize($value, NormalizeMode::ARRAY, $includeNulls);

            if (! $includeNulls && $normalizedValue === null) {
                continue;
            }

            $result[$key] = $normalizedValue;
        }

        return $result;
    }

    private function convertCamelToSnake(string $input): string
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $input));
    }

    public function __toString(): string
    {
        return json_encode($this->normalize(false), JSON_THROW_ON_ERROR);
    }
}
