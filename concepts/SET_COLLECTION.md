# SetCollection - Référence Technique

## Description

`SetCollection` est une collection **immutable** qui représente un ensemble d'éléments uniques. Ici, l'ordre n'est plus la question centrale. Ce qui compte, c'est l'existence. L'important n'est pas "où est l'élément", mais "est-il présent ou absent ?". C'est une structure de vérité simple : oui ou non, présent ou non.

**Elle peut contenir n'importe quel type de données :** scalaires, tableaux, objets, `AbstractRecord`, `AbstractValueObject`, `Eloquent\Model`, enums, etc. Les doublons sont automatiquement supprimés.

## Hiérarchie

```
Transformable
    ↑
SetCollection
```

**Interfaces implémentées :** `ArrayAccess`, `Countable`, `IteratorAggregate`, `JsonSerializable`, `Stringable`, `Transformable`

## Rôle principal

`SetCollection` est une collection **non-typée** qui garantit l'unicité des éléments. Elle utilise une clé unique générée par `getKey()` pour identifier chaque élément. Elle est **immutable** : chaque opération retourne une nouvelle instance.

## Installation

```bash
composer require andy-defer/domain-structures
```

```php
use AndyDefer\DomainStructures\Utils\SetCollection;
```

---

## Cas d'utilisation avancés avec Records, Value Objects et Modèles

### Cas 1 : Set de Notifications (Records)

```php
use AndyDefer\LaravelNotification\Records\NotificationRecord;
use AndyDefer\LaravelNotification\Enums\NotificationStatus;
use AndyDefer\LaravelNotification\ValueObjects\UuidVO;

// Création - les doublons sont automatiquement supprimés
$notifications = new SetCollection([
    new NotificationRecord(
        id: UuidVO::generate(),
        status: NotificationStatus::PENDING,
        message: 'Bienvenue'
    ),
    new NotificationRecord(
        id: UuidVO::generate(),
        status: NotificationStatus::SENT,
        message: 'Commande confirmée'
    ),
    new NotificationRecord(
        id: UuidVO::generate(),
        status: NotificationStatus::PENDING,
        message: 'Bienvenue' // Doublon - sera ignoré (même contenu)
    ),
]);

// ✅ Ajouter une notification
$notifications = $notifications->add(
    new NotificationRecord(
        id: UuidVO::generate(),
        status: NotificationStatus::FAILED,
        message: 'Erreur de paiement'
    )
);

// ✅ Vérifier si une notification existe
$target = new NotificationRecord(
    id: UuidVO::generate(),
    status: NotificationStatus::PENDING,
    message: 'Bienvenue'
);
if ($notifications->contains($target)) {
    echo 'La notification existe';
}

// ✅ Supprimer une notification
$toRemove = new NotificationRecord(
    id: UuidVO::generate(),
    status: NotificationStatus::SENT,
    message: 'Commande confirmée'
);
$notifications = $notifications->remove($toRemove);

// ✅ Filtrer les notifications en attente
$pending = $notifications->filter(
    fn(NotificationRecord $record) => $record->status === NotificationStatus::PENDING
);

// ✅ Transformer en messages
$messages = $notifications->map(
    fn(NotificationRecord $record) => $record->message
);

// ✅ Union de deux sets de notifications
$otherNotifications = new SetCollection([
    new NotificationRecord(
        id: UuidVO::generate(),
        status: NotificationStatus::SENT,
        message: 'Nouvelle notification'
    ),
]);
$all = $notifications->union($otherNotifications);

// ✅ Intersection (notifications communes)
$common = $notifications->intersect($otherNotifications);

// ✅ Différence (notifications uniques)
$unique = $notifications->diff($otherNotifications);
```

---

### Cas 2 : Set de Value Objects

