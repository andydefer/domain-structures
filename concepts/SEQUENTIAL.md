# Sequential - Référence Technique

## Description

`Sequential` est une collection séquentielle **sensible à la casse** qui hérite de `AbstractSequential`. Elle représente une séquence ordonnée d'éléments où l'ordre et la position comptent, comme des pas dans une marche.

## Hiérarchie

```
Transformable
    ↑
AbstractSequential
    ↑
Sequential
```

**Interfaces implémentées :** `ArrayAccess`, `Countable`, `IteratorAggregate`, `JsonSerializable`, `Transformable`

## Rôle principal

`Sequential` est la classe de base concrète pour créer des collections séquentielles plates (non-associatives) avec une **sensibilité à la casse** pour toutes les opérations de recherche (`contains()`, `indexOf()`). C'est l'équivalent d'un tableau indexé immutable avec des méthodes de transformation fluides.

## Installation

```bash
composer require andy-defer/domain-structures
```

```php
use AndyDefer\DomainStructures\Utils\Sequential;
```

## API / Méthodes publiques

### `__construct(array $items = [])`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$items` | `array<mixed>` | Tableau d'éléments pour initialiser la séquence |

**Retourne :** `void`

**Exemple :**
```php
$list = new Sequential(['Apple', 'Banana', 'Cherry']);
```

---

### `add(mixed $item): static`

Ajoute un élément à la fin de la séquence.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$item` | `mixed` | Élément à ajouter (sera normalisé) |

**Retourne :** `static` - Nouvelle instance avec l'élément ajouté

**Exemple :**
```php
$list = new Sequential([1, 2, 3]);
$newList = $list->add(4); // [1, 2, 3, 4]
```

---

### `prepend(mixed $item): static`

Ajoute un élément au début de la séquence.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$item` | `mixed` | Élément à ajouter (sera normalisé) |

**Retourne :** `static` - Nouvelle instance avec l'élément ajouté

**Exemple :**
```php
$list = new Sequential([2, 3, 4]);
$newList = $list->prepend(1); // [1, 2, 3, 4]
```

---

### `insert(int $index, mixed $item): static`

Insère un élément à une position spécifique.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$index` | `int` | Position (0-based) |
| `$item` | `mixed` | Élément à insérer (sera normalisé) |

**Retourne :** `static` - Nouvelle instance avec l'élément inséré

**Exceptions :** `InvalidArgumentException` - Si l'index est hors limites

**Exemple :**
```php
$list = new Sequential([1, 2, 4, 5]);
$newList = $list->insert(2, 3); // [1, 2, 3, 4, 5]
```

---

### `remove(int $index): static`

Retire un élément à une position spécifique.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$index` | `int` | Position (0-based) |

**Retourne :** `static` - Nouvelle instance sans l'élément

**Exceptions :** `InvalidArgumentException` - Si l'index est hors limites

**Exemple :**
```php
$list = new Sequential([1, 2, 3, 4, 5]);
$newList = $list->remove(2); // [1, 2, 4, 5]
```

---

### `removeElement(mixed $item): static`

Retire la première occurrence d'un élément (sensible à la casse).

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$item` | `mixed` | Élément à retirer (sera normalisé pour la comparaison) |

**Retourne :** `static` - Nouvelle instance sans l'élément

**Exemple :**
```php
$list = new Sequential(['Apple', 'Banana', 'apple']);
$newList = $list->removeElement('Apple'); // ['Banana', 'apple']
// 'apple' n'est pas retiré car la casse est différente
```

---

### `replace(int $index, mixed $item): static`

Remplace un élément à une position spécifique.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$index` | `int` | Position (0-based) |
| `$item` | `mixed` | Nouvel élément (sera normalisé) |

**Retourne :** `static` - Nouvelle instance avec l'élément remplacé

**Exceptions :** `InvalidArgumentException` - Si l'index est hors limites

**Exemple :**
```php
$list = new Sequential([1, 2, 3, 4, 5]);
$newList = $list->replace(2, 99); // [1, 2, 99, 4, 5]
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
$list = new Sequential([1, 2, 3]);
$value = $list->get(1); // 2
$notFound = $list->get(5); // null
```

---

### `first(): mixed|null`

Récupère le premier élément.

**Retourne :** `mixed|null` - Le premier élément ou `null` si vide

**Exemple :**
```php
$list = new Sequential([1, 2, 3]);
$first = $list->first(); // 1
```

