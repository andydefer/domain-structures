# Associative - Référence Technique

## Description

`Associative` est un **DataObject** qui normalise automatiquement les clés en **camelCase**. C'est un alias de `DataObject` pour une sémantique plus explicite.

## Hiérarchie

```
Transformable
    ↑
AbstractDataObject
    ↑
AbstractAssociative
    ↑
DataObject
    ↑
Associative
```

**Interfaces implémentées :** `ArrayAccess`, `Transformable`

## Rôle principal

`Associative` est un conteneur de données **immutable** qui normalise les clés en camelCase. Il permet d'accéder aux données via :
- Propriétés (->)
- Tableau ([])
- Méthodes get() et has()

## Installation

```bash
composer require andy-defer/domain-structures
```

```php
use AndyDefer\DomainStructures\Utils\Associative;
```

## API / Méthodes publiques

### `__construct(array $data = [])`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$data` | `array<string|int, mixed>` | Données initiales (clés normalisées en camelCase) |

**Retourne :** `void`

**Exemple :**
```php
$data = new Associative([
    'user_id' => 123,
    'user_name' => 'John Doe'
]);
// Les clés deviennent : userId, userName
```

---

### `with(string $key, mixed $value): static`

Crée une nouvelle instance avec une propriété modifiée.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$key` | `string` | Clé à modifier (sera normalisée en camelCase) |
| `$value` | `mixed` | Nouvelle valeur |

**Retourne :** `static` - Nouvelle instance

**Exemple :**
```php
$data = new Associative(['name' => 'John']);
$newData = $data->with('user_name', 'Jane');
// $newData->user_name = 'Jane'
```

---

### `merge(array $data): static`

Crée une nouvelle instance en fusionnant avec un tableau.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$data` | `array<string|int, mixed>` | Données à fusionner |

**Retourne :** `static` - Nouvelle instance

**Exemple :**
```php
$data = new Associative(['name' => 'John']);
$newData = $data->merge(['age' => 30, 'city' => 'Paris']);
// $newData->age = 30, $newData->city = 'Paris'
```

---

### `without(string ...$keys): static`

Crée une nouvelle instance sans certaines clés.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$keys` | `string` | Clés à supprimer |

**Retourne :** `static` - Nouvelle instance

**Exemple :**
```php
$data = new Associative(['name' => 'John', 'age' => 30]);
$newData = $data->without('age');
// $newData ne contient plus 'age'
```

---

### `get(string $name, mixed $default = null): mixed`

Obtient une propriété avec valeur par défaut.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$name` | `string` | Nom de la propriété |
| `$default` | `mixed` | Valeur par défaut si la clé n'existe pas |

**Retourne :** `mixed` - La valeur ou la valeur par défaut

**Exemple :**
```php
$data = new Associative(['name' => 'John']);
$name = $data->get('name');       // 'John'
$age = $data->get('age', 0);      // 0
```

---

### `has(string $name): bool`

Vérifie si une propriété existe.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$name` | `string` | Nom de la propriété |

**Retourne :** `bool` - `true` si existe, `false` sinon

**Exemple :**
```php
$data = new Associative(['name' => 'John']);
$data->has('name'); // true
$data->has('age');  // false
```

---

### `toArray(): array`

Retourne le tableau original.

**Retourne :** `array<string|int, mixed>`

**Exemple :**
```php
$data = new Associative(['name' => 'John']);
$array = $data->toArray(); // ['name' => 'John']
```

---

### `__get(string $name): mixed`

Accès magique aux propriétés.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$name` | `string` | Nom de la propriété |

**Retourne :** `mixed` - La valeur

**Exemple :**
```php
$data = new Associative(['user_name' => 'John']);
echo $data->user_name; // 'John'
```

---

### `__isset(string $name): bool`

Vérifie si une propriété existe (même avec valeur null).

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$name` | `string` | Nom de la propriété |

**Retourne :** `bool` - `true` si existe, `false` sinon

**Exemple :**
```php
$data = new Associative(['name' => null]);
isset($data->name); // true
```

---

### `offsetExists(mixed $offset): bool`

Vérifie si une clé existe (ArrayAccess).

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$offset` | `mixed` | Clé à vérifier |

