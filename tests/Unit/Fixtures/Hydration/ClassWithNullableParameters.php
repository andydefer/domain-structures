<?php

namespace AndyDefer\DomainStructures\Tests\Unit\Fixtures\Hydration;

class ClassWithNullableParameters
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $email = null,
        public readonly ?string $status = null
    ) {}
}
