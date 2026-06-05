<?php

namespace AndyDefer\DomainStructures\Tests\Unit\Fixtures\Hydration;

class SimpleClassWithFloatParam
{
    public function __construct(public readonly float $value) {}
}
