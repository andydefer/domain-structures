# TypedCollection - Documentation (Version finale)

## 1. Définition

Une **TypedCollection** est une collection **type-safe** qui remplace les tableaux bruts (`array`) dans les `Record`, `ValueObject` et `Data`. Elle garantit que tous les éléments qu'elle contient sont du type déclaré à la construction.

```
TypedCollection → Collection type-safe qui remplace les tableaux bruts
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

> **Une TypedCollection peut contenir des `Record`, `ValueObject`, `Data`, des scalaires, des `stdClass`, ou d'autres `TypedCollection`.**

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
| `AbstractData::class` | **Data (ou sous-classe)** | `new TypedCollection(UserData::class)` |
| `TypedCollection::class` | **Collection imbriquée** | `new TypedCollection(TypedCollection::class)` |
| `stdClass::class` | Objet standard | `new TypedCollection(stdClass::class)` |

### 3.2. Nouveauté : Support des Data et Value Objects

```php
// ✅ BON - Collection de Value Objects
$emails = new TypedCollection(EmailAddress::class);
$emails->add(
    EmailAddress::fromString('john@example.com'),
    EmailAddress::fromString('jane@example.com'),
);

// ✅ BON - Collection de Data
$usersData = new TypedCollection(UserData::class);
$usersData->add(
    UserData::fromRecord($user1),
    UserData::fromRecord($user2),
);

// ✅ BON - Collection mixte (Record + ValueObject + Data)
$mixed = new TypedCollection(
    UserRecord::class,
    EmailAddress::class,
    UserData::class,
    'string',
);
$mixed->add(
    new UserRecord(id: 1, name: 'John'),
    EmailAddress::fromString('john@example.com'),
    UserData::fromRecord($user),
    'additional note',
);
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
```

### 3.4. Types INTERDITS

| Type interdit | Raison | Alternative |
|---------------|--------|-------------|
| `array` brut | Non typé, non contrôlé | `TypedCollection` |
| `Enum` (directement) | Ne peut pas être sérialisé proprement | Utiliser la valeur scalaire de l'Enum |
| `Model` Eloquent | Contient logique et relations | `Record` ou `ValueObject` |
| `Carbon` / `DateTime` | Logique temporelle | `string` ISO |
| `object` (non stdClass) | Pas de typage | Classe spécifique |

```php
// ❌ MAUVAIS - Enum directement interdit
$collection = new TypedCollection(UserRole::class);  // ❌ Exception
$collection->add(UserRole::ADMIN);  // ❌ Exception

// ✅ BON - Utiliser la valeur scalaire de l'Enum
$collection = new TypedCollection('string');
$collection->add(UserRole::ADMIN->value);  // 'admin'

// ✅ BON - Ou utiliser un Value Object qui encapsule l'Enum
final class UserRoleVO extends AbstractValueObject
{
    public function __construct(public readonly UserRole $value) {}
}
$collection = new TypedCollection(UserRoleVO::class);
$collection->add(new UserRoleVO(UserRole::ADMIN));
```

---

## 4. Méthodes de base

### 4.1. Constructeur et création

```php
use AndyDefer\DomainStructures\Collections\TypedCollection;

// Collection de strings
$tags = new TypedCollection('string');
$tags->add('php', 'laravel', 'typescript');

// Collection de Records
$users = new TypedCollection(UserRecord::class);
$users->add(new UserRecord(id: 1, name: 'John'));

// Collection de Value Objects
$emails = new TypedCollection(EmailAddress::class);
$emails->add(EmailAddress::fromString('john@example.com'));

// Collection de Data (NOUVEAU)
$usersData = new TypedCollection(UserData::class);
$usersData->add(UserData::fromRecord($userRecord));

