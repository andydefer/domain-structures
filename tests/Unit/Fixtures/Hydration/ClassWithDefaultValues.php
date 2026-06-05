<?php

namespace AndyDefer\DomainStructures\Tests\Unit\Fixtures\Hydration;

class ClassWithDefaultValues
{
    public function __construct(
        public readonly string $name,
        public readonly string $email = 'default@example.com',
        public readonly string $status = 'active'
    ) {}
}
