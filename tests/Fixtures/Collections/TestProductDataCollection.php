<?php

// FILE: tests/Fixtures/Collections/TestProductDataCollection.php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Fixtures\Collections;

use AndyDefer\DomainStructures\Abstracts\AbstractTypedCollection;
use AndyDefer\DomainStructures\Collections\Utility\StringTypedCollection;
use AndyDefer\DomainStructures\Tests\Fixtures\Data\TestProductData;

/**
 * Collection that can ONLY contain TestProductData objects.
 *
 * @extends AbstractTypedCollection<TestProductData>
 */
final class TestProductDataCollection extends AbstractTypedCollection
{
    public function __construct()
    {
        parent::__construct(TestProductData::class);
    }

    /**
     * Get total price of all products in the collection.
     */
    public function getTotalPrice(): float
    {
        $total = 0.0;
        foreach ($this->items as $product) {
            $total += $product->price;
        }

        return $total;
    }

    /**
     * Get only featured products.
     */
    public function getFeatured(): self
    {
        $result = new self;
        foreach ($this->items as $product) {
            if ($product->isFeatured) {
                $result->add($product);
            }
        }

        return $result;
    }

    /**
     * Get product names as string collection.
     */
    public function getProductNames(): StringTypedCollection
    {
        $names = new StringTypedCollection;
        foreach ($this->items as $product) {
            $names->add($product->name);
        }

        return $names;
    }
}
