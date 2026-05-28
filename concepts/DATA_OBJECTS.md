# DataObject - Documentation Officielle

## Table des matières

1. [Définition et rôle](#1-définition-et-rôle)
2. [Pourquoi DataObject ?](#2-pourquoi-dataobject-)
3. [Normalisation des clés](#3-normalisation-des-clés)
4. [Conversion des tableaux imbriqués](#4-conversion-des-tableaux-imbriqués)
5. [Opérations de transformation](#5-opérations-de-transformation)
6. [Rôle dans Hydratable](#6-rôle-dans-hydratable)
7. [Rôle dans les réponses API et JSON](#7-rôle-dans-les-réponses-api-et-json)
8. [API complète](#8-api-complète)
9. [Exemples concrets](#9-exemples-concrets)
10. [Ce que DataObject n'est PAS](#10-ce-que-dataobject-nest-pas)
11. [Récapitulatif](#11-récapitulatif)

---

## 1. Définition et rôle

`DataObject` est une classe utilitaire qui **normalise et unifie l'accès aux données** provenant de sources variées (tableaux, objets, JSON). Son rôle principal est de servir de **pont entre les sources de données externes** (API, fichiers, bases NoSQL) et le système d'hydratation automatique (`Hydratable`).

```php
use AndyDefer\DomainStructures\Utils\DataObject;

// Source externe (snake_case)
$apiData = [
    'id' => 123,
    'first_name' => 'John',
    'last_name' => 'Doe'
];

// Normalisation
$normalized = DataObject::from($apiData);

// Accès unifié
echo $normalized->id;         // 123
echo $normalized->first_name; // "John" 
echo $normalized->lastName;   // "Doe" (camelCase aussi accepté)
```

---

## 2. Pourquoi DataObject ?

### 2.1. Le problème des sources hétérogènes

```php
// ❌ SANS DataObject - Hydratation manuelle et fragile
class UserRecord extends AbstractRecord
{
    public static function fromApi(array $data): static
    {
        return new static(
            id: $data['id'] ?? 0,
            firstName: $data['first_name'] ?? '',  // mapping manuel
            lastName: $data['last_name'] ?? ''     // répétitif
        );
    }
    
    public static function fromJson(string $json): static
    {
        $data = json_decode($json, true);
        return self::fromApi($data);  // Duplication
    }
}

// ✅ AVEC DataObject - Hydratation automatique
class UserRecord extends AbstractRecord
{
    use Hydratable;  // from() fonctionne automatiquement !
}

// Une seule méthode pour toutes les sources
$user = UserRecord::from($apiData);           // array
$user = UserRecord::fromJson($apiJson);       // string JSON (recommandé)
$user = UserRecord::from($apiObject);         // stdClass
```

### 2.2. Ce que DataObject résout

| Problème | Solution DataObject |
|----------|--------------------|
| Sources multiples (array, object, JSON) | `from()` accepte tout type |
| Accès sécurisé aux clés | `get()` avec valeur par défaut |
| Tableaux imbriqués difficiles à manipuler | Conversion récursive en objet |
| Pas de méthodes utilitaires | `with()`, `merge()`, `without()` |

---

## 3. Normalisation des clés

**Fonction principale** : Normalise les clés pour un accès flexible, mais **ne convertit pas** automatiquement snake_case → camelCase pour l'hydratation.

### 3.1. Comportement réel

```php
// Les clés sont stockées telles quelles
$data = new DataObject([
    'id' => 123,
    'first_name' => 'John',
    'last_name' => 'Doe',
    'email_verified_at' => '2024-01-01'
]);

// Accès possible dans les DEUX formats (recherche normalisée)
echo $data->id;              // 123 (exact match)
echo $data->first_name;      // "John" (exact match)
echo $data->firstName;       // "John" (normalisé → first_name)
echo $data->lastName;        // "Doe" (normalisé → last_name)

// Le DataObject original garde les clés d'origine
$data->toArray();  // ['id' => 123, 'first_name' => 'John', 'last_name' => 'Doe', 'email_verified_at' => '2024-01-01']
```

### 3.2. Accès normalisé

```php
// Vous pouvez accéder indifféremment en camelCase ou snake_case
$data = new DataObject([
    'user_id' => 123,
    'user_email' => 'john@example.com'
]);

echo $data->userId;      // 123 (trouve user_id)
echo $data->user_email;  // "john@example.com" (exact match)
echo $data->userEmail;   // "john@example.com" (trouve user_email)
```

---

## 4. Conversion des tableaux imbriqués

Les tableaux associatifs imbriqués sont automatiquement convertis en `DataObject`.

```php
$data = new DataObject([
    'user' => [
        'profile' => [
            'name' => 'John',
            'email' => 'john@example.com'
        ]
    ],
    'tags' => ['premium', 'vip'],  // Liste indexée → reste array
    'metadata' => [
        'created_at' => '2024-01-01',
        'updated_at' => '2024-01-02'
    ]
]);

// Accès fluide
echo $data->user->profile->name;        // "John"
echo $data['user']['profile']['email']; // "john@example.com"
echo $data->metadata->created_at;       // "2024-01-01"

// Types
$data->user instanceof DataObject;      // true
$data->user->profile instanceof DataObject; // true
is_array($data->tags);                  // true (liste conservée)
```

---

## 5. Opérations de transformation

DataObject fournit des méthodes pour créer de nouvelles instances modifiées.

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

// L'original reste inchangé
echo $user->age;     // 30
echo $updated->age;  // 31
```

---

## 6. Rôle dans Hydratable

**C'est l'utilisation la plus importante de DataObject.** Le trait `Hydratable` utilise DataObject pour normaliser les sources avant hydratation.

### 6.1. Comment ça fonctionne

```php
trait Hydratable
{
    public static function from(mixed $source): static
    {
        // 1. DataObject normalise TOUTE source
        $dataObject = DataObject::from($source);
        
        // 2. Analyse du constructeur
        foreach ($constructor->getParameters() as $parameter) {
            $paramName = $parameter->getName();
            
            // 3. Récupération de la valeur (normalisée)
            $rawValue = self::getValueFromDataObject($dataObject, $paramName);
            
            // 4. Conversion et assignation
            $parameters[] = self::convertToType($rawValue, $paramType);
        }
        
        return new static(...$parameters);
    }
    
    public static function fromJson(string $json): static
    {
        $data = json_decode($json, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException(sprintf('Invalid JSON: %s', json_last_error_msg()));
        }
        
        return static::from($data);
    }
}
```

### 6.2. Exemple concret

```php
use AndyDefer\DomainStructures\Traits\Hydratable;

class ProductRecord extends AbstractRecord
{
    use Hydratable;
    
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly float $price
    ) {}
}

// Source externe
$apiData = [
    'id' => 123,
    'name' => 'Laptop',
    'price' => 999.99
];

// Hydratation automatique
$product = ProductRecord::from($apiData);
```

---

## 7. Rôle dans les réponses API et JSON

### 7.1. Les 3 bonnes méthodes d'hydratation depuis JSON

```php
// Réponse API (JSON)
$jsonResponse = '{
    "id": 123,
    "first_name": "John",
    "email_verified_at": "2024-01-01T12:00:00+00:00"
}';

// Méthode 1 : Décodage manuel + from()
$data = json_decode($jsonResponse, true);
$user = TestUserNullableRecord::from($data);

// Méthode 2 : Via DataObject
$user = TestUserNullableRecord::from(DataObject::fromJson($jsonResponse));

// Méthode 3 : Via Hydratable (RECOMMANDÉ)
$user = TestUserNullableRecord::fromJson($jsonResponse);
```

### 7.2. Pourquoi la méthode 3 est recommandée

```php
// La méthode fromJson() offerte par Hydratable :
// 1. Valide le JSON
// 2. Décode automatiquement
// 3. Gère les erreurs
// 4. Une ligne de code

// À utiliser pour TOUTE donnée JSON
$user = UserRecord::fromJson($jsonResponse);
$products = ProductRecord::collect($productsJson, ProductCollection::class);
```

### 7.3. APIs externes

```php
class ExternalApiService
{
    public function getUser(int $id): UserRecord
    {
        // Appel API externe
        $response = $this->httpClient->get("/users/{$id}");
        
        // Hydratation directe depuis JSON (recommandé)
        return UserRecord::fromJson($response);
    }
    
    public function getProducts(): ProductCollection
    {
        $response = $this->httpClient->get('/products');
        
        // Collection automatique depuis JSON
        return ProductRecord::collect($response, ProductCollection::class);
    }
    
    public function createUser(array $data): UserRecord
    {
        $response = $this->httpClient->post('/users', ['json' => $data]);
        
        // Hydratation depuis la réponse
        return UserRecord::fromJson($response);
    }
}
```

---

## 8. API complète

### 8.1. Constructeur

```php
/**
 * @param array<string|int, mixed> $data
 */
public function __construct(array $data = [])
```

### 8.2. Méthodes statiques

```php
// Crée une instance depuis n'importe quelle source
public static function from(mixed $source): static

// Crée une instance depuis JSON
public static function fromJson(string $json): static

// Hydrate une collection d'objets
public static function collect(
    iterable $sources, 
    string $collectionClass = TypedCollection::class
): AbstractTypedCollection
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

## 9. Exemples concrets

### 9.1. API REST complète

```php
class UserController
{
    public function store(Request $request): JsonResponse
    {
        // 1. Récupération du JSON brut
        $json = $request->getContent();
        
        // 2. Hydratation directe (recommandé)
        $user = UserRecord::fromJson($json);
        
        // 3. Validation métier
        $this->validateUser($user);
        
        // 4. Sauvegarde
        $saved = $this->userRepository->save($user);
        
        // 5. Retour API
        return response()->json($saved->toArray());
    }
    
    public function update(int $id, Request $request): JsonResponse
    {
        $json = $request->getContent();
        $data = json_decode($json, true);
        
        // Mise à jour partielle
        $existing = $this->userRepository->find($id);
        $updated = $existing->withData(DataObject::from($data));
        
        return response()->json($updated->toArray());
    }
}
```

### 9.2. Import CSV

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
            $products[] = ProductRecord::from($data);
        }
        
        return ProductRecord::collect($products);
    }
}
```

### 9.3. Configuration flexible

```php
class AppConfig
{
    private DataObject $config;
    
    public function __construct(array $config)
    {
        $this->config = new DataObject($config);
    }
    
    public function getDatabaseDsn(): string
    {
        return sprintf(
            'mysql:host=%s;port=%s;dbname=%s',
            $this->config->get('database.host', 'localhost'),
            $this->config->get('database.port', 3306),
            $this->config->get('database.name', 'app')
        );
    }
    
    public static function fromJsonFile(string $path): self
    {
        $json = file_get_contents($path);
        $config = DataObject::fromJson($json);
        
        return new self($config->toArray());
    }
}
```

### 9.4. Webhook handler

```php
class WebhookController
{
    public function handle(Request $request): JsonResponse
    {
        // Récupération du payload JSON
        $payload = $request->getContent();
        
        // Hydratation selon le type d'événement
        $event = WebhookEvent::fromJson($payload);
        
        match ($event->type) {
            'user.created' => $this->handleUserCreated($event->data),
            'order.paid' => $this->handleOrderPaid($event->data),
            default => $this->handleUnknown($event)
        };
        
        return response()->json(['status' => 'ok']);
    }
    
    private function handleUserCreated(DataObject $data): void
    {
        $user = UserRecord::from($data->toArray());
        $this->userService->syncFromWebhook($user);
    }
}
```

### 9.5. Transformation de données

```php
class DataEnricher
{
    public function enrich(DataObject $source): DataObject
    {
        return $source
            ->with('id', (int) $source->id)
            ->with('email', strtolower($source->email))
            ->with('full_name', trim($source->first_name . ' ' . $source->last_name))
            ->with('created_at', new DateTimeImmutable())
            ->without('temporary_field', 'debug_info');
    }
}

$rawData = new DataObject([
    'id' => '123',
    'first_name' => '  John  ',
    'last_name' => 'Doe',
    'email' => 'JOHN@EXAMPLE.COM',
    'temporary_field' => 'temp'
]);

$enriched = $enricher->enrich($rawData);
$user = UserRecord::from($enriched->toArray());
```

---

## 10. Ce que DataObject n'est PAS

### 10.1. ❌ Pas un mécanisme d'immutabilité

```php
// DataObject ne protège PAS les objets imbriqués
$nestedObject = new stdClass();
$nestedObject->value = 42;

$data = new DataObject(['nested' => $nestedObject]);

// ⚠️ Ceci est possible !
$data->nested->value = 100;  // Modifie l'objet imbriqué !

// La seule protection concerne l'assignation directe
$data->newProperty = 'value';  // ❌ RuntimeException
```

### 10.2. ❌ Pas un Value Object

```php
// DataObject n'a pas d'égalité structurelle
$data1 = new DataObject(['name' => 'John']);
$data2 = new DataObject(['name' => 'John']);

$data1 == $data2;   // false (pas de comparaison structurelle)
$data1 === $data2;  // false (instances différentes)
```

### 10.3. ❌ Pas un Record métier

```php
// Pour les entités métier, utilisez AbstractRecord
// Pour les Value Objects, utilisez AbstractValueObject
// Pour les DTO, utilisez AbstractData

// DataObject est UNIQUEMENT pour :
// - Normalisation de sources externes
// - Pont entre API et hydratation
// - Configuration dynamique
```

---

## 11. Récapitulatif

### 11.1. Ce que DataObject fait

| Fonctionnalité | Support |
|----------------|---------|
| Normalisation de l'accès (camelCase/snake_case) | ✅ Oui |
| Conversion tableaux imbriqués → DataObject | ✅ Oui |
| Méthodes with(), merge(), without() | ✅ Oui |
| Accès par propriété (->) | ✅ Oui |
| Accès par tableau ([]) | ✅ Oui |
| get() avec valeur par défaut | ✅ Oui |
| Support JSON via fromJson() | ✅ Oui |
| Intégration avec Hydratable | ✅ Oui |

### 11.2. Ce que DataObject ne fait PAS

| Fonctionnalité | Support |
|----------------|---------|
| Immutabilité totale | ❌ Non |
| Protection des objets imbriqués | ❌ Non |
| Égalité structurelle | ❌ Non |
| Validation de données | ❌ Non |
| Type-safety forte | ❌ Non |
| Conversion automatique snake_case → camelCase | ❌ Non (seulement accès normalisé) |

### 11.3. Bonnes pratiques pour l'hydratation JSON

```php
// ✅ RECOMMANDÉ - Utiliser fromJson() directement
$user = UserRecord::fromJson($jsonResponse);

// ✅ ACCEPTABLE - Décodage manuel si besoin de validation
$data = json_decode($jsonResponse, true);
if (json_last_error() === JSON_ERROR_NONE) {
    $user = UserRecord::from($data);
}

// ✅ ACCEPTABLE - Via DataObject si besoin de normalisation
$user = UserRecord::from(DataObject::fromJson($jsonResponse));

// ❌ À ÉVITER - Passer JSON directement à from() (ne fonctionne pas)
$user = UserRecord::from($jsonResponse);  // String JSON != array
```

### 11.4. Quand utiliser DataObject

✅ **À utiliser pour :**
- Sources externes (API, fichiers, bases NoSQL)
- Configuration dynamique
- Données temporaires non structurées
- Pont avec Hydratable

❌ **À éviter pour :**
- Entités métier (utilisez `AbstractRecord`)
- Value Objects (utilisez `AbstractValueObject`)
- DTO structurés (utilisez `AbstractData`)
- Collections typées (utilisez `TypedCollection`)

---

## 12. Conclusion

**DataObject est un normalisateur d'accès aux données, pas un convertisseur automatique de casse.**

Son rôle unique et essentiel dans l'architecture est de :
1. **Normaliser l'accès** (accès indifférent camelCase/snake_case)
2. **Unifier les sources** (array, object)
3. **Permettre l'hydratation automatique** via `Hydratable`
4. **Servir de pont** entre le monde externe (API, fichiers) et le domaine

### Points clés à retenir :

- DataObject **ne convertit pas** les clés snake_case en camelCase automatiquement
- DataObject **permet un accès normalisé** (les deux fonctionnent)
- `Hydratable::fromJson()` est la méthode **recommandée** pour les réponses API
- DataObject **n'est pas immutable** - seules les assignations directes sont bloquées
- Pour l'hydratation JSON, utilisez toujours `fromJson()` pas `from()`