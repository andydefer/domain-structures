<?php

// src/Configs/CollectionFamilyConfig.php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Configs;

use AndyDefer\DomainStructures\Abstracts\AbstractData;
use AndyDefer\DomainStructures\Abstracts\AbstractDataObject;
use AndyDefer\DomainStructures\Abstracts\AbstractRecord;
use AndyDefer\DomainStructures\Abstracts\AbstractValueObject;
use UnitEnum;

/**
 * Configuration des familles de types pour les collections.
 */
final class CollectionFamilyConfig
{
    public function enum(): string
    {
        return UnitEnum::class;
    }

    public function valueObject(): string
    {
        return AbstractValueObject::class;
    }

    public function data(): string
    {
        return AbstractData::class;
    }

    public function record(): string
    {
        return AbstractRecord::class;
    }

    public function dataObject(): string
    {
        return AbstractDataObject::class;
    }

    public function scalar(): string
    {
        return 'scalar';
    }

    public function getDisplayName(string $family): string
    {
        return match ($family) {
            $this->enum() => 'Enum',
            $this->valueObject() => 'ValueObject',
            $this->data() => 'Data',
            $this->record() => 'Record',
            $this->dataObject() => 'DataObject',
            $this->scalar() => 'scalar',
            default => $family,
        };
    }

    public function getAll(): array
    {
        return [
            $this->enum(),
            $this->valueObject(),
            $this->data(),
            $this->record(),
            $this->dataObject(),
            $this->scalar(),
        ];
    }
}