// Collection mixte (types multiples)
$mixed = new TypedCollection('int', 'string', UserRecord::class);
```

### 4.2. Méthodes d'ajout et accès

| Méthode | Description | Exemple |
|---------|-------------|---------|
| `add(...$items)` | Ajoute un ou plusieurs éléments | `$tags->add('php', 'laravel')` |
| `count(): int` | Nombre d'éléments | `$tags->count()` |
| `isEmpty(): bool` | Vérifie si vide | `$tags->isEmpty()` |
| `isNotEmpty(): bool` | Vérifie si non vide | `$tags->isNotEmpty()` |
| `toArray(): array` | Retourne tous les éléments | `$tags->toArray()` |
| `all(): static` | Retourne une nouvelle copie | `$tags->all()` |
| `getAllowedTypes(): array` | Types autorisés | `$tags->getAllowedTypes()` |

### 4.3. Méthodes d'accès aux éléments

| Méthode | Description | Exemple |
|---------|-------------|---------|
| `firstItem(): mixed` | Premier élément | `$tags->firstItem()` |
| `first(int $limit): static` | N premiers éléments | `$tags->first(3)` |
| `lastItem(): mixed` | Dernier élément | `$tags->lastItem()` |
| `last(int $limit): static` | N derniers éléments | `$tags->last(3)` |

---

## 5. Méthodes de transformation

### 5.1. Map, Filter, Reduce

| Méthode | Description | Exemple |
|---------|-------------|---------|
| `map(Closure $callback): static` | Transforme chaque élément | `$tags->map(fn($tag) => strtoupper($tag))` |
| `filter(Closure $callback): static` | Filtre les éléments | `$tags->filter(fn($tag) => strlen($tag) > 3)` |
| `reject(Closure $callback): static` | Rejette les éléments | `$tags->reject(fn($tag) => strlen($tag) > 3)` |
| `each(Closure $callback): static` | Exécute une action (sans modification) | `$collection->each(fn($item) => $sum += $item)` |
| `flatMap(Closure $callback): static` | Aplatit les collections imbriquées | `$nested->flatMap(fn($item) => $item)` |

### 5.2. Tri et ordre

| Méthode | Description | Exemple |
|---------|-------------|---------|
| `sort(int $flags = SORT_REGULAR): static` | Trie les éléments | `$numbers->sort()` |
| `sortBy(Closure\|string $callback, bool $descending = false): static` | Trie par clé ou fonction | `$products->sortBy('price')` |
| `reverse(): static` | Inverse l'ordre | `$collection->reverse()` |
| `shuffle(): static` | Mélange aléatoirement | `$collection->shuffle()` |

### 5.3. Slicing et pagination

| Méthode | Description | Exemple |
|---------|-------------|---------|
| `take(int $limit): static` | Prendre les n premiers | `$collection->take(10)` |
| `skip(int $offset): static` | Ignorer les n premiers | `$collection->skip(5)` |
| `slice(int $offset, ?int $length = null): static` | Extraire une plage | `$collection->slice(2, 3)` |
| `nth(int $step, int $offset = 0): static` | Un élément sur n | `$collection->nth(2)` |
| `values(): static` | Réindexer les clés | `$filtered->values()` |

---

## 6. Méthodes de calcul

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

## 7. Méthodes de filtrage par type

### 7.1. Filtrage générique

| Méthode | Description | Exemple |
|---------|-------------|---------|
| `ofType(string $type): static` | Filtrer par type | `$collection->ofType('string')` |
| `exceptType(string $type): static` | Exclure un type | `$collection->exceptType('int')` |
| `getTypes(): static` | Types distincts présents | `$collection->getTypes()` |
| `containsType(string $type): bool` | Vérifie si un type est présent | `$collection->containsType('int')` |
| `isOnlyType(string $type): bool` | Vérifie si tous sont d'un type | `$collection->isOnlyType('int')` |

### 7.2. Filtrage spécifique (NOUVEAU : Records, ValueObjects, Data)

| Méthode | Description | Exemple |
|---------|-------------|---------|
| `records(): static` | Filtrer les Records | `$collection->records()` |
| `valueObjects(): static` | Filtrer les Value Objects | `$collection->valueObjects()` |
| `data(): static` | **Filtrer les Data** | `$collection->data()` |
| `scalars(): static` | Filtrer les scalaires | `$collection->scalars()` |
| `ofRecord(string $recordClass): static` | Filtrer par classe Record | `$collection->ofRecord(UserRecord::class)` |
| `ofValueObject(string $valueObjectClass): static` | Filtrer par classe Value Object | `$collection->ofValueObject(EmailAddress::class)` |
| `ofData(string $dataClass): static` | **Filtrer par classe Data** | `$collection->ofData(UserData::class)` |
| `anyRecord(): static` | Tous les Records (alias) | `$collection->anyRecord()` |
| `anyValueObject(): static` | Tous les Value Objects (alias) | `$collection->anyValueObject()` |
| `anyData(): static` | **Toutes les Data (alias)** | `$collection->anyData()` |

```php
// Collection mixte
$mixed = new TypedCollection(
    UserRecord::class,
    EmailAddress::class,
    UserData::class,
    'string',
    'int',
);

