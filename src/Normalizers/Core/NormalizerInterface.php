<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Normalizers\Core;

interface NormalizerInterface
{
    /**
     * Checks if this normalizer supports the given value.
     *
     * @param mixed $value The value to check
     * @return bool True if supported, false otherwise
     */
    public function supports(mixed $value): bool;

    /**
     * Normalizes the value to a plain array or scalar.
     * Always returns a normalized representation (array for complex objects, scalar for simple values).
     * Null values are always included.
     *
     * @param mixed $value The value to normalize
     * @return mixed The normalized value (array, scalar, or null)
     */
    public function normalize(mixed $value): mixed;

    /**
     * Sets the next normalizer in the chain.
     *
     * @param NormalizerInterface|null $next The next normalizer
     */
    public function setNext(?NormalizerInterface $next): void;
}
