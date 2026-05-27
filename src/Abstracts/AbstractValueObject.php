<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Abstracts;

use AndyDefer\DomainStructures\Enums\NormalizeMode;
use AndyDefer\DomainStructures\Utils\DataObject;
use AndyDefer\DomainStructures\Interfaces\Transformable;
use AndyDefer\DomainStructures\Normalizers\NormalizerChain;
use UnitEnum;

abstract class AbstractValueObject implements Transformable
{
    protected function __construct() {}

    /**
     * Returns the raw value of the Value Object.
     * Can return scalar, enum, record, data, collection, or DataObject.
     *
     * @return int|string|float|bool|null|UnitEnum|AbstractRecord|AbstractValueObject|AbstractData|AbstractTypedCollection|DataObject
     */
    abstract public function getValue(): int|string|float|bool|null|UnitEnum|AbstractRecord|AbstractValueObject|AbstractData|AbstractTypedCollection|DataObject;

    /**
     * Force children to define from() logic for their specific construction.
     *
     * @param mixed $source
     * @return static
     */
    abstract public static function from(mixed $source): static;

    /**
     * Normalize to value (always returns the raw value, not an array).
     *
     * @return mixed
     */
    public function normalize(): mixed
    {
        $value = $this->getValue();
        return NormalizerChain::get()->normalize($value);
    }

    public function equals(self $other): bool
    {
        return get_class($this) === get_class($other) && $this->getValue() === $other->getValue();
    }

    public function __toString(): string
    {
        return json_encode($this->normalize(), JSON_THROW_ON_ERROR);
    }
}
