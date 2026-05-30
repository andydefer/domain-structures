<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Fixtures\ValueObjects;

use AndyDefer\DomainStructures\Abstracts\AbstractValueObject;
use InvalidArgumentException;

/**
 * Value Object representing an email address.
 *
 * @example
 * $email = TestEmailAddress::from('user@example.com');
 * echo $email->value; // "user@example.com"
 * echo $email->getDomain(); // "example.com"
 * echo $email->isGmail(); // false
 */
final class TestEmailAddress extends AbstractValueObject
{
    public function __construct(public readonly string $value)
    {
        if (! filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("Invalid email: {$value}");
        }
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getDomain(): string
    {
        return substr(strrchr($this->value, '@'), 1);
    }

    public function isGmail(): bool
    {
        return $this->getDomain() === 'gmail.com';
    }
}
