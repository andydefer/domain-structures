# Domain Structures

Une bibliothèque PHP pour la création de structures de domaine type-safety, immutables et robustes, spécialement conçue pour l'architecture hexagonale et le Domain-Driven Design (DDD).

## 📚 Table des matières

1. [À propos](#-à-propos)
2. [Installation](#-installation)
3. [Concepts fondamentaux](#-concepts-fondamentaux)
   - [Value Objects](concepts/VALUE_OBJECTS.md)
   - [Records](concepts/RECORDS.md)
   - [Data DTO](concepts/DATA.md)
   - [Typed Collections](concepts/TYPED_COLLECTIONS.md)
   - [DataObject](concepts/DATA_OBJECTS.md)
4. [Systèmes transverses](#-systèmes-transverses)
   - [Hydratation automatique](concepts/HYDRATABLE.md)
   - [Normalisation](concepts/NORMALIZATION.md)
5. [Utilisation](#-utilisation)
6. [Bonnes pratiques](#-bonnes-pratiques)
7. [Support](#-support)

---

## 🎯 À propos

**Domain Structures** est une bibliothèque PHP qui fournit une base solide pour construire des applications avec une architecture propre et type-safe. Elle implémente les patterns fondamentaux du Domain-Driven Design :

- **Value Objects** : Concepts métier auto-validants
- **Records** : Structures de données internes immutables
- **Data DTO** : Objets de transfert pour les réponses API
- **Typed Collections** : Collections type-safe
- **Hydratation automatique** : Création d'objets depuis n'importe quelle source via `HydrationService`
- **Normalisation** : Export vers des structures simples (JSON, base de données)

### Philosophie

> **"Rien n'est primitif, tout est concept"**

Dans une application bien architecturée, on ne manipule jamais de types primitifs directement. Chaque donnée est représentée par un concept explicite :

| Au lieu de... | Utilisez... |
|---------------|-------------|
| `int $id` | `UserId $id` |
| `string $email` | `EmailAddress $email` |
| `float $price` | `Money $price` |
| `array $products` | `ProductCollection $products` |

---

## 📦 Installation

```bash
composer require andydefer/domain-structures
```

**Prérequis :**
- PHP 8.1 ou supérieur
- Extension JSON activée

---

## 📖 Concepts fondamentaux

### 1. Value Objects

Les Value Objects représentent des **concepts métier** avec leur propre comportement et validation.

```php
use AndyDefer\DomainStructures\Abstracts\AbstractValueObject;

final class EmailAddress extends AbstractValueObject
{
    public function __construct(
        private readonly string $value
    ) {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException("Invalid email address");
        }
    }
    
    public function getValue(): string
    {
        return $this->value;
    }
    
    public function getDomain(): string
    {
        return substr(strrchr($this->value, "@"), 1);
    }
    
    public function isGmail(): bool
    {
        return $this->getDomain() === 'gmail.com';
    }
}

// Utilisation
$email = new EmailAddress('john@example.com');
echo $email->getDomain(); // 'example.com'
```

**Caractéristiques :**
- ✅ Immutable
- ✅ Auto-validant (validation dans le constructeur)
- ✅ Comportement métier
- ✅ Pas d'identité propre

👉 **[Documentation complète des Value Objects](concepts/VALUE_OBJECTS.md)**

---

### 2. Records

Les Records sont des **structures de données internes** pour la communication entre les couches de l'application.

```php
use AndyDefer\DomainStructures\Abstracts\AbstractRecord;

final class UserRecord extends AbstractRecord
{
    public function __construct(
        public readonly ?int $id,
        public readonly string $name,
        public readonly EmailAddress $email,
        public readonly UserRole $role,
        public readonly Iso8601DateTime $createdAt,
    ) {}
}
```

**Caractéristiques :**
- ✅ Immutable
- ✅ Support JSON (via normalisation)
- ❌ Pas de logique métier

👉 **[Documentation complète des Records](concepts/RECORDS.md)**

---

### 3. Data DTO

Les Data DTO sont des **objets de transfert** exclusivement pour les **réponses API**.

```php
use AndyDefer\DomainStructures\Abstracts\AbstractData;

final class UserData extends AbstractData
{
    public function __construct(
        public readonly UserId $id,
        public readonly PersonName $name,
        public readonly EmailAddress $email,
        public readonly Iso8601DateTime $createdAt,
        public readonly UserRole $role,
    ) {}
}
```

**Caractéristiques :**
- ✅ Exclusivement pour les réponses API
- ✅ Normalisation en `camelCase`
- ❌ Aucun type primitif autorisé

👉 **[Documentation complète des Data DTO](concepts/DATA.md)**

---

### 4. Typed Collections

Les Typed Collections remplacent les tableaux bruts par des collections **type-safe**.

**Collections utilitaires prédéfinies :**

| Collection | Type contenu |
|------------|--------------|
| `StringTypedCollection` | `string` |
| `IntTypedCollection` | `int` |
| `FloatTypedCollection` | `float` |
| `BoolTypedCollection` | `bool` |
| `NumberTypedCollection` | `int\|float` |

```php
use AndyDefer\DomainStructures\Collections\Utility\StringTypedCollection;

// Création et ajout
$strings = new StringTypedCollection();
$strings->add('hello', 'world', 'foo');

// Accès
foreach ($strings as $string) {
    echo $string;
}

// Création de collection spécialisée
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
}
```

👉 **[Documentation complète des Typed Collections](concepts/TYPED_COLLECTIONS.md)**

---

### 5. DataObject

DataObject est un **normalisateur d'accès aux données** qui sert de pont entre les sources externes et le système.

```php
use AndyDefer\DomainStructures\Utils\DataObject;

// Source externe (snake_case)
$apiData = [
    'user_id' => 123,
    'first_name' => 'John',
    'last_name' => 'Doe'
];

// Création via le constructeur
$normalized = new DataObject($apiData);

// Accès indifférent camelCase/snake_case
echo $normalized->userId;      // 123
echo $normalized->first_name;  // "John"
echo $normalized->lastName;    // "Doe"

// Transformation immuable
$updated = $normalized->with('email', 'john@example.com');
$merged = $updated->merge(['role' => 'admin']);
$without = $merged->without('temp_field');
```

👉 **[Documentation complète de DataObject](concepts/DATA_OBJECTS.md)**

---

## 🔧 Systèmes transverses

### Hydratation automatique (HydrationService)

Le service `HydrationService` analyse le constructeur d'une classe et l'hydrate automatiquement depuis n'importe quelle source.

```php
use AndyDefer\DomainStructures\Services\HydrationService;

$hydration = new HydrationService();

// Hydratation d'un seul item
$user = $hydration->hydrate(UserRecord::class, [
    'id' => 123,
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'role' => 'admin',
    'created_at' => '2024-01-01T12:00:00+00:00'
]);

// Hydratation depuis JSON
$user = $hydration->hydrateFromJson(UserRecord::class, $jsonString);

// Hydratation d'une collection
$users = $hydration->collect($rows, UserRecordCollection::class);

// Hydratation d'une collection depuis JSON
$users = $hydration->collectFromJson($jsonArray, UserRecordCollection::class);
```

**Support :**
- ✅ Types scalaires (int, float, string, bool)
- ✅ Enums (BackedEnum)
- ✅ Unions types
- ✅ Objets (hydratation récursive)
- ✅ Valeurs par défaut
- ✅ Nullabilité

👉 **[Documentation complète de l'Hydratation](concepts/HYDRATABLE.md)**

---

### Normalisation

Le système de normalisation convertit récursivement les objets complexes en structures simples (tableaux, scalaires).

```php
use AndyDefer\DomainStructures\Normalizers\NormalizerChain;

$user = $hydration->hydrate(UserRecord::class, $row);

// Normalisation automatique
$normalized = NormalizerChain::get()->normalize($user);
// Résultat : ['id' => 123, 'name' => 'John Doe', 'email' => 'john@example.com', 'role' => 'admin', 'created_at' => '2024-01-01T12:00:00+00:00']

// JSON direct
$json = json_encode($normalized);
```

**Normaliseurs disponibles :**
- `NullNormalizer` → null
- `ScalarNormalizer` → scalaires
- `EnumNormalizer` → valeur
- `RecordNormalizer` → tableau (camelCase → snake_case)
- `ValueObjectNormalizer` → valeur brute
- `DataNormalizer` → tableau (conserve camelCase)
- `TypedCollectionNormalizer` → tableau indexé
- `DataObjectNormalizer` → tableau associatif
- `ArrayNormalizer` → récursif

👉 **[Documentation complète de la Normalisation](concepts/NORMALIZATION.md)**

---

## 🚀 Utilisation

### Exemple complet avec HydrationService

```php
use AndyDefer\DomainStructures\Services\HydrationService;
use AndyDefer\DomainStructures\Normalizers\NormalizerChain;
use AndyDefer\DomainStructures\Utils\DataObject;

// 1. Définir les Value Objects
final class EmailAddress extends AbstractValueObject
{
    public function __construct(private readonly string $value)
    {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException("Invalid email");
        }
    }
    
    public function getValue(): string { return $this->value; }
}

final class Iso8601DateTime extends AbstractValueObject
{
    public function __construct(private readonly string $value)
    {
        if (!strtotime($value)) {
            throw new \InvalidArgumentException("Invalid date");
        }
    }
    
    public function getValue(): string { return $this->value; }
}

// 2. Définir l'Enum
enum UserRole: string 
{ 
    case ADMIN = 'admin'; 
    case USER = 'user'; 
}

// 3. Définir le Record
final class UserRecord extends AbstractRecord
{
    public function __construct(
        public readonly ?int $id,
        public readonly string $name,
        public readonly EmailAddress $email,
        public readonly UserRole $role,
        public readonly Iso8601DateTime $createdAt,
    ) {}
}

// 4. Définir la collection spécialisée
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
}

// 5. Définir le Data DTO pour l'API
final class UserData extends AbstractData
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $email,
        public readonly string $role,
        public readonly string $createdAt,
    ) {}
}

// 6. Utilisation dans un Repository
class UserRepository
{
    private HydrationService $hydration;

    public function __construct(HydrationService $hydration)
    {
        $this->hydration = $hydration;
    }

    public function find(int $id): ?UserRecord
    {
        $row = $this->db->fetchAssoc('SELECT * FROM users WHERE id = ?', [$id]);
        if (!$row) return null;
        
        return $this->hydration->hydrate(UserRecord::class, $row);
    }
    
    public function findAll(): UserRecordCollection
    {
        $rows = $this->db->fetchAllAssoc('SELECT * FROM users');
        return $this->hydration->collect($rows, UserRecordCollection::class);
    }
}

// 7. Utilisation dans un Controller
class UserController
{
    public function show(int $id): JsonResponse
    {
        $user = $this->userRepository->find($id);
        
        // Convertir Record en Data DTO pour l'API
        $userData = new UserData(
            id: $user->id,
            name: $user->name,
            email: $user->email->getValue(),
            role: $user->role->value,
            createdAt: $user->createdAt->getValue()
        );
        
        // Normalisation automatique (camelCase pour le client)
        return response()->json(NormalizerChain::get()->normalize($userData));
    }
}

// 8. Utilisation de DataObject pour les sources externes
$apiResponse = new DataObject([
    'user_id' => 123,
    'first_name' => 'John',
    'last_name' => 'Doe'
]);

echo $apiResponse->user_id;      // 123
echo $apiResponse->firstName;    // 'John'
echo $apiResponse->last_name;    // 'Doe'
```

---

## 💡 Bonnes pratiques

### 1. Value Objects

```php
// ✅ BON - Validation centralisée dans le constructeur
final class EmailAddress extends AbstractValueObject
{
    public function __construct(private readonly string $value)
    {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException("Invalid email");
        }
    }
}

// ❌ MAUVAIS - Validation dispersée
$email = $_POST['email'];
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { /* ... */ }
```

### 2. Records

```php
// ✅ BON - Uniquement pour la communication interne
public function find(int $id): UserRecord

// ❌ MAUVAIS - Dans une réponse API (utilisez Data DTO)
return response()->json($userRecord);
```

### 3. Data DTO

```php
// ✅ BON - Pour les réponses API
return response()->json($userData);

// ❌ MAUVAIS - Avec des types primitifs
public readonly int $id; // Interdit ! Utilisez UserId $id
```

### 4. Typed Collections

```php
// ✅ BON - Collection spécialisée avec comportement métier
final class ProductRecordCollection extends TypedCollection
{
    public function getActive(): self { /* ... */ }
}

// ❌ MAUVAIS - TypedCollection générique sans comportement
public readonly TypedCollection $products;
```

### 5. Hydratation

```php
// ✅ BON - Utilisation du service d'hydratation
$hydration = new HydrationService();
$user = $hydration->hydrate(UserRecord::class, $data);

// ❌ MAUVAIS - Hydratation manuelle
$user = new UserRecord(
    id: $data['id'],
    name: $data['name'],
    // ... des dizaines de champs à mapper manuellement
);
```

### 6. DataObject

```php
// ✅ BON - Utilisation du constructeur
$data = new DataObject($source);

// ❌ MAUVAIS - Utilisation de ::from() (déprécié)
$data = DataObject::from($source);
```

---

## 🔗 Liens rapides vers la documentation

| Concept | Documentation |
|---------|---------------|
| **Value Objects** | [VALUE_OBJECTS.md](concepts/VALUE_OBJECTS.md) |
| **Records** | [RECORDS.md](concepts/RECORDS.md) |
| **Data DTO** | [DATA.md](concepts/DATA.md) |
| **Typed Collections** | [TYPED_COLLECTIONS.md](concepts/TYPED_COLLECTIONS.md) |
| **DataObject** | [DATA_OBJECTS.md](concepts/DATA_OBJECTS.md) |
| **Hydratation** | [HYDRATABLE.md](concepts/HYDRATABLE.md) |
| **Normalisation** | [NORMALIZATION.md](concepts/NORMALIZATION.md) |

---

## 🤝 Support

Pour toute question ou suggestion :

- **Issues** : [GitHub Issues](https://github.com/andydefer/domain-structures/issues)
- **Documentation** : Consultez les fichiers dans le dossier `/concepts`

---

## 📄 License

MIT License - Copyright (c) 2024 Andy Defer

---

## ⚡ Résumé

**Domain Structures** vous permet de construire des applications PHP avec :

✅ **Type-safety** : Tous les types sont explicites et validés  
✅ **Immutabilité** : Aucune modification accidentelle  
✅ **Hydratation automatique** : Création d'objets depuis n'importe quelle source  
✅ **Normalisation** : Export vers JSON, base de données, cache  
✅ **Collections typées** : Remplacement type-safe des tableaux  
✅ **Architecture propre** : Séparation claire des responsabilités  

**Commencez dès maintenant :**
```bash
composer require andydefer/domain-structures
```
---