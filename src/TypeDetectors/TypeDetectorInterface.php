<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\TypeDetectors;

interface TypeDetectorInterface
{
    public function supports(mixed $value): bool;

    public function getTypeName(mixed $value): string;

    public function getClassString(mixed $value): string;
}
