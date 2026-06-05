<?php

namespace AndyDefer\DomainStructures\Tests\Unit\Fixtures\Hydration;

class SimpleClassWithBoolParam
{
    public function __construct(public readonly bool $value) {}
}
