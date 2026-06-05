<?php

namespace AndyDefer\DomainStructures\Tests\Unit\Fixtures\Hydration;

class NullableParameterClass
{
    public function __construct(public readonly ?string $value) {}
}
