<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Collections\Core;

use AndyDefer\DomainStructures\Abstracts\AbstractTypedCollection;
use InvalidArgumentException;

/**
 * Container for storing other collections.
 *
 * @template TCollection of AbstractTypedCollection
 *
 * @extends AbstractTypedCollection<TCollection>
 */
final class CollectionContainer extends AbstractTypedCollection
{
    public function __construct(string ...$allowedCollectionTypes)
    {
        if (empty($allowedCollectionTypes)) {
            throw new InvalidArgumentException('At least one concrete Collection class must be provided');
        }

        foreach ($allowedCollectionTypes as $type) {
            if (! is_subclass_of($type, AbstractTypedCollection::class)) {
                throw new InvalidArgumentException(sprintf(
                    'Type "%s" must be a subclass of %s',
                    $type,
                    AbstractTypedCollection::class
                ));
            }
        }

        parent::__construct(...$allowedCollectionTypes);
    }

    public function flatten(): array
    {
        $result = [];
        foreach ($this->items as $collection) {
            if ($collection instanceof AbstractTypedCollection) {
                $result = array_merge($result, $collection->toArray());
            }
        }

        return $result;
    }

    public function flattenDeep(): array
    {
        $result = [];
        foreach ($this->items as $collection) {
            if ($collection instanceof self) {
                $result = array_merge($result, $collection->flattenDeep());
            } elseif ($collection instanceof AbstractTypedCollection) {
                $result = array_merge($result, $collection->toArray());
            } else {
                $result[] = $collection;
            }
        }

        return $result;
    }
}
