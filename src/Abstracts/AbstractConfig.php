<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Abstracts;

/**
 * Abstract base class for configuration classes.
 *
 * Forces child classes to have no constructor parameters.
 * All configuration values must be hardcoded in methods or loaded from environment.
 * Config classes MUST have NO properties - only methods.
 */
abstract class AbstractConfig
{
    /**
     * Final constructor prevents any constructor parameters.
     */
    final public function __construct()
    {
        // No validation, no logic - just prevents parameters
    }
}
