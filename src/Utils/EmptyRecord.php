<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Utils;

use AndyDefer\DomainStructures\Abstracts\AbstractRecord;

/**
 * Empty record for optional filter parameters.
 *
 * This record is used when a Service or Repository needs to accept a Record
 * parameter but no actual data is required. It provides a type-safe way to
 * handle optional filtering without using null or empty arrays.
 */
final class EmptyRecord extends AbstractRecord
{
    public function __construct() {}
}
