<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Fixtures\Enums;

use AndyDefer\DomainStructures\Traits\Enumable;

/**
 * Backed enum for testing with integer values.
 */
enum TestBackedEnum: int
{
    use Enumable;

    case VALUE_ONE = 1;
    case VALUE_TWO = 2;
    case VALUE_THREE = 3;

    public function getLabel(): string
    {
        return match ($this) {
            self::VALUE_ONE => 'One',
            self::VALUE_TWO => 'Two',
            self::VALUE_THREE => 'Three',
        };
    }
}
