<?php

namespace AndyDefer\DomainStructures\Tests\Unit\Fixtures\Hydration;

class ClassWithBoolParameter
{
    public function __construct(public readonly bool $active) {}
}
