# SetCollection - Référence Technique

## Description

`SetCollection` est une collection **immutable** qui stocke des éléments **uniques** sans ordre défini. La question centrale n'est pas "où est l'élément ?" mais "est-il présent ou absent ?". Les doublons sont automatiquement éliminés.

## Hiérarchie

```
Transformable
    ↑
SetCollection
```

**Interfaces implémentées :** `ArrayAccess`, `Countable`, `IteratorAggregate`, `JsonSerializable`, `Stringable`, `Transformable`

## Rôle principal

`SetCollection` est une collection **non-typée** qui garantit l'unicité des éléments en utilisant une clé générée à partir de la valeur normalisée. Elle est **immutable** : chaque opération retourne une nouvelle instance.

### Particularité de l'unicité

Pour les `ValueObjects` (objets implémentant `Transformable`), l'unicité est basée sur **la valeur normalisée + le type**, ce qui permet de considérer deux instances différentes comme égales si elles ont la même valeur.

## Installation

```bash
composer require andy-defer/domain-structures
```

```php
use AndyDefer\DomainStructures\Utils\SetCollection;
```

---

## API / Méthodes publiques

### `__construct(array $items = [])`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$items` | `array<int, mixed>` | Éléments initiaux (les doublons sont ignorés) |

**Retourne :** `void`

**Exemple :**
```php
$set = new SetCollection([1, 2, 2, 3]);
// Résultat : [1, 2, 3]
```

---

### `add(mixed $item): self`

Ajoute un élément au set s'il n'existe pas déjà.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$item` | `mixed` | Élément à ajouter |

**Retourne :** `self` - Nouvelle instance avec l'élément ajouté (ou l'original si déjà présent)

**Exemple :**
```php
$set = new SetCollection([1, 2, 3]);
$newSet = $set->add(4); // [1, 2, 3, 4]
$unchanged = $set->add(2); // [1, 2, 3] (inchangé)
```

---

### `addAll(array $items): self`

Ajoute plusieurs éléments au set.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$items` | `array<int, mixed>` | Éléments à ajouter |

**Retourne :** `self` - Nouvelle instance avec les éléments ajoutés

**Exemple :**
```php
$set = new SetCollection([1, 2, 3]);
$newSet = $set->addAll([2, 3, 4, 5]);
// Résultat : [1, 2, 3, 4, 5]
```

---

### `contains(mixed $item): bool`

Vérifie si un élément est présent dans le set.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$item` | `mixed` | Élément à vérifier |

**Retourne :** `bool` - `true` si présent, `false` sinon

**Exemple :**
```php
$set = new SetCollection([1, 2, 3]);
$set->contains(2); // true
$set->contains(5); // false
```

---

### `remove(mixed $item): self`

Retire un élément du set s'il existe.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$item` | `mixed` | Élément à retirer |

**Retourne :** `self` - Nouvelle instance sans l'élément (ou l'original si non trouvé)

**Exemple :**
```php
$set = new SetCollection([1, 2, 3]);
$newSet = $set->remove(2); // [1, 3]
$unchanged = $set->remove(5); // [1, 2, 3] (inchangé)
```

---

### `first(): mixed|null`

Récupère le premier élément (ordre non garanti).

**Retourne :** `mixed|null` - Le premier élément ou `null` si vide

**Exemple :**
```php
$set = new SetCollection([1, 2, 3]);
$first = $set->first(); // 1 (ordre non garanti)
```

---

### `last(): mixed|null`

Récupère le dernier élément (ordre non garanti).

**Retourne :** `mixed|null` - Le dernier élément ou `null` si vide

**Exemple :**
```php
$set = new SetCollection([1, 2, 3]);
$last = $set->last(); // 3 (ordre non garanti)
```

---

### `get(int $index): mixed|null`

Récupère un élément par son index (ordre non garanti).

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$index` | `int` | Position (0-based) |

**Retourne :** `mixed|null` - L'élément ou `null` si non trouvé

**Exemple :**
```php
$set = new SetCollection([1, 2, 3]);
$value = $set->get(0); // 1
$notFound = $set->get(5); // null
```

---

### `filter(callable $callback): self`

Filtre les éléments selon un critère.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$callback` | `callable` | Fonction qui retourne `true` pour garder l'élément |

**Retourne :** `self` - Nouvelle instance avec les éléments filtrés

**Exemple :**
```php
$set = new SetCollection([1, 2, 3, 4, 5, 6]);
$even = $set->filter(fn($n) => $n % 2 === 0);
// Résultat : [2, 4, 6]
```

---

### `map(callable $callback): self`

Transforme chaque élément et garantit l'unicité du résultat.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$callback` | `callable` | Fonction de transformation |

**Retourne :** `self` - Nouvelle instance avec les éléments transformés (doublons éliminés)

**Exemple :**
```php
$set = new SetCollection([-1, 1, -2, 2]);
$absolute = $set->map(fn($n) => abs($n));
// Résultat : [1, 2] (les doublons sont éliminés)
```

