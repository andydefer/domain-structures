<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Fixtures\ValueObjects;

use AndyDefer\DomainStructures\Abstracts\AbstractValueObject;
use InvalidArgumentException;

/**
 * Value Object representing a postal code (French format: 5 digits).
 *
 * @example
 * $postalCode = TestPostalCode::from('75001');
 * echo $postalCode->value; // "75001"
 * echo $postalCode->getCityCode(); // "75"
 * @example
 * // Invalid postal code
 * $postalCode = TestPostalCode::from('1234'); // ❌ Exception
 * $postalCode = TestPostalCode::from('ABCDE'); // ❌ Exception
 */
final class TestPostalCode extends AbstractValueObject
{
    public function __construct(public readonly string $value)
    {
        if (! preg_match('/^[0-9]{5}$/', $this->value)) {
            throw new InvalidArgumentException("Invalid postal code: {$this->value}");
        }
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
