<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Fixtures\Mixed;

use AndyDefer\DomainStructures\Tests\Fixtures\ValueObjects\TestEmailAddress;

/**
 * Objet avec getters pour tester l'hydratation.
 * Simule un objet externe ou un DTO qui expose ses données via des getters.
 */
final class ObjectWithGetters
{
    private int $id = 42;
    private string $name = 'Getter Name';
    private TestEmailAddress $email;

    public function __construct()
    {
        $this->email = TestEmailAddress::from('getter@example.com');
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getEmail(): TestEmailAddress
    {
        return $this->email;
    }
}
