<?php

// FILE: src/Abstracts/AbstractRecord.php

namespace AndyDefer\DomainStructures\Abstracts;

use AndyDefer\DomainStructures\Enums\NormalizeMode;
use AndyDefer\DomainStructures\Interfaces\RecordInterface;
use AndyDefer\DomainStructures\Normalizers\NormalizerChain;
use AndyDefer\DomainStructures\Traits\Hydratable;
use ReflectionClass;
use ReflectionProperty;

abstract class AbstractRecord implements RecordInterface
{
    use Hydratable;

    public function normalize(NormalizeMode $mode = NormalizeMode::ARRAY, bool $includeNulls = true): array|string
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

            $normalizedValue = NormalizerChain::get()->normalize($value, $mode, $includeNulls);

            if (! $includeNulls && $normalizedValue === null) {
                continue;
            }

            $result[$key] = $normalizedValue;
        }

        return $mode === NormalizeMode::JSON ? json_encode($result, JSON_THROW_ON_ERROR) : $result;
    }

    private function convertCamelToSnake(string $input): string
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $input));
    }
}
