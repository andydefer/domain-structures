<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Fixtures\Records;

use AndyDefer\DomainStructures\Abstracts\AbstractRecord;

final class TestAmbiguousRecordB extends AbstractRecord
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly float $price = 0.0,
    ) {}
}
