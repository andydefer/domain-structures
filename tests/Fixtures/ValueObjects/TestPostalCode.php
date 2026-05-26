<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Fixtures\ValueObjects;

use AndyDefer\DomainStructures\Abstracts\AbstractValueObject;

final class TestPostalCode extends AbstractValueObject
{
    private function __construct(public readonly string $value) {}

    public static function from(...$values): static
    {
        return self::fromString($values[0]);
    }

    public static function fromString(string $postalCode): self
    {
        if (! preg_match('/^[0-9]{5}$/', $postalCode)) {
            throw new \InvalidArgumentException("Invalid postal code: {$postalCode}");
        }

        return new self($postalCode);
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
