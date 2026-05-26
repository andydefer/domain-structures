<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Fixtures\Records;

use AndyDefer\DomainStructures\Abstracts\AbstractRecord;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestCurrency;

final class TestMoneyRecord extends AbstractRecord
{
    public function __construct(
        public readonly float $amount,
        public readonly TestCurrency $currency,
    ) {}
}
