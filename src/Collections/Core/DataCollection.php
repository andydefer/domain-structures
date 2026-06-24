<?php

// src/Collections/Core/DataCollection.php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Collections\Core;

use AndyDefer\DomainStructures\Abstracts\AbstractData;
use AndyDefer\DomainStructures\Abstracts\AbstractTypedCollection;

/**
 * @deprecated Cette classe est dépréciée depuis la version 2.0.0.
 *             Elle sera supprimée dans la version 3.0.0.
 *
 * RAISONS DE LA DÉPRÉCIATION :
 *
 * 1. CONSTRUCTEUR DYNAMIQUE - Anti-pattern
 *    Une collection doit avoir ses types prédéfinis.
 *
 * 2. PAS DE MÉTHODES SPÉCIFIQUES AU DOMAINE
 *    Une collection UserDataCollection peut avoir getActiveUsers().
 *
 * @example
 * // ❌ À ÉVITER (déprécié)
 * $users = new DataCollection(UserData::class);
 *
 * // ✅ RECOMMANDÉ (nouvelle approche)
 * final class UserDataCollection extends AbstractTypedCollection
 * {
 *     public function __construct()
 *     {
 *         parent::__construct(UserData::class);
 *     }
 *
 *     public function toRecords(): UserRecordCollection
 *     {
 *         return $this->mapToType(
 *             fn(UserData $data) => UserRecord::from($data),
 *             UserRecordCollection::class
 *         );
 *     }
 * }
 *
 * @author Andy Defer
 *
 * @deprecated since 2.0.0, will be removed in 3.0.0
 */
final class DataCollection extends AbstractTypedCollection
{
    public function __construct(string ...$allowedConcreteTypes)
    {
        @trigger_error(
            sprintf(
                'La classe %s est dépréciée depuis la version 2.0.0. '.
                    'Créez une classe de collection spécialisée pour vos Data. '.
                    'Elle sera supprimée dans la version 3.0.0.',
                self::class
            ),
            E_USER_DEPRECATED
        );

        if (empty($allowedConcreteTypes)) {
            throw new \InvalidArgumentException('At least one concrete Data class must be provided');
        }

        foreach ($allowedConcreteTypes as $type) {
            if (! is_subclass_of($type, AbstractData::class)) {
                throw new \InvalidArgumentException(sprintf(
                    'Type "%s" must be a subclass of %s',
                    $type,
                    AbstractData::class
                ));
            }
        }

        parent::__construct(...$allowedConcreteTypes);
    }
}
