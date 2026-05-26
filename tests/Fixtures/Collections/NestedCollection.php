<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Fixtures\Collections;

use AndyDefer\DomainStructures\Abstracts\AbstractTypedCollection;
use AndyDefer\DomainStructures\Collections\Core\TypedCollection;

/**
 * Collection that can contain other TypedCollections.
 * Used to test nested collection normalization.
 *
 * @extends AbstractTypedCollection<TypedCollection>
 */
final class NestedCollection extends AbstractTypedCollection
{
    public function __construct()
    {
        parent::__construct(TypedCollection::class);
    }

    /**
     * Flatten all nested collections into one array.
     */
    public function flatten(): array
    {
        $result = [];
        foreach ($this->items as $collection) {
            $result = array_merge($result, $collection->toArray());
        }

        return $result;
    }
}
