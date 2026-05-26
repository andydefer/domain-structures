<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Fixtures\Records;

use AndyDefer\DomainStructures\Abstracts\AbstractRecord;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestLifeStage;
use AndyDefer\DomainStructures\Tests\Fixtures\ValueObjects\TestEmailAddress;

final class TestUserUpdateRecord extends AbstractRecord
{
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?TestEmailAddress $email = null,
        public readonly ?TestLifeStage $lifeStage = null,
    ) {}
}
