<?php

namespace AndyDefer\DomainStructures\Tests\Unit\Fixtures\Hydration;

class SimpleClassWithIntParam
{
    public function __construct(public readonly int $value) {}
}
