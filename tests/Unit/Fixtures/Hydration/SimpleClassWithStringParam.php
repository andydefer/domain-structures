<?php

namespace AndyDefer\DomainStructures\Tests\Unit\Fixtures\Hydration;

class SimpleClassWithStringParam
{
    public function __construct(public readonly string $value) {}
}
