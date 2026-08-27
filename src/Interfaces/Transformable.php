<?php

// FILE: src/Interfaces/Transformable.php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Interfaces;

use AndyDefer\DomainStructures\Abstracts\AbstractTypedCollection;
use AndyDefer\DomainStructures\Utils\Sequential;

/**
 * Interface for objects that can be hydrated from a source, JSON, and collections.
 *
 * @extends Fromable
 */
interface Transformable extends Fromable
{
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
     *
     * @param  iterable<mixed>  $sources
     * @param  class-string<TCollection>  $collectionClass
     *
     * @throws \InvalidArgumentException
     */
    public static function collect(iterable $sources, string $collectionClass = Sequential::class);
}