---

### `last(): mixed|null`

Récupère le dernier élément.

**Retourne :** `mixed|null` - Le dernier élément ou `null` si vide

**Exemple :**
```php
$list = new Sequential([1, 2, 3]);
$last = $list->last(); // 3
```

---

### `indexOf(mixed $item): int|null`

Trouve l'index d'un élément (sensible à la casse).

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$item` | `mixed` | Élément à chercher (sera normalisé) |

**Retourne :** `int|null` - L'index ou `null` si non trouvé

**Exemple :**
```php
$list = new Sequential(['Apple', 'Banana', 'apple']);
$list->indexOf('Apple'); // 0
$list->indexOf('apple'); // 2
$list->indexOf('APPLE'); // null (case sensitive)
```

---

### `contains(mixed $item): bool`

Vérifie si un élément existe (sensible à la casse).

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$item` | `mixed` | Élément à vérifier (sera normalisé) |

**Retourne :** `bool` - `true` si présent, `false` sinon

**Exemple :**
```php
$list = new Sequential(['Apple', 'Banana']);
$list->contains('Apple');  // true
$list->contains('apple');  // false (case sensitive)
```

---

### `filter(callable $callback): static`

Filtre les éléments.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$callback` | `callable` | Fonction qui retourne `true` pour garder l'élément |

**Retourne :** `static` - Nouvelle instance avec les éléments filtrés

**Exemple :**
```php
$list = new Sequential([1, 2, 3, 4, 5, 6]);
$even = $list->filter(fn($n) => $n % 2 === 0); // [2, 4, 6]
```

---

### `map(callable $callback): static`

Transforme les éléments.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$callback` | `callable` | Fonction de transformation |

**Retourne :** `static` - Nouvelle instance avec les éléments transformés

**Exemple :**
```php
$list = new Sequential([1, 2, 3]);
$doubled = $list->map(fn($n) => $n * 2); // [2, 4, 6]
```

---

### `reduce(callable $callback, mixed $initial = null): mixed`

Réduit la séquence à une seule valeur.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$callback` | `callable` | Fonction de réduction |
| `$initial` | `mixed` | Valeur initiale |

**Retourne :** `mixed` - La valeur réduite

**Exemple :**
```php
$list = new Sequential([1, 2, 3, 4, 5]);
$sum = $list->reduce(fn($carry, $n) => $carry + $n, 0); // 15
```

---

### `reverse(): static`

Inverse l'ordre des éléments.

**Retourne :** `static` - Nouvelle instance avec l'ordre inversé

**Exemple :**
```php
$list = new Sequential([1, 2, 3]);
$reversed = $list->reverse(); // [3, 2, 1]
```

---

### `sort(?callable $callback = null): static`

Trie les éléments.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$callback` | `callable|null` | Fonction de comparaison (tri ascendant par défaut) |

**Retourne :** `static` - Nouvelle instance triée

**Exemple :**
```php
$list = new Sequential([5, 2, 8, 1]);
$sorted = $list->sort(); // [1, 2, 5, 8]
$desc = $list->sort(fn($a, $b) => $b <=> $a); // [8, 5, 2, 1]
```

---

### `slice(int $start, ?int $length = null): static`

Récupère une tranche de la séquence.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$start` | `int` | Index de début |
| `$length` | `int|null` | Nombre d'éléments (`null` = jusqu'à la fin) |

**Retourne :** `static` - Nouvelle instance avec la tranche

**Exemple :**
```php
$list = new Sequential([1, 2, 3, 4, 5]);
$slice = $list->slice(1, 3); // [2, 3, 4]
```

---

### `take(int $n): static`

Prend les `n` premiers éléments.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$n` | `int` | Nombre d'éléments à prendre |

**Retourne :** `static` - Nouvelle instance avec les `n` premiers éléments

**Exemple :**
```php
$list = new Sequential([1, 2, 3, 4, 5]);
$first3 = $list->take(3); // [1, 2, 3]
```

---

### `skip(int $n): static`

Saute les `n` premiers éléments.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$n` | `int` | Nombre d'éléments à sauter |

**Retourne :** `static` - Nouvelle instance sans les `n` premiers éléments

**Exemple :**
```php
$list = new Sequential([1, 2, 3, 4, 5]);
$last2 = $list->skip(3); // [4, 5]
```

---

### `merge(self $other): static`

Fusionne avec une autre séquence.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$other` | `self` | L'autre séquence |

