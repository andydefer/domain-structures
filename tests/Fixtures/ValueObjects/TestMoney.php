<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Fixtures\ValueObjects;

use AndyDefer\DomainStructures\Abstracts\AbstractValueObject;
use AndyDefer\DomainStructures\Utils\DataObject;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestCurrency;
use AndyDefer\DomainStructures\Tests\Fixtures\Records\TestMoneyRecord;
use InvalidArgumentException;

final class TestMoney extends AbstractValueObject
{
    private function __construct(
        private readonly float $amount,
        private readonly TestCurrency $currency,
    ) {}

    public static function from(mixed $source): static
    {
        // Si c'est déjà un TestMoney
        if ($source instanceof self) {
            return $source;
        }

        // Normalisation : toute source devient DataObject
        $data = DataObject::from($source);

        $amount = $data->amount ?? null;
        $currency = $data->currency ?? null;

        if ($amount === null) {
            throw new InvalidArgumentException('Missing required property "amount"');
        }

        if ($currency === null) {
            throw new InvalidArgumentException('Missing required property "currency"');
        }

        $amountFloat = (float) $amount;
        $currencyEnum = TestCurrency::from($currency);

        if ($amountFloat <= 0) {
            throw new InvalidArgumentException("Amount must be positive: {$amountFloat}");
        }

        return new self($amountFloat, $currencyEnum);
    }

    public function getValue(): TestMoneyRecord
    {
        return new TestMoneyRecord(
            amount: $this->amount,
            currency: $this->currency,
        );
    }

    public function add(self $other): self
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException('Cannot add different currencies');
        }

        return new self($this->amount + $other->amount, $this->currency);
    }

    public function format(): string
    {
        return $this->currency->getSymbol() . number_format($this->amount, 2);
    }
}
