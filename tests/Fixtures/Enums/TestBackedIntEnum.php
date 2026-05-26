<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Fixtures\Enums;

use AndyDefer\DomainStructures\Traits\Enumable;

/**
 * Backed enum with integer values for testing.
 */
enum TestBackedIntEnum: int
{
    use Enumable;

    case VALUE_ONE = 1;
    case VALUE_TWO = 2;
    case VALUE_THREE = 3;

    public function getMultiplier(): int
    {
        return $this->value * 10;
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::VALUE_ONE => 'One',
            self::VALUE_TWO => 'Two',
            self::VALUE_THREE => 'Three',
        };
    }

    public function isOne(): bool
    {
        return $this === self::VALUE_ONE;
    }

    public function isTwo(): bool
    {
        return $this === self::VALUE_TWO;
    }

    public function isThree(): bool
    {
        return $this === self::VALUE_THREE;
    }
}
