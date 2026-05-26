<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Fixtures\Enums;

use AndyDefer\DomainStructures\Traits\Enumable;

enum TestCurrency: string
{
    use Enumable;

    case EUR = 'EUR';
    case USD = 'USD';
    case GBP = 'GBP';

    public function getSymbol(): string
    {
        return match ($this) {
            self::EUR => '€',
            self::USD => '$',
            self::GBP => '£',
        };
    }
}