**Retourne :** `bool` - `true` si existe, `false` sinon

**Exemple :**
```php
$data = new Associative(['name' => 'John']);
isset($data['name']); // true
```

---

### `offsetGet(mixed $offset): mixed`

Récupère une valeur par clé (ArrayAccess).

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$offset` | `mixed` | Clé |

**Retourne :** `mixed` - La valeur

**Exemple :**
```php
$data = new Associative(['name' => 'John']);
echo $data['name']; // 'John'
```

---

### `from(mixed $source): static`

Crée une instance à partir d'une source.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$source` | `mixed` | Source (array, objet) |

**Retourne :** `static` - Nouvelle instance

**Exceptions :** `InvalidArgumentException` - Si la source ne peut pas être convertie

**Exemple :**
```php
$data = Associative::from(['name' => 'John']);
```

---

### `fromJson(string $json): static`

Crée une instance à partir de JSON.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$json` | `string` | Chaîne JSON |

**Retourne :** `static` - Nouvelle instance

**Exemple :**
```php
$json = '{"name":"John","age":30}';
$data = Associative::fromJson($json);
```

---

### `collect(iterable $sources, string $collectionClass = TypedCollection::class): AbstractTypedCollection`

Collecte des sources dans une collection typée.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$sources` | `iterable` | Sources à collecter |
| `$collectionClass` | `class-string<AbstractTypedCollection>` | Classe de collection |

**Retourne :** `AbstractTypedCollection` - Collection typée

**Exceptions :** `InvalidArgumentException` - Si la classe de collection est invalide

**Exemple :**
```php
$collection = Associative::collect([
    ['name' => 'John'],
    ['name' => 'Jane']
]);
```

---

## Cas d'utilisation

### Cas 1 : Configuration d'application

```php
use AndyDefer\DomainStructures\Utils\Associative;

$config = new Associative([
    'app_name' => 'MonApp',
    'debug_mode' => true,
    'database_host' => 'localhost',
    'database_port' => 3306
]);

// Accès normalisé en camelCase
$appName = $config->appName;      // 'MonApp'
$debug = $config->debugMode;      // true
$host = $config->databaseHost;    // 'localhost'

// Modification
$newConfig = $config->with('app_name', 'MonApp V2');

// Fusion
$newConfig = $config->merge([
    'cache_driver' => 'redis',
    'session_lifetime' => 120
]);
```

### Cas 2 : DTO (Data Transfer Object)

```php
class UserDTO extends Associative
{
    public function getFullName(): string
    {
        return $this->firstName . ' ' . $this->lastName;
    }

    public function isAdult(): bool
    {
        return $this->age >= 18;
    }
}

$user = new UserDTO([
    'first_name' => 'John',
    'last_name' => 'Doe',
    'age' => 25
]);

echo $user->getFullName(); // 'John Doe'
echo $user->isAdult();     // true
```

### Cas 3 : Transformation de données

```php
$input = [
    'user_id' => 123,
    'user_name' => 'john_doe',
    'user_email' => 'john@example.com'
];

$data = Associative::from($input);

// Accès en camelCase
$id = $data->userId;    // 123
$name = $data->userName; // 'john_doe'
$email = $data->userEmail; // 'john@example.com'

// Export
$output = $data->toArray();
```

---

## Flux d'exécution

```
Création avec array
    ↓
Normalisation des clés (camelCase)
    ↓
Stockage interne
    ↓
Accès via ->, [], get()
    ↓
Immutable : modification → nouvelle instance
```

**Immutable :** Chaque opération retourne une **nouvelle instance**.

```php
$data = new Associative(['name' => 'John']);
$newData = $data->with('age', 30);

$data->toArray();    // ['name' => 'John'] - inchangé
$newData->toArray(); // ['name' => 'John', 'age' => 30] - nouvelle instance
```

---

## Gestion des erreurs

