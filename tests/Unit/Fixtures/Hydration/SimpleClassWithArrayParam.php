<?php

namespace AndyDefer\DomainStructures\Tests\Unit\Fixtures\Hydration;

class SimpleClassWithArrayParam
{
    public function __construct(public readonly array $value) {}
}
