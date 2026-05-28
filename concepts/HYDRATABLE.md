# Trait Hydratable - Documentation Complète

## Table des matières

1. [Définition et concepts](#1-définition-et-concepts)
2. [Pourquoi Hydratable ?](#2-pourquoi-hydratable-)
3. [Fonctionnement général](#3-fonctionnement-général)
4. [Méthodes disponibles](#4-méthodes-disponibles)
5. [Processus d'hydratation détaillé](#5-processus-dhydratation-détaillé)
6. [Gestion des types](#6-gestion-des-types)
7. [Gestion des valeurs absentes et null](#7-gestion-des-valeurs-absentes-et-null)
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

class UserRecord extends AbstractRecord
{
    use Hydratable;
    
    public function __construct(
        public readonly int $id,
        public readonly string $firstName,
        public readonly string $lastName,
        public readonly ?string $email = null
    ) {}
}

// Hydratation automatique
$user = UserRecord::from([
    'id' => 123,
    'first_name' => 'John',  // snake_case accepté
    'last_name' => 'Doe'
]);

// Résultat : objet UserRecord avec propriétés remplies
echo $user->firstName;  // "John"
```

### 1.2. Principes fondamentaux

| Principe | Description |
|----------|-------------|
| **Analyse réflexive** | Examine le constructeur pour connaître les paramètres attendus |
| **Normalisation des clés** | Utilise DataObject pour un accès flexible (camelCase/snake_case) |
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
        public readonly int $id,
        public readonly string $firstName,
        public readonly string $lastName
    ) {}
    
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? 0,
            firstName: $data['first_name'] ?? $data['firstName'] ?? '',
            lastName: $data['last_name'] ?? $data['lastName'] ?? ''
        );
    }
    
    public static function fromJson(string $json): self
    {
        $data = json_decode($json, true);
        return self::fromArray($data);
    }
}

// ✅ AVEC Hydratable - Automatique et standardisé
class UserRecord extends AbstractRecord
{
    use Hydratable;
    
    public function __construct(
        public readonly int $id,
        public readonly string $firstName,
        public readonly string $lastName
    ) {}
}

// Une seule méthode pour toutes les sources
$user = UserRecord::from($array);
$user = UserRecord::fromJson($json);
$user = UserRecord::from($object);
```

### 2.2. Ce que Hydratable résout

| Problème | Solution |
|----------|----------|
| Mapping manuel des clés | Normalisation automatique via DataObject |
| Conversion de types | Conversion automatique (int, float, string, bool) |
| Gestion des enums | Conversion automatique via tryFrom() |
| Objets imbriqués | Hydratation récursive des Transformable |
| Valeurs par défaut | Utilisation des defaults du constructeur |
| Sources multiples | Interface unifiée (array, object, JSON) |
| Code répétitif | Une seule méthode from() pour tout |

---

## 3. Fonctionnement général

### 3.1. Architecture

```
Source (array, object, JSON)
    ↓
DataObject::from() → Normalisation des clés
    ↓
Analyse du constructeur via Reflection
    ↓
Pour chaque paramètre :
    ↓
Récupération de la valeur dans DataObject
    ↓
Conversion du type (si nécessaire)
    ↓
Gestion des cas (absent, null, default)
    ↓
Construction de l'objet
```

### 3.2. Flux de décision

```php
// Pour chaque paramètre du constructeur
$rawValue = getValueFromDataObject($paramName);
$isAbsent = ($rawValue === VALUE_ABSENT);

if ($isAbsent && $parameter->isDefaultValueAvailable()) {
    // Cas 1: Valeur absente mais défaut disponible
    $parameters[] = $parameter->getDefaultValue();
} 
elseif ($value === null && $parameter->allowsNull()) {
    // Cas 2: null explicite autorisé
    $parameters[] = null;
}
elseif ($value !== null) {
    // Cas 3: Valeur normale
    $parameters[] = $value;
}
else {
    // Cas 4: Valeur requise manquante → Exception
    throw new RuntimeException(...);
}
```

---

## 4. Méthodes disponibles

### 4.1. `from(mixed $source): static`

Crée une instance à partir de n'importe quelle source.

**Sources acceptées :**
- `array` : Tableau associatif
- `object` : Objet standard ou DataObject
- `DataObject` : Déjà normalisé
- `string` : (à éviter, utilisez fromJson pour JSON)

```php
// Depuis un tableau
$user = UserRecord::from(['id' => 123, 'first_name' => 'John']);

// Depuis un objet
$obj = (object) ['id' => 123, 'first_name' => 'John'];
$user = UserRecord::from($obj);

// Depuis un DataObject
$dataObject = DataObject::from(['id' => 123, 'first_name' => 'John']);
$user = UserRecord::from($dataObject);
```

### 4.2. `fromJson(string $json): static`

Crée une instance à partir d'une chaîne JSON.

```php
$json = '{"id": 123, "first_name": "John"}';
$user = UserRecord::fromJson($json);

// Gère les erreurs JSON
try {
    $user = UserRecord::fromJson('invalid json');
} catch (RuntimeException $e) {
    // Invalid JSON: Syntax error
}
```

### 4.3. `collect(iterable $sources, string $collectionClass = TypedCollection::class): AbstractTypedCollection`

Hydrate une collection d'objets.

```php
$sources = [
    ['id' => 1, 'first_name' => 'John'],
    ['id' => 2, 'first_name' => 'Jane'],
    ['id' => 3, 'first_name' => 'Bob']
];

// Collection standard
$users = UserRecord::collect($sources);

// Collection personnalisée
$users = UserRecord::collect($sources, UserRecordCollection::class);

// Depuis JSON
$users = UserRecord::collect([
    '{"id":1,"first_name":"John"}',
    '{"id":2,"first_name":"Jane"}'
]);
```

---

## 5. Processus d'hydratation détaillé

### 5.1. Étape 1: Normalisation de la source

```php
// La source est normalisée en DataObject
$dataObject = DataObject::from($source);

// DataObject permet :
// - Accès indifférent camelCase/snake_case
// - Conversion récursive des tableaux imbriqués
```

### 5.2. Étape 2: Analyse du constructeur

```php
$reflection = new ReflectionClass(static::class);
$constructor = $reflection->getConstructor();

// Récupération des paramètres
foreach ($constructor->getParameters() as $parameter) {
    $paramName = $parameter->getName();     // "firstName"
    $paramType = $parameter->getType();     // string
    $hasDefault = $parameter->isDefaultValueAvailable();
    $allowsNull = $parameter->allowsNull();
}
```

### 5.3. Étape 3: Récupération de la valeur

```php
// Recherche dans DataObject
private static function getValueFromDataObject(DataObject $dataObject, string $paramName): mixed
{
    // DataObject trouve automatiquement 'first_name' ou 'firstName'
    if ($dataObject->has($paramName)) {
        return $dataObject->get($paramName);
    }
    
    return self::VALUE_ABSENT;  // Sentinelle
}
```

### 5.4. Étape 4: Conversion du type

```php
// Conversion automatique selon le type attendu
private static function convertToType(mixed $rawValue, ?ReflectionType $paramType, string $paramName): mixed
{
    // Déjà du bon type ?
    if ($rawValue instanceof $typeName) {
        return $rawValue;
    }
    
    // Scalaires
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
}
```

### 5.5. Étape 5: Construction

```php
// Appel du constructeur avec tous les paramètres résolus
return new static(...$parameters);
```

---

## 6. Gestion des types

### 6.1. Types scalaires

```php
class ProductRecord extends AbstractRecord
{
    use Hydratable;
    
    public function __construct(
        public readonly int $id,           // int
        public readonly string $name,      // string
        public readonly float $price,      // float
        public readonly bool $active       // bool
    ) {}
}

// Hydratation
$product = ProductRecord::from([
    'id' => '123',        // string → int
    'name' => 'Laptop',
    'price' => '99.99',   // string → float
    'active' => 'true'    // string → bool
]);
```

### 6.2. Types unions

```php
class ConfigRecord extends AbstractRecord
{
    use Hydratable;
    
    public function __construct(
        public readonly int|float $value,  // Union type
        public readonly string|null $description  // Nullable
    ) {}
}

// Accepte int ou float
$config = ConfigRecord::from(['value' => 42]);      // int
$config = ConfigRecord::from(['value' => 42.5]);    // float

// Le système essaie chaque type de l'union
// int échoue avec 42.5? → essaie float → OK
```

### 6.3. Enums

```php
enum Status: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
}

class OrderRecord extends AbstractRecord
{
    use Hydratable;
    
    public function __construct(
        public readonly Status $status
    ) {}
}

// Hydratation automatique
$order = OrderRecord::from(['status' => 'active']);
echo $order->status->value;  // 'active'
echo $order->status->name;   // 'ACTIVE'
```

### 6.4. Objets Transformable

```php
class EmailValueObject extends AbstractValueObject implements Transformable
{
    public function __construct(private string $email) {}
    
    public static function from(mixed $source): static
    {
        return new static((string) $source);
    }
}

class UserRecord extends AbstractRecord
{
    use Hydratable;
    
    public function __construct(
        public readonly string $name,
        public readonly EmailValueObject $email  // Transformable
    ) {}
}

// Hydratation récursive
$user = UserRecord::from([
    'name' => 'John',
    'email' => 'john@example.com'  // string → EmailValueObject
]);

echo get_class($user->email);  // EmailValueObject
```

### 6.5. Collections

```php
class ProductRecord extends AbstractRecord
{
    use Hydratable;
    
    public function __construct(
        public readonly string $name,
        public readonly StringTypedCollection $tags  // Collection
    ) {}
}

// Hydratation
$product = ProductRecord::from([
    'name' => 'Laptop',
    'tags' => ['electronics', 'premium']  // array → StringTypedCollection
]);

echo get_class($product->tags);  // StringTypedCollection
```

---

## 7. Gestion des valeurs absentes et null

### 7.1. Quatre cas de figure

```php
class UserRecord extends AbstractRecord
{
    use Hydratable;
    
    public function __construct(
        public readonly string $name,           // Requis
        public readonly string $email,          // Requis
        public readonly ?string $phone,         // Nullable
        public readonly string $country = 'FR'  // Default
    ) {}
}
```

#### Cas 1: Valeur absente avec défaut

```php
$user = UserRecord::from(['name' => 'John', 'email' => 'john@ex.com']);
// $country → 'FR' (valeur par défaut)
```

#### Cas 2: null explicite autorisé

```php
$user = UserRecord::from([
    'name' => 'John',
    'email' => 'john@ex.com',
    'phone' => null  // null explicite
]);
// $phone → null
```

#### Cas 3: Valeur normale

```php
$user = UserRecord::from([
    'name' => 'John',
    'email' => 'john@ex.com',
    'country' => 'BE'  // Valeur fournie
]);
// $country → 'BE' (surcharge le default)
```

#### Cas 4: Requis manquant → Exception

```php
// ❌ Exception: Missing required parameter "email"
$user = UserRecord::from(['name' => 'John']);
```

### 7.2. Distinction null vs absent

```php
// Source avec null explicite
$data = ['name' => 'John', 'email' => null];
$user = UserRecord::from($data);
// $email → null (OK car nullable)

// Source sans la clé
$data = ['name' => 'John'];
$user = UserRecord::from($data);
// ❌ Exception car email requis et pas de default
```

---

## 8. Cas d'utilisation

### 8.1. API Response

```php
class ApiService
{
    public function getUser(int $id): UserRecord
    {
        $response = $this->httpClient->get("/users/{$id}");
        
        // Hydratation directe depuis JSON
        return UserRecord::fromJson($response->getBody());
    }
}
```

### 8.2. Formulaire

```php
class UserController
{
    public function store(Request $request): JsonResponse
    {
        // Hydratation depuis les données du formulaire
        $user = UserRecord::from($request->all());
        
        // Validation automatique via le constructeur
        // Les types sont déjà vérifiés
        
        $this->userRepository->save($user);
        
        return response()->json($user);
    }
}
```

### 8.3. Import CSV

```php
class CsvImporter
{
    public function import(string $filePath): ProductCollection
    {
        $rows = array_map('str_getcsv', file($filePath));
        $headers = array_shift($rows);
        
        $products = [];
        foreach ($rows as $row) {
            $data = array_combine($headers, $row);
            // Hydratation ligne par ligne
            $products[] = ProductRecord::from($data);
        }
        
        return ProductRecord::collect($products);
    }
}
```

### 8.4. Base de données

```php
class UserRepository
{
    public function find(int $id): ?UserRecord
    {
        $row = $this->db->fetchAssoc(
            'SELECT id, first_name, last_name, email FROM users WHERE id = ?',
            [$id]
        );
        
        return $row ? UserRecord::from($row) : null;
    }
    
    public function findAll(): UserCollection
    {
        $rows = $this->db->fetchAllAssoc('SELECT * FROM users');
        
        return UserRecord::collect($rows, UserCollection::class);
    }
}
```

### 8.5. Webhook

```php
class WebhookController
{
    public function handle(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        
        // Hydratation selon le type d'événement
        $event = match ($request->header('X-Event-Type')) {
            'user.created' => UserCreatedEvent::fromJson($payload),
            'order.paid' => OrderPaidEvent::fromJson($payload),
            default => UnknownEvent::fromJson($payload)
        };
        
        $this->dispatcher->dispatch($event);
        
        return response()->json(['status' => 'ok']);
    }
}
```

---

## 9. Exemples concrets

### 9.1. Structure complexe

```php
// Définitions
class AddressValueObject extends AbstractValueObject implements Transformable
{
    public function __construct(
        private string $street,
        private string $city,
        private string $country
    ) {}
    
    public static function from(mixed $source): static
    {
        if ($source instanceof DataObject) {
            return new static(
                street: $source->street,
                city: $source->city,
                country: $source->country
            );
        }
        // ...
    }
}

class OrderItemRecord extends AbstractRecord
{
    use Hydratable;
    
    public function __construct(
        public readonly int $productId,
        public readonly int $quantity,
        public readonly float $price
    ) {}
}

class OrderRecord extends AbstractRecord
{
    use Hydratable;
    
    public function __construct(
        public readonly int $id,
        public readonly AddressValueObject $shippingAddress,
        public readonly TypedCollection $items,  // OrderItemRecord[]
        public readonly OrderStatus $status,
        public readonly ?string $notes = null
    ) {}
}

// Source JSON
$json = '{
    "id": 12345,
    "shipping_address": {
        "street": "123 Main St",
        "city": "Paris",
        "country": "France"
    },
    "items": [
        {"product_id": 1, "quantity": 2, "price": 49.99},
        {"product_id": 2, "quantity": 1, "price": 99.99}
    ],
    "status": "paid",
    "notes": "Gift wrap please"
}';

// Hydratation complète
$order = OrderRecord::fromJson($json);

// Vérification
echo $order->id;  // 12345
echo $order->shippingAddress->city;  // "Paris"
echo $order->items->count();  // 2
echo $order->status->value;  // "paid"
```

### 9.2. Validation des types

```php
class ProductRecord extends AbstractRecord
{
    use Hydratable;
    
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly float $price,
        public readonly bool $inStock
    ) {}
}

// Données avec types incorrects
$data = [
    'id' => 'abc',        // String au lieu de int
    'name' => 'Laptop',
    'price' => '99.99',   // String au lieu de float
    'inStock' => 'yes'    // String au lieu de bool
];

// ❌ Exception: Cannot convert value to int for parameter $id
try {
    $product = ProductRecord::from($data);
} catch (RuntimeException $e) {
    echo $e->getMessage();
}
```

### 9.3. Union types flexibles

```php
class ConfigRecord extends AbstractRecord
{
    use Hydratable;
    
    public function __construct(
        public readonly int|string $value  // Accepte int ou string
    ) {}
}

$config1 = ConfigRecord::from(['value' => 42]);     // int
$config2 = ConfigRecord::from(['value' => '42']);   // string
$config3 = ConfigRecord::from(['value' => 'text']); // string

// Les deux fonctionnent !
```

---

## 10. Bonnes pratiques

### 10.1. Toujours utiliser les types

```php
// ✅ BON - Types explicites
public function __construct(
    public readonly int $id,
    public readonly string $name
) {}

// ❌ MAUVAIS - Pas de type
public function __construct(
    public readonly $id,
    public readonly $name
) {}
```

### 10.2. Définir des valeurs par défaut quand pertinent

```php
// ✅ BON - Valeurs par défaut
public function __construct(
    public readonly string $name,
    public readonly string $country = 'FR',
    public readonly bool $active = true
) {}

// ❌ MAUVAIS - Propiétés requises qui pourraient être optionnelles
public function __construct(
    public readonly string $name,
    public readonly ?string $middleName  // Devrait être nullable avec default null
) {}
```

### 10.3. Utiliser les bonnes méthodes

```php
// ✅ Pour JSON → fromJson()
$user = UserRecord::fromJson($jsonString);

// ✅ Pour tableau → from()
$user = UserRecord::from($array);

// ✅ Pour collection → collect()
$users = UserRecord::collect($sources);

// ❌ Éviter de passer du JSON à from()
$user = UserRecord::from($jsonString);  // Traite JSON comme string
```

### 10.4. Gérer les erreurs

```php
try {
    $user = UserRecord::from($malformedData);
} catch (RuntimeException $e) {
    // Log l'erreur
    logger()->error('Hydration failed', [
        'error' => $e->getMessage(),
        'data' => $malformedData
    ]);
    
    // Retourner une réponse appropriée
    throw new ValidationException('Invalid user data');
}
```

### 10.5. Valider après hydratation

```php
class UserRecord extends AbstractRecord
{
    use Hydratable;
    
    public function __construct(
        public readonly string $email
    ) {
        // Validation post-hydratation
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Invalid email format');
        }
    }
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

### 11.2. Méthodes principales

| Méthode | Utilisation |
|---------|-------------|
| `from(mixed $source)` | Hydrate depuis array, object, DataObject |
| `fromJson(string $json)` | Hydrate depuis JSON |
| `collect(iterable $sources, string $class)` | Hydrate une collection |

### 11.3. Types supportés

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

### 11.4. Gestion des cas

| Cas | Comportement |
|-----|--------------|
| Valeur présente | Convertie et assignée |
| Valeur absente + défaut | Utilise la valeur par défaut |
| null explicite + nullable | Assigne null |
| null explicite + non-nullable | ❌ Exception |
| Valeur absente + requis | ❌ Exception |
| Conversion impossible | ❌ Exception |

### 11.5. Flowchart

```
Source (array, object, JSON)
    ↓
DataObject::from() (normalisation)
    ↓
Analyse du constructeur
    ↓
Pour chaque paramètre :
    ↓
    La clé existe dans DataObject ?
    ├─ OUI → Valeur trouvée
    │   ↓
    │   Conversion vers le type attendu
    │   ↓
    │   OK ? → Assigner
    │   ↓
    │   NON → Exception
    │
    └─ NON → Valeur absente
        ↓
        Default disponible ?
        ├─ OUI → Utiliser default
        └─ NON → Exception
    ↓
Construction de l'objet
```

### 11.6. Erreurs possibles

| Exception | Cause |
|-----------|-------|
| `RuntimeException: must have a constructor` | Classe sans constructeur |
| `Missing required parameter` | Paramètre requis absent |
| `Cannot convert value to int/float/string/bool` | Conversion impossible |
| `Invalid value for enum` | Valeur ne correspond à aucun cas |
| `no matching union type` | Aucun type de l'union ne correspond |
| `does not implement Transformable` | Type non supporté |

---

## 12. Annexe : Équivalences

### 12.1. Source → Paramètre

| Source (array) | Paramètre constructeur | Résultat |
|----------------|----------------------|----------|
| `'user_id' => 123` | `int $userId` | ✅ 123 |
| `'first_name' => 'John'` | `string $firstName` | ✅ 'John' |
| `'active' => 'true'` | `bool $active` | ✅ true |
| `'email' => null` | `?string $email` | ✅ null |
| `'tags' => ['a','b']` | `StringCollection $tags` | ✅ Collection |
| `'user' => [...]` | `UserRecord $user` | ✅ Hydratation récursive |

### 12.2. Méthodes recommandées

```php
// ✅ RECOMMANDÉ
$user = UserRecord::from($array);
$user = UserRecord::fromJson($json);
$users = UserRecord::collect($sources);

// ⚠️ ACCEPTABLE (mais moins clair)
$user = UserRecord::from($json);  // Traite comme string, pas comme JSON

// ❌ À ÉVITER
$user = new UserRecord();  // Ne fait pas l'hydratation
```