| Situation | Exception | Message |
|-----------|-----------|---------|
| Source invalide (from) | `InvalidArgumentException` | `Cannot create Associative from X. Expected array or object.` |
| Modification directe (__set) | `RuntimeException` | `Associative is immutable. Use with() or merge()...` |
| Modification directe (offsetSet) | `RuntimeException` | `Associative is immutable. Use with() or merge()...` |
| Suppression directe (offsetUnset) | `RuntimeException` | `Associative is immutable. Use without()...` |
| Classe de collection invalide (collect) | `InvalidArgumentException` | `Collection class "X" must extend AbstractTypedCollection` |

---

## Intégration

### Avec `AbstractDataObject`

`Associative` hérite de `DataObject` qui hérite de `AbstractAssociative` qui hérite de `AbstractDataObject`. Toutes les méthodes de `AbstractDataObject` sont disponibles.

### Avec `StrictAssociative`

`StrictAssociative` est le pendant qui **préserve la casse** des clés.

---

## Performance

| Opération | Complexité | Notes |
|-----------|------------|-------|
| `get()` | O(1) | Accès direct |
| `has()` | O(1) | Vérification directe |
| `with()` | O(n) | Copie du tableau |
| `merge()` | O(n) | Copie + fusion |
| `without()` | O(n) | Copie + suppression |

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

use AndyDefer\DomainStructures\Utils\Associative;

// Création
$user = new Associative([
    'first_name' => 'John',
    'last_name' => 'Doe',
    'email' => 'john@example.com',
    'age' => 30
]);

// Accès
echo $user->firstName;    // 'John'
echo $user->lastName;     // 'Doe'
echo $user['email'];      // 'john@example.com'

// Vérification
if ($user->has('age')) {
    echo $user->get('age'); // 30
}

// Modification
$updated = $user
    ->with('age', 31)
    ->with('city', 'Paris');

// Fusion
$updated = $updated->merge([
    'country' => 'France',
    'zip_code' => 75001
]);

// Suppression
$updated = $updated->without('zip_code');

// Export
$array = $updated->toArray();
print_r($array);

// JSON
echo $updated;
```

---

## Voir aussi

- `StrictAssociative` - Version qui préserve la casse
- `DataObject` - Classe parente
- `AbstractDataObject` - Classe de base
- `AbstractAssociative` - Classe abstraite
- `Transformable` - Interface pour l'hydratation

---

# StrictAssociative - Référence Technique

## Description

`StrictAssociative` est un **DataObject** qui **préserve la casse originale** des clés. Contrairement à `Associative` / `DataObject` qui normalise en camelCase, cette classe garde les clés exactement comme fournies.

## Hiérarchie

```
Transformable
    ↑
AbstractDataObject
    ↑
AbstractAssociative
    ↑
StrictAssociative
```

**Interfaces implémentées :** `ArrayAccess`, `Transformable`

## Rôle principal

`StrictAssociative` est un conteneur de données **immutable** qui préserve la casse des clés. Il permet d'accéder aux données via :
- Propriétés (->) avec la casse exacte
- Tableau ([])
- Méthodes get() et has()

## Installation

```bash
composer require andy-defer/domain-structures
```

```php
use AndyDefer\DomainStructures\Utils\StrictAssociative;
```

## API / Méthodes publiques

### `__construct(array $data = [])`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$data` | `array<string|int, mixed>` | Données initiales (clés conservées telles quelles) |

**Retourne :** `void`

**Exemple :**
```php
$data = new StrictAssociative([
    'user_id' => 123,
    'user_name' => 'John Doe'
]);
// Les clés restent : user_id, user_name
```

---

### `with(string $key, mixed $value): static`

Crée une nouvelle instance avec une propriété modifiée.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$key` | `string` | Clé à modifier (casse préservée) |
| `$value` | `mixed` | Nouvelle valeur |

**Retourne :** `static` - Nouvelle instance

**Exemple :**
```php
$data = new StrictAssociative(['user_id' => 123]);
$newData = $data->with('user_name', 'Jane');
// $newData->user_name = 'Jane'
```

---

### `merge(array $data): static`

Crée une nouvelle instance en fusionnant avec un tableau.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$data` | `array<string|int, mixed>` | Données à fusionner |

**Retourne :** `static` - Nouvelle instance

**Exemple :**
```php
$data = new StrictAssociative(['user_id' => 123]);
$newData = $data->merge(['user_name' => 'John', 'age' => 30]);
```

