<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Abstracts;

use AndyDefer\DomainStructures\Collections\Core\TypedCollection;
use AndyDefer\DomainStructures\Interfaces\Transformable;
use AndyDefer\DomainStructures\Normalizers\NormalizerChain;
use AndyDefer\DomainStructures\Utils\DataObject;
use UnitEnum;
use InvalidArgumentException;

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
     * Creates an instance from a JSON string.
     *
     * @param  string  $json  JSON string representation of the value object
     * @return static
     *
     * @throws InvalidArgumentException If the JSON is invalid
     */
    final public static function fromJson(string $json): static
    {
        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidArgumentException(sprintf(
                'Invalid JSON: %s',
                json_last_error_msg()
            ));
        }

        return static::from($data);
    }

    /**
     * Hydrates a collection of sources into a typed collection.
     *
     * @template TCollection of AbstractTypedCollection
     * @param  iterable<mixed>       $sources
     * @param  class-string<TCollection>  $collectionClass
     * @return TCollection
     */
    public static function collect(iterable $sources, string $collectionClass = TypedCollection::class): AbstractTypedCollection
    {
        if (!is_subclass_of($collectionClass, AbstractTypedCollection::class)) {
            throw new \InvalidArgumentException(sprintf(
                'Collection class "%s" must extend %s',
                $collectionClass,
                AbstractTypedCollection::class
            ));
        }

        $collection = new $collectionClass(static::class);

        foreach ($sources as $source) {
            $collection->add(static::from($source));
        }

        return $collection;
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
