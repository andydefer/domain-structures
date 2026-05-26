<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Interfaces;

use AndyDefer\DomainStructures\Collections\Core\DataCollection;
use AndyDefer\DomainStructures\Enums\NormalizeMode;
use InvalidArgumentException;

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
     * Normalizes the Data DTO to array or JSON for API responses.
     *
     * The conversion handles:
     * - Nested Data objects (recursive conversion)
     * - Value Objects (recursive conversion)
     * - Records (recursive conversion)
     * - Enums (converted to their scalar values or names)
     * - DateTime objects (converted to ISO 8601 format)
     * - Property keys remain in camelCase
     *
     * @param  NormalizeMode  $mode  Output mode: ARRAY or JSON
     * @return array<string, mixed>|string Associative array or JSON string
     */
    public function normalize(NormalizeMode $mode = NormalizeMode::ARRAY): array|string;

    /**
     * Create a Data instance from any object (Record, ValueObject, stdClass, etc.).
     *
     * @param  object  $source  Source object to convert from
     */
    public static function from(object $source): static;

    /**
     * Creates an array of Data DTO instances from a DataCollection.
     *
     * @param  DataCollection  $collection  Collection of Data objects
     * @param  NormalizeMode  $mode  Output mode: ARRAY or JSON
     * @return array<int, static>|string Array of DTO instances or JSON string
     *
     * @throws InvalidArgumentException When the collection contains invalid items
     */
    public static function collect(DataCollection $collection, NormalizeMode $mode = NormalizeMode::ARRAY): array|string;
}
