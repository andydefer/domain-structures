<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Fixtures\Enums;

use AndyDefer\DomainStructures\Traits\Enumable;

/**
 * Backed enum for testing with integer values.
 */
enum TestSingleCaseEnum: string
{
    use Enumable;
    case SINGLE = 'single';
}
