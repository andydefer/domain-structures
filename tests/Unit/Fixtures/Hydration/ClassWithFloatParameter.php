<?php

namespace AndyDefer\DomainStructures\Tests\Unit\Fixtures\Hydration;

class ClassWithFloatParameter
{
    public function __construct(public readonly float $price) {}
}
