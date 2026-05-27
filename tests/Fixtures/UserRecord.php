<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Fixtures;

use AndyDefer\DomainStructures\Tests\Fixtures\ValueObjects\TestEmailAddress;
use AndyDefer\DomainStructures\Traits\Hydratable;

final class UserRecord
{
    use Hydratable;

    public function __construct(
        public readonly int $id,
        public readonly string $email,
        public readonly string $firstName,
        public readonly ?string $lastName = null,
        public readonly ?TestEmailAddress $emailVO = null
    ) {}
}
