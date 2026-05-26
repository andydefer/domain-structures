<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Fixtures\ValueObjects;

use AndyDefer\DomainStructures\Abstracts\AbstractValueObject;
use InvalidArgumentException;

/**
 * Value Object representing an email address.
 */
final class TestEmailAddress extends AbstractValueObject
{
    private function __construct(public readonly string $value) {}

    public static function fromString(string $email): self
    {
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("Invalid email: {$email}");
        }

        return new self($email);
    }

    public static function from(...$values): static
    {
        return self::fromString($values[0]);
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
