<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Fixtures\ValueObjects;

use AndyDefer\DomainStructures\Abstracts\AbstractValueObject;
use InvalidArgumentException;

final class TestPostalCode extends AbstractValueObject
{
    private function __construct(public readonly string $value) {}

    public static function from(mixed $source): static
    {
        if (! is_string($source)) {
            throw new InvalidArgumentException('Postal code must be a string');
        }

        if (! preg_match('/^[0-9]{5}$/', $source)) {
            throw new InvalidArgumentException("Invalid postal code: {$source}");
        }

        return new self($source);
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getCityCode(): string
    {
        return substr($this->value, 0, 2);
    }
}
