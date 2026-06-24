# ListCollection - Référence Technique

## Description

`ListCollection` est une collection **séquentielle ordonnée** et **immutable** qui organise les éléments par position. Les éléments y vivent dans un ordre précis, comme des pas dans une marche. La question centrale n'est pas "est-ce que cet élément existe ?" mais "quel est le premier, le suivant, le dernier ?".

**Elle peut contenir n'importe quel type de données :** scalaires, tableaux, objets, `AbstractRecord`, `AbstractValueObject`, `Eloquent\Model`, enums, etc.

## Hiérarchie

```
Transformable
    ↑
ListCollection
```

**Interfaces implémentées :** `ArrayAccess`, `Countable`, `IteratorAggregate`, `JsonSerializable`, `Stringable`, `Transformable`

## Rôle principal

`ListCollection` est une collection **non-typée** (contrairement à `AbstractTypedCollection`) qui accepte n'importe quel type d'éléments et les normalise via `NormalizerChain`. Elle est **immutable** : chaque opération retourne une nouvelle instance.

## Installation

```bash
composer require andy-defer/domain-structures
```

```php
use AndyDefer\DomainStructures\Utils\ListCollection;
```

---

## API / Méthodes publiques

### `__construct(array $items = [])`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$items` | `array<int, mixed>` | Éléments initiaux (normalisés via `NormalizerChain`) |

**Retourne :** `void`

**Exemple :**
```php
$list = new ListCollection(['Apple', 'Banana', 'Cherry']);
```

---

### `add(mixed $item): self`

Ajoute un élément à la fin de la liste.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$item` | `mixed` | Élément à ajouter (sera normalisé) |

**Retourne :** `self` - Nouvelle instance avec l'élément ajouté

**Exemple :**
```php
$list = new ListCollection([1, 2, 3]);
$newList = $list->add(4); // [1, 2, 3, 4]
```

---

### `prepend(mixed $item): self`

Ajoute un élément au début de la liste.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$item` | `mixed` | Élément à ajouter (sera normalisé) |

**Retourne :** `self` - Nouvelle instance avec l'élément ajouté

**Exemple :**
```php
$list = new ListCollection([2, 3, 4]);
$newList = $list->prepend(1); // [1, 2, 3, 4]
```

---

### `insert(int $index, mixed $item): self`

Insère un élément à une position spécifique.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$index` | `int` | Position (0-based) |
| `$item` | `mixed` | Élément à insérer (sera normalisé) |

**Retourne :** `self` - Nouvelle instance avec l'élément inséré

**Exceptions :** `InvalidArgumentException` - Si l'index est hors limites (0 à n)

**Exemple :**
```php
$list = new ListCollection([1, 2, 4, 5]);
$newList = $list->insert(2, 3); // [1, 2, 3, 4, 5]
```

---

### `removeAt(int $index): self`

Retire un élément à une position spécifique.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$index` | `int` | Position (0-based) |

**Retourne :** `self` - Nouvelle instance sans l'élément

**Exceptions :** `InvalidArgumentException` - Si l'index est hors limites

**Exemple :**
```php
$list = new ListCollection([1, 2, 3, 4, 5]);
$newList = $list->removeAt(2); // [1, 2, 4, 5]
```

---

### `remove(mixed $item): self`

Retire la première occurrence d'un élément.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$item` | `mixed` | Élément à retirer (comparaison stricte) |

**Retourne :** `self` - Nouvelle instance sans l'élément (ou l'original si non trouvé)

**Exemple :**
```php
$list = new ListCollection([1, 2, 3, 2, 4]);
$newList = $list->remove(2); // [1, 3, 2, 4]
```

---

### `replace(int $index, mixed $item): self`

Remplace un élément à une position spécifique.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$index` | `int` | Position (0-based) |
| `$item` | `mixed` | Nouvel élément (sera normalisé) |

**Retourne :** `self` - Nouvelle instance avec l'élément remplacé

**Exceptions :** `InvalidArgumentException` - Si l'index est hors limites

**Exemple :**
```php
$list = new ListCollection([1, 2, 3, 4, 5]);
$newList = $list->replace(2, 99); // [1, 2, 99, 4, 5]
```

---

### `first(): mixed|null`

Récupère le premier élément.

**Retourne :** `mixed|null` - Le premier élément ou `null` si la liste est vide

**Exemple :**
```php
$list = new ListCollection([1, 2, 3]);
$first = $list->first(); // 1
```

---

### `last(): mixed|null`

Récupère le dernier élément.

**Retourne :** `mixed|null` - Le dernier élément ou `null` si la liste est vide

**Exemple :**
```php
$list = new ListCollection([1, 2, 3]);
$last = $list->last(); // 3
```

---

### `get(int $index): mixed|null`

Récupère un élément par son index.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$index` | `int` | Position (0-based) |

