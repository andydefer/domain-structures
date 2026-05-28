<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Fixtures\Collections;

use AndyDefer\DomainStructures\Abstracts\AbstractTypedCollection;
use AndyDefer\DomainStructures\Tests\Fixtures\Data\TestProductData;
use AndyDefer\DomainStructures\Tests\Fixtures\Records\TestProductRecord;

/**
 * Collection that can contain both TestProductData and TestProductRecord.
 * Used for transformation from Record to Data.
 *
 * @extends AbstractTypedCollection<TestProductData|TestProductRecord>
 */
final class TestProductCollection extends AbstractTypedCollection
{
    public function __construct()
    {
        parent::__construct(TestProductData::class, TestProductRecord::class);
    }

    /**
     * Convert all items to Data objects.
     */
    public function toDataCollection(): TestProductDataCollection
    {
        $dataCollection = new TestProductDataCollection;

        foreach ($this->items as $item) {
            if ($item instanceof TestProductRecord) {
                $dataCollection->add(TestProductData::from($item));
            } elseif ($item instanceof TestProductData) {
                $dataCollection->add($item);
            }
        }

        return $dataCollection;
    }

    /**
     * Convert all items to Record objects.
     */
    public function toRecordCollection(): TestProductRecordCollection
    {
        $recordCollection = new TestProductRecordCollection;

        foreach ($this->items as $item) {
            if ($item instanceof TestProductData) {
                // On ne peut pas convertir Data en Record sans avoir un modèle source
                throw new \RuntimeException('Cannot convert Data to Record');
            }
            $recordCollection->add($item);
        }

        return $recordCollection;
    }
}
