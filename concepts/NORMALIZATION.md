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

// Avant normalisation (objets complexes)
$user = new UserRecord(
    id: 123,
    name: 'John Doe',
    email: EmailValueObject::from('john@example.com'),
    tags: new StringTypedCollection(['premium', 'vip'])
);

// Après normalisation (structure simple)
$normalized = NormalizerChain::get()->normalize($user);
// Résultat :
// [
//     'id' => 123,
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
// ❌ SANS normalisation - Sérialisation directe
$user = new UserRecord(id: 123, name: 'John');

// Problème : json_encode ne gère pas les objets complexes
$json = json_encode($user);  // {} ou erreur

// Solution manuelle (répétitive)
function toArray($user) {
    return [
        'id' => $user->id,
        'name' => $user->name,
        'email' => $user->email->getValue(),  // ValueObject
        'tags' => $user->tags->toArray()       // Collection
    ];
}

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

### 3.1. Structure des classes

```
NormalizerInterface (contrat)
    ↓
AbstractNormalizer (implémentation de base)
    ↓
Normalizers spécifiques :
    ├── NullNormalizer
    ├── ScalarNormalizer
    ├── EnumNormalizer
    ├── RecordNormalizer
    ├── ValueObjectNormalizer
    ├── DataNormalizer
    ├── TypedCollectionNormalizer
    ├── DataObjectNormalizer
    └── ArrayNormalizer
    ↓
RootNormalizer (normaliseur racine)
    ↓
NormalizerChain (point d'entrée unique)
```

### 3.2. Flux de traitement

```
Valeur d'entrée
    ↓
RootNormalizer
    ↓
Parcourt chaque normaliseur dans l'ordre
    ↓
Normaliseur.support(value)? → OUI → normalize(value) → résultat
    ↓                            ↓
    NON                          Fait appel à next() pour les sous-valeurs
    ↓
Normaliseur suivant
    ↓
Résultat final (array/scalaire/null)
```

### 3.3. Points d'entrée

```php
// Point d'entrée unique (recommandé)
use AndyDefer\DomainStructures\Normalizers\NormalizerChain;

$normalizer = NormalizerChain::get();
$normalized = $normalizer->normalize($anyValue);

// Accès direct au normaliseur racine (équivalent)
use AndyDefer\DomainStructures\Normalizers\RootNormalizer;

$normalizer = new RootNormalizer();
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
class UserRecord extends AbstractRecord
{
    public function __construct(
        public readonly int $userId,
        public readonly string $firstName,
        public readonly string $lastName
    ) {}
}

$user = new UserRecord(userId: 123, firstName: 'John', lastName: 'Doe');
$normalized = $normalizer->normalize($user);

// Résultat :
// [
//     'user_id' => 123,      // camelCase → snake_case
//     'first_name' => 'John',
//     'last_name' => 'Doe'
// ]
```

**Ordre** : 4ème
**Spécificité** : Seul normaliseur qui convertit camelCase → snake_case

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
class UserData extends AbstractData
{
    public function __construct(
        public readonly int $userId,
        public readonly string $firstName
    ) {}
}

$data = new UserData(userId: 123, firstName: 'John');
$normalized = $normalizer->normalize($data);

// Résultat :
// [
//     'userId' => 123,      // camelCase conservé
//     'firstName' => 'John'
// ]
```

**Ordre** : 6ème
**Spécificité** : Conserve les noms de propriétés d'origine

### 4.7. TypedCollectionNormalizer

**Rôle** : Convertit les collections typées en tableau

```php
$tags = new StringTypedCollection(['php', 'laravel', 'typescript']);
$normalized = $normalizer->normalize($tags);  // ['php', 'laravel', 'typescript']

$users = new UserRecordCollection();
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
    'user' => new UserRecord(userId: 123, firstName: 'John'),
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
    $record,      // 4. Records (camelCase → snake_case)
    $vo,          // 5. ValueObjects
    $data,        // 6. Data (conserve camelCase)
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

### 5.2. Importance de l'ordre

```php
// Si ArrayNormalizer était avant RecordNormalizer
$arrayNormalizer->supports($record);  // false (ce n'est pas un array)
// OK, pas de problème

// Mais ArrayNormalizer DOIT être après les normaliseurs spécifiques
// car il traite les tableaux qui contiennent des objets spécifiques
$data = ['user' => new UserRecord(...)];
// ArrayNormalizer doit normaliser récursivement le UserRecord
// donc il doit appeler le normaliseur racine qui redescend dans la chaîne
```

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

### 6.3. Configuration par RootNormalizer

```php
// RootNormalizer s'auto-configure comme normaliseur récursif
foreach ($normalizers as $normalizer) {
    if (method_exists($normalizer, 'setRecursiveNormalizer')) {
        $normalizer->setRecursiveNormalizer($this);
    }
}
```

---

## 7. Cas d'utilisation

### 7.1. Sérialisation JSON pour API

```php
class UserController
{
    public function show(int $id): JsonResponse
    {
        $user = $this->userRepository->find($id);
        
        // Normalisation automatique
        $normalized = NormalizerChain::get()->normalize($user);
        
        // Les clés sont en snake_case pour l'API
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

### 7.2. Logging structuré

```php
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

### 7.3. Stockage en base de données (NoSQL/JSON)

