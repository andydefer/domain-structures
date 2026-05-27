<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Fixtures\Mixed;

/**
 * Objet avec getters booléens pour tester l'hydratation.
 */
final class ObjectWithBooleanGetters
{
    private bool $active = true;

    public function isActive(): bool
    {
        return $this->active;
    }
}
