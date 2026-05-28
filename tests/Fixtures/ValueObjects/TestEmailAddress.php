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

    public static function from(mixed $source): static
    {
        if ($source instanceof self) {
            return $source;
        }

        if (! is_string($source)) {
            throw new InvalidArgumentException('Email must be a string');
        }

        if (! filter_var($source, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("Invalid email: {$source}");
        }

        return new self($source);
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
