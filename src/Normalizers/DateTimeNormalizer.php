<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Normalizers;

use AndyDefer\DomainStructures\Normalizers\Core\NormalizerInterface;
use DateTimeInterface;

/**
 * Normalizer for DateTimeInterface objects.
 * 
 * Converts DateTime objects to ISO 8601 string format.
 * 
 * @example
 * $date = new DateTime('2024-01-15 14:30:00');
 * $normalizer = new DateTimeNormalizer();
 * $result = $normalizer->normalize($date); // "2024-01-15T14:30:00+00:00"
 */
final class DateTimeNormalizer implements NormalizerInterface
{
    private const FORMAT = 'Y-m-d\TH:i:sP';

    private ?NormalizerInterface $next = null;

    public function supports(mixed $value): bool
    {
        return $value instanceof DateTimeInterface;
    }

    public function normalize(mixed $value): string
    {
        if (!$value instanceof DateTimeInterface) {
            throw new \InvalidArgumentException('Expected DateTimeInterface instance');
        }

        return $value->format(self::FORMAT);
    }

    public function setNext(?NormalizerInterface $next): void
    {
        $this->next = $next;
    }
}
