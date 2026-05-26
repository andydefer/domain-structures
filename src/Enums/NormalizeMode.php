<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Enums;

/**
 * Normalization mode for Record and Data objects.
 */
enum NormalizeMode: string
{
    case ARRAY = 'array';
    case JSON = 'json';
}
