<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Collections\Core;

use AndyDefer\DomainStructures\Abstracts\AbstractRecord;
use AndyDefer\DomainStructures\Abstracts\AbstractTypedCollection;

/**
 * Type-safe collection that can ONLY contain AbstractRecord objects or its concrete implementations.
 *
 * @extends AbstractTypedCollection<AbstractRecord>
 */
final class RecordCollection extends AbstractTypedCollection
{
    /**
     * @param  class-string<AbstractRecord>  ...$allowedConcreteTypes
     */
    public function __construct(string ...$allowedConcreteTypes)
    {
        if (empty($allowedConcreteTypes)) {
            throw new \InvalidArgumentException('At least one concrete Record class must be provided');
        }

        // Vérifier que tous les types sont bien des sous-classes de AbstractRecord
        foreach ($allowedConcreteTypes as $type) {
            if (! is_subclass_of($type, AbstractRecord::class)) {
                throw new \InvalidArgumentException(sprintf(
                    'Type "%s" must be a subclass of %s',
                    $type,
                    AbstractRecord::class
                ));
            }
        }

        parent::__construct(...$allowedConcreteTypes);
    }
}
