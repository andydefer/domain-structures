<?php

// FILE: src/Interfaces/Transformable.php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Interfaces;

use AndyDefer\DomainStructures\Abstracts\AbstractTypedCollection;
use AndyDefer\DomainStructures\Collections\Core\TypedCollection;

/**
 * Interface for objects that can be hydrated from a source and normalized.
 */
interface Transformable
{
    /**
     * Creates an instance from a source.
     *
     * @param  mixed  $source  The source data (array, object, string, int, etc.)
     *
     * @throws \InvalidArgumentException If the source cannot be converted
     */
    public static function from(mixed $source): static;

    /**
     * Creates an instance from a JSON string.
     *
     * @param  string  $json  JSON string representation of the object
     *
     * @throws \InvalidArgumentException If the JSON is invalid or cannot be converted
     */
    public static function fromJson(string $json): static;

    /**
     * Hydrates a collection of sources into a typed collection.
     *
     * @template TCollection of AbstractTypedCollection
     * @param  iterable<mixed>       $sources
     * @param  class-string<TCollection>  $collectionClass
     * @return TCollection
     *
     * @throws \InvalidArgumentException
     */
    public static function collect(iterable $sources, string $collectionClass = TypedCollection::class): AbstractTypedCollection;
}
