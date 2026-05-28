<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Fixtures\Mixed;

use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestUserStatus;
use AndyDefer\DomainStructures\Tests\Fixtures\ValueObjects\TestEmailAddress;
use AndyDefer\DomainStructures\Tests\Fixtures\ValueObjects\TestIso8601DateTime;

/**
 * Objet avec méthode toArray() pour tester l'hydratation.
 * Simule un objet qui ne peut pas exposer ses propriétés directement.
 */
final class ObjectWithToArray
{
    public function toArray(): array
    {
        return [
            'id' => 123,
            'name' => 'ToArray Name',
            'email' => TestEmailAddress::from('toarray@example.com'),
            'status' => TestUserStatus::ACTIVE,
            'createdAt' => TestIso8601DateTime::from(new DateTime),
        ];
    }
}
