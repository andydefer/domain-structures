<?php

namespace AndyDefer\DomainStructures\Tests\Unit\Fixtures\Hydration;

class ClassWithRequiredParameters
{
    public function __construct(
        public readonly string $name,
        public readonly string $email
    ) {}
}