**Retourne :** `mixed|null` - L'élément ou `null` si non trouvé

**Exemple :**
```php
$list = new ListCollection([1, 2, 3]);
$value = $list->get(1); // 2
$notFound = $list->get(5); // null
```

---

### `indexOf(mixed $item): int|null`

Trouve l'index de la **première occurrence** d'un élément.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$item` | `mixed` | Élément à chercher (comparaison stricte) |

**Retourne :** `int|null` - L'index ou `null` si non trouvé

**Exemple :**
```php
$list = new ListCollection(['a', 'b', 'c', 'b']);
$list->indexOf('b'); // 1 (première occurrence)
$list->indexOf('z'); // null
```

---

### `contains(mixed $item): bool`

Vérifie si un élément existe.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$item` | `mixed` | Élément à vérifier (comparaison stricte) |

**Retourne :** `bool` - `true` si présent, `false` sinon

**Exemple :**
```php
$list = new ListCollection(['Apple', 'Banana']);
$list->contains('Apple');  // true
$list->contains('apple');  // false (case sensitive)
```

---

### `filter(callable $callback): self`

Filtre les éléments selon un critère.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$callback` | `callable` | Fonction qui retourne `true` pour garder l'élément |

**Retourne :** `self` - Nouvelle instance avec les éléments filtrés (réindexée)

**Exemple :**
```php
$list = new ListCollection([1, 2, 3, 4, 5, 6]);
$even = $list->filter(fn($n) => $n % 2 === 0); // [2, 4, 6]
```

---

### `map(callable $callback): self`

Transforme chaque élément.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$callback` | `callable` | Fonction de transformation |

**Retourne :** `self` - Nouvelle instance avec les éléments transformés (réindexée)

**Exemple :**
```php
$list = new ListCollection([1, 2, 3]);
$doubled = $list->map(fn($n) => $n * 2); // [2, 4, 6]
```

---

### `reduce(callable $callback, mixed $initial = null): mixed`

Réduit la liste à une seule valeur.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$callback` | `callable` | Fonction de réduction `(carry, item) => newCarry` |
| `$initial` | `mixed` | Valeur initiale |

**Retourne :** `mixed` - La valeur réduite

**Exemple :**
```php
$list = new ListCollection([1, 2, 3, 4, 5]);
$sum = $list->reduce(fn($carry, $n) => $carry + $n, 0); // 15
```

---

### `reverse(): self`

Inverse l'ordre des éléments.

**Retourne :** `self` - Nouvelle instance avec l'ordre inversé

**Exemple :**
```php
$list = new ListCollection([1, 2, 3]);
$reversed = $list->reverse(); // [3, 2, 1]
```

---

### `sort(?callable $callback = null): self`

Trie les éléments.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$callback` | `callable|null` | Fonction de comparaison (tri ascendant par défaut) |

**Retourne :** `self` - Nouvelle instance triée

**Exemple :**
```php
$list = new ListCollection([5, 2, 8, 1]);
$sorted = $list->sort(); // [1, 2, 5, 8]
$desc = $list->sort(fn($a, $b) => $b <=> $a); // [8, 5, 2, 1]
```

---

### `slice(int $start, ?int $length = null): self`

Récupère une tranche de la liste.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$start` | `int` | Index de début |
| `$length` | `int|null` | Nombre d'éléments (`null` = jusqu'à la fin) |

**Retourne :** `self` - Nouvelle instance avec la tranche

**Exemple :**
```php
$list = new ListCollection([1, 2, 3, 4, 5]);
$slice = $list->slice(1, 3); // [2, 3, 4]
```

---

### `take(int $n): self`

Prend les `n` premiers éléments.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$n` | `int` | Nombre d'éléments à prendre |

**Retourne :** `self` - Nouvelle instance avec les `n` premiers éléments

**Exemple :**
```php
$list = new ListCollection([1, 2, 3, 4, 5]);
$first3 = $list->take(3); // [1, 2, 3]
```

---

### `skip(int $n): self`

Saute les `n` premiers éléments.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$n` | `int` | Nombre d'éléments à sauter |

**Retourne :** `self` - Nouvelle instance sans les `n` premiers éléments

**Exemple :**
```php
$list = new ListCollection([1, 2, 3, 4, 5]);
$last2 = $list->skip(3); // [4, 5]
```

---

### `merge(self $other): self`

Fusionne avec une autre liste.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$other` | `self` | L'autre liste |

**Retourne :** `self` - Nouvelle instance avec les éléments fusionnés

