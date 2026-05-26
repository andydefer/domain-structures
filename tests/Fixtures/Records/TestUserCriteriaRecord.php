<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Fixtures\Records;

use AndyDefer\DomainStructures\Abstracts\AbstractRecord;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestLifeStage;

final class TestUserCriteriaRecord extends AbstractRecord
{
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?string $email = null,
        public readonly ?TestLifeStage $lifeStage = null,
    ) {}
}
