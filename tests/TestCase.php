<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests;

use PHPUnit\Framework\TestCase as FrameworkTestCase;

/**
 * Base test case for the Domain Structures package.
 *
 * Provides a consistent testing environment.
 */
abstract class TestCase extends FrameworkTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }
}
