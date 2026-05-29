<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Abstracts;

use AndyDefer\DomainStructures\Interfaces\Transformable;
use AndyDefer\DomainStructures\Normalizers\NormalizerChain;
use AndyDefer\DomainStructures\Traits\Hydratable;
use InvalidArgumentException;
use UnitEnum;

/**
 * Abstract Value Object with automatic hydration via Hydratable trait.
 * 
 * Children only need to:
 * 1. Define a public constructor with typed properties (validation inside constructor)
 * 2. Implement getValue()
 * 
 * @example
 * final class EmailAddress extends AbstractValueObject
 * {
 *     public function __construct(public readonly string $value) 
 *     {
 *         if (!filter_var($this->value, FILTER_VALIDATE_EMAIL)) {
 *             throw new InvalidArgumentException("Invalid email");
 *         }
 *     }
 *     
 *     public function getValue(): string { return $this->value; }
 * }
 * 
 * // Usage - all provided by Hydratable trait
 * $email = EmailAddress::from('user@example.com');
 * $email = EmailAddress::fromJson('"user@example.com"');
 * $collection = EmailAddress::collect(['a@b.com', 'c@d.com']);
 */
abstract class AbstractValueObject implements Transformable
{
    use Hydratable;

    /**
     * Returns the raw value of the Value Object.
     * Can return scalar, enum, record, data, collection, or DataObject.
     */
    abstract public function getValue(): int|string|float|bool|null|UnitEnum|Transformable;

    /**
     * Checks if this value object is equal to another.
     */
    public function equals(self $other): bool
    {
        if (get_class($this) !== get_class($other)) {
            return false;
        }

        $thisValue = $this->getValue();
        $otherValue = $other->getValue();

        if (is_object($thisValue) && is_object($otherValue)) {
            if ($thisValue instanceof $otherValue) {
                return $thisValue == $otherValue;
            }

            return false;
        }

        return $thisValue === $otherValue;
    }

    public function __toString(): string
    {
        return json_encode(NormalizerChain::get()->normalize($this), JSON_THROW_ON_ERROR);
    }
}
