<?php

// src/Collections/Core/RecordCollection.php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Collections\Core;

use AndyDefer\DomainStructures\Abstracts\AbstractRecord;
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
 * 2. PAS DE MÉTHODES SPÉCIFIQUES
 *    Une collection spécialisée peut avoir des méthodes comme getUsersByRole().
 *
 * 3. VALIDATION À L'EXÉCUTION SEULEMENT
 *    Les erreurs de type ne sont détectées qu'à l'exécution.
 *
 * @example
 * // ❌ À ÉVITER (déprécié)
 * $users = new RecordCollection(UserRecord::class);
 *
 * // ✅ RECOMMANDÉ (nouvelle approche)
 * final class UserRecordCollection extends AbstractTypedCollection
 * {
 *     public function __construct()
 *     {
 *         parent::__construct(UserRecord::class);
 *     }
 *
 *     public function getByEmail(string $email): ?UserRecord
 *     {
 *         return $this->find(fn(UserRecord $user) => $user->email === $email);
 *     }
 * }
 *
 * @author Andy Defer
 *
 * @deprecated since 2.0.0, will be removed in 3.0.0
 */
final class RecordCollection extends AbstractTypedCollection
{
    public function __construct(string ...$allowedConcreteTypes)
    {

        if (empty($allowedConcreteTypes)) {
            throw new \InvalidArgumentException('At least one concrete Record class must be provided');
        }

        foreach ($allowedConcreteTypes as $type) {
            if (! is_subclass_of($type, AbstractRecord::class)) {
                throw new \InvalidArgumentException(sprintf(
                    'Type "%s" must be a subclass of %s',
                    $type,
                    AbstractRecord::class
                ));
            }
        }

        parent::__construct(...$allowedConcreteTypes);
    }
}
