<?php

// FILE: src/Interfaces/Fromable.php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Interfaces;

/**
 * Interface for objects that can be hydrated from a source.
 */
interface Fromable
{
    /**
     * Creates an instance from a source.
     *
     * @param  mixed  $source  The source data (array, object, string, int, etc.)
     *
     * @throws \InvalidArgumentException If the source cannot be converted
     */
    public static function from(mixed $source): static;
}
