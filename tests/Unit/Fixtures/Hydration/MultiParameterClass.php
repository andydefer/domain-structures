<?php

namespace AndyDefer\DomainStructures\Tests\Unit\Fixtures\Hydration;

class MultiParameterClass
{
    public function __construct(public string $param1, public string $param2) {}
}