$mixed->add(
    new UserRecord(id: 1, name: 'John'),
    EmailAddress::fromString('john@example.com'),
    UserData::fromRecord($userRecord),
    'note',
    42,
);

// Filtrage par type
$onlyRecords = $mixed->records();        // TypedCollection<UserRecord>
$onlyVOs = $mixed->valueObjects();       // TypedCollection<EmailAddress>
$onlyData = $mixed->data();              // TypedCollection<UserData> (NOUVEAU)
$onlyScalars = $mixed->scalars();        // TypedCollection<string|int>

// Filtrage par classe spécifique
$specificRecords = $mixed->ofRecord(UserRecord::class);
$specificVOs = $mixed->ofValueObject(EmailAddress::class);
$specificData = $mixed->ofData(UserData::class);  // (NOUVEAU)
```

---

## 8. Méthodes de recherche et manipulation avancées

### 8.1. Recherche

| Méthode | Description | Exemple |
|---------|-------------|---------|
| `contains(mixed $value): bool` | Vérifie si un élément existe | `$tags->contains('laravel')` |
| `every(Closure $callback): bool` | Tous les éléments satisfont le prédicat | `$numbers->every(fn($n) => $n > 0)` |
| `some(Closure $callback): bool` | Au moins un élément satisfait | `$numbers->some(fn($n) => $n > 100)` |
| `where(string $property, mixed $value): static` | Filtrer par propriété | `$products->where('price', 100)` |
| `whereNotNull(string $property): static` | Propriété non nulle | `$products->whereNotNull('price')` |
| `whereNull(string $property): static` | Propriété nulle | `$products->whereNull('price')` |

### 8.2. Manipulation

| Méthode | Description | Exemple |
|---------|-------------|---------|
| `unique(?Closure $callback = null): static` | Supprimer les doublons | `$collection->unique()` |
| `merge(self $collection): static` | Fusionner deux collections | `$collection1->merge($collection2)` |
| `intersect(self $collection): static` | Éléments communs | `$collection1->intersect($collection2)` |
| `diff(self $collection): static` | Éléments uniques à la première | `$collection1->diff($collection2)` |
| `filterNull(): static` | Supprimer les valeurs null | `$collection->filterNull()` |
| `random(int $number = 1): static` | Éléments aléatoires | `$collection->random(3)` |

---

## 9. Méthodes de validation et assertions

| Méthode | Description | Exemple |
|---------|-------------|---------|
| `isHomogeneous(): bool` | Tous les éléments du même type ? | `$collection->isHomogeneous()` |
| `isHeterogeneous(): bool` | Types différents ? | `$collection->isHeterogeneous()` |
| `assertAllOfType(string $type): self` | Vérifie que tous sont d'un type | `$collection->assertAllOfType('int')` |
| `assertNotEmpty(): self` | Vérifie que non vide | `$collection->assertNotEmpty()` |
| `assertContainsType(string $type): self` | Vérifie qu'un type est présent | `$collection->assertContainsType('int')` |
| `assertAllImplement(string $interface): self` | Vérifie l'implémentation d'interface | `$collection->assertAllImplement(AbstractRecord::class)` |
| `assertScalar(): self` | Vérifie que tous sont scalaires | `$collection->assertScalar()` |
| `assertRecords(): self` | Vérifie que tous sont des Records | `$collection->assertRecords()` |
| `assertValueObjects(): self` | Vérifie que tous sont des Value Objects | `$collection->assertValueObjects()` |
| `assertData(): self` | **Vérifie que tous sont des Data** | `$collection->assertData()` |
| `validate(Closure $validator): self` | Validation personnalisée | `$collection->validate(fn($item) => $item > 0)` |

---

## 10. Collections utilitaires prédéfinies

Le package fournit des collections spécialisées pour les types scalaires les plus courants. Ces classes étendent `TypedCollection` et ajoutent des méthodes spécifiques.

### 10.1. StringTypedCollection

Collection spécialisée pour les chaînes de caractères.

```php
use AndyDefer\DomainStructures\Collections\Utility\StringTypedCollection;

