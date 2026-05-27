<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Fixtures\Records;

use AndyDefer\DomainStructures\Abstracts\AbstractRecord;

/**
 * Record avec des propriétés REQUISES (sans valeurs par défaut)
 * Pour tester les erreurs d'hydratation.
 */
final class TestRequiredRecord extends AbstractRecord
{
    public function __construct(
        public readonly string $name,        // ← REQUIS ! Pas de valeur par défaut
        public readonly string $email,       // ← REQUIS ! Pas de valeur par défaut
        public readonly ?string $optional = null,  // ← Optionnel
    ) {}
}