```php
use AndyDefer\LaravelNotification\ValueObjects\UuidVO;
use AndyDefer\LaravelNotification\ValueObjects\MessageBodyVO;
use AndyDefer\DomainStructures\Utils\SetCollection;

// Création - les doublons sont supprimés
$messages = new SetCollection([
    new MessageBodyVO('Bienvenue sur notre plateforme'),
    new MessageBodyVO('Votre commande est confirmée'),
    new MessageBodyVO('Bienvenue sur notre plateforme'), // Doublon - ignoré
]);

// ✅ Ajouter un message
$messages = $messages->add(new MessageBodyVO('Erreur de paiement'));

// ✅ Vérifier si un message existe
$search = new MessageBodyVO('Bienvenue sur notre plateforme');
if ($messages->contains($search)) {
    echo 'Message trouvé';
}

// ✅ Filtrer les messages longs
$longMessages = $messages->filter(
    fn(MessageBodyVO $body) => strlen($body->getValue()) > 20
);

// ✅ Transformer en majuscules
$uppercase = $messages->map(
    fn(MessageBodyVO $body) => strtoupper($body->getValue())
);

// ✅ Set d'UUIDs
$uuids = new SetCollection([
    UuidVO::generate(),
    UuidVO::generate(),
    UuidVO::generate(),
]);

// ✅ Vérifier si un UUID existe
$targetUuid = UuidVO::generate();
if ($uuids->contains($targetUuid)) {
    echo 'UUID présent';
}
```

---

### Cas 3 : Set d'Utilisateurs (Modèles Eloquent)

```php
use App\Models\User;

// Création
$users = new SetCollection([
    User::find(1),
    User::find(2),
    User::find(3),
]);

// ✅ Ajouter un utilisateur
$users = $users->add(User::find(4));

// ✅ Vérifier si un utilisateur existe
$targetUser = User::find(2);
if ($users->contains($targetUser)) {
    echo 'Utilisateur présent';
}

// ✅ Filtrer les utilisateurs actifs
$activeUsers = $users->filter(
    fn(User $user) => $user->is_active === true
);

// ✅ Filtrer par âge
$adults = $users->filter(
    fn(User $user) => $user->age >= 18
);

// ✅ Extraire les emails (set unique d'emails)
$emails = $users->map(
    fn(User $user) => $user->email
);

// ✅ Union de deux sets d'utilisateurs
$otherUsers = new SetCollection([User::find(4), User::find(5)]);
$allUsers = $users->union($otherUsers);
```

---

### Cas 4 : Set de Tags (Strings)

```php
// Tags uniques pour une application
$tags = new SetCollection(['php', 'laravel', 'php', 'vuejs', 'javascript']);

// Résultat : ['php', 'laravel', 'vuejs', 'javascript']

// ✅ Ajouter un tag
$tags = $tags->add('react');

// ✅ Ajouter plusieurs tags
$tags = $tags->addAll(['angular', 'nodejs', 'vuejs']); // 'vuejs' déjà présent

// ✅ Vérifier si un tag existe
if ($tags->contains('php')) {
    echo 'Tag php présent';
}

// ✅ Supprimer un tag
$tags = $tags->remove('javascript');

// ✅ Union de tags
$otherTags = new SetCollection(['python', 'django', 'laravel']);
$allTags = $tags->union($otherTags);

// ✅ Intersection (tags communs)
$commonTags = $tags->intersect($otherTags);

// ✅ Différence (tags uniques)
$uniqueTags = $tags->diff($otherTags);
```

---

### Cas 5 : Set de Clés Mixtes (Plusieurs Types)

```php
use AndyDefer\LaravelNotification\Records\NotificationRecord;
use AndyDefer\LaravelNotification\ValueObjects\UuidVO;

// Set avec différents types d'éléments
$mixed = new SetCollection([
    'string value',
    123,
    45.67,
    true,
    new NotificationRecord(...),
    ['nested' => 'array'],
    UuidVO::generate(),
]);

// ✅ Les doublons sont supprimés, même entre types différents
$mixed = new SetCollection([
    '123',  // string
    123,    // int - considéré comme différent
    'true', // string
    true,   // bool - considéré comme différent
]);

// Résultat : ['123', 123, 'true', true]

// ✅ Vérifier si un élément existe
if ($mixed->contains('123')) {
    echo 'Présent';
}
```

---

### Cas 6 : Set de Collections

