<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Collections\Core;

use AndyDefer\DomainStructures\Abstracts\AbstractData;
use AndyDefer\DomainStructures\Abstracts\AbstractTypedCollection;

/**
 * Type-safe collection that can ONLY contain AbstractData objects or its concrete implementations.
 *
 * @extends AbstractTypedCollection<AbstractData>
 */
final class DataCollection extends AbstractTypedCollection
{
    /**
     * @param class-string<AbstractData> ...$allowedConcreteTypes
     */
    public function __construct(string ...$allowedConcreteTypes)
    {
        if (empty($allowedConcreteTypes)) {
            throw new \InvalidArgumentException('At least one concrete Data class must be provided');
        }

        // Vérifier que tous les types sont bien des sous-classes de AbstractData
        foreach ($allowedConcreteTypes as $type) {
            if (!is_subclass_of($type, AbstractData::class)) {
                throw new \InvalidArgumentException(sprintf(
                    'Type "%s" must be a subclass of %s',
                    $type,
                    AbstractData::class
                ));
            }
        }

        parent::__construct(...$allowedConcreteTypes);
    }
}
