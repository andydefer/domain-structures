<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Abstracts;

use AndyDefer\DomainStructures\Enums\NormalizeMode;
use AndyDefer\DomainStructures\Interfaces\DataInterface;
use AndyDefer\DomainStructures\Interfaces\Transformable;
use AndyDefer\DomainStructures\Normalizers\NormalizerChain;
use AndyDefer\DomainStructures\Traits\Hydratable;
use ReflectionClass;
use ReflectionProperty;

abstract class AbstractData implements DataInterface, Transformable
{
    use Hydratable;

    final public function normalize(): array
    {
        $reflection = new ReflectionClass($this);
        $properties = $reflection->getProperties(ReflectionProperty::IS_PUBLIC);
        $result = [];

        foreach ($properties as $property) {
            $value = $property->getValue($this);
            $key = $property->getName();
            // 🔥 Toujours normaliser en ARRAY
            $result[$key] = NormalizerChain::get()->normalize($value, NormalizeMode::ARRAY, true);
        }

        return $result;
    }

    /**
     * Convertit l'objet en JSON string.
     */
    public function __toString(): string
    {
        return json_encode($this->normalize(), JSON_THROW_ON_ERROR);
    }
}
