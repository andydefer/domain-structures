<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Collections\Core;

use AndyDefer\DomainStructures\Abstracts\AbstractTypedCollection;
use AndyDefer\DomainStructures\Abstracts\AbstractValueObject;

/**
 * Type-safe collection that can ONLY contain AbstractValueObject objects or its concrete implementations.
 *
 * @extends AbstractTypedCollection<AbstractValueObject>
 */
final class ValueObjectCollection extends AbstractTypedCollection
{
    /**
     * @param class-string<AbstractValueObject> ...$allowedConcreteTypes
     */
    public function __construct(string ...$allowedConcreteTypes)
    {
        if (empty($allowedConcreteTypes)) {
            throw new \InvalidArgumentException('At least one concrete ValueObject class must be provided');
        }

        // Vérifier que tous les types sont bien des sous-classes de AbstractValueObject
        foreach ($allowedConcreteTypes as $type) {
            if (!is_subclass_of($type, AbstractValueObject::class)) {
                throw new \InvalidArgumentException(sprintf(
                    'Type "%s" must be a subclass of %s',
                    $type,
                    AbstractValueObject::class
                ));
            }
        }

        parent::__construct(...$allowedConcreteTypes);
    }
}
