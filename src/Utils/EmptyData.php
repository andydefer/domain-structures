<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Utils;

use AndyDefer\DomainStructures\Abstracts\AbstractData;

/**
 * Empty Data DTO representing an empty API response.
 *
 * Useful for endpoints that return no data (204 No Content) or when
 * the response should be an empty object.
 */
final class EmptyData extends AbstractData
{
    public function __construct() {}

    public function toArray(): array
    {
        return [];
    }
}
