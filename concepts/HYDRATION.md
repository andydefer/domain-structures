# Trait Hydratable - Documentation Complète

## Table des matières

1. [Définition et concepts](#1-définition-et-concepts)
2. [Pourquoi Hydratable ?](#2-pourquoi-hydratable-)
3. [Hydratation avec HydrationService](#3-hydratation-avec-hydrationservice)
4. [Processus d'hydratation détaillé](#4-processus-dhydratation-détaillé)
5. [Gestion des types](#5-gestion-des-types)
6. [Gestion des valeurs absentes et null](#6-gestion-des-valeurs-absentes-et-null)
7. [Conventions de casse](#7-conventions-de-casse)
8. [Cas d'utilisation](#8-cas-dutilisation)
9. [Exemples concrets](#9-exemples-concrets)
10. [Bonnes pratiques](#10-bonnes-pratiques)
11. [Récapitulatif](#11-récapitulatif)

---

## 1. Définition et concepts

Le trait `Hydratable` est un système d'**hydratation automatique** qui analyse le constructeur d'une classe et remplit ses propriétés à partir de sources diverses (tableaux, objets, JSON, DataObject).

### 1.1. Qu'est-ce que l'hydratation ?

L'hydratation est le processus de création d'un objet à partir de données brutes (souvent des tableaux ou du JSON). C'est l'inverse de la normalisation.

```php
use AndyDefer\DomainStructures\Traits\Hydratable;
use AndyDefer\DomainStructures\Services\HydrationService;

class UserRecord extends AbstractRecord
{
    use Hydratable;
    
    public function __construct(
        public readonly int $user_id,      
        public readonly string $first_name, 
        public readonly string $last_name,  
        public readonly ?string $email = null
    ) {}
}

// Hydratation via HydrationService
$hydration = new HydrationService();
$user = $hydration->hydrate(UserRecord::class, [
    'user_id' => 123,
    'first_name' => 'John',
    'last_name' => 'Doe'
]);

echo $user->first_name;  // "John"
```

### 1.2. Principes fondamentaux

| Principe | Description |
|----------|-------------|
| **Analyse réflexive** | Examine le constructeur pour connaître les paramètres attendus |
| **Normalisation des clés** | Utilise DataObject pour un accès flexible |
| **Conversion automatique** | Convertit les valeurs vers les types attendus (int, string, etc.) |
| **Support des unions** | Gère les types unions (int\|float, etc.) |
| **Récursivité** | Hydrate automatiquement les objets Transformable |
| **Gestion des defaults** | Utilise les valeurs par défaut du constructeur |
| **Support null** | Gère correctement les paramètres nullables |

---

## 2. Pourquoi Hydratable ?

### 2.1. Le problème de l'hydratation manuelle

```php
// ❌ SANS Hydratable - Hydratation manuelle et répétitive
class UserRecord extends AbstractRecord
{
    public function __construct(
        public readonly int $user_id,
        public readonly string $first_name,
        public readonly string $last_name
    ) {}
    
    public static function fromArray(array $data): self
    {
        return new self(
            user_id: $data['user_id'] ?? 0,
            first_name: $data['first_name'] ?? '',
            last_name: $data['last_name'] ?? ''
        );
    }
}

// ✅ AVEC Hydratable - Automatique et standardisé
class UserRecord extends AbstractRecord
{
    use Hydratable;
    
    public function __construct(
        public readonly int $user_id,
        public readonly string $first_name,
        public readonly string $last_name
    ) {}
}

// Hydratation via HydrationService
$hydration = new HydrationService();
$user = $hydration->hydrate(UserRecord::class, [
    'user_id' => 123,
    'first_name' => 'John',
    'last_name' => 'Doe'
]);
```

### 2.2. Ce que Hydratable résout

| Problème | Solution |
|----------|----------|
| Mapping manuel des clés | Normalisation automatique via DataObject |
| Conversion de types | Conversion automatique (int, float, string, bool) |
| Gestion des enums | Conversion automatique via tryFrom() |
| Objets imbriqués | Hydratation récursive des Transformable |
| Valeurs par défaut | Utilisation des defaults du constructeur |
| Sources multiples | Interface unifiée via HydrationService |
| Code répétitif | Une seule logique d'hydratation centralisée |

---

## 3. Hydratation avec HydrationService

### 3.1. Installation du service

```php
use AndyDefer\DomainStructures\Services\HydrationService;

$hydration = new HydrationService();
```

### 3.2. Hydratation d'un seul objet

```php
// Depuis un tableau (clés snake_case pour les Records)
$user = $hydration->hydrate(UserRecord::class, [
    'user_id' => 123,
    'first_name' => 'John',
    'last_name' => 'Doe'
]);

// Depuis JSON
$user = $hydration->hydrateFromJson(UserRecord::class, '{"user_id":123,"first_name":"John"}');

// Depuis un objet existant
$user = $hydration->hydrate(UserRecord::class, $existingObject);
```

### 3.3. Hydratation d'une collection

```php
$sources = [
    ['user_id' => 1, 'first_name' => 'John'],
    ['user_id' => 2, 'first_name' => 'Jane'],
];

$users = $hydration->collect($sources, UserCollection::class);

// Depuis JSON
$json = '[{"user_id":1,"first_name":"John"},{"user_id":2,"first_name":"Jane"}]';
$users = $hydration->collectFromJson($json, UserCollection::class);
```

### 3.4. Dans une Action

```php
use AndyDefer\DomainStructures\Services\HydrationService;
use AndyDefer\Actions\Http\ResponseFactory;

final class CreateUserAction extends AbstractAction
{
    private HydrationService $hydration;
    
    public function __construct(
        private readonly UserService $userService,
    ) {
        $this->hydration = new HydrationService();
    }
    
    protected function handle(AbstractRecord $request): ResponseFactory
    {
        /** @var CreateUserRecord $request */
        
        // Hydratation vers Data (camelCase)
        $userData = $this->hydration->hydrate(
            UserData::class,
            $request->toArray()
        );
        
        $result = $this->userService->create($userData);
        
        // Retour Data (camelCase)
        return ResponseFactory::json(
            $this->hydration->hydrate(UserData::class, $result->toArray()),
            201
        );
    }
}
```

---

## 4. Processus d'hydratation détaillé

### 4.1. Étape 1: Normalisation de la source

```php
// La source est normalisée en DataObject via HydrationService
$dataObject = new DataObject($source);

// DataObject permet :
// - Accès indifférent camelCase/snake_case
// - Conversion récursive des tableaux imbriqués
```

### 4.2. Étape 2: Analyse du constructeur

```php
$reflection = new ReflectionClass(static::class);
$constructor = $reflection->getConstructor();

foreach ($constructor->getParameters() as $parameter) {
    $paramName = $parameter->getName();     // "first_name" pour Record
    $paramType = $parameter->getType();     // string
    $hasDefault = $parameter->isDefaultValueAvailable();
    $allowsNull = $parameter->allowsNull();
}
```

### 4.3. Étape 3: Récupération de la valeur

```php
// Recherche dans DataObject
private static function getValueFromDataObject(DataObject $dataObject, string $paramName): mixed
{
    // DataObject trouve automatiquement la correspondance
    if ($dataObject->has($paramName)) {
        return $dataObject->get($paramName);
    }
    
    return self::VALUE_ABSENT;
}
```

### 4.4. Étape 4: Conversion du type

```php
// Conversion automatique selon le type attendu
match ($typeName) {
    'int' => self::toInt($rawValue, $paramName),
    'float' => self::toFloat($rawValue, $paramName),
    'string' => self::toString($rawValue, $paramName),
    'bool' => self::toBool($rawValue, $paramName),
};

// Enums
if ($phpType->isEnum()) {
    return $typeName::tryFrom($rawValue);
}

// Transformable (hydratation récursive)
if (is_subclass_of($typeName, Transformable::class)) {
    return $typeName::from($rawValue);
}
```

### 4.5. Étape 5: Construction

```php
// Appel du constructeur avec tous les paramètres résolus
return new static(...$parameters);
```

---

## 5. Gestion des types

### 5.1. Types scalaires

```php
class ProductRecord extends AbstractRecord
{
    use Hydratable;
    
    public function __construct(
        public readonly int $product_id,   
        public readonly string $product_name,
        public readonly float $price,
        public readonly bool $is_active
    ) {}
}

$hydration = new HydrationService();
$product = $hydration->hydrate(ProductRecord::class, [
    'product_id' => '123',      // string → int
    'product_name' => 'Laptop',
    'price' => '99.99',         // string → float
    'is_active' => 'true'       // string → bool
]);
```

### 5.2. Types unions

```php
class ConfigRecord extends AbstractRecord
{
    use Hydratable;
    
    public function __construct(
        public readonly int|float $config_value,  // Union type
        public readonly string|null $description   // Nullable
    ) {}
}

$hydration = new HydrationService();

$config1 = $hydration->hydrate(ConfigRecord::class, ['config_value' => 42]);     // int
$config2 = $hydration->hydrate(ConfigRecord::class, ['config_value' => 42.5]);   // float
```

### 5.3. Enums

```php
enum OrderStatus: string
{
    case PENDING = 'pending';
    case PAID = 'paid';
    case SHIPPED = 'shipped';
}

class OrderRecord extends AbstractRecord
{
    use Hydratable;
    
    public function __construct(
        public readonly OrderStatus $order_status
    ) {}
}

$hydration = new HydrationService();
$order = $hydration->hydrate(OrderRecord::class, ['order_status' => 'paid']);

echo $order->order_status->value;  // 'paid'
```

### 5.4. Objets Transformable (Value Objects en camelCase)

```php
class EmailAddress extends AbstractValueObject implements Transformable
{
    public function __construct(private string $emailAddress) {}  // camelCase
    
    public static function from(mixed $source): static
    {
        return new static((string) $source);
    }
}

class UserRecord extends AbstractRecord
{
    use Hydratable;
    
    public function __construct(
        public readonly string $user_name,
        public readonly EmailAddress $email_address  // snake_case pour Record
    ) {}
}

$hydration = new HydrationService();
$user = $hydration->hydrate(UserRecord::class, [
    'user_name' => 'John',
    'email_address' => 'john@example.com'
]);

echo get_class($user->email_address);  // EmailAddress
```

### 5.5. Collections

```php
class ProductRecord extends AbstractRecord
{
    use Hydratable;
    
    public function __construct(
        public readonly string $product_name,
        public readonly StringTypedCollection $tags  // Collection
    ) {}
}

$hydration = new HydrationService();
$product = $hydration->hydrate(ProductRecord::class, [
    'product_name' => 'Laptop',
    'tags' => ['electronics', 'premium']
]);

echo get_class($product->tags);  // StringTypedCollection
```

---

## 6. Gestion des valeurs absentes et null

### 6.1. Quatre cas de figure

```php
class UserRecord extends AbstractRecord
{
    use Hydratable;
    
    public function __construct(
        public readonly string $user_name,        // Requis
        public readonly string $email,            // Requis
        public readonly ?string $phone_number,    // Nullable
        public readonly string $country = 'FR'    // Default
    ) {}
}
```

#### Cas 1: Valeur absente avec défaut

```php
$hydration = new HydrationService();
$user = $hydration->hydrate(UserRecord::class, [
    'user_name' => 'John',
    'email' => 'john@ex.com'
]);
// $country → 'FR' (valeur par défaut)
```

#### Cas 2: null explicite autorisé

```php
$user = $hydration->hydrate(UserRecord::class, [
    'user_name' => 'John',
    'email' => 'john@ex.com',
    'phone_number' => null
]);
// $phone_number → null
```

#### Cas 3: Valeur normale

```php
$user = $hydration->hydrate(UserRecord::class, [
    'user_name' => 'John',
    'email' => 'john@ex.com',
    'country' => 'BE'
]);
// $country → 'BE' (surcharge le default)
```

#### Cas 4: Requis manquant → Exception

```php
// ❌ Exception: Missing required parameter "email"
$user = $hydration->hydrate(UserRecord::class, ['user_name' => 'John']);
```

### 6.2. Distinction null vs absent

```php
$hydration = new HydrationService();

// Source avec null explicite
$data = ['user_name' => 'John', 'email' => null];
$user = $hydration->hydrate(UserRecord::class, $data);
// $email → null (OK car nullable)

// Source sans la clé
$data = ['user_name' => 'John'];
$user = $hydration->hydrate(UserRecord::class, $data);
// ❌ Exception car email requis
```

---

## 7. Conventions de casse

> **⚠️ CONVENTIONS STRICTES À RESPECTER**

| Type de classe | Convention | Exemple |
|----------------|------------|---------|
| **Record** | `snake_case` | `$user_id`, `$first_name`, `$created_at` |
| **Data** | `camelCase` | `$userId`, `$firstName`, `$createdAt` |
| **Value Object** | `camelCase` | `$emailAddress`, `$phoneNumber` |
| **Enum** | `SCREAMING_SNAKE_CASE` | `UserRole::ADMIN`, `OrderStatus::PENDING` |

### 7.1. Record (snake_case)

```php
// ✅ BON - Record avec propriétés en snake_case
final class UserRecord extends AbstractRecord
{
    use Hydratable;
    
    public function __construct(
        public readonly int $user_id,
        public readonly string $first_name,
        public readonly string $last_name,
        public readonly string $email_address,
        public readonly \DateTimeImmutable $created_at,
    ) {}
}

// Hydratation (clés snake_case)
$user = $hydration->hydrate(UserRecord::class, [
    'user_id' => 123,
    'first_name' => 'John',
    'last_name' => 'Doe',
    'email_address' => 'john@example.com',
    'created_at' => '2024-01-01 12:00:00'
]);
```

### 7.2. Value Object (camelCase)

```php
// ✅ BON - Value Object avec propriétés en camelCase
final class EmailAddress extends AbstractValueObject
{
    public function __construct(
        public readonly string $emailAddress  // camelCase
    ) {}
}

final class Money extends AbstractValueObject
{
    public function __construct(
        public readonly float $amount,      
        public readonly string $currency    
    ) {}
}
```

### 7.3. Data (camelCase)

```php
// ✅ BON - Data avec propriétés en camelCase
final class UserData extends AbstractData
{
    use Hydratable;
    
    public function __construct(
        public readonly int $userId,        
        public readonly string $firstName,  
        public readonly string $lastName,   
        public readonly string $email,      
    ) {}
}
```

### 7.4. Exemple de conversion Record → Data

```php
$hydration = new HydrationService();

// Record (snake_case)
$record = $hydration->hydrate(UserRecord::class, [
    'user_id' => 123,
    'first_name' => 'John',
    'last_name' => 'Doe'
]);

// Data (camelCase) - conversion automatique
$data = $hydration->hydrate(UserData::class, $record->toArray());

echo $data->userId;     // 123 (camelCase)
echo $data->firstName;  // John (camelCase)
```

---

## 8. Cas d'utilisation

### 8.1. API Response

```php
use AndyDefer\DomainStructures\Services\HydrationService;

class ApiService
{
    private HydrationService $hydration;
    
    public function __construct()
    {
        $this->hydration = new HydrationService();
    }
    
    public function getUser(int $id): UserData
    {
        $response = $this->httpClient->get("/users/{$id}");
        
        // Hydratation directe en Data (camelCase pour l'API)
        return $this->hydration->hydrateFromJson(UserData::class, $response->getBody());
    }
}
```

### 8.2. Repository

```php
use AndyDefer\DomainStructures\Services\HydrationService;

class UserRepository
{
    private HydrationService $hydration;
    
    public function __construct()
    {
        $this->hydration = new HydrationService();
    }
    
    public function find(int $id): ?UserRecord
    {
        $row = $this->db->fetchAssoc('SELECT * FROM users WHERE user_id = ?', [$id]);
        // Base de données → Record (snake_case)
        return $row ? $this->hydration->hydrate(UserRecord::class, $row) : null;
    }
}
```

### 8.3. Service avec transformation Record → Data

```php
use AndyDefer\DomainStructures\Services\HydrationService;

class UserService
{
    private HydrationService $hydration;
    
    public function __construct(
        private readonly UserRepository $repository
    ) {
        $this->hydration = new HydrationService();
    }
    
    public function getUserData(int $id): UserData
    {
        // Record (interne, snake_case)
        $record = $this->repository->find($id);
        
        // Data (API, camelCase)
        return $this->hydration->hydrate(UserData::class, $record->toArray());
    }
}
```

---

## 9. Exemples concrets

### 9.1. Structure complexe avec conventions

```php
// Value Objects (camelCase)
class Address extends AbstractValueObject implements Transformable
{
    public function __construct(
        private string $streetAddress,
        private string $city,
        private string $postalCode
    ) {}
    
    public static function from(mixed $source): static
    {
        return new static($source['streetAddress'], $source['city'], $source['postalCode']);
    }
}

// Record (snake_case)
class OrderRecord extends AbstractRecord
{
    use Hydratable;
    
    public function __construct(
        public readonly int $order_id,
        public readonly Address $shipping_address,
        public readonly OrderStatus $order_status,
        public readonly ?string $notes = null
    ) {}
}

// Data (camelCase)
class OrderData extends AbstractData
{
    use Hydratable;
    
    public function __construct(
        public readonly int $orderId,
        public readonly Address $shippingAddress,
        public readonly OrderStatus $orderStatus,
        public readonly ?string $notes = null
    ) {}
}

$hydration = new HydrationService();

// Hydratation depuis JSON externe (snake_case pour l'API externe)
$json = '{
    "order_id": 12345,
    "shipping_address": {
        "streetAddress": "123 Main St",
        "city": "Paris",
        "postalCode": "75001"
    },
    "order_status": "paid",
    "notes": "Gift wrap please"
}';

$order = $hydration->hydrateFromJson(OrderRecord::class, $json);
echo $order->shipping_address->city;  // "Paris"

// Transformation en Data pour réponse API
$orderData = $hydration->hydrate(OrderData::class, $order->toArray());
echo $orderData->orderId;  // 12345
```

### 9.2. Conversion Record → Data complète

```php
class UserRecord extends AbstractRecord
{
    use Hydratable;
    
    public function __construct(
        public readonly int $user_id,
        public readonly string $first_name,
        public readonly string $last_name,
        public readonly string $email_address,
        public readonly \DateTimeImmutable $created_at,
    ) {}
}

class UserData extends AbstractData
{
    use Hydratable;
    
    public function __construct(
        public readonly int $userId,
        public readonly string $firstName,
        public readonly string $lastName,
        public readonly string $email,
        public readonly string $createdAt,
    ) {}
}

$hydration = new HydrationService();

// Record (snake_case)
$record = $hydration->hydrate(UserRecord::class, [
    'user_id' => 123,
    'first_name' => 'John',
    'last_name' => 'Doe',
    'email_address' => 'john@example.com',
    'created_at' => new \DateTimeImmutable('2024-01-01')
]);

// Data (camelCase) - conversion automatique des clés
$data = $hydration->hydrate(UserData::class, $record->toArray());

echo $data->userId;     // 123
echo $data->firstName;  // John
echo $data->email;      // john@example.com
```

---

## 10. Bonnes pratiques

### 10.1. Respecter les conventions de casse

```php
// ✅ BON
class UserRecord extends AbstractRecord
{
    public function __construct(
        public readonly int $user_id,      
        public readonly string $first_name 
    ) {}
}

class UserData extends AbstractData
{
    public function __construct(
        public readonly int $userId,       
        public readonly string $firstName  
    ) {}
}

// ❌ MAUVAIS
class UserRecord extends AbstractRecord
{
    public function __construct(
        public readonly int $userId,       // camelCase dans Record
        public readonly string $firstName  // camelCase dans Record
    ) {}
}
```

### 10.2. Toujours utiliser les types

```php
// ✅ BON - Types explicites
public function __construct(
    public readonly int $user_id,
    public readonly string $first_name
) {}

// ❌ MAUVAIS - Pas de type
public function __construct(
    public readonly $user_id,
    public readonly $first_name
) {}
```

### 10.3. Définir des valeurs par défaut quand pertinent

```php
// ✅ BON - Valeurs par défaut
public function __construct(
    public readonly string $user_name,
    public readonly string $country = 'FR',
    public readonly bool $is_active = true
) {}
```

### 10.4. Injecter HydrationService

```php
// ✅ BON - Injection de dépendance
final class UserService
{
    public function __construct(
        private readonly HydrationService $hydration
    ) {}
    
    public function createUser(array $data): UserData
    {
        $record = $this->hydration->hydrate(UserRecord::class, $data);
        return $this->hydration->hydrate(UserData::class, $record->toArray());
    }
}
```

### 10.5. Gérer les erreurs

```php
try {
    $user = $this->hydration->hydrate(UserRecord::class, $malformedData);
} catch (RuntimeException $e) {
    logger()->error('Hydration failed', [
        'error' => $e->getMessage(),
        'data' => $malformedData
    ]);
    
    throw new ValidationException('Invalid user data');
}
```

---

## 11. Récapitulatif

### 11.1. Points clés

| Concept | Description |
|---------|-------------|
| **Objectif** | Hydratation automatique d'objets depuis sources variées |
| **Normalisation** | Utilise DataObject pour normaliser les clés |
| **Récursivité** | Hydrate automatiquement les objets Transformable |
| **Types** | Gère scalaires, enums, unions, collections |
| **Nullabilité** | Distingue null explicite de valeur absente |
| **Defaults** | Utilise les valeurs par défaut du constructeur |

### 11.2. Conventions de casse

| Type | Convention | Exemple |
|------|------------|---------|
| **Record** | `snake_case` | `$user_id`, `$first_name` |
| **Data** | `camelCase` | `$userId`, `$firstName` |
| **Value Object** | `camelCase` | `$emailAddress` |
| **Enum** | `SCREAMING_SNAKE_CASE` | `UserRole::ADMIN` |

### 11.3. Méthodes de HydrationService

| Méthode | Utilisation |
|---------|-------------|
| `hydrate(string $class, mixed $source)` | Hydrate depuis array, object |
| `hydrateFromJson(string $class, string $json)` | Hydrate depuis JSON |
| `collect(iterable $sources, string $class)` | Hydrate une collection |
| `collectFromJson(string $json, string $class)` | Hydrate collection depuis JSON |

### 11.4. Types supportés

| Type PHP | Support |
|----------|---------|
| `int` | ✅ Conversion auto |
| `float` | ✅ Conversion auto |
| `string` | ✅ Conversion auto |
| `bool` | ✅ Conversion auto |
| `array` | ✅ (reste array) |
| `Enum` | ✅ via tryFrom() |
| `Union types` | ✅ (essaie chaque type) |
| `Transformable` | ✅ Hydratation récursive |
| `TypedCollection` | ✅ Hydratation collection |

### 11.5. Exemple complet avec conventions

```php
use AndyDefer\DomainStructures\Services\HydrationService;
use AndyDefer\DomainStructures\Traits\Hydratable;

// 1. Record (snake_case)
class UserRecord extends AbstractRecord
{
    use Hydratable;
    
    public function __construct(
        public readonly int $user_id,
        public readonly string $first_name,
        public readonly string $last_name,
        public readonly string $email_address,
        public readonly ?string $phone_number = null
    ) {}
}

// 2. Data (camelCase)
class UserData extends AbstractData
{
    use Hydratable;
    
    public function __construct(
        public readonly int $userId,
        public readonly string $firstName,
        public readonly string $lastName,
        public readonly string $email,
        public readonly ?string $phoneNumber = null
    ) {}
}

// 3. Hydratation
$hydration = new HydrationService();

// Source externe (snake_case pour l'API)
$source = [
    'user_id' => 123,
    'first_name' => 'John',
    'last_name' => 'Doe',
    'email_address' => 'john@example.com'
];

// Record interne
$record = $hydration->hydrate(UserRecord::class, $source);

// Data pour réponse API
$data = $hydration->hydrate(UserData::class, $record->toArray());

echo $data->userId;    // 123
echo $data->firstName; // John
```