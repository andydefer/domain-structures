<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Traits;

use AndyDefer\DomainStructures\Hydration\Hydrator;
use AndyDefer\DomainStructures\Normalizers\NormalizerChain;
use AndyDefer\DomainStructures\Utils\DataObject;
use InvalidArgumentException;

/**
 * @deprecated Ce trait est déprécié depuis la version 2.0.0.
 *             Il sera supprimé dans la version 3.0.0.
 * 
 * RAISONS DE LA DÉPRÉCIATION :
 * 
 * 1. MAGIE IMPLICITE - Violation du principe "Explicit is better than implicit"
 *    Le magic __get() rend le code difficile à suivre et à déboguer.
 *    On ne sait pas d'où viennent les propriétés ni comment elles sont résolues.
 * 
 * 2. COUPLAGE CACHÉ - Le trait dépend implicitement de plusieurs services
 *    (Hydrator, NormalizerChain, DataObject) créant un couplage fort invisible.
 * 
 * 3. PERFORMANCE - La réflexion à chaque accès propriété est coûteuse.
 *    Le __get() est appelé pour chaque lecture, avec normalisation et hydratation.
 * 
 * 4. DIFFICULTÉ DE DEBUG - Les erreurs sont masquées, le stack trace est illisible.
 *    Quand une propriété n'existe pas, l'exception est levée dans __get().
 * 
 * 5. VIOLATION DU SRP - Un Value Object ne devrait pas gérer la normalisation,
 *    l'hydratation et la réflexion. Ces responsabilités appartiennent à des Services.
 * 
 * 6. PAS DE TYPE-HINTING - Les IDEs ne peuvent pas suggérer les propriétés.
 *    Pas d'autocomplétion, pas de refactoring sécurisé.
 * 
 * 7. RISQUE D'EFFETS DE BORD - Le __get() modifie le comportement normal.
 *    Les propriétés privées deviennent accessibles comme si elles étaient publiques.
 * 
 * 8. DÉPENDANCE À LA RÉFLEXION - Fragile, casse facilement avec l'optimisation OPcache.
 * 
 * ✅ NOUVELLE APPROCHE : Utilisez des Records ou des méthodes getter explicites.
 * 
 * @example
 * // ❌ À ÉVITER (déprécié)
 * final class Money extends AbstractValueObject
 * {
 *     use HasPropertiesAccess;
 * 
 *     public function __construct(
 *         private readonly Amount $amount,
 *         private readonly Currency $currency
 *     ) {}
 * }
 * 
 * $money = Money::from(['amount' => 100, 'currency' => 'EUR']);
 * echo $money->amount;  // Magique - on ne sait pas d'où ça vient
 * echo $money->currency; // Magique
 * 
 * // ✅ RECOMMANDÉ (nouvelle approche)
 * final class Money extends AbstractValueObject
 * {
 *     public function __construct(
 *         private readonly Amount $amount,
 *         private readonly Currency $currency
 *     ) {}
 * 
 *     // Getter explicite - clair, prévisible, typé
 *     public function getAmount(): Amount
 *     {
 *         return $this->amount;
 *     }
 * 
 *     public function getCurrency(): Currency
 *     {
 *         return $this->currency;
 *     }
 * 
 *     // Alternative : Record (PHP 8.0+)
 *     public readonly Amount $amount;
 *     public readonly Currency $currency;
 * }
 * 
 * $money = new Money(new Amount(100), new Currency('EUR'));
 * echo $money->getAmount();  // Explicite, typé, prévisible
 * echo $money->amount;        // Pour les Records (PHP 8.0+)
 * 
 * @author Andy Defer
 * @deprecated since 2.0.0, will be removed in 3.0.0
 */
trait HasPropertiesAccess
{
    /**
     * Magic getter for accessing properties.
     * 
     * @deprecated L'utilisation du magic __get() est dépréciée.
     *             Utilisez des getters explicites ou des Records.
     * 
     * @param  string  $name  Property name
     * @throws InvalidArgumentException
     */
    public function __get(string $name): mixed
    {
        // Déclencher une erreur de dépréciation
        @trigger_error(
            sprintf(
                'L\'utilisation du magic __get() dans %s est dépréciée depuis la version 2.0.0. ' .
                    'Cette méthode sera supprimée dans la version 3.0.0. ' .
                    'Utilisez des getters explicites (ex: get%s()) ou utilisez un Record PHP 8.0+.',
                static::class,
                ucfirst($name)
            ),
            E_USER_DEPRECATED
        );

        // Get flattened data
        $flatData = NormalizerChain::get()->normalize($this);
        $dataObject = DataObject::from($flatData);

        // Check if property exists in flattened data
        if (! isset($dataObject[$name])) {
            throw new InvalidArgumentException(
                sprintf('Property "%s" does not exist in %s', $name, static::class)
            );
        }

        $rawValue = $dataObject[$name];

        // Get property type via reflection
        $property = $this->getPropertyType($name);

        if ($property === null) {
            return $rawValue;
        }

        $typeName = $property->getName();

        // Si la valeur est déjà du bon type
        if ($rawValue instanceof $typeName) {
            return $rawValue;
        }

        // Utiliser Hydrator pour reconstruire l'objet
        if (class_exists($typeName) && method_exists($typeName, 'from')) {
            return Hydrator::hydrate($typeName, $rawValue);
        }

        return $rawValue;
    }

    /**
     * Check if a property exists.
     * 
     * @deprecated L'utilisation du magic __isset() est dépréciée.
     *             Utilisez des méthodes hasXXX() explicites.
     */
    public function __isset(string $name): bool
    {
        @trigger_error(
            sprintf(
                'L\'utilisation du magic __isset() dans %s est dépréciée depuis la version 2.0.0. ' .
                    'Cette méthode sera supprimée dans la version 3.0.0.',
                static::class
            ),
            E_USER_DEPRECATED
        );

        $flatData = NormalizerChain::get()->normalize($this);
        $dataObject = DataObject::from($flatData);

        return isset($dataObject[$name]);
    }

    /**
     * Get property type using reflection.
     * 
     * @deprecated La réflexion pour accéder aux propriétés privées est dépréciée.
     *             Les propriétés doivent être directement accessibles via getters publics.
     */
    private function getPropertyType(string $propertyName): ?\ReflectionNamedType
    {
        try {
            $reflection = new \ReflectionClass($this);

            if (! $reflection->hasProperty($propertyName)) {
                return null;
            }

            $property = $reflection->getProperty($propertyName);
            $type = $property->getType();

            if ($type instanceof \ReflectionNamedType) {
                return $type;
            }

            return null;
        } catch (\RuntimeException) {
            return null;
        }
    }
}