$strings = new StringTypedCollection();
$strings->add('  HELLO  ', 'world', 'PHP', '', '  test  ');
```

| Méthode | Description | Exemple |
|---------|-------------|---------|
| `toLowercase(): self` | Convertit en minuscules | `$strings->toLowercase()` |
| `toUppercase(): self` | Convertit en majuscules | `$strings->toUppercase()` |
| `containsSubstring(string): self` | Filtre par sous-chaîne | `$strings->containsSubstring('ell')` |
| `startsWith(string): self` | Filtre par préfixe | `$strings->startsWith('he')` |
| `endsWith(string): self` | Filtre par suffixe | `$strings->endsWith('lo')` |
| `filterEmpty(): self` | Supprime les chaînes vides | `$strings->filterEmpty()` |
| `trim(): self` | Supprime les espaces | `$strings->trim()` |
| `truncate(int, string): self` | Limite la longueur | `$strings->truncate(5, '...')` |
| `matchingRegex(string): self` | Filtre par regex | `$strings->matchingRegex('/^\d+$/')` |
| `join(string): string` | Joint toutes les chaînes | `$strings->join(', ')` |
| `slugify(): self` | Convertit en slug URL | `$strings->slugify()` |
| `wrap(string, ?string): self` | Encadre les chaînes | `$strings->wrap('[', ']')` |
| `removePrefix(string): self` | Supprime un préfixe | `$strings->removePrefix('pre_')` |
| `removeSuffix(string): self` | Supprime un suffixe | `$strings->removeSuffix('_suf')` |

### 10.2. IntTypedCollection

Collection spécialisée pour les entiers.

```php
use AndyDefer\DomainStructures\Collections\Utility\IntTypedCollection;

$numbers = new IntTypedCollection();
$numbers->add(1, 2, 3, 4, 5, 6, 7, 8, 9, 10);
```

| Méthode | Description | Exemple |
|---------|-------------|---------|
| `even(): self` | Nombres pairs | `$numbers->even()` → `[2, 4, 6, 8, 10]` |
| `odd(): self` | Nombres impairs | `$numbers->odd()` → `[1, 3, 5, 7, 9]` |
| `positive(): self` | Nombres positifs (>0) | `$numbers->positive()` |
| `negative(): self` | Nombres négatifs (<0) | `$numbers->negative()` |
| `zero(): self` | Zéros | `$numbers->zero()` |
| `nonNegative(): self` | Non négatifs (>=0) | `$numbers->nonNegative()` |
| `between(int, int): self` | Intervalle | `$numbers->between(2, 5)` |
| `median(): float` | Médiane | `$numbers->median()` → `5.5` |

### 10.3. FloatTypedCollection

Collection spécialisée pour les nombres décimaux.

```php
use AndyDefer\DomainStructures\Collections\Utility\FloatTypedCollection;

$floats = new FloatTypedCollection();
$floats->add(1.234, 2.567, 3.891);
```

| Méthode | Description | Exemple |
|---------|-------------|---------|
| `round(int $precision): self` | Arrondit à une précision | `$floats->round(2)` → `[1.23, 2.57, 3.89]` |
| `ceil(): self` | Entier supérieur | `$floats->ceil()` → `[2.0, 3.0, 4.0]` |
| `floor(): self` | Entier inférieur | `$floats->floor()` → `[1.0, 2.0, 3.0]` |
| `positive(): self` | Nombres positifs | `$floats->positive()` |
| `negative(): self` | Nombres négatifs | `$floats->negative()` |
| `between(float, float): self` | Intervalle | `$floats->between(1.5, 3.0)` |

### 10.4. BoolTypedCollection

Collection spécialisée pour les booléens.

```php
use AndyDefer\DomainStructures\Collections\Utility\BoolTypedCollection;

