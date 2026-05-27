<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Interfaces;

/**
 * Contract for Data DTO objects.
 *
 * Defines the essential methods that all Data DTOs must implement to ensure
 * consistent data transformation across the application. Data DTOs are pure,
 * immutable structures used exclusively for HTTP responses.
 *
 * @author Andy Defer
 */
interface DataInterface
{
    /**
     * Normalizes the Data DTO to an array for API responses.
     *
     * The conversion handles:
     * - Nested Data objects (recursive conversion)
     * - Value Objects (recursive conversion)
     * - Records (recursive conversion)
     * - Enums (converted to their scalar values or names)
     * - DateTime objects (converted to ISO 8601 format)
     * - Property keys remain in camelCase
     *
     * @return array<string, mixed> Associative array
     */
    public function normalize(): array;
}
