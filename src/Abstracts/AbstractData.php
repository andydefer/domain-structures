<?php

// FILE: src/Abstracts/AbstractData.php

namespace AndyDefer\DomainStructures\Abstracts;

use AndyDefer\DomainStructures\Enums\NormalizeMode;
use AndyDefer\DomainStructures\Interfaces\DataInterface;
use AndyDefer\DomainStructures\Normalizers\NormalizerChain;
use AndyDefer\DomainStructures\Traits\Hydratable;
use ReflectionClass;
use ReflectionProperty;

abstract class AbstractData implements DataInterface
{
    use Hydratable;

    final public function normalize(NormalizeMode $mode = NormalizeMode::ARRAY): array|string
    {
        $reflection = new ReflectionClass($this);
        $properties = $reflection->getProperties(ReflectionProperty::IS_PUBLIC);
        $result = [];

        foreach ($properties as $property) {
            $value = $property->getValue($this);
            $key = $property->getName();
            $result[$key] = NormalizerChain::get()->normalize($value, $mode, true);
        }

        return $mode === NormalizeMode::JSON ? json_encode($result, JSON_THROW_ON_ERROR) : $result;
    }
}