```php
class UserRepository
{
    public function save(UserRecord $user): void
    {
        $data = NormalizerChain::get()->normalize($user);
        
        // Stockage en MongoDB ou autre base JSON
        $this->collection->insertOne($data);
    }
}
```

### 7.4. Cache

```php
class CacheService
{
    public function set(string $key, mixed $value, int $ttl = 3600): void
    {
        $normalized = NormalizerChain::get()->normalize($value);
        $serialized = serialize($normalized);
        
        $this->cache->set($key, $serialized, $ttl);
    }
    
    public function get(string $key): mixed
    {
        $serialized = $this->cache->get($key);
        
        return unserialize($serialized);
    }
}
```

---

## 8. Exemples concrets

### 8.1. Structure complexe

```php
// Structure objet complexe
$order = new OrderRecord(
    id: 12345,
    status: OrderStatus::PAID,  // Enum
    customer: new CustomerRecord(
        id: 789,
        email: EmailValueObject::from('customer@example.com'),  // ValueObject
        tags: new StringTypedCollection(['vip', 'premium'])      // Collection
    ),
    items: new TypedCollection(OrderItemRecord::class)
);

$items = $order->items;
$items->add(
    new OrderItemRecord(productId: 1, quantity: 2, price: 49.99),
    new OrderItemRecord(productId: 2, quantity: 1, price: 99.99)
);

// Normalisation
$normalized = NormalizerChain::get()->normalize($order);

// Résultat :
// [
//     'id' => 12345,
//     'status' => 'paid',                    // BackedEnum
//     'customer' => [
//         'id' => 789,
//         'email' => 'customer@example.com', // ValueObject
//         'tags' => ['vip', 'premium']        // Collection
//     ],
//     'items' => [
//         ['product_id' => 1, 'quantity' => 2, 'price' => 49.99],
//         ['product_id' => 2, 'quantity' => 1, 'price' => 99.99]
//     ]
// ]
```

### 8.2. Mix DataObject et Records

```php
$apiData = new DataObject([
    'user' => new UserRecord(id: 123, firstName: 'John'),
    'metadata' => [
        'source' => 'api',
        'timestamp' => time()
    ],
    'tags' => new StringTypedCollection(['php', 'laravel'])
]);

$normalized = NormalizerChain::get()->normalize($apiData);

// Résultat :
// [
//     'user' => ['id' => 123, 'first_name' => 'John'],
//     'metadata' => ['source' => 'api', 'timestamp' => 1234567890],
//     'tags' => ['php', 'laravel']
// ]
```

### 8.3. Surcharge personnalisée

```php
// Normalisation conditionnelle
class ApiNormalizer
{
    public function normalizeForApi(UserRecord $user): array
    {
        $fullNormalized = NormalizerChain::get()->normalize($user);
        
        // Supprimer les données sensibles
        unset($fullNormalized['email_verified_at']);
        unset($fullNormalized['password_hash']);
        
        // Ajouter des métadonnées
        $fullNormalized['_links'] = [
            'self' => "/api/users/{$user->id}",
            'orders' => "/api/users/{$user->id}/orders"
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
        
        // Logique de normalisation personnalisée
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

// L'ordre est important - Insérer au bon endroit
$normalizers = [
    $null,
    $scalar,
    $enum,
    $custom,  // Ajouter ici si CustomObject n'est ni Record ni DataObject
    $record,
    $vo,
    $data,
    $collection,
    $dataObject,
    $array
];
```

### 9.3. Normaliseur avec contexte

```php
class ContextualNormalizer extends AbstractNormalizer
{
    private array $context = [];
    
    public function setContext(array $context): void
    {
        $this->context = $context;
    }
    
    public function normalize(mixed $value): mixed
    {
        // Utiliser le contexte pour modifier la normalisation
        if ($this->context['format'] === 'api') {
            return $this->normalizeForApi($value);
        }
        
        return $this->next($value);
    }
}
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
    // Aucun normaliseur trouvé
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
| `AbstractRecord` | → tableau (camelCase → snake_case) |
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
- Hydratation (c'est l'inverse)
- Transformation métier (utilisez des mappers)
- Affichage direct (utilisez des présentateurs)

---

## 12. Annexe : Équivalences

### 12.1. Avant/Après normalisation

| Avant | Après |
|-------|-------|
| `new UserRecord(id: 123)` | `['id' => 123]` |
| `EmailValueObject::from('test@ex.com')` | `'test@ex.com'` |
| `Status::ACTIVE` (BackedEnum) | `'active'` |
| `Role::ADMIN` (PureEnum) | `'ADMIN'` |
| `new StringTypedCollection(['a','b'])` | `['a','b']` |
| `new DataObject(['key' => 'value'])` | `['key' => 'value']` |

### 12.2. Conversion camelCase → snake_case

```php
// RecordNormalizer convertit :
'userId'     → 'user_id'
'firstName'  → 'first_name'
'emailVerifiedAt' → 'email_verified_at'
'XMLHttpRequest'  → 'xml_http_request'  (géré mais rare)
```

### 12.3. Valeurs préservées

```php
$normalizer->normalize(null);      // null
$normalizer->normalize(0);         // 0
$normalizer->normalize('');        // ''
$normalizer->normalize(false);     // false
$normalizer->normalize([]);        // []
```