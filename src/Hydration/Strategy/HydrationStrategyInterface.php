<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Hydration\Strategy;

use AndyDefer\DomainStructures\Abstracts\AbstractData;
use AndyDefer\DomainStructures\Hydration\Converter\TypeConverterInterface;

interface HydrationStrategyInterface
{
    public function supports(string $className, mixed $source): bool;
    public function hydrate(string $className, mixed $source): object;
}
