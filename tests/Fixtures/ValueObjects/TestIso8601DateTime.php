<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Fixtures\ValueObjects;

use AndyDefer\DomainStructures\Abstracts\AbstractValueObject;
use DateTime;
use DateTimeInterface;
use InvalidArgumentException;

/**
 * Value Object representing an ISO 8601 datetime string.
 */
final class TestIso8601DateTime extends AbstractValueObject
{
    private const FORMAT = 'Y-m-d\TH:i:sP';

    private function __construct(public readonly string $value) {}

    public static function fromString(string $datetime): self
    {
        $date = DateTime::createFromFormat(self::FORMAT, $datetime);
        if (! $date || $date->format(self::FORMAT) !== $datetime) {
            throw new InvalidArgumentException("Invalid ISO 8601 datetime: {$datetime}");
        }

        return new self($datetime);
    }

    public static function fromDateTime(DateTimeInterface $datetime): self
    {
        return new self($datetime->format(self::FORMAT));
    }

    public static function now(): self
    {
        return new self((new DateTime)->format(self::FORMAT));
    }

    public static function from(...$values): static
    {
        return self::fromString($values[0]);
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function toDateTime(): DateTime
    {
        return DateTime::createFromFormat(self::FORMAT, $this->value);
    }

    public function isAfter(TestIso8601DateTime $other): bool
    {
        return $this->toDateTime() > $other->toDateTime();
    }

    public function isBefore(TestIso8601DateTime $other): bool
    {
        return $this->toDateTime() < $other->toDateTime();
    }
}
