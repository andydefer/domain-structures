<?php

namespace AndyDefer\DomainStructures\Tests\Unit\Fixtures\Hydration;


// Classe helper pour tester la normalisation des floats
class StringParameterClass
{
    public function __construct(public readonly string $value) {}
}