---

### `reduce(callable $callback, mixed $initial = null): mixed`

Réduit le set à une seule valeur.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$callback` | `callable` | Fonction de réduction `(carry, item) => newCarry` |
| `$initial` | `mixed` | Valeur initiale |

**Retourne :** `mixed` - La valeur réduite

**Exemple :**
```php
$set = new SetCollection([1, 2, 3, 4, 5]);
$sum = $set->reduce(fn($carry, $n) => $carry + $n, 0);
// Résultat : 15
```

---

### `union(self $other): self`

Fusionne deux sets (union ensembliste).

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$other` | `self` | L'autre set |

**Retourne :** `self` - Nouvelle instance avec l'union des deux sets

**Exemple :**
```php
$set1 = new SetCollection([1, 2, 3]);
$set2 = new SetCollection([3, 4, 5]);
$union = $set1->union($set2);
// Résultat : [1, 2, 3, 4, 5]
```

---

### `intersect(self $other): self`

Intersection de deux sets (éléments communs).

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$other` | `self` | L'autre set |

**Retourne :** `self` - Nouvelle instance avec l'intersection

**Exemple :**
```php
$set1 = new SetCollection([1, 2, 3, 4]);
$set2 = new SetCollection([3, 4, 5, 6]);
$intersect = $set1->intersect($set2);
// Résultat : [3, 4]
```

---

### `diff(self $other): self`

Différence de deux sets (éléments dans le premier mais pas dans le second).

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$other` | `self` | L'autre set |

**Retourne :** `self` - Nouvelle instance avec la différence

**Exemple :**
```php
$set1 = new SetCollection([1, 2, 3, 4]);
$set2 = new SetCollection([3, 4, 5, 6]);
$diff = $set1->diff($set2);
// Résultat : [1, 2]
```

---

### `isEmpty(): bool`

Vérifie si le set est vide.

**Retourne :** `bool` - `true` si vide, `false` sinon

---

### `isNotEmpty(): bool`

Vérifie si le set n'est pas vide.

**Retourne :** `bool` - `true` si non vide, `false` sinon

---

### `count(): int`

Compte les éléments uniques.

**Retourne :** `int` - Nombre d'éléments uniques

---

### `toArray(): array`

Retourne tous les éléments (hydratés).

**Retourne :** `array<int, mixed>` - Tableau des éléments

---

### `toRawArray(): array`

Retourne les données brutes normalisées (scalaires). Utilisé pour le débogage et l'export.

**Retourne :** `array<int, mixed>` - Tableau des données normalisées

**Exemple :**
```php
$set = new SetCollection([TestIntVO::from(42)]);
$raw = $set->toRawArray(); // [42]
```

---

### `toRawTypedArray(): array`

Retourne les données brutes avec préservation des types. Utilisé pour les opérations internes.

**Retourne :** `array<int, mixed>` - Tableau des données brutes (objets préservés)

**Exemple :**
```php
$set = new SetCollection([TestIntVO::from(42)]);
$rawTyped = $set->toRawTypedArray(); // [TestIntVO(42)]
```

---

### `toJson(): string`

Convertit le set en chaîne JSON.

**Retourne :** `string` - Représentation JSON

**Exemple :**
```php
$set = new SetCollection([1, 2, 3]);
echo $set->toJson(); // '[1,2,3]'
```

---

### `keys(): ListCollection`

Retourne les clés du set sous forme de `ListCollection`.

**Retourne :** `ListCollection` - Les clés du set

---

### `values(): ListCollection`

Retourne les valeurs du set sous forme de `ListCollection`.

**Retourne :** `ListCollection` - Les valeurs du set

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

**Retourne :** `\ArrayIterator<int, mixed>` - Itérateur sur les éléments hydratés

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
$set = SetCollection::from([1, 2, 3]);
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

Collecte des sources et les transforme en un set.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$sources` | `iterable` | Les sources à collecter |

**Retourne :** `static` - Le set contenant les sources collectées

**Exceptions :** `InvalidArgumentException` - Si un objet sans propriétés est fourni

---

## Cas d'utilisation

### Cas 1 : Set de Tags (ValueObjects)

```php
use AndyDefer\DomainStructures\Utils\SetCollection;
use AndyDefer\PhpVo\ValueObjects\Types\StringVO;

// Création d'un set de tags uniques
$tags = new SetCollection([
    StringVO::from('php'),
    StringVO::from('laravel'),
    StringVO::from('php'), // Doublon ignoré
]);

// Ajout d'un tag
$tags = $tags->add(StringVO::from('docker'));

// Vérification
if ($tags->contains(StringVO::from('php'))) {
    echo 'Tag PHP présent';
}

// Filtrage
$longTags = $tags->filter(
    fn(StringVO $tag) => strlen($tag->getValue()) > 3
);

// Export
$tagList = $tags->toArray();
```

---

### Cas 2 : Set d'Utilisateurs Uniques

```php
use App\Models\User;
use AndyDefer\DomainStructures\Utils\SetCollection;

