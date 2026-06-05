<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Hydration\Strategy;

use AndyDefer\DomainStructures\Abstracts\AbstractData;

final class InstanceStrategy implements HydrationStrategyInterface
{
    public function supports(string $className, mixed $source): bool
    {
        return is_object($source)
            && $source instanceof $className
            && ! is_subclass_of($className, AbstractData::class);
    }

    public function hydrate(string $className, mixed $source): object
    {
        return $source;
    }
}
