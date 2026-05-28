<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Abstracts;

use AndyDefer\DomainStructures\Interfaces\Transformable;
use AndyDefer\DomainStructures\Normalizers\NormalizerChain;
use AndyDefer\DomainStructures\Traits\Hydratable;

abstract class AbstractRecord implements Transformable
{
    use Hydratable;

    public function __toString(): string
    {
        return json_encode(NormalizerChain::get()->normalize($this), JSON_THROW_ON_ERROR);
    }
}