// Récupération des utilisateurs
$users = User::where('active', true)->get();
$userSet = new SetCollection($users->toArray());

// Ajout d'un utilisateur
$userSet = $userSet->add($newUser);

// Vérification de présence
if ($userSet->contains($targetUser)) {
    echo 'Utilisateur présent';
}

// Union avec un autre set
$admins = new SetCollection(User::where('role', 'admin')->get());
$allUsers = $userSet->union($admins);
```

---

### Cas 3 : Opérations Ensemblistes sur des IDs

```php
use AndyDefer\DomainStructures\Utils\SetCollection;

// Set des IDs autorisés
$allowed = new SetCollection([1, 2, 3, 4, 5]);

// Set des IDs traités
$processed = new SetCollection([2, 4, 6]);

// IDs non encore traités (différence)
$pending = $allowed->diff($processed);
// Résultat : [1, 3, 5]

// IDs communs (intersection)
$common = $allowed->intersect($processed);
// Résultat : [2, 4]

// Tous les IDs (union)
$all = $allowed->union($processed);
// Résultat : [1, 2, 3, 4, 5, 6]
```

---

### Cas 4 : Set avec Chaînage

```php
$result = (new SetCollection([1, 2, 3, 4, 5]))
    ->filter(fn($n) => $n % 2 === 0)   // [2, 4]
    ->map(fn($n) => $n * 2)             // [4, 8]
    ->add(10)                           // [4, 8, 10]
    ->addAll([8, 12, 14])               // [4, 8, 10, 12, 14]
    ->remove(8);                        // [4, 10, 12, 14]
```

---

## Flux d'exécution

```
Création
    ↓
Génération des clés uniques (getKey)
    ↓
Stockage en array associatif (clé → valeur)
    ↓
Opération (add, remove, filter, map, etc.)
    ↓
Création d'une nouvelle instance (clone)
    ↓
Résultat disponible
```

**Immutable :** Chaque opération retourne une **nouvelle instance**.

```php
$set = new SetCollection([1, 2, 3]);
$newSet = $set->add(4);

$set->toArray();    // [1, 2, 3] - inchangé
$newSet->toArray(); // [1, 2, 3, 4] - nouvelle instance
```

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

## Intégration

`SetCollection` s'intègre avec :

- **`ListCollection`** : via `keys()`, `values()`, `toArray()`
- **`Transformable`** : pour la normalisation et l'hydratation
- **`NormalizerChain`** : pour la normalisation automatique
- **`ValueObjects`** : via `from()` et la préservation des types

---

## Performance

| Opération | Complexité | Notes |
|-----------|------------|-------|
| `contains()` | O(1) | Accès direct par clé |
| `add()` | O(1) | Insertion directe |
| `remove()` | O(1) | Suppression directe |
| `filter()` | O(n) | Parcours complet |
| `map()` | O(n) | Parcours complet |
| `union()` | O(n) | Fusion des sets |
| `intersect()` | O(n) | Comparaison des clés |
| `diff()` | O(n) | Comparaison des clés |

**Mémoire :** Chaque opération crée une nouvelle instance O(n).

---

## Compatibilité

| Version PHP | Support | Notes |
|-------------|---------|-------|
| PHP 8.1+ | ✅ Complet | Types union, mixed, etc. |
| PHP 8.0 | ✅ Complet | Supporté |

---

## Exemple complet

```php
<?php

declare(strict_types=1);

use AndyDefer\DomainStructures\Utils\SetCollection;
use AndyDefer\PhpVo\ValueObjects\Types\IntVO;
use AndyDefer\PhpVo\ValueObjects\Types\StringVO;

// Création avec des ValueObjects
$set = new SetCollection([
    IntVO::from(1),
    IntVO::from(2),
    IntVO::from(1), // Doublon ignoré
    StringVO::from('Hello'),
]);

// Chaînage d'opérations
$result = $set
    ->filter(fn($item) => $item instanceof IntVO) // Garder les IntVO
    ->map(fn(IntVO $item) => $item->multiply(2))  // Doubler les valeurs
    ->add(IntVO::from(10))                        // Ajouter un nouvel élément
    ->remove(IntVO::from(2));                     // Retirer un élément

// Accès
$first = $result->first();
$last = $result->last();

// Vérification
if ($result->contains(IntVO::from(10))) {
    echo '10 est présent';
}

// Itération
foreach ($result as $item) {
    echo $item->getValue(); // 2, 6, 10
}

// Export
$array = $result->toArray();
$json = $result->toJson();
$raw = $result->toRawArray();

echo $result; // JSON
```

---

## Voir aussi

- `ListCollection` - Collection séquentielle ordonnée
- `MapCollection` - Collection clé → valeur
- `Sequential` - Classe de base pour les séquences
- `NormalizerChain` - Système de normalisation
- `Transformable` - Interface pour les objets transformables