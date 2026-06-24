## ✅ **EXCUSE-MOI, VOICI DES EXEMPLES AVEC DES RECORDS, VALUE OBJECTS ET MODÈLES**

---

# MapCollection - Référence Technique (Version Complète)

## Description

`MapCollection` est une collection **associative** et **immutable** qui représente une relation clé → valeur. Chaque élément est une réponse à une clé. Ce n'est ni une suite ni un groupe, mais une correspondance. Une clé ouvre une valeur. C'est une structure de sens : "si tu connais ceci, alors tu obtiens cela".

**Elle peut contenir n'importe quel type de données :** scalaires, tableaux, objets, `AbstractRecord`, `AbstractValueObject`, `Eloquent\Model`, enums, etc.

## Hiérarchie

```
Transformable
    ↑
MapCollection
```

**Interfaces implémentées :** `ArrayAccess`, `Countable`, `IteratorAggregate`, `JsonSerializable`, `Stringable`, `Transformable`

## Installation

```bash
composer require andy-defer/domain-structures
```

```php
use AndyDefer\DomainStructures\Utils\MapCollection;
```

---

## Cas d'utilisation avancés avec Records, Value Objects et Modèles

### Cas 1 : Map de Notifications (Records)

```php
use AndyDefer\LaravelNotification\Records\NotificationRecord;
use AndyDefer\LaravelNotification\ValueObjects\UuidVO;
use AndyDefer\LaravelNotification\Enums\NotificationStatus;

// Création : Map ID → NotificationRecord
$notifications = new MapCollection([
    'notif_001' => new NotificationRecord(
        id: UuidVO::from('notif_001'),
        status: NotificationStatus::PENDING,
        message: 'Bienvenue sur notre plateforme'
    ),
    'notif_002' => new NotificationRecord(
        id: UuidVO::from('notif_002'),
        status: NotificationStatus::SENT,
        message: 'Votre commande est confirmée'
    ),
    'notif_003' => new NotificationRecord(
        id: UuidVO::from('notif_003'),
        status: NotificationStatus::FAILED,
        message: 'Erreur de paiement'
    ),
]);

// ✅ Ajouter une notification
$notifications = $notifications->put(
    'notif_004',
    new NotificationRecord(
        id: UuidVO::from('notif_004'),
        status: NotificationStatus::PENDING,
        message: 'Nouveau message reçu'
    )
);

// ✅ Récupérer une notification par son ID
$notification = $notifications->get('notif_002');
echo $notification->message; // 'Votre commande est confirmée'

// ✅ Vérifier si une notification existe
if ($notifications->hasKey('notif_001')) {
    echo 'La notification existe';
}

// ✅ Filtrer les notifications en attente
$pending = $notifications->filter(
    fn(NotificationRecord $record, string $key) => $record->status === NotificationStatus::PENDING
);

// ✅ Filtrer par statut SENT
$sent = $notifications->filter(
    fn(NotificationRecord $record) => $record->status === NotificationStatus::SENT
);

// ✅ Transformer les messages en majuscules
$uppercaseMessages = $notifications->map(
    fn(NotificationRecord $record) => strtoupper($record->message)
);

// ✅ Récupérer toutes les clés
$ids = $notifications->keys(); // ListCollection ['notif_001', 'notif_002', 'notif_003', 'notif_004']

// ✅ Récupérer toutes les notifications
$allNotifications = $notifications->values(); // ListCollection de NotificationRecord

// ✅ Supprimer une notification
$notifications = $notifications->remove('notif_003');

// ✅ Vérifier si une valeur existe
$exists = $notifications->hasValue(
    new NotificationRecord(id: UuidVO::from('notif_001'))
); // true (comparaison stricte)
```

---

### Cas 2 : Map de Value Objects

