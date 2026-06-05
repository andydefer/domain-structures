<?php

namespace AndyDefer\DomainStructures\Tests\Unit\Fixtures\Hydration;

class ClassWithAllNullableParameters
{
    public function __construct(
        public readonly ?string $value1 = null,
        public readonly ?string $value2 = null
    ) {}
}
