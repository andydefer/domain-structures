<?php

namespace AndyDefer\DomainStructures\Tests\Unit\Fixtures\Hydration;

class ClassWithIntParameter
{
    public function __construct(public readonly int $count) {}
}
