# Système de Normalisation - Documentation

## Table des matières

1. [Définition et concepts](#1-définition-et-concepts)
2. [Pourquoi un système de normalisation ?](#2-pourquoi-un-système-de-normalisation-)
3. [Architecture du système](#3-architecture-du-système)
4. [Les normaliseurs disponibles](#4-les-normaliseurs-disponibles)
5. [Ordre de traitement](#5-ordre-de-traitement)
6. [Fonctionnement détaillé](#6-fonctionnement-détaillé)
7. [Cas d'utilisation](#7-cas-dutilisation)
8. [Exemples concrets](#8-exemples-concrets)
9. [Extension du système](#9-extension-du-système)
10. [Bonnes pratiques](#10-bonnes-pratiques)
11. [Récapitulatif](#11-récapitulatif)

---

## 1. Définition et concepts

Le système de normalisation est un mécanisme qui convertit récursivement des objets complexes (Records, ValueObjects, Data, Collections, DataObject) en structures de données simples (tableaux, scalaires, null).

### 1.1. Qu'est-ce que la normalisation ?

La normalisation transforme une structure objet complexe en une représentation portable (array/scalaire) qui peut être :
- Sérialisée en JSON
- Stockée en base de données
- Transmise via une API
- Logguée ou déboguée

```php
use AndyDefer\DomainStructures\Normalizers\NormalizerChain;
use AndyDefer\DomainStructures\Services\HydrationService;

$hydration = new HydrationService();

// Avant normalisation (objets complexes)
$user = $hydration->hydrate(UserRecord::class, [
    'user_id' => 123,
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'tags' => ['premium', 'vip']
]);

// Après normalisation (structure simple)
$normalized = NormalizerChain::get()->normalize($user);
// Résultat :
// [
//     'user_id' => 123,
//     'name' => 'John Doe',
//     'email' => 'john@example.com',
//     'tags' => ['premium', 'vip']
// ]
```

### 1.2. Principes fondamentaux

| Principe | Description |
|----------|-------------|
| **Récursivité** | Normalise automatiquement les objets imbriqués |
| **Chaîne de responsabilité** | Chaque normaliseur gère un type spécifique |
| **Préservation des clés** | Les clés des tableaux sont conservées |
| **Null inclus** | Les valeurs null sont préservées |
| **Conversion camelCase → snake_case** | Pour les Records uniquement |

---

## 2. Pourquoi un système de normalisation ?

### 2.1. Le problème de la sérialisation

```php
use AndyDefer\DomainStructures\Services\HydrationService;

$hydration = new HydrationService();

// ❌ SANS normalisation - Sérialisation directe
$user = $hydration->hydrate(UserRecord::class, ['user_id' => 123, 'name' => 'John']);

// Problème : json_encode ne gère pas les objets complexes
$json = json_encode($user);  // {} ou erreur

// ✅ AVEC normalisation - Automatique et récursif
$normalized = NormalizerChain::get()->normalize($user);
$json = json_encode($normalized);  // Parfait !
```

### 2.2. Ce que le système résout

| Problème | Solution |
|----------|----------|
| Sérialisation d'objets complexes | Normalisation automatique |
| ValueObjects (wrapper classes) | Extraction de la valeur brute |
| Enums (Backed/Pure) | Conversion en valeur scalaire |
| Collections typées | Conversion en tableau |
| DataObject | Conversion en tableau associatif |
| camelCase → snake_case (API) | Conversion automatique pour Records |
| Récursivité des structures imbriquées | Traitement profond automatique |

---

## 3. Architecture du système

### 3.1. Point d'entrée unique

```php
// Point d'entrée unique (recommandé)
use AndyDefer\DomainStructures\Normalizers\NormalizerChain;

$normalizer = NormalizerChain::get();
$normalized = $normalizer->normalize($anyValue);
```

---

## 4. Les normaliseurs disponibles

### 4.1. NullNormalizer

**Rôle** : Gère les valeurs `null`

```php
$normalized = $normalizer->normalize(null);  // null
```

**Ordre** : 1er

### 4.2. ScalarNormalizer

**Rôle** : Passe les valeurs scalaires telles quelles

```php
$normalized = $normalizer->normalize(42);      // 42
$normalized = $normalizer->normalize(3.14);    // 3.14
$normalized = $normalizer->normalize('text');  // 'text'
$normalized = $normalizer->normalize(true);    // true
```

**Ordre** : 2ème

### 4.3. EnumNormalizer

**Rôle** : Convertit les enums en valeurs scalaires

```php
enum Status: string {
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
}

// BackedEnum → retourne la valeur
$normalized = $normalizer->normalize(Status::ACTIVE);  // 'active'

// PureEnum → retourne le nom
enum Role {
    case ADMIN;
    case USER;
}

$normalized = $normalizer->normalize(Role::ADMIN);  // 'ADMIN'
```

**Ordre** : 3ème

### 4.4. RecordNormalizer

**Rôle** : Convertit les `AbstractRecord` en tableau avec conversion camelCase → snake_case

```php
use AndyDefer\DomainStructures\Services\HydrationService;

$hydration = new HydrationService();

$user = $hydration->hydrate(UserRecord::class, [
    'user_id' => 123,
    'first_name' => 'John',
    'last_name' => 'Doe'
]);

$normalized = $normalizer->normalize($user);

// Résultat :
// [
//     'user_id' => 123,
//     'first_name' => 'John',
//     'last_name' => 'Doe'
// ]
```

**Ordre** : 4ème
**Spécificité** : Les Records sont déjà en snake_case

### 4.5. ValueObjectNormalizer

**Rôle** : Extrait la valeur brute d'un `AbstractValueObject`

```php
class EmailAddress extends AbstractValueObject
{
    public function __construct(private string $email) {}
    
    public function getValue(): string
    {
        return $this->email;
    }
}

$email = new EmailAddress('john@example.com');
$normalized = $normalizer->normalize($email);  // 'john@example.com'
```

**Ordre** : 5ème

### 4.6. DataNormalizer

**Rôle** : Convertit les `AbstractData` en tableau (conserve camelCase)

```php
use AndyDefer\DomainStructures\Services\HydrationService;

$hydration = new HydrationService();

$data = $hydration->hydrate(UserData::class, [
    'userId' => 123,
    'firstName' => 'John'
]);

$normalized = $normalizer->normalize($data);

// Résultat :
// [
//     'userId' => 123,
//     'firstName' => 'John'
// ]
```

**Ordre** : 6ème
**Spécificité** : Conserve les noms de propriétés en camelCase

### 4.7. TypedCollectionNormalizer

**Rôle** : Convertit les collections typées en tableau

```php
use AndyDefer\DomainStructures\Collections\Utility\StringTypedCollection;

$tags = new StringTypedCollection(['php', 'laravel', 'typescript']);
$normalized = $normalizer->normalize($tags);  // ['php', 'laravel', 'typescript']

$users = new UserCollection();
$users->add($user1, $user2);
$normalized = $normalizer->normalize($users);  
// [
//     ['user_id' => 1, 'first_name' => 'John'],
//     ['user_id' => 2, 'first_name' => 'Jane']
// ]
```

**Ordre** : 7ème

### 4.8. DataObjectNormalizer

**Rôle** : Convertit `DataObject` en tableau associatif

```php
$data = new DataObject([
    'user_id' => 123,
    'first_name' => 'John',
    'profile' => new DataObject(['age' => 30])
]);

$normalized = $normalizer->normalize($data);
// [
//     'user_id' => 123,
//     'first_name' => 'John',
//     'profile' => ['age' => 30]
// ]
```

**Ordre** : 8ème

### 4.9. ArrayNormalizer

**Rôle** : Parcourt récursivement les tableaux pour normaliser chaque élément

```php
$data = [
    'user' => $userRecord,
    'tags' => new StringTypedCollection(['php', 'js'])
];

$normalized = $normalizer->normalize($data);
// [
//     'user' => ['user_id' => 123, 'first_name' => 'John'],
//     'tags' => ['php', 'js']
// ]
```

**Ordre** : 9ème (dernier)

---

## 5. Ordre de traitement

L'ordre des normaliseurs est **critique** et défini dans `RootNormalizer::initialize()` :

```php
$normalizers = [
    $null,        // 1. Null
    $scalar,      // 2. Scalaires
    $enum,        // 3. Enums
    $record,      // 4. Records (snake_case)
    $vo,          // 5. ValueObjects
    $data,        // 6. Data (camelCase)
    $collection,  // 7. Collections
    $dataObject,  // 8. DataObject
    $array        // 9. Tableaux (doit être dernier)
];
```

### 5.1. Pourquoi cet ordre ?

| Règle | Explication |
|-------|-------------|
| **Null et Scalar en premier** | Cas de base, le plus simple |
| **Enum avant Record** | Un Record peut contenir des enums |
| **Record avant DataObject** | Détection spécifique avant DataObject |
| **Array en dernier** | Capture tout ce qui reste et normalise récursivement |

---

## 6. Fonctionnement détaillé

### 6.1. Récursivité via `next()`

Chaque normaliseur appelle `$this->next($value)` pour normaliser les sous-valeurs :

```php
// RecordNormalizer exemple
public function normalize(mixed $value): mixed
{
    if (! $value instanceof AbstractRecord) {
        return $this->next($value);
    }
    
    $result = [];
    foreach ($properties as $property) {
        $propValue = $property->getValue($value);
        // Normalisation récursive de la propriété
        $result[$key] = $this->next($propValue);
    }
    
    return $result;
}
```

### 6.2. Mécanisme de chaîne

```php
abstract class AbstractNormalizer
{
    protected function next(mixed $value): mixed
    {
        // Utilise le normaliseur récursif si disponible
        if ($this->recursiveNormalizer !== null) {
            return $this->recursiveNormalizer->normalize($value);
        }
        
        // Sinon utilise la chaîne classique
        return $this->next->normalize($value);
    }
}
```

---

## 7. Cas d'utilisation

### 7.1. Sérialisation JSON pour API

```php
use AndyDefer\DomainStructures\Services\HydrationService;
use AndyDefer\DomainStructures\Normalizers\NormalizerChain;

class UserController
{
    private HydrationService $hydration;
    
    public function __construct()
    {
        $this->hydration = new HydrationService();
    }
    
    public function show(int $id): JsonResponse
    {
        $user = $this->userRepository->find($id);
        
        // Normalisation automatique
        $normalized = NormalizerChain::get()->normalize($user);
        
        return response()->json($normalized);
        // {
        //     "user_id": 123,
        //     "first_name": "John",
        //     "last_name": "Doe",
        //     "email": "john@example.com"
        // }
    }
}
```

### 7.2. Dans une Action

```php
use AndyDefer\DomainStructures\Services\HydrationService;
use AndyDefer\DomainStructures\Normalizers\NormalizerChain;
use AndyDefer\Actions\Http\ResponseFactory;

final class ShowUserAction extends AbstractAction
{
    private HydrationService $hydration;
    
    public function __construct(
        private readonly UserRepository $userRepository,
    ) {
        $this->hydration = new HydrationService();
    }
    
    protected function handle(AbstractRecord $request): ResponseFactory
    {
        /** @var ShowUserRecord $request */
        
        $user = $this->userRepository->find($request->user_id);
        
        $userData = $this->hydration(UserData::class, $user->toArray())
        
        return ResponseFactory::json($userData);
    }
}
```

### 7.3. Logging structuré

```php
use AndyDefer\DomainStructures\Normalizers\NormalizerChain;

class Logger
{
    public function logUserAction(UserRecord $user, string $action): void
    {
        $context = NormalizerChain::get()->normalize($user);
        $context['action'] = $action;
        $context['timestamp'] = time();
        
        $this->logger->info('User action', $context);
    }
}
```

### 7.4. Transformation Record → Data via HydrationService

```php
use AndyDefer\DomainStructures\Services\HydrationService;
use AndyDefer\DomainStructures\Normalizers\NormalizerChain;

class UserTransformer
{
    private HydrationService $hydration;
    
    public function __construct()
    {
        $this->hydration = new HydrationService();
    }
    
    public function toData(UserRecord $record): UserData
    {
        // Normalisation du Record (snake_case)
        $normalized = NormalizerChain::get()->normalize($record);
        
        // Hydratation en Data (camelCase)
        return $this->hydration->hydrate(UserData::class, $normalized);
    }
}
```

---

## 8. Exemples concrets

### 8.1. Structure complexe avec HydrationService

```php
use AndyDefer\DomainStructures\Services\HydrationService;
use AndyDefer\DomainStructures\Normalizers\NormalizerChain;

$hydration = new HydrationService();

// Hydratation d'une structure complexe
$order = $hydration->hydrate(OrderRecord::class, [
    'order_id' => 12345,
    'order_status' => 'paid',
    'customer' => [
        'customer_id' => 789,
        'email' => 'customer@example.com',
        'tags' => ['vip', 'premium']
    ],
    'items' => [
        ['product_id' => 1, 'quantity' => 2, 'price' => 49.99],
        ['product_id' => 2, 'quantity' => 1, 'price' => 99.99]
    ]
]);

// Normalisation
$normalized = NormalizerChain::get()->normalize($order);

// Résultat :
// [
//     'order_id' => 12345,
//     'order_status' => 'paid',
//     'customer' => [
//         'customer_id' => 789,
//         'email' => 'customer@example.com',
//         'tags' => ['vip', 'premium']
//     ],
//     'items' => [
//         ['product_id' => 1, 'quantity' => 2, 'price' => 49.99],
//         ['product_id' => 2, 'quantity' => 1, 'price' => 99.99]
//     ]
// ]
```

### 8.2. Mix DataObject et Records

```php
use AndyDefer\DomainStructures\Services\HydrationService;
use AndyDefer\DomainStructures\Normalizers\NormalizerChain;

$hydration = new HydrationService();

$user = $hydration->hydrate(UserRecord::class, [
    'user_id' => 123,
    'first_name' => 'John'
]);

$apiData = new DataObject([
    'user' => $user,
    'metadata' => [
        'source' => 'api',
        'timestamp' => time()
    ],
    'tags' => new StringTypedCollection(['php', 'laravel'])
]);

$normalized = NormalizerChain::get()->normalize($apiData);

// Résultat :
// [
//     'user' => ['user_id' => 123, 'first_name' => 'John'],
//     'metadata' => ['source' => 'api', 'timestamp' => 1234567890],
//     'tags' => ['php', 'laravel']
// ]
```

### 8.3. Surcharge personnalisée

```php
use AndyDefer\DomainStructures\Services\HydrationService;
use AndyDefer\DomainStructures\Normalizers\NormalizerChain;

class ApiNormalizer
{
    private HydrationService $hydration;
    
    public function __construct()
    {
        $this->hydration = new HydrationService();
    }
    
    public function normalizeForApi(UserRecord $user): array
    {
        $fullNormalized = NormalizerChain::get()->normalize($user);
        
        // Supprimer les données sensibles
        unset($fullNormalized['email_verified_at']);
        unset($fullNormalized['password_hash']);
        
        // Ajouter des métadonnées
        $fullNormalized['_links'] = [
            'self' => "/api/users/{$user->user_id}",
            'orders' => "/api/users/{$user->user_id}/orders"
        ];
        
        return $fullNormalized;
    }
}
```

---

## 9. Extension du système

### 9.1. Créer un normaliseur personnalisé

```php
use AndyDefer\DomainStructures\Normalizers\Core\AbstractNormalizer;

final class CustomObjectNormalizer extends AbstractNormalizer
{
    public function supports(mixed $value): bool
    {
        return $value instanceof CustomObject;
    }
    
    public function normalize(mixed $value): mixed
    {
        if (! $value instanceof CustomObject) {
            return $this->next($value);
        }
        
        return [
            'custom_id' => $value->getId(),
            'custom_data' => $this->next($value->getData()),
            'custom_metadata' => $value->getMetadata()
        ];
    }
}
```

### 9.2. Ajouter au RootNormalizer

```php
// Dans RootNormalizer::initialize()
$custom = new CustomObjectNormalizer;

$normalizers = [
    $null,
    $scalar,
    $enum,
    $custom,  // Ajouter ici
    $record,
    $vo,
    $data,
    $collection,
    $dataObject,
    $array
];
```

---

## 10. Bonnes pratiques

### 10.1. Utiliser NormalizerChain

```php
// ✅ RECOMMANDÉ - Point d'entrée unique
$normalizer = NormalizerChain::get();

// ❌ À ÉVITER - Créer directement RootNormalizer
$normalizer = new RootNormalizer();  // Pas d'initialisation automatique
```

### 10.2. Ne pas normaliser deux fois

```php
// ✅ BON - Une seule normalisation
$normalized = NormalizerChain::get()->normalize($user);
$json = json_encode($normalized);

// ❌ MAUVAIS - Double normalisation (inutile)
$normalized1 = NormalizerChain::get()->normalize($user);
$normalized2 = NormalizerChain::get()->normalize($normalized1);  // Inutile
```

### 10.3. Gestion des erreurs

```php
try {
    $normalized = NormalizerChain::get()->normalize($complexObject);
} catch (RuntimeException $e) {
    Log::error('Normalization failed', ['error' => $e->getMessage()]);
    throw $e;
}
```

### 10.4. Performance

```php
// ✅ BON - Réutiliser le normaliseur
$normalizer = NormalizerChain::get();
$data1 = $normalizer->normalize($object1);
$data2 = $normalizer->normalize($object2);

// NormalizerChain est un singleton - la même instance est toujours retournée
```

---

## 11. Récapitulatif

### 11.1. Points clés

| Élément | Description |
|---------|-------------|
| **Objectif** | Convertir objets complexes → structures simples |
| **Pattern** | Chaîne de responsabilité |
| **Récursivité** | Traite automatiquement les structures imbriquées |
| **Singleton** | NormalizerChain.get() retourne toujours la même instance |
| **Extensible** | Ajouter des normaliseurs personnalisés facilement |

### 11.2. Types supportés

| Type | Normalisation |
|------|---------------|
| `null` | → null |
| Scalaires | → valeur inchangée |
| `UnitEnum` | → valeur du backed enum ou nom |
| `AbstractRecord` | → tableau (snake_case) |
| `AbstractValueObject` | → valeur brute (getValue()) |
| `AbstractData` | → tableau (conserve camelCase) |
| `AbstractTypedCollection` | → tableau indexé |
| `DataObject` | → tableau associatif |
| `array` | → récursif sur chaque élément |

### 11.3. Ordre de normalisation

```
Null → Scalar → Enum → Record → ValueObject → Data → Collection → DataObject → Array
```

### 11.4. Quand utiliser

✅ **À utiliser pour :**
- Sérialisation JSON (API)
- Stockage en base NoSQL
- Cache
- Logging
- Debugging
- Export de données

❌ **À ne pas utiliser pour :**
- Hydratation (c'est l'inverse - utilisez HydrationService)
- Transformation métier (utilisez des mappers)
- Affichage direct (utilisez des présentateurs)

---

## 12. Flux complet avec HydrationService

```php
use AndyDefer\DomainStructures\Services\HydrationService;
use AndyDefer\DomainStructures\Normalizers\NormalizerChain;

// 1. Hydratation (source → objet)
$hydration = new HydrationService();

$user = $hydration->hydrate(UserRecord::class, [
    'user_id' => 123,
    'first_name' => 'John',
    'last_name' => 'Doe',
    'email' => 'john@example.com'
]);

// 2. Normalisation (objet → tableau)
$normalized = NormalizerChain::get()->normalize($user);

// 3. Sérialisation JSON
$json = json_encode($normalized);

// Résultat final :
// {
//     "user_id": 123,
//     "first_name": "John",
//     "last_name": "Doe",
//     "email": "john@example.com"
// }
```

### 12.2. Conversion Record → Data

```php
use AndyDefer\DomainStructures\Services\HydrationService;
use AndyDefer\DomainStructures\Normalizers\NormalizerChain;

$hydration = new HydrationService();

// Record (interne, snake_case)
$record = $hydration->hydrate(UserRecord::class, [
    'user_id' => 123,
    'first_name' => 'John',
    'last_name' => 'Doe'
]);

// Normalisation
$normalized = NormalizerChain::get()->normalize($record);

// Data (API, camelCase)
$data = $hydration->hydrate(UserData::class, $normalized);

echo $data->userId;     // 123
echo $data->firstName;  // John
```
---