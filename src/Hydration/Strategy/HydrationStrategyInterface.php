<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Hydration\Strategy;

interface HydrationStrategyInterface
{
    public function supports(string $className, mixed $source): bool;

    public function hydrate(string $className, mixed $source): object;
}
