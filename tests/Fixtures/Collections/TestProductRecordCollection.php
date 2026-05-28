<?php

// FILE: tests/Fixtures/Collections/TestProductRecordCollection.php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Fixtures\Collections;

use AndyDefer\DomainStructures\Abstracts\AbstractTypedCollection;
use AndyDefer\DomainStructures\Collections\Utility\StringTypedCollection;
use AndyDefer\DomainStructures\Tests\Fixtures\Records\TestProductRecord;

/**
 * Collection that can ONLY contain TestProductRecord objects.
 *
 * @extends AbstractTypedCollection<TestProductRecord>
 */
final class TestProductRecordCollection extends AbstractTypedCollection
{
    public function __construct()
    {
        parent::__construct(TestProductRecord::class);
    }

    /**
     * Get total price of all products in the collection.
     */
    public function getTotalPrice(): float
    {
        $total = 0.0;
        foreach ($this->items as $product) {
            $total += $product->price ?? 0;
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
            if ($product->isFeatured === true) {
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
            $names->add($product->name ?? '');
        }

        return $names;
    }

    /**
     * Get product IDs as array.
     *
     * @return array<int>
     */
    public function getProductIds(): array
    {
        $ids = [];
        foreach ($this->items as $product) {
            if ($product->id !== null) {
                $ids[] = $product->id;
            }
        }

        return $ids;
    }

    /**
     * Filter products by minimum price.
     */
    public function wherePriceGreaterThan(float $minPrice): self
    {
        $result = new self;
        foreach ($this->items as $product) {
            if (($product->price ?? 0) > $minPrice) {
                $result->add($product);
            }
        }

        return $result;
    }

    /**
     * Filter products by maximum price.
     */
    public function wherePriceLessThan(float $maxPrice): self
    {
        $result = new self;
        foreach ($this->items as $product) {
            if (($product->price ?? 0) < $maxPrice) {
                $result->add($product);
            }
        }

        return $result;
    }

    /**
     * Check if collection contains a product with given ID.
     */
    public function containsProductId(int $productId): bool
    {
        foreach ($this->items as $product) {
            if ($product->id === $productId) {
                return true;
            }
        }

        return false;
    }
}