$bools = new BoolTypedCollection();
$bools->add(true, false, true, false, true);
```

| Méthode | Description | Exemple |
|---------|-------------|---------|
| `trueOnly(): self` | Uniquement `true` | `$bools->trueOnly()` → `[true, true, true]` |
| `falseOnly(): self` | Uniquement `false` | `$bools->falseOnly()` → `[false, false]` |
| `countTrue(): int` | Nombre de `true` | `$bools->countTrue()` → `3` |
| `countFalse(): int` | Nombre de `false` | `$bools->countFalse()` → `2` |
| `allTrue(): bool` | Tous `true` ? | `$bools->allTrue()` → `false` |
| `allFalse(): bool` | Tous `false` ? | `$bools->allFalse()` → `false` |
| `anyTrue(): bool` | Au moins un `true` ? | `$bools->anyTrue()` → `true` |
| `anyFalse(): bool` | Au moins un `false` ? | `$bools->anyFalse()` → `true` |

### 10.5. NumberTypedCollection

Collection pour les nombres mixtes (int + float).

```php
use AndyDefer\DomainStructures\Collections\Utility\NumberTypedCollection;

$numbers = new NumberTypedCollection();
$numbers->add(1, 2.5, 3, 4.7, 5);
```

| Méthode | Description | Exemple |
|---------|-------------|---------|
| `positive(): self` | Nombres positifs (> 0) | `$numbers->positive()` |
| `negative(): self` | Nombres négatifs (< 0) | `$numbers->negative()` |
| `between(int\|float, int\|float): self` | Intervalle | `$numbers->between(2, 4)` |
| `average(): float` | Moyenne | `$numbers->average()` |
| `zero(): self` | Zéros (0 ou 0.0) | `$numbers->zero()` |
| `nonNegative(): self` | Non négatifs (>= 0) | `$numbers->nonNegative()` |
| `areAllIntegers(): bool` | Tous entiers ? | `$numbers->areAllIntegers()` → `false` |
| `hasAnyFloat(): bool` | Au moins un float ? | `$numbers->hasAnyFloat()` → `true` |
| `toFloats(): FloatTypedCollection` | Convertit en floats | `$numbers->toFloats()` |
| `toIntegers(): IntTypedCollection` | Convertit en ints | `$numbers->toIntegers()` |
| `separateTypes(): array` | Sépare ints et floats | `$numbers->separateTypes()` |

### 10.6. Génération de séquences avec `range()`

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

## 11. Collections personnalisées

Pour les collections réutilisées partout avec des méthodes métier, créez une classe dédiée qui étend `TypedCollection`.

```php
use AndyDefer\DomainStructures\Collections\TypedCollection;

final class OrderCollection extends TypedCollection
{
    public function __construct()
    {
        parent::__construct(OrderRecord::class);
    }
    
    public function getTotal(): float
    {
        return $this->sum(fn($order) => $order->total);
    }
    
    public function getPending(): self
    {
        return $this->filter(fn($order) => $order->status === 'pending');
    }
    
    public function getCompleted(): self
    {
        return $this->filter(fn($order) => $order->status === 'completed');
    }
    
    public function getByCustomer(CustomerRecord $customer): self
    {
        return $this->filter(fn($order) => $order->customerId === $customer->id);
    }
}

// Utilisation
$orders = new OrderCollection();
$orders->add($order1, $order2, $order3);
$total = $orders->getTotal();           // 1500
$pending = $orders->getPending();       // OrderCollection avec commandes en attente
$completed = $orders->getCompleted();   // OrderCollection avec commandes terminées
```

---

## 12. Exemples complets

### 12.1. Record avec TypedCollection de Value Objects

```php
final class UserProfileRecord extends AbstractRecord
{
    public function __construct(
        public readonly EmailAddress $email,
        public readonly TypedCollection $tags,        // TypedCollection<string>
        public readonly TypedCollection $emails,      // TypedCollection<EmailAddress>
        public readonly TypedCollection $addresses,   // TypedCollection<Address>
    ) {}
}

// Utilisation
$profile = new UserProfileRecord(
    email: EmailAddress::fromString('john@example.com'),
    tags: (new TypedCollection('string'))->add('vip', 'premium', 'verified'),
    emails: (new TypedCollection(EmailAddress::class))->add(
        EmailAddress::fromString('john@example.com'),
        EmailAddress::fromString('john.work@example.com'),
    ),
    addresses: (new TypedCollection(Address::class))->add(
        Address::fromString('123 Main St, Paris'),
        Address::fromString('456 Oak Ave, Lyon'),
    ),
);
```

### 12.2. Service qui manipule une TypedCollection de Records

```php
final class OrderService
{
    public function calculateTotal(OrderRecord $order): float
    {
        return $order->items->sum(fn($item) => $item->price * $item->quantity);
    }
    
