<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Interfaces;

/**
 * Contract for Record objects.
 *
 * Defines the essential methods that all Records must implement to ensure
 * consistent data transformation for database operations.
 *
 * @author Andy Defer
 */
interface RecordInterface
{
    /**
     * Normalizes the Record to an array for database operations.
     *
     * The conversion handles:
     * - Nested Records (recursive conversion)
     * - Value Objects (recursive conversion)
     * - Data objects (recursive conversion)
     * - Enums (converted to their scalar values or names)
     * - DateTime objects (converted to ISO 8601 format)
     * - Property keys are converted to snake_case
     *
     * @param bool $includeNulls Whether to include null values in the result
     * @return array<string, mixed> Associative array with snake_case keys
     */
    public function normalize(bool $includeNulls = true): array;
}
