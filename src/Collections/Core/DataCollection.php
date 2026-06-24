<?php

// src/Collections/Core/DataCollection.php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Collections\Core;

use AndyDefer\DomainStructures\Abstracts\AbstractData;
use AndyDefer\DomainStructures\Abstracts\AbstractTypedCollection;

final class DataCollection extends AbstractTypedCollection
{
    public function __construct(string ...$allowedConcreteTypes)
    {

        if (empty($allowedConcreteTypes)) {
            throw new \InvalidArgumentException('At least one concrete Data class must be provided');
        }

        foreach ($allowedConcreteTypes as $type) {
            if (! is_subclass_of($type, AbstractData::class)) {
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
