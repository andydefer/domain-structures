# DataObject & StrictDataObject - Documentation Officielle

## Table des matières

1. [Présentation](#1-présentation)
2. [DataObject - Normalisation camelCase](#2-dataobject---normalisation-camelcase)
3. [StrictDataObject - Préservation de la casse](#3-strictdataobject---préservation-de-la-casse)
4. [Points communs](#4-points-communs)
5. [Conversion des tableaux imbriqués](#5-conversion-des-tableaux-imbriqués)
6. [Opérations de transformation](#6-opérations-de-transformation)
7. [Rôle dans les réponses API et JSON](#7-rôle-dans-les-réponses-api-et-json)
8. [API complète](#8-api-complète)
9. [Exemples concrets avec HydrationService](#9-exemples-concrets-avec-hydrationservice)
10. [Quand utiliser DataObject vs StrictDataObject](#10-quand-utiliser-dataobject-vs-strictdataobject)
11. [Récapitulatif](#11-récapitulatif)

---

## 1. Présentation

`DataObject` et `StrictDataObject` sont des classes utilitaires qui **normalisent et unifient l'accès aux données** provenant de sources variées (tableaux, objets, JSON). Leur différence principale réside dans la **normalisation des clés**.

| Classe | Normalisation | Cas d'usage |
|--------|---------------|-------------|
| `DataObject` | Convertit snake_case → camelCase | APIs externes, bases de données (convention standard) |
| `StrictDataObject` | Préserve la casse originale | Données avec clés en PascalCase, UPPER_CASE, ou mixtes |

```php
use AndyDefer\DomainStructures\Utils\DataObject;
use AndyDefer\DomainStructures\Utils\StrictDataObject;
use AndyDefer\DomainStructures\Services\HydrationService;

$hydration = new HydrationService();

// Source externe (snake_case)
$apiData = [
    'id' => 123,
    'first_name' => 'John',
    'last_name' => 'Doe'
];

// DataObject : normalise en camelCase
$normalized = new DataObject($apiData);
echo $normalized->firstName;  // "John" (camelCase)
echo $normalized->first_name; // "John" (snake_case aussi accepté)

// Hydratation avec HydrationService
$user = $hydration->hydrate(UserRecord::class, $normalized->toArray());

// StrictDataObject : préserve la casse
$strict = new StrictDataObject($apiData);
echo $strict->first_name;     // "John"
echo $strict->firstName;      // ❌ null (clé non existante)
```

---

## 2. DataObject - Normalisation camelCase

### 2.1. Principe

`DataObject` **convertit automatiquement** les clés snake_case en camelCase tout en permettant un accès indifférent.

```php
use AndyDefer\DomainStructures\Utils\DataObject;

$data = new DataObject([
    'user_id' => 123,
    'first_name' => 'John',
    'last_name' => 'Doe',
    'email_verified_at' => '2024-01-01'
]);

// Stockage interne en camelCase
$data->toArray();  
// ['userId' => 123, 'firstName' => 'John', 'lastName' => 'Doe', 'emailVerifiedAt' => '2024-01-01']

// Accès possible dans les DEUX formats
echo $data->userId;           // 123 (camelCase)
echo $data->user_id;          // 123 (snake_case → camelCase)
echo $data->firstName;        // "John"
echo $data->first_name;       // "John" (snake_case → camelCase)
echo $data->emailVerifiedAt;  // "2024-01-01"
echo $data->email_verified_at; // "2024-01-01"
```

### 2.2. Conversion snake_case → camelCase

```php
// Règles de conversion
$data = new DataObject([
    'id' => 1,                    // id → id
    'user_id' => 2,               // user_id → userId
    'first_name' => 'John',       // first_name → firstName
    'email_verified_at' => 'now', // email_verified_at → emailVerifiedAt
    'HTTP_STATUS' => 200,         // HTTP_STATUS → httpStatus
    'XML_parser' => 'libxml'      // XML_parser → xmlParser
]);
```

### 2.3. Cas d'usage privilégiés

```php
use AndyDefer\DomainStructures\Services\HydrationService;

$hydration = new HydrationService();

// ✅ APIs externes (convention snake_case)
$apiResponse = [
    'user_id' => 123,
    'first_name' => 'John',
    'created_at' => '2024-01-01'
];

$data = new DataObject($apiResponse);
// Utilisation fluide en camelCase
$userId = $data->userId;
$firstName = $data->firstName;

// Hydratation directe
$user = $hydration->hydrate(UserRecord::class, $data->toArray());
```

---

## 3. StrictDataObject - Préservation de la casse

### 3.1. Principe

`StrictDataObject` **préserve exactement** les clés telles qu'elles sont fournies.

```php
use AndyDefer\DomainStructures\Utils\StrictDataObject;

$data = new StrictDataObject([
    'user_id' => 123,
    'firstName' => 'John',
    'UserName' => 'johndoe',
    'UPPER_CASE_KEY' => 'value',
    'PascalCaseKey' => 'pascal'
]);

// Stockage interne identique à l'original
$data->toArray();  
// ['user_id' => 123, 'firstName' => 'John', 'UserName' => 'johndoe', 'UPPER_CASE_KEY' => 'value', 'PascalCaseKey' => 'pascal']

// Accès STRICT - uniquement la clé exacte
echo $data->user_id;        // 123
echo $data->firstName;      // "John"
echo $data->UserName;       // "johndoe"
echo $data->UPPER_CASE_KEY; // "value"

// ❌ Pas d'accès normalisé
echo $data->UserId;         // null (n'existe pas)
echo $data->userName;       // null (n'existe pas)
```

### 3.2. Cas d'usage privilégiés

```php
use AndyDefer\DomainStructures\Services\HydrationService;

$hydration = new HydrationService();

// ✅ Données avec clés en PascalCase
$pascalCaseData = [
    'UserId' => 123,
    'FirstName' => 'John',
    'CreatedAt' => '2024-01-01'
];

$data = new StrictDataObject($pascalCaseData);
echo $data->UserId;     // 123
echo $data->FirstName;  // "John"

// ✅ Données avec clés en UPPER_CASE
$envData = [
    'DB_HOST' => 'localhost',
    'DB_PORT' => 3306,
    'APP_ENV' => 'production'
];

$config = new StrictDataObject($envData);
echo $config->DB_HOST;  // "localhost"
echo $config->DB_PORT;  // 3306

// ✅ Données avec casse mixte à préserver
$mixedCaseData = [
    'userID' => 123,
    'User_Name' => 'john_doe',
    'emailAddress' => 'john@example.com'
];

$data = new StrictDataObject($mixedCaseData);
// Toutes les clés restent exactement comme fournies
```

---

## 4. Points communs

### 4.1. Héritage commun

Les deux classes héritent de `AbstractDataObject` et partagent les mêmes fonctionnalités de base.

```php
// Toutes deux supportent :
// - Accès par propriété (->)
// - Accès par tableau ([])
// - Méthodes with(), merge(), without()
// - Conversion récursive des tableaux imbriqués
```

### 4.2. Création d'instance

```php
use AndyDefer\DomainStructures\Utils\DataObject;
use AndyDefer\DomainStructures\Utils\StrictDataObject;

// À partir d'un tableau
$data1 = new DataObject(['key' => 'value']);
$data2 = new StrictDataObject(['key' => 'value']);

// À partir d'un objet existant
$data3 = new DataObject($existingObject);
$data4 = new StrictDataObject($existingObject);

// À partir de JSON (via utilitaire)
$json = '{"key":"value"}';
$data5 = DataObject::fromJson($json);
$data6 = StrictDataObject::fromJson($json);
```

### 4.3. Immutabilité

Les deux classes sont **immutables** : les modifications créent de nouvelles instances.

```php
$original = new DataObject(['name' => 'John', 'age' => 30]);

$modified = $original->with('age', 31);
$merged = $original->merge(['email' => 'john@example.com']);
$reduced = $original->without('age');

// L'original reste inchangé
echo $original->age;  // 30
echo $modified->age;  // 31
```

---

## 5. Conversion des tableaux imbriqués

Les deux classes convertissent récursivement les tableaux associatifs imbriqués en instances de la même classe.

```php
use AndyDefer\DomainStructures\Utils\DataObject;
use AndyDefer\DomainStructures\Utils\StrictDataObject;

// DataObject convertit récursivement en DataObject
$data = new DataObject([
    'user' => [
        'profile' => [
            'first_name' => 'John',
            'last_name' => 'Doe'
        ]
    ],
    'tags' => ['premium', 'vip']  // Liste indexée → reste array
]);

echo $data->user->profile->firstName;  // "John" (camelCase)
$data->user instanceof DataObject;     // true

// StrictDataObject convertit récursivement en StrictDataObject
$strict = new StrictDataObject([
    'user' => [
        'profile' => [
            'first_name' => 'John',
            'last_name' => 'Doe'
        ]
    ]
]);

echo $strict->user->profile->first_name;  // "John" (casse préservée)
$strict->user instanceof StrictDataObject; // true
```

---

## 6. Opérations de transformation

Les deux classes fournissent les mêmes méthodes pour créer de nouvelles instances modifiées.

```php
$user = new DataObject(['name' => 'John', 'age' => 30]);

// with() - Ajouter/Modifier une propriété
$updated = $user->with('age', 31);
$withEmail = $user->with('email', 'john@example.com');

// merge() - Fusionner avec un tableau
$merged = $user->merge([
    'email' => 'john@example.com',
    'age' => 31
]);

// without() - Supprimer des propriétés
$reduced = $user->without('email', 'age');
```

---

## 7. Rôle dans les réponses API et JSON

### 7.1. Hydratation depuis JSON avec HydrationService

```php
use AndyDefer\DomainStructures\Services\HydrationService;
use AndyDefer\DomainStructures\Utils\DataObject;
use AndyDefer\DomainStructures\Utils\StrictDataObject;

$hydration = new HydrationService();

// Réponse API (JSON) avec snake_case
$jsonResponse = '{
    "id": 123,
    "first_name": "John",
    "email_verified_at": "2024-01-01T12:00:00+00:00"
}';

// Méthode 1 : hydrateFromJson() (RECOMMANDÉ)
$user = $hydration->hydrateFromJson(UserRecord::class, $jsonResponse);

// Méthode 2 : Décodage manuel + hydrate()
$data = json_decode($jsonResponse, true);
$user = $hydration->hydrate(UserRecord::class, $data);

// Méthode 3 : Via DataObject (normalisation camelCase)
$dataObject = new DataObject(json_decode($jsonResponse, true));
$user = $hydration->hydrate(UserRecord::class, $dataObject->toArray());

// Méthode 4 : Via StrictDataObject (casse préservée)
$strictObject = new StrictDataObject(json_decode($jsonResponse, true));
$user = $hydration->hydrate(UserRecord::class, $strictObject->toArray());
```

### 7.2. Collection depuis JSON

```php
use AndyDefer\DomainStructures\Services\HydrationService;

$hydration = new HydrationService();

$jsonResponse = '[
    {"id": 1, "name": "Product A", "price": 99.99},
    {"id": 2, "name": "Product B", "price": 149.99}
]';

$products = $hydration->collectFromJson($jsonResponse, ProductCollection::class);
```

---

## 8. API complète

### 8.1. Constructeur

```php
/**
 * @param array<string|int, mixed>|object $data
 */
public function __construct(array|object $data = [])
```

### 8.2. Méthodes statiques

```php
// Crée une instance depuis JSON
public static function fromJson(string $json): static
```

### 8.3. Méthodes d'instance

```php
// Transformation (créent de nouvelles instances)
public function with(string $key, mixed $value): static
public function merge(array $data): static
public function without(string ...$keys): static

// Lecture
public function get(string $name, mixed $default = null): mixed
public function has(string $name): bool
public function toArray(): array

// Magic methods
public function __get(string $name): mixed
public function __isset(string $name): bool
public function __toString(): string

// ArrayAccess (lecture seule)
public function offsetExists(mixed $offset): bool
public function offsetGet(mixed $offset): mixed
```

---

## 9. Exemples concrets avec HydrationService

### 9.1. API externe en snake_case

```php
use AndyDefer\DomainStructures\Services\HydrationService;
use AndyDefer\DomainStructures\Utils\DataObject;

class UserController
{
    private HydrationService $hydration;
    
    public function __construct()
    {
        $this->hydration = new HydrationService();
    }
    
    public function fetchUser(int $id): UserRecord
    {
        // API externe renvoie du snake_case
        $response = $this->httpClient->get("/users/{$id}");
        $data = json_decode($response, true);
        
        // DataObject normalise en camelCase pour l'hydratation
        $dataObject = new DataObject($data);
        
        return $this->hydration->hydrate(UserRecord::class, $dataObject->toArray());
    }
    
    public function fetchUsers(): UserCollection
    {
        $response = $this->httpClient->get('/users');
        return $this->hydration->collectFromJson($response, UserCollection::class);
    }
}
```

### 9.2. Configuration en UPPER_CASE

```php
use AndyDefer\DomainStructures\Services\HydrationService;
use AndyDefer\DomainStructures\Utils\StrictDataObject;

class DatabaseConfig
{
    private StrictDataObject $config;
    private HydrationService $hydration;
    
    public function __construct(array $config)
    {
        $this->hydration = new HydrationService();
        // Préserve les clés UPPER_CASE
        $this->config = new StrictDataObject($config);
    }
    
    public function getHost(): string
    {
        return $this->config->DB_HOST;
    }
    
    public function getPort(): int
    {
        return $this->config->DB_PORT;
    }
    
    public function toConfigRecord(): DatabaseConfigRecord
    {
        return $this->hydration->hydrate(
            DatabaseConfigRecord::class,
            $this->config->toArray()
        );
    }
}

// Utilisation
$config = new DatabaseConfig(parse_ini_file('.env'));
echo $config->getHost();  // localhost
```

### 9.3. Transformation avant hydratation

```php
use AndyDefer\DomainStructures\Services\HydrationService;
use AndyDefer\DomainStructures\Utils\DataObject;

class DataEnricher
{
    private HydrationService $hydration;
    
    public function __construct()
    {
        $this->hydration = new HydrationService();
    }
    
    public function enrichAndHydrate(array $source, string $targetClass): object
    {
        $dataObject = new DataObject($source);
        
        $enriched = $dataObject
            ->with('id', (int) $dataObject->id)
            ->with('email', strtolower($dataObject->email))
            ->with('full_name', trim($dataObject->first_name . ' ' . $dataObject->last_name))
            ->without('temporary_field');
        
        return $this->hydration->hydrate($targetClass, $enriched->toArray());
    }
    
    public function enrichAndCollect(array $sources, string $collectionClass): AbstractTypedCollection
    {
        $enriched = array_map(function($source) {
            $dataObject = new DataObject($source);
            return $dataObject
                ->with('id', (int) $dataObject->id)
                ->without('temporary_field')
                ->toArray();
        }, $sources);
        
        return $this->hydration->collect($enriched, $collectionClass);
    }
}
```

### 9.4. Webhook avec casse mixte

```php
use AndyDefer\DomainStructures\Services\HydrationService;
use AndyDefer\DomainStructures\Utils\StrictDataObject;

class WebhookHandler
{
    private HydrationService $hydration;
    
    public function __construct()
    {
        $this->hydration = new HydrationService();
    }
    
    public function handle(string $payload): void
    {
        $data = json_decode($payload, true);
        
        // StrictDataObject préserve la casse du webhook
        $webhookData = new StrictDataObject($data);
        
        // Accès avec la casse exacte du webhook
        $eventType = $webhookData->event_type;
        $userId = $webhookData->userId;
        $timestamp = $webhookData->TIMESTAMP;
        
        $event = $this->hydration->hydrate(WebhookEvent::class, $webhookData->toArray());
        $this->processEvent($event);
    }
}
```

### 9.5. Service complet avec HydrationService

```php
use AndyDefer\DomainStructures\Services\HydrationService;
use AndyDefer\DomainStructures\Utils\DataObject;

class ProductService
{
    private HydrationService $hydration;
    
    public function __construct()
    {
        $this->hydration = new HydrationService();
    }
    
    public function createFromApi(array $apiData): ProductRecord
    {
        // Normalisation des données API
        $normalized = new DataObject($apiData);
        
        // Enrichissement
        $enriched = $normalized
            ->with('price', (float) $normalized->price)
            ->with('stock', (int) ($normalized->stock ?? 0))
            ->with('created_at', new DateTimeImmutable()->format('Y-m-d H:i:s'));
        
        // Hydratation
        return $this->hydration->hydrate(ProductRecord::class, $enriched->toArray());
    }
    
    public function importFromJson(string $json): ProductCollection
    {
        return $this->hydration->collectFromJson($json, ProductCollection::class);
    }
    
    public function updateFromJson(int $id, string $json): ProductRecord
    {
        $existing = $this->find($id);
        $data = json_decode($json, true);
        $dataObject = new DataObject($data);
        
        $merged = $existing->toArray();
        $merged = array_merge($merged, $dataObject->toArray());
        
        return $this->hydration->hydrate(ProductRecord::class, $merged);
    }
}
```

---

## 10. Quand utiliser DataObject vs StrictDataObject

### 10.1. Utilisez DataObject quand :

| Situation | Exemple |
|-----------|---------|
| API externes en snake_case | `first_name`, `user_id`, `created_at` |
| Bases de données conventionnelles | `last_login_at`, `is_active` |
| Vous voulez un accès unifié camelCase | `$data->firstName`, `$data->userId` |
| Vous travaillez avec des DTO standards | Données JSON classiques |

```php
use AndyDefer\DomainStructures\Services\HydrationService;
use AndyDefer\DomainStructures\Utils\DataObject;

$hydration = new HydrationService();

// ✅ Bon usage de DataObject
$apiData = ['first_name' => 'John', 'last_name' => 'Doe'];
$data = new DataObject($apiData);
echo $data->firstName;  // "John"

$user = $hydration->hydrate(UserRecord::class, $data->toArray());
```

### 10.2. Utilisez StrictDataObject quand :

| Situation | Exemple |
|-----------|---------|
| Clés en PascalCase | `UserId`, `FirstName`, `CreatedAt` |
| Clés en UPPER_CASE | `DB_HOST`, `API_KEY`, `MAX_RETRIES` |
| Clés avec casse mixte spécifique | `userID`, `XMLparser`, `HTTP_STATUS` |
| Vous devez préserver exactement les clés | Configuration, variables d'environnement |

```php
use AndyDefer\DomainStructures\Services\HydrationService;
use AndyDefer\DomainStructures\Utils\StrictDataObject;

$hydration = new HydrationService();

// ✅ Bon usage de StrictDataObject
$pascalData = ['UserId' => 123, 'FirstName' => 'John'];
$strict = new StrictDataObject($pascalData);
echo $strict->UserId;     // 123
echo $strict->FirstName;  // "John"

$envData = ['DB_HOST' => 'localhost', 'DB_PORT' => 3306];
$config = new StrictDataObject($envData);
echo $config->DB_HOST;  // "localhost"

$configRecord = $hydration->hydrate(DatabaseConfig::class, $config->toArray());
```

### 10.3. Tableau comparatif

| Caractéristique | DataObject | StrictDataObject |
|-----------------|------------|------------------|
| Normalisation camelCase | ✅ Oui | ❌ Non |
| Préserve la casse originale | ❌ Non | ✅ Oui |
| Accès snake_case → camelCase | ✅ Oui | ❌ Non |
| Accès camelCase → camelCase | ✅ Oui | ✅ Oui (si clé exacte) |
| Accès UPPER_CASE | → lowercase | ✅ Exact |
| Idéal pour APIs externes | ✅ Oui | ❌ Non |
| Idéal pour configurations | ❌ Non | ✅ Oui |

---

## 11. Récapitulatif

### 11.1. Ce que DataObject et StrictDataObject font

| Fonctionnalité | DataObject | StrictDataObject |
|----------------|------------|------------------|
| Accès par propriété (->) | ✅ Oui | ✅ Oui |
| Accès par tableau ([]) | ✅ Oui | ✅ Oui |
| Conversion tableaux imbriqués | ✅ Oui (même classe) | ✅ Oui (même classe) |
| Méthodes with/merge/without | ✅ Oui | ✅ Oui |
| Immutabilité | ✅ Oui | ✅ Oui |
| get() avec valeur par défaut | ✅ Oui | ✅ Oui |

### 11.2. Bonnes pratiques avec HydrationService

```php
use AndyDefer\DomainStructures\Services\HydrationService;

$hydration = new HydrationService();

// ✅ RECOMMANDÉ - hydrateFromJson() direct
$user = $hydration->hydrateFromJson(UserRecord::class, $jsonResponse);
$products = $hydration->collectFromJson($jsonResponse, ProductCollection::class);

// ✅ ACCEPTABLE - via DataObject si besoin normalisation
$dataObject = new DataObject(json_decode($jsonResponse, true));
$user = $hydration->hydrate(UserRecord::class, $dataObject->toArray());

// ✅ ACCEPTABLE - via StrictDataObject si préservation casse
$strictObject = new StrictDataObject(json_decode($jsonResponse, true));
$config = $hydration->hydrate(ConfigRecord::class, $strictObject->toArray());

// ✅ Pour les collections
$users = $hydration->collect($dataArray, UserCollection::class);
```

### 11.3. Points clés à retenir

1. **DataObject** = normalisation camelCase → APIs externes
2. **StrictDataObject** = préservation de la casse → configurations, clés spécifiques
3. Les deux sont **immutables** → `with()`, `merge()`, `without()` créent de nouvelles instances
4. Les deux convertissent récursivement les tableaux imbriqués
5. Utilisez **HydrationService** pour l'hydratation des objets métier (items et collections)
6. **Plus d'utilisation de `::from()`** → utilisez `new DataObject()` ou `new StrictDataObject()`

---

## 12. Conclusion

**DataObject** et **StrictDataObject** sont des normalisateurs d'accès aux données avec des stratégies de normalisation différentes.

- **DataObject** : Idéal pour les APIs externes (snake_case → camelCase)
- **StrictDataObject** : Idéal pour les configurations et données avec casse spécifique

Les deux servent de pont entre le monde externe (API, fichiers) et **HydrationService**, sans remplacer les objets métier (`AbstractRecord`, `AbstractValueObject`, etc.).

```php
// Flux complet recommandé
$json = '{"first_name":"John","last_name":"Doe"}';
$hydration = new HydrationService();

// Étape 1 : Normalisation (optionnelle)
$dataObject = new DataObject(json_decode($json, true));

// Étape 2 : Hydratation
$user = $hydration->hydrate(UserRecord::class, $dataObject->toArray());

// Ou directement
$user = $hydration->hydrateFromJson(UserRecord::class, $json);
```
---