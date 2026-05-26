<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Fixtures\Collections;

use AndyDefer\DomainStructures\Abstracts\AbstractRecord;
use AndyDefer\DomainStructures\Abstracts\AbstractTypedCollection;
use AndyDefer\DomainStructures\Abstracts\AbstractValueObject;
use stdClass;
use UnitEnum;

/**
 * Mixed collection for testing multiple allowed types.
 *
 * @extends AbstractTypedCollection<AbstractRecord|AbstractValueObject|UnitEnum|stdClass>
 */
final class MixedTypedCollection extends AbstractTypedCollection
{
    public function __construct()
    {
        parent::__construct(
            AbstractRecord::class,
            AbstractValueObject::class,
            UnitEnum::class,
            stdClass::class
        );
    }

    /**
     * Group items by their type.
     *
     * @return array<string, array>
     */
    public function groupByType(): array
    {
        $groups = [];

        foreach ($this->items as $item) {
            $type = match (true) {
                $item instanceof AbstractRecord => 'record',
                $item instanceof AbstractValueObject => 'value_object',
                $item instanceof UnitEnum => 'enum',
                $item instanceof stdClass => 'stdclass',
                default => 'unknown'
            };
            $groups[$type][] = $item;
        }

        return $groups;
    }
}
