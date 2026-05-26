<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Fixtures\Enums;

use AndyDefer\DomainStructures\Traits\Enumable;

enum TestUserRole: string
{
    use Enumable;

    case ADMIN = 'admin';
    case USER = 'user';
    case GUEST = 'guest';
}
