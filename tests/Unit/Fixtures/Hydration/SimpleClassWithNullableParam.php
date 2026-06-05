<?php

namespace AndyDefer\DomainStructures\Tests\Unit\Fixtures\Hydration;

class SimpleClassWithNullableParam
{
    public function __construct(public readonly ?string $value) {}
}
