<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Fixtures\ValueObjects;

use AndyDefer\DomainStructures\Abstracts\AbstractValueObject;

final class TestStringVO extends AbstractValueObject
{
    private string $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function toUpper(): self
    {
        return new self(strtoupper($this->value));
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