**Exemple :**
```php
$list1 = new ListCollection([1, 2, 3]);
$list2 = new ListCollection([4, 5, 6]);
$merged = $list1->merge($list2); // [1, 2, 3, 4, 5, 6]
```

---

### `mergeArray(array $items): self`

Fusionne avec un tableau.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$items` | `array<int, mixed>` | Les éléments à fusionner |

**Retourne :** `self` - Nouvelle instance avec les éléments fusionnés

**Exemple :**
```php
$list = new ListCollection([1, 2, 3]);
$merged = $list->mergeArray([4, 5, 6]); // [1, 2, 3, 4, 5, 6]
```

---

### `isEmpty(): bool`

Vérifie si la liste est vide.

**Retourne :** `bool` - `true` si vide, `false` sinon

---

### `isNotEmpty(): bool`

Vérifie si la liste n'est pas vide.

**Retourne :** `bool` - `true` si non vide, `false` sinon

---

### `count(): int`

Compte les éléments.

**Retourne :** `int` - Nombre d'éléments

---

### `toArray(): array`

Retourne tous les éléments.

**Retourne :** `array<int, mixed>` - Tableau des éléments

---

### `toJson(): string`

Convertit la liste en chaîne JSON.

**Retourne :** `string` - Représentation JSON

**Exemple :**
```php
$list = new ListCollection([1, 2, 3]);
echo $list->toJson(); // '[1,2,3]'
```

---

### `__toString(): string`

Représentation JSON.

**Retourne :** `string` - La représentation JSON

---

### `jsonSerialize(): mixed`

Interface `JsonSerializable`.

**Retourne :** `mixed` - Les données à sérialiser

---

### `getIterator(): ArrayIterator`

Interface `IteratorAggregate`.

**Retourne :** `\ArrayIterator<int, mixed>`

---

### `from(mixed $source): static`

Crée une instance à partir d'une source.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$source` | `mixed` | Source (array, objet, scalaire, enum) |

**Retourne :** `static` - Nouvelle instance

**Exceptions :** `InvalidArgumentException` - Si la source ne peut pas être convertie

**Exemple :**
```php
$list = ListCollection::from([1, 2, 3]);
```

---

### `fromJson(string $json): static`

Crée une instance à partir de JSON.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$json` | `string` | Chaîne JSON |

**Retourne :** `static` - Nouvelle instance

**Exceptions :** `InvalidArgumentException` - Si le JSON est invalide

---

### `collect(iterable $sources): static`

Collecte des sources et les transforme en une liste.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$sources` | `iterable` | Les sources à collecter |

**Retourne :** `static` - La liste contenant les sources collectées

**Exceptions :** `InvalidArgumentException` - Si un objet sans propriétés est fourni

---

## Cas d'utilisation avancés

### Cas 1 : Liste de Notifications (Records)

```php
use AndyDefer\LaravelNotification\Records\NotificationRecord;
use AndyDefer\LaravelNotification\Enums\NotificationStatus;

// Création
$notifications = new ListCollection([
    new NotificationRecord(status: NotificationStatus::PENDING, priority: 1),
    new NotificationRecord(status: NotificationStatus::SENT, priority: 3),
    new NotificationRecord(status: NotificationStatus::PENDING, priority: 2),
]);

// Filtrer les notifications en attente
$pending = $notifications->filter(
    fn(NotificationRecord $r) => $r->status === NotificationStatus::PENDING
);

// Trier par priorité
$sorted = $notifications->sort(
    fn(NotificationRecord $a, NotificationRecord $b) => $a->priority <=> $b->priority
);

// Prendre les 3 plus prioritaires
$topPriority = $notifications
    ->sort(fn($a, $b) => $a->priority <=> $b->priority)
    ->take(3);
```

---

### Cas 2 : Liste de Value Objects

```php
use AndyDefer\LaravelNotification\ValueObjects\UuidVO;
use AndyDefer\LaravelNotification\ValueObjects\MessageBodyVO;

// Création
$messages = new ListCollection([
    new MessageBodyVO('Bienvenue'),
    new MessageBodyVO('Commande confirmée'),
    new MessageBodyVO('Erreur de paiement'),
]);

// Transformer en majuscules
$uppercase = $messages->map(
    fn(MessageBodyVO $body) => strtoupper($body->getValue())
);

// Filtrer les messages longs
$longMessages = $messages->filter(
    fn(MessageBodyVO $body) => strlen($body->getValue()) > 20
);
```

---

### Cas 3 : Liste d'Utilisateurs (Modèles Eloquent)

```php
use App\Models\User;

// Récupération depuis la base de données
$users = User::where('active', true)->get();
$userList = new ListCollection($users->toArray());

// Filtrer par âge
$adults = $userList->filter(fn(User $user) => $user->age >= 18);

// Extraire les emails
$emails = $userList->map(fn(User $user) => $user->email);

// Vérifier la présence
if ($userList->contains($targetUser)) {
    echo 'Utilisateur présent';
}
```