```php
use AndyDefer\DomainStructures\Utils\ListCollection;
use AndyDefer\LaravelNotification\Records\NotificationRecord;

// Set de ListCollection
$sets = new SetCollection([
    new ListCollection([1, 2, 3]),
    new ListCollection(['a', 'b', 'c']),
    new ListCollection([1, 2, 3]), // Doublon - ignoré
]);

// ✅ Ajouter une ListCollection
$sets = $sets->add(new ListCollection(['x', 'y', 'z']));

// ✅ Vérifier si une liste existe
$search = new ListCollection([1, 2, 3]);
if ($sets->contains($search)) {
    echo 'Liste trouvée';
}

// ✅ Filtrer les listes vides
$nonEmpty = $sets->filter(
    fn(ListCollection $list) => $list->isNotEmpty()
);
```

---

### Cas 7 : Set de Records avec `collect()`

```php
// Collecter depuis un itérable de records
$records = [
    new NotificationRecord(id: UuidVO::generate(), status: NotificationStatus::PENDING),
    new NotificationRecord(id: UuidVO::generate(), status: NotificationStatus::SENT),
    new NotificationRecord(id: UuidVO::generate(), status: NotificationStatus::PENDING), // Doublon - ignoré
];

$collection = SetCollection::collect($records);

// Collecter depuis des value objects
$uuids = [
    UuidVO::generate(),
    UuidVO::generate(),
    UuidVO::generate(), // Si doublon, ignoré
];

$uuidSet = SetCollection::collect($uuids);

// Collecter depuis des utilisateurs
$users = [
    User::find(1),
    User::find(2),
    User::find(1), // Doublon - ignoré (même instance)
];

$userSet = SetCollection::collect($users);
```

---

### Cas 8 : Chaining avec des Records

```php
// ✅ Chaînage puissant avec des records
$result = $notifications
    ->filter(fn(NotificationRecord $r) => $r->status === NotificationStatus::PENDING)
    ->map(fn(NotificationRecord $r) => $r->message)
    ->filter(fn($message) => strlen($message) > 10)
    ->add('Nouveau message') // Ajout d'un string
    ->filter(fn($item) => is_string($item)); // Garder que les strings

// Résultat : Set des messages uniques de plus de 10 caractères
```

---

### Cas 9 : Opérations Ensemblistes avec des Records

```php
// Set A : Notifications du jour
$today = new SetCollection([
    new NotificationRecord(id: UuidVO::generate(), status: NotificationStatus::SENT),
    new NotificationRecord(id: UuidVO::generate(), status: NotificationStatus::PENDING),
]);

// Set B : Notifications importantes
$important = new SetCollection([
    new NotificationRecord(id: UuidVO::generate(), status: NotificationStatus::SENT),
    new NotificationRecord(id: UuidVO::generate(), status: NotificationStatus::FAILED),
]);

// ✅ Union : toutes les notifications
$all = $today->union($important);

// ✅ Intersection : notifications communes
$common = $today->intersect($important);

// ✅ Différence : notifications uniques à aujourd'hui
$unique = $today->diff($important);

// ✅ Différence symétrique : notifications qui ne sont pas dans les deux
$symmetricDiff = $today->union($important)->diff($today->intersect($important));
```

---

### Cas 10 : Set avec `from()`

```php
// Créer un set depuis un tableau de records
$set = SetCollection::from([
    new NotificationRecord(...),
    new NotificationRecord(...),
]);

// Créer depuis un objet transformable
$record = new TestUserRecord(name: 'John', email: $this->testEmail);
$set = SetCollection::from($record);
// Résultat : [['name' => 'John', 'email' => 'test@example.com']]

// Créer depuis un objet standard
$obj = new stdClass();
$obj->name = 'John';
$obj->age = 30;
$set = SetCollection::from($obj);
// Résultat : ['John', 30]

// Créer depuis des scalaires
$set = SetCollection::from('hello');
// Résultat : ['hello']
```

---

## Pourquoi utiliser SetCollection ?

| Avantage | Explication |
|----------|-------------|
| **Unicité garantie** | Les doublons sont automatiquement éliminés |
| **Immutabilité** | Les modifications créent de nouvelles instances |
| **Vérification rapide** | `contains()` est en O(1) pour les scalaires |
| **Opérations ensemblistes** | `union()`, `intersect()`, `diff()` disponibles |
| **Normalisation** | Les objets sont normalisés via `NormalizerChain` |
| **Chaînage** | Les opérations s'enchaînent de manière fluide |

---

