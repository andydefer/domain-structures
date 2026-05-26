<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Fixtures\Enums;

use AndyDefer\DomainStructures\Traits\Enumable;

/**
 * Backed enum with string values for testing.
 */
enum TestBackedStringEnum: string
{
    use Enumable;

    case VALUE_ONE = 'one';
    case VALUE_TWO = 'two';
    case VALUE_THREE = 'three';

    public function toUpperCase(): string
    {
        return strtoupper($this->value);
    }

    public function toLowerCase(): string
    {
        return strtolower($this->value);
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

    public function getLength(): int
    {
        return strlen($this->value);
    }
}