---

### Cas 4 : Liste Mixte avec `collect()`

```php
// Collecter depuis des objets transformables
$records = [
    new NotificationRecord(...),
    new NotificationRecord(...),
];

$collection = ListCollection::collect($records);

// Chaining puissant
$result = $notifications
    ->filter(fn(NotificationRecord $r) => $r->status === NotificationStatus::PENDING)
    ->sort(fn($a, $b) => $a->priority <=> $b->priority)
    ->map(fn(NotificationRecord $r) => $r->message)
    ->take(5);
```

---

## Flux d'exécution

```
Création
    ↓
Normalisation via NormalizerChain
    ↓
Stockage en array indexé
    ↓
Opération (add, insert, filter, map, etc.)
    ↓
Création d'une nouvelle instance (clone)
    ↓
Résultat disponible
```

**Immutable :** Chaque opération retourne une **nouvelle instance**.

```php
$list = new ListCollection([1, 2, 3]);
$newList = $list->add(4);

$list->toArray();    // [1, 2, 3] - inchangé
$newList->toArray(); // [1, 2, 3, 4] - nouvelle instance
```

---

## Gestion des erreurs

| Situation | Exception | Message |
|-----------|-----------|---------|
| Index hors limites (insert) | `InvalidArgumentException` | `Index X is out of range (0-Y)` |
| Index hors limites (removeAt) | `InvalidArgumentException` | `Index X is out of range (0-Y)` |
| Index hors limites (replace) | `InvalidArgumentException` | `Index X is out of range (0-Y)` |
| Source invalide (from) | `InvalidArgumentException` | `Cannot create ListCollection from X...` |
| JSON invalide (fromJson) | `InvalidArgumentException` | `Invalid JSON: ...` |
| Objet sans propriétés | `InvalidArgumentException` | `Cannot create ListCollection from ... Object has no public properties.` |
| Modification directe (offsetSet) | `RuntimeException` | `ListCollection is immutable. Use add() or insert()...` |
| Suppression directe (offsetUnset) | `RuntimeException` | `ListCollection is immutable. Use removeAt()...` |

---

## Performance

| Opération | Complexité | Notes |
|-----------|------------|-------|
| `get()` | O(1) | Accès direct |
| `add()` | O(1) | Ajout à la fin |
| `prepend()` | O(n) | Décalage |
| `insert()` | O(n) | Décalage |
| `removeAt()` | O(n) | Décalage |
| `remove()` | O(n) | Recherche + décalage |
| `filter()` | O(n) | Parcours complet |
| `map()` | O(n) | Parcours complet |
| `contains()` | O(n) | Recherche linéaire |
| `indexOf()` | O(n) | Recherche linéaire |
| `sort()` | O(n log n) | Tri |

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

use AndyDefer\DomainStructures\Utils\ListCollection;
use AndyDefer\LaravelNotification\Records\NotificationRecord;
use AndyDefer\LaravelNotification\Enums\NotificationStatus;

// Création avec des records
$notifications = new ListCollection([
    new NotificationRecord(
        id: UuidVO::generate(),
        status: NotificationStatus::PENDING,
        message: 'Bienvenue',
        priority: 1
    ),
    new NotificationRecord(
        id: UuidVO::generate(),
        status: NotificationStatus::SENT,
        message: 'Commande confirmée',
        priority: 3
    ),
    new NotificationRecord(
        id: UuidVO::generate(),
        status: NotificationStatus::PENDING,
        message: 'Alerte importante',
        priority: 2
    ),
]);

// Chaînage d'opérations
$result = $notifications
    ->filter(fn(NotificationRecord $r) => $r->status === NotificationStatus::PENDING)
    ->sort(fn(NotificationRecord $a, NotificationRecord $b) => $a->priority <=> $b->priority)
    ->map(fn(NotificationRecord $r) => $r->message)
    ->take(5);

// Accès
$first = $result->first();    // Premier message
$last = $result->last();      // Dernier message
$index = $result->indexOf('Alerte importante');

// Vérification
if ($result->contains('Bienvenue')) {
    echo 'Message trouvé';
}

// Itération
foreach ($result as $index => $message) {
    echo "{$index}: {$message}\n";
}

// Export
$array = $result->toArray();
$json = $result->toJson();

echo $result; // JSON
```

---

## Voir aussi

- `SetCollection` - Collection d'éléments uniques
- `MapCollection` - Collection clé → valeur
- `Sequential` - Classe de base pour les séquences
- `NormalizerChain` - Système de normalisation
- `Transformable` - Interface pour les objets transformables