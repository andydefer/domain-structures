<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Fixtures\ValueObjects;

use AndyDefer\DomainStructures\Abstracts\AbstractValueObject;
use DateTime;
use InvalidArgumentException;

/**
 * Value Object representing an ISO 8601 datetime.
 *
 * @example
 * $date = TestIso8601DateTime::from('2024-01-15T14:30:00+01:00');
 * echo $date->value; // "2024-01-15T14:30:00+01:00"
 * echo $date->toDateTime()->format('Y-m-d H:i:s'); // "2024-01-15 14:30:00"
 */
final class TestIso8601DateTime extends AbstractValueObject
{
    private const FORMAT = 'Y-m-d\TH:i:sP';

    public function __construct(public readonly string $value)
    {
        $date = DateTime::createFromFormat(self::FORMAT, $this->value);

        if (! $date || $date->format(self::FORMAT) !== $this->value) {
            throw new InvalidArgumentException("Invalid ISO 8601 datetime: {$this->value}");
        }
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function toDateTime(): DateTime
    {
        return DateTime::createFromFormat(self::FORMAT, $this->value);
    }

    public function isAfter(self $other): bool
    {
        return $this->toDateTime() > $other->toDateTime();
    }

    public function isBefore(self $other): bool
    {
        return $this->toDateTime() < $other->toDateTime();
    }
}
