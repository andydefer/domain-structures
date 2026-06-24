<?php

// src/Collections/Core/CollectionContainer.php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Collections\Core;

use AndyDefer\DomainStructures\Abstracts\AbstractTypedCollection;
use InvalidArgumentException;

/**
 * @deprecated Cette classe est dépréciée depuis la version 2.0.0.
 *             Elle sera supprimée dans la version 3.0.0.
 *
 * RAISONS DE LA DÉPRÉCIATION :
 *
 * 1. CONSTRUCTEUR DYNAMIQUE - Anti-pattern
 *    Une collection de collections doit avoir des types prédéfinis.
 *
 * 2. RESPONSABILITÉ TROP LARGE
 *    Cette classe essaie de gérer toutes les collections.
 *
 * 3. COMPLEXITÉ INUTILE
 *    flatten() et flattenDeep() peuvent être implémentés dans des services dédiés.
 *
 * @example
 * // ❌ À ÉVITER (déprécié)
 * $container = new CollectionContainer(UserCollection::class);
 *
 * // ✅ RECOMMANDÉ (nouvelle approche)
 * final class UserCollectionContainer extends AbstractTypedCollection
 * {
 *     public function __construct()
 *     {
 *         parent::__construct(UserCollection::class);
 *     }
 *
 *     public function getAllUsers(): UserCollection
 *     {
 *         $all = new UserCollection();
 *         foreach ($this->items as $collection) {
 *             foreach ($collection->toArray() as $user) {
 *                 $all->add($user);
 *             }
 *         }
 *         return $all;
 *     }
 * }
 *
 * @author Andy Defer
 *
 * @deprecated since 2.0.0, will be removed in 3.0.0
 */
final class CollectionContainer extends AbstractTypedCollection
{
    public function __construct(string ...$allowedCollectionTypes)
    {
        @trigger_error(
            sprintf(
                'La classe %s est dépréciée depuis la version 2.0.0. '.
                    'Créez une classe de collection conteneur spécialisée. '.
                    'Elle sera supprimée dans la version 3.0.0.',
                self::class
            ),
            E_USER_DEPRECATED
        );

        if (empty($allowedCollectionTypes)) {
            throw new InvalidArgumentException('At least one concrete Collection class must be provided');
        }

        foreach ($allowedCollectionTypes as $type) {
            if (! is_subclass_of($type, AbstractTypedCollection::class)) {
                throw new InvalidArgumentException(sprintf(
                    'Type "%s" must be a subclass of %s',
                    $type,
                    AbstractTypedCollection::class
                ));
            }
        }

        parent::__construct(...$allowedCollectionTypes);
    }

    public function flatten(): array
    {
        @trigger_error(
            'La méthode flatten() est dépréciée. Utilisez un Service dédié à la place.',
            E_USER_DEPRECATED
        );

        $result = [];
        foreach ($this->items as $collection) {
            if ($collection instanceof AbstractTypedCollection) {
                $result = array_merge($result, $collection->toArray());
            }
        }

        return $result;
    }

    public function flattenDeep(): array
    {
        @trigger_error(
            'La méthode flattenDeep() est dépréciée. Utilisez un Service dédié à la place.',
            E_USER_DEPRECATED
        );

        $result = [];
        foreach ($this->items as $collection) {
            if ($collection instanceof self) {
                $result = array_merge($result, $collection->flattenDeep());
            } elseif ($collection instanceof AbstractTypedCollection) {
                $result = array_merge($result, $collection->toArray());
            } else {
                $result[] = $collection;
            }
        }

        return $result;
    }
}