```php
use AndyDefer\LaravelNotification\ValueObjects\UuidVO;
use AndyDefer\LaravelNotification\ValueObjects\MessageBodyVO;
use AndyDefer\LaravelNotification\ValueObjects\MessageSubjectVO;

// Création : Map UUID → Message
$messages = new MapCollection([
    'msg_001' => new MessageBodyVO('Bienvenue sur notre plateforme'),
    'msg_002' => new MessageBodyVO('Votre commande est confirmée'),
    'msg_003' => new MessageBodyVO('Erreur de paiement'),
]);

// ✅ Ajouter un message
$messages = $messages->put(
    'msg_004',
    new MessageBodyVO('Nouveau message reçu')
);

// ✅ Récupérer un message
$message = $messages->get('msg_002');
echo $message->getValue(); // 'Votre commande est confirmée'

// ✅ Filtrer les messages longs
$longMessages = $messages->filter(
    fn(MessageBodyVO $body) => strlen($body->getValue()) > 20
);

// ✅ Transformer en majuscules
$uppercase = $messages->map(
    fn(MessageBodyVO $body, string $key) => strtoupper($body->getValue())
);

// ✅ Map de Sujets
$subjects = new MapCollection([
    'sub_001' => new MessageSubjectVO('Bienvenue'),
    'sub_002' => new MessageSubjectVO('Confirmation'),
]);

// ✅ Fusionner deux maps de Value Objects
$merged = $messages->merge($subjects);
```

---

### Cas 3 : Map d'Utilisateurs (Modèles Eloquent)

```php
use App\Models\User;

// Création : Map ID → User
$users = new MapCollection([
    1 => User::find(1),
    2 => User::find(2),
    3 => User::find(3),
]);

// ✅ Ajouter un utilisateur
$users = $users->put(4, User::find(4));

// ✅ Récupérer un utilisateur
$user = $users->get(1);
echo $user->name;

// ✅ Filtrer les utilisateurs actifs
$activeUsers = $users->filter(
    fn(User $user) => $user->is_active === true
);

// ✅ Filtrer par âge
$adults = $users->filter(
    fn(User $user) => $user->age >= 18
);

// ✅ Extraire les emails
$emails = $users->map(
    fn(User $user, int $id) => $user->email
);

// ✅ Récupérer toutes les clés (IDs)
$ids = $users->keys(); // ListCollection [1, 2, 3, 4]

// ✅ Récupérer tous les utilisateurs
$allUsers = $users->values(); // ListCollection de User
```

---

### Cas 4 : Map de Configurations (StrictDataObject)

```php
use AndyDefer\DomainStructures\Utils\StrictDataObject;

// Création : Map App → Config
$configs = new MapCollection([
    'app' => new StrictDataObject([
        'version' => '1.0.0',
        'debug' => true,
        'environment' => 'production'
    ]),
    'api' => new StrictDataObject([
        'version' => '2.3.1',
        'timeout' => 30,
        'retry' => 3
    ]),
    'database' => new StrictDataObject([
        'driver' => 'mysql',
        'host' => 'localhost',
        'port' => 3306
    ]),
]);

// ✅ Ajouter une configuration
$configs = $configs->put(
    'cache',
    new StrictDataObject([
        'driver' => 'redis',
        'ttl' => 3600
    ])
);

// ✅ Récupérer une configuration
$dbConfig = $configs->get('database');
$host = $dbConfig->get('host'); // 'localhost'

// ✅ Filtrer par version
$versioned = $configs->filter(
    fn(StrictDataObject $config, string $key) => $config->has('version')
);

// ✅ Transformer en versions
$versions = $configs->map(
    fn(StrictDataObject $config, string $key) => $config->get('version', 'N/A')
);

// ✅ Vérifier si une configuration existe
if ($configs->hasKey('api')) {
    echo 'Configuration API présente';
}
```

---

### Cas 5 : Map avec Clés Complexes (UUIDs)

```php
use AndyDefer\LaravelNotification\ValueObjects\UuidVO;
use AndyDefer\LaravelNotification\Records\NotificationRecord;

// Création : Map UUID → NotificationRecord
$notifications = new MapCollection();

// Ajouter avec des clés UUID
$uuid1 = UuidVO::generate();
$uuid2 = UuidVO::generate();

$notifications = $notifications
    ->put($uuid1->getValue(), new NotificationRecord(
        id: $uuid1,
        status: NotificationStatus::PENDING,
        message: 'Première notification'
    ))
    ->put($uuid2->getValue(), new NotificationRecord(
        id: $uuid2,
        status: NotificationStatus::SENT,
        message: 'Deuxième notification'
    ));

// ✅ Récupérer par UUID
$notification = $notifications->get($uuid1->getValue());

// ✅ Vérifier si un UUID existe
if ($notifications->hasKey($uuid1->getValue())) {
    echo 'Notification trouvée';
}

// ✅ Filtrer par statut
$pending = $notifications->filter(
    fn(NotificationRecord $record) => $record->status === NotificationStatus::PENDING
);
```