## Gestion des erreurs

| Situation | Exception | Message |
|-----------|-----------|---------|
| Source invalide (from) | `InvalidArgumentException` | `Cannot create SetCollection from X...` |
| JSON invalide (fromJson) | `InvalidArgumentException` | `Invalid JSON: ...` |
| Objet sans propriétés | `InvalidArgumentException` | `Cannot create SetCollection from ... Object has no public properties.` |
| Modification directe (offsetSet) | `RuntimeException` | `SetCollection is immutable. Use add() to create a new instance.` |
| Suppression directe (offsetUnset) | `RuntimeException` | `SetCollection is immutable. Use remove() to create a new instance.` |

---

## Performance

| Opération | Complexité | Notes |
|-----------|------------|-------|
| `add()` | O(1) | Ajout direct (hash) |
| `contains()` | O(1) | Recherche directe (hash) |
| `remove()` | O(1) | Suppression directe (hash) |
| `filter()` | O(n) | Parcours complet |
| `map()` | O(n) | Parcours complet |
| `reduce()` | O(n) | Parcours complet |
| `union()` | O(n) | Fusion des sets |
| `intersect()` | O(n) | Recherche dans l'autre set |
| `diff()` | O(n) | Recherche dans l'autre set |

**Mémoire :** Chaque opération crée une nouvelle instance O(n).

---

## Compatibilité

| Version PHP | Support | Notes |
|-------------|---------|-------|
| PHP 8.1+ | ✅ Complet | Types union, mixed, etc. |
| PHP 8.0 | ✅ Complet | Supporté |
| PHP 7.4 | ❌ Non | Nécessite PHP 8.0+ |

---

## Exemple complet

```php
<?php

declare(strict_types=1);

use AndyDefer\DomainStructures\Utils\SetCollection;
use AndyDefer\LaravelNotification\Records\NotificationRecord;
use AndyDefer\LaravelNotification\Enums\NotificationStatus;
use AndyDefer\LaravelNotification\ValueObjects\UuidVO;

// Création
$notifications = new SetCollection([
    new NotificationRecord(
        id: UuidVO::generate(),
        status: NotificationStatus::PENDING,
        message: 'Bienvenue'
    ),
    new NotificationRecord(
        id: UuidVO::generate(),
        status: NotificationStatus::SENT,
        message: 'Commande confirmée'
    ),
    new NotificationRecord(
        id: UuidVO::generate(),
        status: NotificationStatus::PENDING,
        message: 'Bienvenue' // Doublon - ignoré
    ),
]);

// Ajout
$notifications = $notifications->add(
    new NotificationRecord(
        id: UuidVO::generate(),
        status: NotificationStatus::FAILED,
        message: 'Erreur de paiement'
    )
);

// Vérification
$target = new NotificationRecord(
    id: UuidVO::generate(),
    status: NotificationStatus::PENDING,
    message: 'Bienvenue'
);
if ($notifications->contains($target)) {
    echo 'Notification trouvée';
}

// Filtrage
$pending = $notifications->filter(
    fn(NotificationRecord $r) => $r->status === NotificationStatus::PENDING
);

// Transformation
$messages = $notifications->map(
    fn(NotificationRecord $r) => $r->message
);

// Suppression
$toRemove = new NotificationRecord(
    id: UuidVO::generate(),
    status: NotificationStatus::SENT,
    message: 'Commande confirmée'
);
$notifications = $notifications->remove($toRemove);

// Opérations ensemblistes
$other = new SetCollection([
    new NotificationRecord(
        id: UuidVO::generate(),
        status: NotificationStatus::SENT,
        message: 'Nouvelle notification'
    ),
]);

$all = $notifications->union($other);
$common = $notifications->intersect($other);
$unique = $notifications->diff($other);

// Itération
foreach ($notifications as $notification) {
    echo $notification->message . "\n";
}

// Export
$array = $notifications->toArray();
$json = $notifications->toJson();
echo $notifications; // JSON
```

---

## Voir aussi

- `ListCollection` - Collection séquentielle ordonnée
- `MapCollection` - Collection clé → valeur
- `Sequential` - Classe de base pour les séquences
- `NormalizerChain` - Système de normalisation
- `Transformable` - Interface pour les objets transformabless