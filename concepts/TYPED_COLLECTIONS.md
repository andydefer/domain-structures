# TypedCollection - Documentation du Package

## Table des matières

1. [Définition et concepts fondamentaux](#1-définition-et-concepts-fondamentaux)
2. [Pourquoi TypedCollection ?](#2-pourquoi-typedcollection-)
3. [Types supportés](#3-types-supportés)
4. [La règle d'or : préférer les collections spécialisées](#4-la-règle-dor--préférer-les-collections-spécialisées)
5. [Quand utiliser TypedCollection générique ?](#5-quand-utiliser-typedcollection-générique-)
6. [Les collections utilitaires prédéfinies](#6-les-collections-utilitaires-prédéfinies)
7. [Méthodes de base](#7-méthodes-de-base)
8. [Méthodes de transformation](#8-méthodes-de-transformation)
9. [Méthodes de calcul](#9-méthodes-de-calcul)
10. [Méthodes de filtrage](#10-méthodes-de-filtrage)
11. [Méthodes de recherche et manipulation avancées](#11-méthodes-de-recherche-et-manipulation-avancées)
12. [Méthodes de validation et assertions](#12-méthodes-de-validation-et-assertions)
13. [Créer une collection spécialisée](#13-créer-une-collection-spécialisée)
14. [Hydratation et normalisation](#14-hydratation-et-normalisation)
15. [Exemples complets](#15-exemples-complets)
16. [Récapitulatif des contraintes](#16-récapitulatif-des-contraintes)

---

## 1. Définition et concepts fondamentaux

Une **TypedCollection** est une collection **type-safe** qui remplace les tableaux bruts (`array`) dans l'ensemble de l'architecture (`Record`, `ValueObject`, `Data`). Elle garantit que tous les éléments qu'elle contient sont du type déclaré à la construction.

```
TypedCollection → Collection type-safe → Remplacement des tableaux bruts
```

> ⚠️ **Les tableaux bruts (`array`) sont STRICTEMENT INTERDITS dans les Records, ValueObjects et Data. Utilisez toujours `TypedCollection` pour les collections d'éléments.**

---

## 2. Pourquoi TypedCollection ?

### 2.1. Le problème des tableaux bruts

```php
// ❌ MAUVAIS - Tableau brut non typé
final class OrderRecord extends AbstractRecord
{
    public function __construct(
        public readonly array $items,  // On ne sait pas ce qu'il contient !
    ) {}
}

// Problèmes :
// - On ne sait pas si c'est un tableau de int, string, ou d'objets
// - On peut ajouter n'importe quoi sans contrôle
// - Pas de méthodes utilitaires (map, filter, sum, etc.)
// - Risque d'erreurs à l'exécution

// ✅ BON - Collection typée
final class OrderRecord extends AbstractRecord
{
    public function __construct(
        public readonly TypedCollection $items,  // TypedCollection<OrderItemRecord>
    ) {}
}
```

### 2.2. Ce que TypedCollection résout

| Problème des tableaux | Solution avec TypedCollection |
|----------------------|-------------------------------|
| On ne sait pas ce qu'il contient | Le type est explicite à la construction |
| Pas de validation à l'ajout | Validation automatique du type |
| Modification dangereuse | Type-safe garanti |
| Documentation implicite | Documentation explicite |
| Pas de méthodes utilitaires | Nombreuses méthodes disponibles (map, filter, sum, etc.) |

---

## 3. Types supportés

> **Une TypedCollection peut contenir tout objet qui implémente l'interface `Transformable`, ainsi que les scalaires.**

### 3.1. Types autorisés

| Type | Description | Exemple |
|------|-------------|---------|
| `'int'` | Entier | `new TypedCollection('int')` |
| `'string'` | Chaîne de caractères | `new TypedCollection('string')` |
| `'float'` | Nombre à virgule flottante | `new TypedCollection('float')` |
| `'bool'` | Booléen | `new TypedCollection('bool')` |
| `'null'` | Valeur nulle | `new TypedCollection('string', 'null')` |
| `AbstractRecord::class` | Record (ou sous-classe) | `new TypedCollection(UserRecord::class)` |
| `AbstractValueObject::class` | Value Object (ou sous-classe) | `new TypedCollection(EmailAddress::class)` |
| `AbstractData::class` | Data (ou sous-classe) | `new TypedCollection(UserData::class)` |
| `AbstractTypedCollection::class` | Collection imbriquée | `new TypedCollection(TypedCollection::class)` |
| `DataObject::class` | DataObject flexible | `new TypedCollection(DataObject::class)` |
| `UnitEnum::class` | Enum (PHP 8.1+) | `new TypedCollection(UnitEnum::class)` |

### 3.2. La règle fondamentale

> **Tout objet stocké dans une TypedCollection DOIT implémenter l'interface `Transformable`.**

```php
// ✅ BON - Les objets Transformable sont acceptés
$collection = new TypedCollection(UserRecord::class);     // UserRecord extends AbstractRecord implements Transformable
$collection = new TypedCollection(EmailAddress::class);   // EmailAddress extends AbstractValueObject implements Transformable
$collection = new TypedCollection(UserData::class);       // UserData extends AbstractData implements Transformable
$collection = new TypedCollection(TypedCollection::class); // TypedCollection extends AbstractTypedCollection implements Transformable
$collection = new TypedCollection(DataObject::class);      // DataObject implements Transformable
$collection = new TypedCollection(UnitEnum::class);        // UnitEnum - les enums sont acceptés

// ❌ MAUVAIS - Objet qui n'implémente pas Transformable
$collection = new TypedCollection(DateTime::class);        // DateTime n'implémente pas Transformable
$collection = new TypedCollection(UnknownClass::class);    // N'existe pas ou n'implémente pas Transformable
```

### 3.3. Types multiples

```php
// Collection acceptant plusieurs types
$mixed = new TypedCollection('int', 'float', 'string');
$mixed->add(42, 3.14, 'text');

// Collection acceptant Records et scalaires
$items = new TypedCollection(ProductRecord::class, 'string');
$items->add(
    new ProductRecord(name: 'Laptop', price: 999.99),
    'Just a description'
);

// Collection acceptant Enums et Value Objects
$collection = new TypedCollection(TestUserRole::class, EmailAddress::class);
$collection->add(TestUserRole::ADMIN, EmailAddress::from('admin@example.com'));
```

---

## 4. La règle d'or : préférer les collections spécialisées

> **⚠️ Dans les `Record`, `ValueObject` et `Data`, on utilise de préférence des collections spécialisées (qui étendent `TypedCollection`) plutôt que `TypedCollection` générique.**

### 4.1. Pourquoi éviter `TypedCollection` générique ?

```php
// ❌ À ÉVITER - On ne sait pas ce que contient la collection
final class UserRecord extends AbstractRecord
{
    public function __construct(
        public readonly TypedCollection $products,  // Quels produits ? On ne sait pas !
        public readonly TypedCollection $friends,   // Quels amis ? On ne sait pas !
    ) {}
}

// ❌ À ÉVITER - Même problème avec Data
final class UserData extends AbstractData
{
    public function __construct(
        public readonly TypedCollection $orders,    // On ne sait pas ce que c'est !
        public readonly TypedCollection $tags,      // Des strings ? Des objets ?
    ) {}
}
```

### 4.2. Pourquoi privilégier les collections spécialisées ?

```php
// ✅ RECOMMANDÉ - La collection dit explicitement ce qu'elle contient
final class UserRecord extends AbstractRecord
{
    public function __construct(
        public readonly ProductRecordCollection $products,  // TypedCollection<ProductRecord>
        public readonly UserRecordCollection $friends,      // TypedCollection<UserRecord>
    ) {}
}

// ✅ RECOMMANDÉ - Pour les Data aussi
final class UserData extends AbstractData
{
    public function __construct(
        public readonly OrderDataCollection $orders,  // TypedCollection<OrderData>
        public readonly StringTypedCollection $tags,  // TypedCollection<string>
    ) {}
}
```

### 4.3. Avantages des collections spécialisées

| Aspect | `TypedCollection` générique | Collection spécialisée |
|--------|----------------------------|----------------------|
| **Documentation** | On ne sait pas ce qu'elle contient | Le nom dit tout (`ProductDataCollection`) |
| **Type-safety** | Type-safe à l'ajout | Type-safe à l'ajout + méthodes spécifiques |
| **Méthodes métier** | Aucune | Peut avoir (`getFeatured()`, `getTotalPrice()`) |
| **Maintenabilité** | Moins bonne | Excellente |
| **IDE** | Aucun autocomplétion sur le contenu | Autocomplétion complète |

---

## 5. Quand utiliser TypedCollection générique ?

> **La seule exception où `TypedCollection` générique est acceptable : quand les données proviennent d'une source externe non maîtrisée ou pour des données temporaires.**

### 5.1. Cas d'usage acceptables

```php
// ✅ Acceptable - Données externes (API tierce, fichier CSV)
final class ExternalDataRecord extends AbstractRecord
{
    public function __construct(
        public readonly TypedCollection $rawData,  // Source externe, on ne maîtrise pas le type
    ) {}
}

// ✅ Acceptable - Configuration dynamique
final class ConfigRecord extends AbstractRecord
{
    public function __construct(
        public readonly TypedCollection $settings,  // Configuration aux types variés
    ) {}
}

// ✅ Acceptable - Transformation temporaire
function processData(TypedCollection $items): TypedCollection
{
    // Traitement temporaire où le type n'est pas critique
    return $items->map(fn($item) => $item->value);
}
```

### 5.2. Récapitulatif

| Situation | Utilisation |
|-----------|-------------|
| **Données internes maîtrisées** | ✅ Collection spécialisée |
| **Données internes (scalaires simples)** | ✅ `StringTypedCollection`, `IntTypedCollection` |
| **Données provenant d'une source externe** | ⚠️ `TypedCollection` générique (acceptable) |
| **Configuration dynamique** | ⚠️ `TypedCollection` générique (acceptable) |
| **Dans une Data pour l'API** | ✅ TOUJOURS collection spécialisée |
| **Dans un Record métier** | ✅ TOUJOURS collection spécialisée |

### 5.3. Exemple concret : Source externe

```php
// API externe qui retourne des données non typées
$externalApiResponse = $httpClient->get('https://api.external.com/data');
$externalData = json_decode($externalApiResponse, true);

// ✅ Acceptable - On ne maîtrise pas la structure des données externes
$rawCollection = new TypedCollection(DataObject::class);
foreach ($externalData as $item) {
    $rawCollection->add(DataObject::from($item));
}
```

---

## 6. Les collections utilitaires prédéfinies

Le package fournit des collections spécialisées pour les types scalaires les plus courants. Ces classes étendent `TypedCollection` et ajoutent des méthodes spécifiques.

### 6.1. StringTypedCollection

Collection spécialisée pour les chaînes de caractères.

```php
use AndyDefer\DomainStructures\Collections\Utility\StringTypedCollection;

$strings = new StringTypedCollection();
$strings->add('  HELLO  ', 'world', 'PHP', '', '  test  ');
```

### 6.2. IntTypedCollection

Collection spécialisée pour les entiers.

```php
use AndyDefer\DomainStructures\Collections\Utility\IntTypedCollection;

$numbers = new IntTypedCollection();
$numbers->add(1, 2, 3, 4, 5, 6, 7, 8, 9, 10);
```
### 6.3. FloatTypedCollection

Collection spécialisée pour les nombres décimaux.

```php
use AndyDefer\DomainStructures\Collections\Utility\FloatTypedCollection;

$floats = new FloatTypedCollection();
$floats->add(1.234, 2.567, 3.891);
```
### 6.4. BoolTypedCollection

Collection spécialisée pour les booléens.

```php
use AndyDefer\DomainStructures\Collections\Utility\BoolTypedCollection;

$bools = new BoolTypedCollection();
$bools->add(true, false, true, false, true);
```

### 6.5. NumberTypedCollection

Collection pour les nombres mixtes (int + float).

```php
use AndyDefer\DomainStructures\Collections\Utility\NumberTypedCollection;

$numbers = new NumberTypedCollection();
$numbers->add(1, 2.5, 3, 4.7, 5);
```

### 6.6. Génération de séquences avec `range()`

Toutes les collections numériques disposent de la méthode statique `range()` :

```php
// IntTypedCollection::range()
$evenNumbers = IntTypedCollection::range(2, 20, 2);
// [2, 4, 6, 8, 10, 12, 14, 16, 18, 20]

$descending = IntTypedCollection::range(10, 1, -1);
// [10, 9, 8, 7, 6, 5, 4, 3, 2, 1]

// FloatTypedCollection::range()
$floats = FloatTypedCollection::range(0, 1, 0.25);
// [0.0, 0.25, 0.5, 0.75, 1.0]

// NumberTypedCollection::range()
$mixed = NumberTypedCollection::range(0, 5, 1);
// [0, 1, 2, 3, 4, 5]
```

---

## 7. Méthodes de base

### 7.1. Constructeur et création

```php
use AndyDefer\DomainStructures\Collections\Core\TypedCollection;

// Collection de strings
$tags = new TypedCollection('string');
$tags->add('php', 'laravel', 'typescript');

// Collection de Records
$users = new TypedCollection(UserRecord::class);
$users->add(new UserRecord(id: 1, name: 'John'));

// Collection de Value Objects
$emails = new TypedCollection(EmailAddress::class);
$emails->add(EmailAddress::from('john@example.com'));

// Collection de Data
$usersData = new TypedCollection(UserData::class);
$usersData->add(UserData::fromRecord($userRecord));

// Collection mixte (types multiples)
$mixed = new TypedCollection('int', 'string', UserRecord::class);
```

### 7.2. Méthodes d'ajout et accès

| Méthode | Description | Exemple |
|---------|-------------|---------|
| `add(...$items)` | Ajoute un ou plusieurs éléments | `$tags->add('php', 'laravel')` |
| `count(): int` | Nombre d'éléments | `$tags->count()` |
| `isEmpty(): bool` | Vérifie si vide | `$tags->isEmpty()` |
| `isNotEmpty(): bool` | Vérifie si non vide | `$tags->isNotEmpty()` |
| `toArray(): array` | Retourne tous les éléments | `$tags->toArray()` |
| `all(): static` | Retourne une nouvelle copie | `$tags->all()` |
| `getAllowedTypes(): array` | Types autorisés | `$tags->getAllowedTypes()` |

### 7.3. Méthodes d'accès aux éléments

| Méthode | Description | Exemple |
|---------|-------------|---------|
| `firstItem(): mixed` | Premier élément | `$tags->firstItem()` |
| `first(int $limit): static` | N premiers éléments | `$tags->first(3)` |
| `lastItem(): mixed` | Dernier élément | `$tags->lastItem()` |
| `last(int $limit): static` | N derniers éléments | `$tags->last(3)` |

---

## 8. Méthodes de transformation

### 8.1. Map, Filter, Reduce

| Méthode | Description | Exemple |
|---------|-------------|---------|
| `map(Closure $callback): static` | Transforme chaque élément | `$tags->map(fn($tag) => strtoupper($tag))` |
| `filter(Closure $callback): static` | Filtre les éléments | `$tags->filter(fn($tag) => strlen($tag) > 3)` |
| `each(Closure $callback): static` | Exécute une action (sans modification) | `$collection->each(fn($item) => $sum += $item)` |

### 8.2. Tri et ordre

| Méthode | Description | Exemple |
|---------|-------------|---------|
| `sort(int $flags = SORT_REGULAR): static` | Trie les éléments | `$numbers->sort()` |
| `sortBy(Closure\|string $callback, bool $descending = false): static` | Trie par clé ou fonction | `$products->sortBy('price')` |
| `reverse(): static` | Inverse l'ordre | `$collection->reverse()` |


## 9. Méthodes de calcul sur les collections numériques

| Méthode | Description | Exemple |
|---------|-------------|---------|
| `sum(?Closure $callback = null): int\|float` | Calcule la somme | `$numbers->sum()` ou `$orders->sum(fn($o) => $o->total)` |
| `avg(?Closure $callback = null): ?float` | Calcule la moyenne | `$numbers->avg()` |
| `max(?Closure $callback = null): mixed` | Valeur maximale | `$numbers->max()` |
| `min(?Closure $callback = null): mixed` | Valeur minimale | `$numbers->min()` |

```php
// Exemple avec des Records
$orders = new TypedCollection(OrderRecord::class);
$orders->add(
    new OrderRecord(total: 100),
    new OrderRecord(total: 250),
    new OrderRecord(total: 75),
);

$total = $orders->sum(fn($order) => $order->total);  // 425
$average = $orders->avg(fn($order) => $order->total); // 141.67
$max = $orders->max(fn($order) => $order->total);     // 250
```

---

## 10. Méthodes de recherche et manipulation avancées

### 10.1. Recherche

| Méthode | Description | Exemple |
|---------|-------------|---------|
| `contains(mixed $value): bool` | Vérifie si un élément existe | `$tags->contains('laravel')` |
| `every(Closure $callback): bool` | Tous les éléments satisfont le prédicat | `$numbers->every(fn($n) => $n > 0)` |
| `some(Closure $callback): bool` | Au moins un élément satisfait | `$numbers->some(fn($n) => $n > 100)` |

### 10.2. Manipulation

| Méthode | Description | Exemple |
|---------|-------------|---------|
| `unique(?Closure $callback = null): static` | Supprimer les doublons | `$collection->unique()` |
| `merge(self $collection): static` | Fusionner deux collections | `$collection1->merge($collection2)` |

---

## 11. Créer une collection spécialisée

### 11.1. Pour les Records

```php
use AndyDefer\DomainStructures\Collections\Core\TypedCollection;

final class UserRecordCollection extends TypedCollection
{
    public function __construct()
    {
        parent::__construct(UserRecord::class);
    }
    
    public function getAdmins(): self
    {
        return $this->filter(fn(UserRecord $user) => $user->role === UserRole::ADMIN);
    }
    
    public function getActive(): self
    {
        return $this->filter(fn(UserRecord $user) => $user->status === UserStatus::ACTIVE);
    }
    
    public function getById(int $id): ?UserRecord
    {
        return $this->find(fn(UserRecord $user) => $user->id === $id);
    }
}
```

### 11.2. Pour les Data

```php
use AndyDefer\DomainStructures\Collections\Core\DataCollection;

final class ProductDataCollection extends DataCollection
{
    public function __construct()
    {
        parent::__construct(ProductData::class);
    }
    
    public function getFeatured(): self
    {
        return $this->filter(fn(ProductData $product) => $product->isFeatured === true);
    }
    
    public function getTotalPrice(): Price
    {
        $total = $this->reduce(fn($carry, ProductData $product) => $carry + $product->price->getAmount(), 0);
        return Price::from(['amount' => $total, 'currency' => 'EUR']);
    }
}
```

### 11.3. Utilisation

```php
$users = new UserRecordCollection();
$users->add(
    new UserRecord(id: 1, name: 'John', role: UserRole::ADMIN),
    new UserRecord(id: 2, name: 'Jane', role: UserRole::USER),
    new UserRecord(id: 3, name: 'Bob', role: UserRole::ADMIN),
);

$admins = $users->getAdmins();  // UserRecordCollection avec John et Bob
$activeUsers = $users->getActive();  // UserRecordCollection filtré
```
---

## 12. Hydratation et normalisation

### 12.1. Hydratation depuis une source

```php
// Depuis un tableau
$collection = TypedCollection::from([
    ['id' => 1, 'name' => 'Product 1', 'price' => 100],
    ['id' => 2, 'name' => 'Product 2', 'price' => 200],
]);