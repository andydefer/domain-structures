<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Fixtures\Records;

use AndyDefer\DomainStructures\Abstracts\AbstractRecord;
use AndyDefer\DomainStructures\Collections\Utility\StringTypedCollection;

final class TestProductRecord extends AbstractRecord
{
    public function __construct(
        public readonly ?int $id = null,

        public readonly ?string $name = null,
        public readonly ?float $price = null,
        /**
         * @param  array<string, mixed>|null  $metadata  JSON metadata (key-value pairs)
         */
        public readonly ?StringTypedCollection $metadata = null,
        public readonly ?bool $isFeatured = null,
        public readonly ?int $productableId = null,
        public readonly ?string $productableType = null,
    ) {}
}
