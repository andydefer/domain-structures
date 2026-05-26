<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Normalizers;

use AndyDefer\DomainStructures\Enums\NormalizeMode;

interface NormalizerInterface
{
    public function supports(mixed $value): bool;

    public function normalize(mixed $value, NormalizeMode $mode = NormalizeMode::ARRAY, bool $includeNulls = true): mixed;

    public function setNext(?NormalizerInterface $next): void;
}
