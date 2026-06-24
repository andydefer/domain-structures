<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Utils;

use AndyDefer\DomainStructures\Abstracts\AbstractSequential;

/**
 * Sequential - Collection séquentielle sensible à la casse.
 *
 * Toutes les opérations (contains, indexOf) sont sensibles à la casse.
 * Les éléments sont indexés par position (0, 1, 2, ...).
 *
 * @template T
 */
class Sequential extends AbstractSequential
{
    // Hérite de AbstractSequential - sensible à la casse par défaut
}
