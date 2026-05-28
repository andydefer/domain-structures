<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Fixtures\Records;

use AndyDefer\DomainStructures\Abstracts\AbstractRecord;
use AndyDefer\DomainStructures\Tests\Fixtures\ValueObjects\TestEmailAddress;

/**
 * Test record with REQUIRED properties (no defaults, not nullable)
 * Used for testing hydration errors when required properties are missing.
 */
final class TestRequiredUserRecord extends AbstractRecord
{
    public function __construct(
        public readonly string $name,                    // REQUIRED
        public readonly TestEmailAddress $email,         // REQUIRED
        public readonly ?int $id = null,                 // Optional
        public readonly ?string $status = null,          // Optional
        public readonly ?string $role = null,            // Optional
    ) {}
}
