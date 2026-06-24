<?php

// src/Collections/Core/TypedCollection.php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Collections\Core;

use AndyDefer\DomainStructures\Abstracts\AbstractTypedCollection;

/**
 * @deprecated Cette classe est dépréciée depuis la version 2.0.0.
 *             Elle sera supprimée dans la version 3.0.0.
 *
 * RAISONS DE LA DÉPRÉCIATION :
 *
 * 1. CONSTRUCTEUR DYNAMIQUE - Anti-pattern
 *    Une collection doit avoir des types prédéfinis à la déclaration,
 *    pas être configurée dynamiquement à l'instanciation.
 *
 * 2. PAS DE TYPE-SAFETY À LA COMPILATION
 *    On ne sait pas quels types la collection accepte sans lire le code.
 *    L'IDE ne peut pas suggérer les méthodes spécifiques.
 *
 * 3. RISQUE D'ERREURS À L'EXÉCUTION
 *    On peut créer une collection avec n'importe quels types,
 *    ce qui peut causer des erreurs difficiles à déboguer.
 *
 * 4. VIOLATION DU PRINCIPE DE MOINDRE SURPRISE
 *    Une collection de UserData ne peut pas accepter ProductData.
 *    Pourtant, TypedCollection le permet.
 *
 * ✅ NOUVELLE APPROCHE : Créez des classes de collection spécialisées.
 *
 * @example
 * // ❌ À ÉVITER (déprécié)
 * $users = new TypedCollection(UserRecord::class);
 * $users->add(new UserRecord()); // OK
 * $users->add(new ProductRecord()); // ⚠️ Devrait être interdit mais techniquement possible
 *
 * // ✅ RECOMMANDÉ (nouvelle approche)
 * final class UserRecordCollection extends AbstractTypedCollection
 * {
 *     public function __construct()
 *     {
 *         parent::__construct(UserRecord::class);
 *     }
 *
 *     // Méthodes spécifiques à UserRecord
 *     public function getAdmins(): self
 *     {
 *         return $this->filter(fn(UserRecord $user) => $user->role === 'admin');
 *     }
 * }
 *
 * $users = new UserRecordCollection();
 * $users->add(new UserRecord()); // ✅ Type-safe
 * $users->add(new ProductRecord()); // ❌ Erreur à la compilation
 *
 * @author Andy Defer
 *
 * @deprecated since 2.0.0, will be removed in 3.0.0
 */
class TypedCollection extends AbstractTypedCollection
{
    public function __construct(string ...$types)
    {
        @trigger_error(
            sprintf(
                'La classe %s est dépréciée depuis la version 2.0.0. '.
                    'Créez une classe de collection spécialisée qui étend AbstractTypedCollection. '.
                    'Elle sera supprimée dans la version 3.0.0.',
                self::class
            ),
            E_USER_DEPRECATED
        );

        parent::__construct(...$types);
    }
}
