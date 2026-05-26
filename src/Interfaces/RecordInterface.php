<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Interfaces;

use AndyDefer\DomainStructures\Abstracts\AbstractTypedCollection;
use AndyDefer\DomainStructures\Collections\Core\RecordCollection;
use AndyDefer\DomainStructures\Enums\NormalizeMode;

/**
 * Contract for Record objects.
 */
interface RecordInterface
{
    /**
     * Convert the record to array or JSON for database operations.
     *
     * Serialization is automatic from all public properties.
     * Keys are automatically converted to snake_case.
     *
     * @param  bool  $includeNulls  If true, includes null values; if false, excludes them (useful for updates)
     * @param  NormalizeMode  $mode  Output mode: ARRAY or JSON
     * @return array<string, mixed>|string
     */
    public function normalize(NormalizeMode $mode = NormalizeMode::ARRAY, bool $includeNulls = true): array|string;

    /**
     * Create a Record instance from any object (Record, ValueObject, stdClass, etc.).
     *
     * @param  object  $source  Source object to convert from
     */
    public static function from(object $source): static;

    /**
     * Create an array or JSON of Record instances from a RecordCollection.
     *
     * @param  RecordCollection  $collection  Collection of Record objects
     * @param  NormalizeMode  $mode  Output mode: ARRAY or JSON
     * @return array<int, static>|string
     */
    public static function collect(TypedCollectionInterface $collection, NormalizeMode $mode = NormalizeMode::ARRAY): array|string;
}