---

### `without(string ...$keys): static`

Crée une nouvelle instance sans certaines clés.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$keys` | `string` | Clés à supprimer |

**Retourne :** `static` - Nouvelle instance

**Exemple :**
```php
$data = new StrictAssociative(['user_id' => 123, 'age' => 30]);
$newData = $data->without('age');
```

---

### `get(string $name, mixed $default = null): mixed`

Obtient une propriété avec valeur par défaut.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$name` | `string` | Nom de la propriété (casse exacte) |
| `$default` | `mixed` | Valeur par défaut si la clé n'existe pas |

**Retourne :** `mixed` - La valeur ou la valeur par défaut

**Exemple :**
```php
$data = new StrictAssociative(['user_id' => 123]);
$id = $data->get('user_id');   // 123
$name = $data->get('user_name', 'Unknown'); // 'Unknown'
```

---

### `has(string $name): bool`

Vérifie si une propriété existe.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$name` | `string` | Nom de la propriété (casse exacte) |

**Retourne :** `bool` - `true` si existe, `false` sinon

**Exemple :**
```php
$data = new StrictAssociative(['user_id' => 123]);
$data->has('user_id'); // true
$data->has('userId');  // false (casse différente)
```

---

### `toArray(): array`

Retourne le tableau original.

**Retourne :** `array<string|int, mixed>`

**Exemple :**
```php
$data = new StrictAssociative(['user_id' => 123]);
$array = $data->toArray(); // ['user_id' => 123]
```

---

### `__get(string $name): mixed`

Accès magique aux propriétés.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$name` | `string` | Nom de la propriété (casse exacte) |

**Retourne :** `mixed` - La valeur

**Exemple :**
```php
$data = new StrictAssociative(['user_id' => 123]);
echo $data->user_id; // 123
```

---

### `__isset(string $name): bool`

Vérifie si une propriété existe.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$name` | `string` | Nom de la propriété (casse exacte) |

**Retourne :** `bool` - `true` si existe, `false` sinon

**Exemple :**
```php
$data = new StrictAssociative(['user_id' => null]);
isset($data->user_id); // true
isset($data->userId);  // false
```

---

### `offsetExists(mixed $offset): bool`

Vérifie si une clé existe (ArrayAccess).

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$offset` | `mixed` | Clé à vérifier |

**Retourne :** `bool` - `true` si existe, `false` sinon

**Exemple :**
```php
$data = new StrictAssociative(['user_id' => 123]);
isset($data['user_id']); // true
isset($data['userId']);  // false
```

---

### `offsetGet(mixed $offset): mixed`

Récupère une valeur par clé (ArrayAccess).

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$offset` | `mixed` | Clé |

**Retourne :** `mixed` - La valeur

**Exemple :**
```php
$data = new StrictAssociative(['user_id' => 123]);
echo $data['user_id']; // 123
```

---

### `from(mixed $source): static`

Crée une instance à partir d'une source.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$source` | `mixed` | Source (array, objet) |

**Retourne :** `static` - Nouvelle instance

**Exceptions :** `InvalidArgumentException` - Si la source ne peut pas être convertie

**Exemple :**
```php
$data = StrictAssociative::from(['user_id' => 123]);
```

---

### `fromJson(string $json): static`

Crée une instance à partir de JSON.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$json` | `string` | Chaîne JSON |

**Retourne :** `static` - Nouvelle instance

**Exemple :**
```php
$json = '{"user_id":123,"user_name":"John"}';
$data = StrictAssociative::fromJson($json);
```

---

### `collect(iterable $sources, string $collectionClass = TypedCollection::class): AbstractTypedCollection`

Collecte des sources dans une collection typée.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$sources` | `iterable` | Sources à collecter |
| `$collectionClass` | `class-string<AbstractTypedCollection>` | Classe de collection |

**Retourne :** `AbstractTypedCollection` - Collection typée

**Exceptions :** `InvalidArgumentException` - Si la classe de collection est invalide

---

## Cas d'utilisation

### Cas 1 : Données API (casse préservée)