---

### Cas 6 : Map avec Clés de Type Mixte

```php
use App\Models\User;
use AndyDefer\LaravelNotification\Records\NotificationRecord;

// Map avec différents types de clés
$mixedMap = new MapCollection([
    'string_key' => 'Valeur string',
    123 => 'Valeur avec clé int',
    45.67 => 'Valeur avec clé float', // Converti en string '45.67'
    $user1->id => $user1, // Clé int
    $notification->id->getValue() => $notification, // Clé string UUID
]);

// ✅ Accès par différents types de clés
$stringValue = $mixedMap->get('string_key');
$intValue = $mixedMap->get(123);
$floatValue = $mixedMap->get(45.67);
$user = $mixedMap->get($user1->id);
```

---

### Cas 7 : Chaining avec des Records

```php
// ✅ Chaînage puissant de MapCollection avec des records
$result = $notifications
    ->filter(fn(NotificationRecord $r) => $r->status === NotificationStatus::PENDING)
    ->map(fn(NotificationRecord $r, string $id) => [
        'id' => $id,
        'message' => strtoupper($r->message),
        'priority' => $r->priority ?? 0
    ])
    ->filter(fn(array $data) => $data['priority'] > 0)
    ->keys();

// Résultat : Liste des IDs des notifications prioritaires
```

---

### Cas 8 : Map de Collections

```php
use AndyDefer\DomainStructures\Utils\ListCollection;

// Map d'utilisateurs avec leurs notifications
$userNotifications = new MapCollection([
    'user_001' => new ListCollection([
        new NotificationRecord(...),
        new NotificationRecord(...),
    ]),
    'user_002' => new ListCollection([
        new NotificationRecord(...),
    ]),
]);

// ✅ Ajouter une notification à un utilisateur
$userNotifications = $userNotifications->put(
    'user_003',
    new ListCollection([
        new NotificationRecord(...),
        new NotificationRecord(...),
        new NotificationRecord(...),
    ])
);

// ✅ Récupérer les notifications d'un utilisateur
$notifications = $userNotifications->get('user_001');

// ✅ Ajouter une notification à la liste
$notifications = $notifications->add(new NotificationRecord(...));
$userNotifications = $userNotifications->put('user_001', $notifications);

// ✅ Filtrer les utilisateurs avec notifications
$usersWithNotifications = $userNotifications->filter(
    fn(ListCollection $list) => $list->isNotEmpty()
);
```

---

### Cas 9 : Map avec `collect()`

```php
// Collecter depuis des objets transformables
$records = [
    'a' => new NotificationRecord(...),
    'b' => new NotificationRecord(...),
    'c' => new NotificationRecord(...),
];

$collection = MapCollection::collect($records);

// Collecter depuis des value objects
$uuids = [
    'uuid1' => UuidVO::generate(),
    'uuid2' => UuidVO::generate(),
];

$uuidMap = MapCollection::collect($uuids);
// Résultat : ['uuid1' => 'abc-123', 'uuid2' => 'def-456']

// Collecter depuis des utilisateurs
$users = [
    1 => User::find(1),
    2 => User::find(2),
];

$userMap = MapCollection::collect($users);
```

---

### Cas 10 : Map avec `from()`

```php
// Créer une map depuis un tableau de records
$map = MapCollection::from([
    'notif_001' => new NotificationRecord(...),
    'notif_002' => new NotificationRecord(...),
]);

// Créer depuis un objet transformable
$record = new TestUserRecord(name: 'John', email: $this->testEmail);
$map = MapCollection::from($record);
// Résultat : ['name' => 'John', 'email' => 'test@example.com']

// Créer depuis un objet standard
$obj = new stdClass();
$obj->name = 'John';
$obj->age = 30;
$map = MapCollection::from($obj);
// Résultat : ['name' => 'John', 'age' => 30]
```