    public function getExpensiveItems(OrderRecord $order, float $threshold): TypedCollection
    {
        return $order->items->filter(fn($item) => $item->price > $threshold);
    }
    
    public function getProductNames(OrderRecord $order): StringTypedCollection
    {
        return $order->items->map(fn($item) => $item->productName);
    }
    
    public function validateOrder(OrderRecord $order): bool
    {
        return $order->items->every(fn($item) => $item->quantity > 0)
            && $order->items->some(fn($item) => $item->price > 0);
    }
}
```

### 12.3. Data avec TypedCollection (NOUVEAU)

```php
final class OrderData extends AbstractData
{
    public function __construct(
        public readonly string $id,
        public readonly TypedCollection $items,  // TypedCollection<OrderItemData>
        public readonly float $total,
    ) {}
    
    public static function fromRecord(OrderRecord $record): self
    {
        $items = new TypedCollection(OrderItemData::class);
        foreach ($record->items->all() as $item) {
            $items->add(OrderItemData::fromRecord($item));
        }
        
        return new self(
            id: (string) $record->id,
            items: $items,
            total: $record->total,
        );
    }
}

// Dans l'Action
final class ShowOrderAction extends AbstractAction
{
    protected function handle(Recordable $request): JsonResponse
    {
        $orderRecord = $this->orderService->getOrder($request);
        $orderData = OrderData::fromRecord($orderRecord);
        
        return $this->json($orderData);
    }
}
```

### 12.4. Value Object avec TypedCollection

```php
final class EmailAddressList extends AbstractValueObject
{
    public function __construct(
        public readonly TypedCollection $emails,  // TypedCollection<EmailAddress>
    ) {}
    
    public static function fromArray(array $emails): self
    {
        $collection = new TypedCollection(EmailAddress::class);
        foreach ($emails as $email) {
            $collection->add(EmailAddress::fromString($email));
        }
        
        return new self($collection);
    }
    
    public function getDomains(): StringTypedCollection
    {
        return $this->emails->map(fn($email) => $email->getDomain());
    }
    
    public function getGmailAddresses(): self
    {
        return new self(
            $this->emails->filter(fn($email) => $email->isGmail())
        );
    }
    
    public function contains(EmailAddress $email): bool
    {
        return $this->emails->some(fn($e) => $e->equals($email));
    }
}
```

### 12.5. Collection mixte avancée

```php
// Logger qui accepte différents types d'entrées
final class AuditLogRecord extends AbstractRecord
{
    public function __construct(
        public readonly TypedCollection $entries,  // TypedCollection<UserRecord|ActionRecord|string>
    ) {}
}

// Utilisation
$log = new AuditLogRecord(
    entries: (new TypedCollection(UserRecord::class, ActionRecord::class, 'string'))->add(
        new UserRecord(id: 1, name: 'John'),
        new ActionRecord(name: 'login', timestamp: '2024-01-15T10:00:00Z'),
        'User logged in successfully',
    ),
);

// Extraction par type
$users = $log->entries->records();        // TypedCollection<UserRecord>
$actions = $log->entries->ofRecord(ActionRecord::class);  // TypedCollection<ActionRecord>
$messages = $log->entries->scalars();     // TypedCollection<string>
```

---

## 13. Ce qu'une TypedCollection ne peut PAS faire

| Interdit | Pourquoi | Alternative |
|----------|----------|-------------|
| Contenir des Enums directement | Non sérialisable proprement | Utiliser la valeur scalaire ou un VO |
| Contenir des `array` bruts | Non typé | Utiliser une TypedCollection imbriquée |
| Contenir des `Model` Eloquent | Logique + DB | Transformer en Record d'abord |
| Contenir des `Carbon`/`DateTime` | Logique temporelle | Utiliser `string` ISO |
| Contenir des objets non autorisés (`object` générique) | Pas de typage | Utiliser stdClass ou une classe spécifique |

```php
// ❌ MAUVAIS - Enum interdit
$collection = new TypedCollection(UserRole::class);  // Exception
$collection->add(UserRole::ADMIN);  // Exception

// ✅ BON - Utiliser la valeur scalaire
$collection = new TypedCollection('string');
$collection->add(UserRole::ADMIN->value);  // 'admin'

