<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Fixtures\ValueObjects;

use AndyDefer\DomainStructures\Abstracts\AbstractValueObject;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestCurrency;
use AndyDefer\DomainStructures\Tests\Fixtures\Records\TestMoneyRecord;
use AndyDefer\DomainStructures\Traits\HasPropertiesAccess;
use InvalidArgumentException;

/**
 * Value Object representing money with amount, currency, and optional email.
 *
 * @example
 * $money = TestMoney::from(['amount' => 99.99, 'currency' => 'EUR']);
 * echo $money->amount; // 99.99
 * echo $money->currency->getSymbol(); // €
 * echo $money->format(); // €99.99
 * @example
 * // With optional email
 * $money = TestMoney::from([
 *     'amount' => 100.00,
 *     'currency' => 'USD',
 *     'emailAddress' => 'user@example.com'
 * ]);
 * @example
 * // Operations
 * $total = $money->add(TestMoney::from(['amount' => 50, 'currency' => 'EUR']));
 */
final class TestMoney extends AbstractValueObject
{
    use HasPropertiesAccess;

    public function __construct(
        private readonly float $amount,
        private readonly TestCurrency $currency,
        private readonly ?TestEmailAddress $emailAddress = null,
    ) {
        if ($amount <= 0) {
            throw new InvalidArgumentException("Amount must be positive: {$amount}");
        }
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

        return new self($this->amount + $other->amount, $this->currency, $this->emailAddress);
    }

    public function format(): string
    {
        return $this->currency->getSymbol().number_format($this->amount, 2);
    }
}
