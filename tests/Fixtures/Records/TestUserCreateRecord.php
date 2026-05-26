<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Fixtures\Records;

use AndyDefer\DomainStructures\Abstracts\AbstractRecord;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestLifeStage;

final class TestUserCreateRecord extends AbstractRecord
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly TestLifeStage $lifeStage = TestLifeStage::UNKNOWN,
    ) {}
}
