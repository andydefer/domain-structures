<?php

namespace AndyDefer\DomainStructures\Tests\Unit\Fixtures\Hydration;

class SimpleClassWithObjectParam
{
    public function __construct(public readonly object $value) {}
}
