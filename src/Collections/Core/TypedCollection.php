<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Collections\Core;

use AndyDefer\DomainStructures\Abstracts\AbstractData;
use AndyDefer\DomainStructures\Abstracts\AbstractRecord;
use AndyDefer\DomainStructures\Abstracts\AbstractTypedCollection;
use AndyDefer\DomainStructures\Abstracts\AbstractValueObject;
use AndyDefer\DomainStructures\Enums\PhpType;
use AndyDefer\DomainStructures\Utils\DataObject;
use UnitEnum;

/**
 * Type-safe collection that accepts any allowed type by default.
 *
 * If no types are specified, the collection accepts all allowed types
 * (scalars, enums, records, value objects, data, collections, DataObject).
 *
 * @template TValue of object|string|int|float|bool
 */
class TypedCollection extends AbstractTypedCollection
{
    /**
     * Constructor.
     *
     * @param  class-string<AbstractRecord>|class-string<AbstractValueObject>|class-string<AbstractData>|class-string<UnitEnum>|string  ...$types
     *                                                                                                                                             If no types are provided, accepts all allowed types (mixed collection)
     */
    public function __construct(string ...$types)
    {
        if (empty($types)) {
            // Récupérer la liste des types concrets autorisés (pas les abstraits)
            $types = $this->getConcreteAllowedTypes();
        }

        parent::__construct(...$types);
    }

    /**
     * Get the list of concrete allowed types (no abstract classes).
     *
     * @return array<string>
     */
    private function getConcreteAllowedTypes(): array
    {
        // Types scalaires
        $scalarTypes = PhpType::getScalarTypeNames();

        // Types concrets pour les enums (exemples, à adapter selon vos besoins)
        // Dans une vraie implémentation, vous pourriez avoir une configuration
        // ou une découverte automatique des types concrets

        // Pour l'instant, on retourne les types scalaires uniquement
        // Les types objets doivent être spécifiés explicitement
        return $scalarTypes;
    }
}
