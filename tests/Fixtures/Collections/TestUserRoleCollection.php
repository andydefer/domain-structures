<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Fixtures\Collections;

use AndyDefer\DomainStructures\Abstracts\AbstractTypedCollection;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestUserRole;

/**
 * Collection personnalisée qui ne peut contenir que des TestUserRole.
 *
 * @extends TypedCollection<TestUserRole>
 */
final class TestUserRoleCollection extends AbstractTypedCollection
{
    public function __construct()
    {
        parent::__construct(TestUserRole::class);
    }

    /**
     * Vérifie si la collection contient le rôle ADMIN.
     */
    public function hasAdmin(): bool
    {
        return $this->contains(TestUserRole::ADMIN);
    }

    /**
     * Vérifie si la collection contient le rôle USER.
     */
    public function hasUser(): bool
    {
        return $this->contains(TestUserRole::USER);
    }

    /**
     * Vérifie si la collection contient le rôle GUEST.
     */
    public function hasGuest(): bool
    {
        return $this->contains(TestUserRole::GUEST);
    }

    /**
     * Retourne tous les rôles sous forme de tableau de valeurs scalaires.
     *
     * @return array<string>
     */
    public function toValuesArray(): array
    {
        return $this->map(fn (TestUserRole $role) => $role->value)->toArray();
    }
}
