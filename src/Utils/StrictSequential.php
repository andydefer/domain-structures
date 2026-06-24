<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Utils;

use AndyDefer\DomainStructures\Abstracts\AbstractSequential;

/**
 * StrictSequential - Collection séquentielle sensible à la casse.
 *
 * Toutes les opérations sont sensibles à la casse.
 * C'est l'équivalent de Sequential mais avec un nom plus explicite.
 *
 * @template T
 */
class StrictSequential extends AbstractSequential
{
    // Hérite de AbstractSequential - sensible à la casse par défaut
}