```php
$apiResponse = StrictAssociative::from([
    'user_id' => 123,
    'user_name' => 'John Doe',
    'user_email' => 'john@example.com',
    'created_at' => '2024-01-01',
    'is_active' => true
]);

// Accès avec la casse exacte de l'API
$id = $apiResponse->user_id;        // 123
$name = $apiResponse->user_name;    // 'John Doe'
$email = $apiResponse->user_email;  // 'john@example.com'
$created = $apiResponse->created_at; // '2024-01-01'
$active = $apiResponse->is_active;   // true
```

### Cas 2 : Configuration avec clés complexes

```php
$config = new StrictAssociative([
    'DATABASE_HOST' => 'localhost',
    'DATABASE_PORT' => 3306,
    'CACHE_DRIVER' => 'redis',
    'SESSION_LIFETIME' => 120
]);

// Accès avec la casse exacte
$host = $config->DATABASE_HOST;      // 'localhost'
$port = $config->DATABASE_PORT;      // 3306
$driver = $config->CACHE_DRIVER;     // 'redis'
```

### Cas 3 : Migration depuis un tableau

```php
class UserDTO extends StrictAssociative
{
    public function getFullName(): string
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    public function isActive(): bool
    {
        return $this->is_active ?? false;
    }
}

$user = new UserDTO([
    'first_name' => 'John',
    'last_name' => 'Doe',
    'is_active' => true
]);

echo $user->getFullName(); // 'John Doe'
echo $user->isActive();    // true
```

---

## Comparaison Associative vs StrictAssociative

| Feature | Associative | StrictAssociative |
|---------|-------------|-------------------|
| Normalisation | camelCase | Aucune |
| snake_case → | camelCase | snake_case (préservé) |
| camelCase → | camelCase | camelCase (préservé) |
| UPPER_CASE → | upperCase | UPPER_CASE (préservé) |
| `->user_id` | ❌ (user_id → userId) | ✅ (user_id préservé) |
| `->userId` | ✅ | ✅ |

---

## Flux d'exécution

```
Création avec array
    ↓
Clés préservées (sans normalisation)
    ↓
Stockage interne
    ↓
Accès via ->, [], get() avec casse exacte
    ↓
Immutable : modification → nouvelle instance
```

---

## Gestion des erreurs

| Situation | Exception | Message |
|-----------|-----------|---------|
| Source invalide (from) | `InvalidArgumentException` | `Cannot create StrictAssociative from X. Expected array or object.` |
| Modification directe (__set) | `RuntimeException` | `StrictAssociative is immutable. Use with() or merge()...` |
| Modification directe (offsetSet) | `RuntimeException` | `StrictAssociative is immutable. Use with() or merge()...` |
| Suppression directe (offsetUnset) | `RuntimeException` | `StrictAssociative is immutable. Use without()...` |

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

use AndyDefer\DomainStructures\Utils\StrictAssociative;

// Création avec clés variées
$data = new StrictAssociative([
    'user_id' => 123,
    'user_name' => 'John Doe',
    'userEmail' => 'john@example.com',
    'CREATED_AT' => '2024-01-01',
    'is_active' => true,
    'metadata' => ['role' => 'admin']
]);

// Accès avec la casse exacte
echo $data->user_id;      // 123
echo $data->user_name;    // 'John Doe'
echo $data->userEmail;    // 'john@example.com'
echo $data->CREATED_AT;   // '2024-01-01'
echo $data->is_active;    // true

// Vérification
if ($data->has('user_id')) {
    echo $data->get('user_id'); // 123
}

// Modification
$updated = $data
    ->with('user_name', 'Jane Doe')
    ->with('is_active', false);

// Fusion
$updated = $updated->merge([
    'last_login' => '2024-01-02',
    'updated_at' => '2024-01-02'
]);

// Suppression
$updated = $updated->without('CREATED_AT');

// Export
$array = $updated->toArray();
print_r($array);

// JSON
echo $updated;
```

---

## Voir aussi

- `Associative` - Version qui normalise en camelCase
- `DataObject` - Alias de Associative
- `AbstractDataObject` - Classe de base
- `AbstractAssociative` - Classe abstraite
- `Transformable` - Interface pour l'hydratation