---

## Pourquoi utiliser MapCollection avec des Records ?

| Avantage | Explication |
|----------|-------------|
| **Immutabilité** | Les modifications créent de nouvelles instances, pas de side effects |
| **Typage fort** | Les callbacks peuvent typer les paramètres |
| **Normalisation** | Les objets sont automatiquement normalisés via `NormalizerChain` |
| **Chaînage** | Les opérations s'enchaînent de manière fluide |
| **Accès par clé** | `get()` et `hasKey()` permettent un accès direct et efficace |

---

## Gestion des erreurs

| Situation | Exception | Message |
|-----------|-----------|---------|
| Source invalide (from) | `InvalidArgumentException` | `Cannot create MapCollection from X...` |
| JSON invalide (fromJson) | `InvalidArgumentException` | `Invalid JSON: ...` |
| Objet sans propriétés | `InvalidArgumentException` | `Cannot create MapCollection from ... Object has no public properties.` |
| Modification directe (offsetSet) | `RuntimeException` | `MapCollection is immutable. Use put() to create a new instance.` |
| Suppression directe (offsetUnset) | `RuntimeException` | `MapCollection is immutable. Use remove() to create a new instance.` |

---

## Performance

| Opération | Complexité | Notes |
|-----------|------------|-------|
| `get()` | O(1) | Accès direct par clé |
| `hasKey()` | O(1) | Vérification directe |
| `put()` | O(1) | Ajout direct |
| `remove()` | O(1) | Suppression directe |
| `filter()` | O(n) | Parcours complet |
| `map()` | O(n) | Parcours complet |
| `reduce()` | O(n) | Parcours complet |
| `keys()` | O(n) | Extraction des clés |
| `values()` | O(n) | Extraction des valeurs |

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

use AndyDefer\DomainStructures\Utils\MapCollection;
use AndyDefer\LaravelNotification\Records\NotificationRecord;
use AndyDefer\LaravelNotification\Enums\NotificationStatus;
use AndyDefer\LaravelNotification\ValueObjects\UuidVO;

// Création : Map ID → NotificationRecord
$notifications = new MapCollection([
    'notif_001' => new NotificationRecord(
        id: UuidVO::from('notif_001'),
        status: NotificationStatus::PENDING,
        message: 'Bienvenue'
    ),
    'notif_002' => new NotificationRecord(
        id: UuidVO::from('notif_002'),
        status: NotificationStatus::SENT,
        message: 'Commande confirmée'
    ),
]);

// Ajout
$notifications = $notifications->put(
    'notif_003',
    new NotificationRecord(
        id: UuidVO::from('notif_003'),
        status: NotificationStatus::PENDING,
        message: 'Alerte importante'
    )
);

// Accès
$notification = $notifications->get('notif_002');
echo $notification->message; // 'Commande confirmée'

// Vérification
if ($notifications->hasKey('notif_001')) {
    echo 'Notification trouvée';
}

// Filtrage
$pending = $notifications->filter(
    fn(NotificationRecord $r) => $r->status === NotificationStatus::PENDING
);

// Transformation
$uppercase = $notifications->map(
    fn(NotificationRecord $r) => strtoupper($r->message)
);

// Suppression
$notifications = $notifications->remove('notif_003');

// Clés et valeurs
$ids = $notifications->keys();     // ListCollection
$all = $notifications->values();   // ListCollection

// Itération
foreach ($notifications as $id => $notification) {
    echo "{$id}: {$notification->message}\n";
}

// Export
$array = $notifications->toArray();
$json = $notifications->toJson();
echo $notifications; // JSON
```

---

## Voir aussi

- `ListCollection` - Collection séquentielle ordonnée
- `SetCollection` - Collection d'éléments uniques
- `Sequential` - Classe de base pour les séquences
- `NormalizerChain` - Système de normalisation
- `Transformable` - Interface pour les objets transformables