<?php

// FILE: tests/Fixtures/Data/TestProductData.php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Fixtures\Data;

use AndyDefer\DomainStructures\Abstracts\AbstractData;
use AndyDefer\DomainStructures\Collections\Utility\StringTypedCollection;

final class TestProductData extends AbstractData
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly float $price,
        public readonly ?StringTypedCollection $metadata = null,
        public readonly ?bool $isFeatured = false,
    ) {}
}
