<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Fixtures\ValueObjects;

use AndyDefer\DomainStructures\Abstracts\AbstractValueObject;
use DateTime;
use DateTimeInterface;
use InvalidArgumentException;

final class TestIso8601DateTime extends AbstractValueObject
{
    private const FORMAT = 'Y-m-d\TH:i:sP';

    private function __construct(public readonly string $value) {}

    public static function from(mixed $source): static
    {
        // Si c'est déjà un TestIso8601DateTime
        if ($source instanceof self) {
            return $source;
        }

        // 🔥 Si la source est null, retourner null ?
        // Mais Attention : le type de retour est static, pas nullable
        // Donc on doit lancer une exception ou ne pas appeler from avec null
        if ($source === null) {
            throw new InvalidArgumentException('Cannot create TestIso8601DateTime from null');
        }

        // Si c'est une string ISO
        if (is_string($source)) {
            $date = DateTime::createFromFormat(self::FORMAT, $source);
            if (! $date || $date->format(self::FORMAT) !== $source) {
                throw new InvalidArgumentException("Invalid ISO 8601 datetime: {$source}");
            }

            return new self($source);
        }

        // Si c'est un DateTime ou Carbon
        if ($source instanceof DateTimeInterface) {
            return new self($source->format(self::FORMAT));
        }

        throw new InvalidArgumentException(sprintf(
            'Cannot create TestIso8601DateTime from %s',
            is_object($source) ? $source::class : gettype($source)
        ));
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
