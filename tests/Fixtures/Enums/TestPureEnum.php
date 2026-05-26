<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Fixtures\Enums;

use AndyDefer\DomainStructures\Traits\Enumable;

/**
 * Pure enum (no backing value) for testing.
 */
enum TestPureEnum
{
    use Enumable;

    case VALUE_ONE;
    case VALUE_TWO;
    case VALUE_THREE;

    public function getLabel(): string
    {
        return match ($this) {
            self::VALUE_ONE => 'One',
            self::VALUE_TWO => 'Two',
            self::VALUE_THREE => 'Three',
        };
    }

    public function isFirst(): bool
    {
        return $this === self::VALUE_ONE;
    }
}