**Retourne :** `static` - Nouvelle instance avec les éléments fusionnés

**Exemple :**
```php
$list1 = new Sequential([1, 2, 3]);
$list2 = new Sequential([4, 5, 6]);
$merged = $list1->merge($list2); // [1, 2, 3, 4, 5, 6]
```

---

### `mergeArray(array $items): static`

Fusionne avec un tableau.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$items` | `array<mixed>` | Les éléments à fusionner |

**Retourne :** `static` - Nouvelle instance avec les éléments fusionnés

**Exemple :**
```php
$list = new Sequential([1, 2, 3]);
$merged = $list->mergeArray([4, 5, 6]); // [1, 2, 3, 4, 5, 6]
```

---

### `isEmpty(): bool`

Vérifie si la séquence est vide.

**Retourne :** `bool` - `true` si vide, `false` sinon

---

### `isNotEmpty(): bool`

Vérifie si la séquence n'est pas vide.

**Retourne :** `bool` - `true` si non vide, `false` sinon

---

### `count(): int`

Compte les éléments.

**Retourne :** `int` - Nombre d'éléments

**Exemple :**
```php
$list = new Sequential([1, 2, 3]);
$count = $list->count(); // 3
```

---

### `toArray(): array`

Retourne tous les éléments.

**Retourne :** `array<int, mixed>` - Tableau des éléments

---

### `toJson(): string`

Convertit la séquence en chaîne JSON.

**Retourne :** `string` - Représentation JSON

**Exemple :**
```php
$list = new Sequential([1, 2, 3]);
$json = $list->toJson(); // '[1,2,3]'
```

---

### `__toString(): string`

Représentation JSON.

**Retourne :** `string` - La représentation JSON

**Exemple :**
```php
$list = new Sequential([1, 2, 3]);
echo $list; // '[1,2,3]'
```

---

### `jsonSerialize(): mixed`

**Retourne :** `mixed` - Données pour la sérialisation JSON

---

### `getIterator(): ArrayIterator`

Récupère l'itérateur.

**Retourne :** `\ArrayIterator<int, mixed>`

**Exemple :**
```php
foreach ($list as $item) {
    echo $item;
}
```

---

### `from(mixed $source): static`

Crée une instance à partir d'une source.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$source` | `mixed` | Source (array, objet, scalaire, enum) |

**Retourne :** `static` - Nouvelle instance

**Exceptions :** `InvalidArgumentException` - Si la source ne peut pas être convertie

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

Collecte des sources et les transforme en une séquence.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$sources` | `iterable` | Les sources à collecter |

**Retourne :** `static` - La séquence contenant les sources collectées

**Exceptions :** `InvalidArgumentException` - Si un objet sans propriétés est fourni

---

## Cas d'utilisation

### Cas 1 : Gestion d'une playlist

```php
$playlist = new Sequential(['Song A', 'Song B', 'Song C']);

// Ajouter une chanson
$playlist = $playlist->add('Song D');

// Insérer une chanson en position 2
$playlist = $playlist->insert(2, 'Song X');

// Supprimer une chanson
$playlist = $playlist->removeElement('Song B');

// Récupérer la première chanson
$first = $playlist->first(); // 'Song A'

// Afficher la playlist
echo $playlist; // '["Song A","Song X","Song C","Song D"]'
```

### Cas 2 : Analyse de données

```php
$data = new Sequential([5, 2, 8, 1, 9, 3]);

// Filtrer les nombres pairs
$even = $data->filter(fn($n) => $n % 2 === 0); // [2, 8]

// Doubler chaque valeur
$doubled = $data->map(fn($n) => $n * 2); // [10, 4, 16, 2, 18, 6]

// Calculer la somme
$sum = $data->reduce(fn($carry, $n) => $carry + $n, 0); // 28

// Trier et prendre les 3 plus grands
$top3 = $data->sort(fn($a, $b) => $b <=> $a)->take(3); // [9, 8, 5]
```

### Cas 3 : Traitement de chaînes (case sensitive)

```php
$items = new Sequential(['Apple', 'Banana', 'Cherry', 'apple']);

// Filtrer les éléments contenant 'a' (minuscule)
$filtered = $items->filter(fn($item) => str_contains($item, 'a'));
// Résultat : ['Banana', 'apple'] (Apple est exclu car 'A' majuscule)

// Vérifier l'existence
$items->contains('Apple');  // true
$items->contains('APPLE');  // false (case sensitive)

