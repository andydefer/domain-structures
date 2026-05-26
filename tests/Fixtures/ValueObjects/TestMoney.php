<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Fixtures\ValueObjects;

use AndyDefer\DomainStructures\Abstracts\AbstractValueObject;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestCurrency;
use AndyDefer\DomainStructures\Tests\Fixtures\Records\TestMoneyRecord;

final class TestMoney extends AbstractValueObject
{
    public function __construct(
        public readonly float $amount,
        public readonly TestCurrency $currency,
    ) {}

    public static function from(...$values): static
    {
        return self::fromFloat($values[0], $values[1]);
    }

    public static function fromFloat(float $amount, TestCurrency $currency): self
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException("Amount must be positive: {$amount}");
        }

        return new self($amount, $currency);
    }

    public function getValue(): TestMoneyRecord
    {
        return new TestMoneyRecord(
            amount: $this->amount,
            currency: $this->currency,
        );
    }

    public function add(TestMoney $other): self
    {
        if ($this->currency !== $other->currency) {
            throw new \InvalidArgumentException('Cannot add different currencies');
        }

        return new self($this->amount + $other->amount, $this->currency);
    }

    public function format(): string
    {
        return $this->currency->getSymbol().number_format($this->amount, 2);
    }
}