// ❌ MAUVAIS - Model interdit
$collection = new TypedCollection(User::class);
$collection->add(User::find(1));

// ✅ BON - Transformer en Record d'abord
$collection = new TypedCollection(UserRecord::class);
$collection->add(UserRecord::fromModel(User::find(1)));
```

---

## 14. Résumé des contraintes

| Contrainte | Règle |
|------------|-------|
| **Constructeur** | Doit recevoir au moins un type |
| **Types autorisés** | `int`, `string`, `float`, `bool`, `null`, `Record`, `ValueObject`, **`Data`**, `TypedCollection`, `stdClass` |
| **Types interdits** | `array`, `Enum`, `Model`, `Carbon`, `DateTime`, `object` générique |
| **Validation** | Automatique à l'ajout |
| **Immutabilité** | Les méthodes retournent de nouvelles instances |
| **Sérialisation** | `toArray()` convertit récursivement |

---

## 15. Règle d'or

> **Une TypedCollection est une collection type-safe qui remplace les tableaux bruts. Elle garantit que tous les éléments sont du type déclaré. Elle peut contenir des `Record`, `ValueObject`, `Data`, des scalaires, des `stdClass`, ou d'autres `TypedCollection`. Les Enums doivent être convertis en leurs valeurs scalaires.**

```php
// La TypedCollection parfaite
final class PerfectRecord extends AbstractRecord
{
    public function __construct(
        public readonly TypedCollection $items,     // TypedCollection<ItemRecord>
        public readonly StringTypedCollection $tags, // StringTypedCollection
        public readonly TypedCollection $emails,    // TypedCollection<EmailAddress>
        public readonly TypedCollection $data,      // TypedCollection<UserData> (NOUVEAU)
    ) {}
}

// Utilisation
$record = new PerfectRecord(
    items: (new TypedCollection(ItemRecord::class))->add(
        new ItemRecord(name: 'Laptop', price: 999.99),
        new ItemRecord(name: 'Mouse', price: 29.99),
    ),
    tags: (new StringTypedCollection())->add('electronics', 'sale'),
    emails: (new TypedCollection(EmailAddress::class))->add(
        EmailAddress::fromString('john@example.com'),
    ),
    data: (new TypedCollection(UserData::class))->add(
        UserData::fromRecord($userRecord),
    ),
);
```

---

## 16. Avantages pour le code

| Avantage | Explication |
|----------|-------------|
| **Type safety** | Le compilateur/IDE garantit les types |
| **Validation automatique** | Impossible d'ajouter un mauvais type |
| **Méthodes utilitaires** | `map`, `filter`, `sum`, `avg`, etc. |
| **Documentation vivante** | Le type déclaré documente l'intention |
| **Réutilisation** | Collections personnalisées avec méthodes métier |
| **Testabilité** | Facile à tester (collection pure) |
| **Support des Data** | Peut transporter des Data pour les réponses API |
| **Support des Value Objects** | Peut contenir des concepts métier |

---

## 17. En résumé : Où utiliser TypedCollection ?

| Situation | Utilisation |
|-----------|-------------|
| **Plusieurs éléments dans un Record** | `TypedCollection<Record>` |
| **Plusieurs Value Objects** | `TypedCollection<ValueObject>` |
| **Plusieurs Data** | `TypedCollection<Data>` (NOUVEAU) |
| **Plusieurs scalaires** | `TypedCollection<string>` ou `StringTypedCollection` |
| **Collection mixte** | `TypedCollection(Record::class, ValueObject::class, 'string')` |
| **Collection réutilisée avec méthodes** | Collection personnalisée héritant de `TypedCollection` |

```php
// Record interne → utilise TypedCollection de Records ou VOs
final class OrderRecord extends AbstractRecord
{
    public function __construct(
        public readonly TypedCollection $items,  // TypedCollection<OrderItemRecord>
    ) {}
}

// Data API → utilise TypedCollection de Data
final class OrderData extends AbstractData
{
    public function __construct(
        public readonly TypedCollection $items,  // TypedCollection<OrderItemData>
    ) {}
}

// Value Object → utilise TypedCollection de VOs
final class EmailList extends AbstractValueObject
{
    public function __construct(
        public readonly TypedCollection $emails,  // TypedCollection<EmailAddress>
    ) {}
}
```