// Trouver l'index
$items->indexOf('apple');   // 3
$items->indexOf('Apple');   // 0
```

---

## Flux d'exécution

```
Création d'une séquence
    ↓
Opération (add, prepend, insert, remove, replace, filter, map, etc.)
    ↓
Création d'une nouvelle instance (immutabilité)
    ↓
Résultat disponible
```

**Immutable :** Chaque opération retourne une **nouvelle instance**, l'originale reste inchangée.

```
$list = new Sequential([1, 2, 3]);
$newList = $list->add(4);

$list->toArray();    // [1, 2, 3] - inchangé
$newList->toArray(); // [1, 2, 3, 4] - nouvelle instance
```

---

## Gestion des erreurs

| Situation | Exception | Message |
|-----------|-----------|---------|
| Index hors limites (insert) | `InvalidArgumentException` | `Index X is out of range (0-Y)` |
| Index hors limites (remove) | `InvalidArgumentException` | `Index X is out of range (0-Y)` |
| Index hors limites (replace) | `InvalidArgumentException` | `Index X is out of range (0-Y)` |
| Source invalide (from) | `InvalidArgumentException` | `Cannot create Sequential from X. Expected array...` |
| JSON invalide (fromJson) | `InvalidArgumentException` | `Invalid JSON: ...` |
| Objet sans propriétés (collect/from) | `InvalidArgumentException` | `Cannot create Sequential from ... Object has no public properties.` |
| Modification directe (offsetSet) | `RuntimeException` | `Sequential is immutable. Use add() or insert()...` |
| Suppression directe (offsetUnset) | `RuntimeException` | `Sequential is immutable. Use remove()...` |

---

## Intégration

### Avec `NormalizerChain`

`Sequential` utilise `NormalizerChain` pour normaliser les éléments lors de la construction et des opérations :

- Les objets `Transformable` sont normalisés en tableaux
- Les scalaires sont conservés
- Les tableaux sont aplatis

### Avec d'autres collections

`Sequential` peut être combiné avec d'autres collections :

```php
use AndyDefer\DomainStructures\Utils\Sequential;
use AndyDefer\DomainStructures\Utils\ListCollection;

$list = new ListCollection([1, 2, 3]);
$sequential = Sequential::from($list->toArray());
```

---

## Performance

| Opération | Complexité | Notes |
|-----------|------------|-------|
| `get()` | O(1) | Accès direct par index |
| `add()` | O(1) | Ajout à la fin |
| `prepend()` | O(n) | Décalage des éléments |
| `insert()` | O(n) | Décalage des éléments |
| `remove()` | O(n) | Décalage des éléments |
| `filter()` | O(n) | Parcours complet |
| `map()` | O(n) | Parcours complet |
| `reduce()` | O(n) | Parcours complet |
| `contains()` | O(n) | Recherche linéaire |
| `indexOf()` | O(n) | Recherche linéaire |
| `sort()` | O(n log n) | Tri |

**Mémoire :** Chaque opération crée une nouvelle instance, la mémoire est donc O(n) par opération.

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

use AndyDefer\DomainStructures\Utils\Sequential;

// Création
$fruits = new Sequential(['Apple', 'Banana', 'Cherry']);

// Ajout et modification
$fruits = $fruits
    ->add('Date')
    ->insert(1, 'Blueberry')
    ->removeElement('Cherry');

// Transformation
$uppercase = $fruits->map(fn($fruit) => strtoupper($fruit));

// Filtrage
$fruitsWithA = $uppercase->filter(fn($fruit) => str_contains($fruit, 'A'));

// Récupération
$first = $fruits->first();        // 'Apple'
$last = $fruits->last();          // 'Date'
$index = $fruits->indexOf('Date'); // 3

// Vérification
if ($fruits->contains('Banana')) {
    echo 'Banana is in the list';
}

// JSON
echo $fruits; // '["Apple","Blueberry","Banana","Date"]'

// Itération
foreach ($fruits as $index => $fruit) {
    echo "{$index}: {$fruit}\n";
}
```

---

## Voir aussi

- `AbstractSequential` - La classe parente
- `ListCollection` - Collection typée spécialisée
- `NormalizerChain` - Système de normalisation des valeurs
- `Transformable` - Interface pour les objets transformables
- `ArrayAccess`, `Countable`, `IteratorAggregate` - Interfaces PHP natives