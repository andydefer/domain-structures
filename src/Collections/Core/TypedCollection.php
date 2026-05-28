<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Collections\Core;

use AndyDefer\DomainStructures\Abstracts\AbstractTypedCollection;

/**
 * Type-safe collection that accepts any allowed type by default.
 *
 * If no types are specified, the collection accepts all allowed types
 * (scalars, enums, records, value objects, data, collections, DataObject).
 *
 * @template TValue of object|string|int|float|bool
 */
class TypedCollection extends AbstractTypedCollection
{
    /**
     * Constructor.
     *
     * @param  class-string<AbstractRecord>|class-string<AbstractValueObject>|class-string<AbstractData>|class-string<UnitEnum>|string  ...$types
     *                                                                                                                                             If no types are provided, accepts all allowed types (mixed collection)
     */
    public function __construct(...$types)
    {
        if (empty($types)) {
            $types = self::getAllowedTypesList();
        }

        parent::__construct(...$types);
    }
}
