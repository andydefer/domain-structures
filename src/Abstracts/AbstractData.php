<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Abstracts;

use AndyDefer\DomainStructures\Interfaces\Transformable;
use AndyDefer\DomainStructures\Normalizers\NormalizerChain;
use AndyDefer\DomainStructures\Traits\Hydratable;

abstract class AbstractData implements Transformable
{
    use Hydratable;

    public function toArray(): array
    {
        return NormalizerChain::get()->normalize($this);
    }

    public function __toString(): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR);
    }
}
