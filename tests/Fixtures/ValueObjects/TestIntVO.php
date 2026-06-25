<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Fixtures\ValueObjects;

use AndyDefer\DomainStructures\Abstracts\AbstractValueObject;

final class TestIntVO extends AbstractValueObject
{
    private int $value;

    public function __construct(int $value)
    {
        $this->value = $value;
    }

    public function getValue(): int
    {
        return $this->value;
    }

    public function multiply(int $multiplier): self
    {
        return new self($this->value * $multiplier);
    }

    public function add(int $add): self
    {
        return new self($this->value + $add);
    }

    public function __toString(): string
    {
        return (string) $this->value;
    }
}
