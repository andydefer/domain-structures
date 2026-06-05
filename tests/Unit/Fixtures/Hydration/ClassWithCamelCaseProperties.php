<?php

namespace AndyDefer\DomainStructures\Tests\Unit\Fixtures\Hydration;

class ClassWithCamelCaseProperties
{
    public function __construct(
        public readonly string $fullName,
        public readonly string $emailAddress
    ) {}
}
