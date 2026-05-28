<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Abstracts;

use AndyDefer\DomainStructures\Interfaces\Transformable;
use AndyDefer\DomainStructures\Normalizers\NormalizerChain;
use AndyDefer\DomainStructures\Utils\DataObject;
use UnitEnum;

abstract class AbstractValueObject implements Transformable
{
    protected function __construct() {}

    /**
     * Returns the raw value of the Value Object.
     * Can return scalar, enum, record, data, collection, or DataObject.
     */
    abstract public function getValue(): int|string|float|bool|null|UnitEnum|AbstractRecord|AbstractValueObject|AbstractData|AbstractTypedCollection|DataObject;

    /**
     * Force children to define from() logic for their specific construction.
     */
    abstract public static function from(mixed $source): static;

    /**
     * Normalize to value (always returns the raw value, not an array).
     */
    public function normalize(): mixed
    {
        $value = $this->getValue();

        return NormalizerChain::get()->normalize($value);
    }

    /**
     * Checks if this value object is equal to another.
     *
     * @param  self  $other  The other value object to compare
     * @return bool True if the value objects are equal, false otherwise
     */
    public function equals(self $other): bool
    {
        if (get_class($this) !== get_class($other)) {
            return false;
        }

        $thisValue = $this->getValue();
        $otherValue = $other->getValue();

        // Si les deux sont des objets, comparer leurs propriétés
        if (is_object($thisValue) && is_object($otherValue)) {
            if ($thisValue instanceof $otherValue) {
                return $thisValue == $otherValue;
            }
            return false;
        }

        // Sinon, comparaison stricte
        return $thisValue === $otherValue;
    }

    public function __toString(): string
    {
        return json_encode($this->normalize(), JSON_THROW_ON_ERROR);
    }
}
