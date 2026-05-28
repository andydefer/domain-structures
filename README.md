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
   - [Hydratation automatique (Hydratable)](concepts/HYDRATABLE.md)
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
- **Hydratation automatique** : Création d'objets depuis n'importe quelle source
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

### 1. [Value Objects](concepts/VALUE_OBJECTS.md)

Les Value Objects représentent des **concepts métier** avec leur propre comportement et validation.

```php
use AndyDefer\DomainStructures\Abstracts\AbstractValueObject;

final class EmailAddress extends AbstractValueObject
{
    private function __construct(public readonly string $value) {}
    
    public static function from(mixed $source): static
    {
        if (!filter_var($source, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("Invalid email");
        }
        return new self($source);
    }
    
    public function getDomain(): string { /* ... */ }
    public function isGmail(): bool { /* ... */ }
}

// Utilisation
$email = EmailAddress::from('john@example.com');
echo $email->getDomain(); // 'example.com'
```

**Caractéristiques :**
- ✅ Immutable
- ✅ Auto-validant
- ✅ Comportement métier
- ✅ Pas d'identité propre
- ❌ Pas d'effets de bord

👉 **[Documentation complète des Value Objects](concepts/VALUE_OBJECTS.md)**

---

### 2. [Records](concepts/RECORDS.md)

Les Records sont des **structures de données internes** pour la communication entre les couches de l'application.

```php
use AndyDefer\DomainStructures\Abstracts\AbstractRecord;
use AndyDefer\DomainStructures\Traits\Hydratable;

final class UserRecord extends AbstractRecord
{
    use Hydratable;
    
    public function __construct(
        public readonly ?int $id,
        public readonly string $name,
        public readonly EmailAddress $email,
        public readonly UserRole $role,
        public readonly Iso8601DateTime $createdAt,
    ) {}
}

// Hydratation automatique depuis une source externe
$user = UserRecord::from([
    'id' => 123,
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'role' => 'admin',
    'created_at' => '2024-01-01T12:00:00+00:00'
]);
```

**Caractéristiques :**
- ✅ Immutable
- ✅ Hydratation automatique
- ✅ Normalisation en `snake_case`
- ✅ Support JSON
- ❌ Pas de logique métier

👉 **[Documentation complète des Records](concepts/RECORDS.md)**

---

### 3. [Data DTO](concepts/DATA.md)

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
        public readonly ProductDataCollection $purchasedProducts,
    ) {}
}

// Réponse API
$userData = UserData::from($userRecord);
return response()->json($userData); // camelCase pour le client
```

**Caractéristiques :**
- ✅ Exclusivement pour les réponses API
- ✅ Normalisation en `camelCase`
- ✅ Collections typées concrètes
- ❌ Aucun type primitif autorisé

👉 **[Documentation complète des Data DTO](concepts/DATA.md)**

---

### 4. [Typed Collections](concepts/TYPED_COLLECTIONS.md)

Les Typed Collections remplacent les tableaux bruts par des collections **type-safe**.

```php
// ❌ Tableau brut
public readonly array $items; // On ne sait pas ce qu'il contient

// ✅ Collection typée
public readonly ProductRecordCollection $items; // TypedCollection<ProductRecord>

// Création d'une collection spécialisée
final class ProductRecordCollection extends TypedCollection
{
    public function __construct()
    {
        parent::__construct(ProductRecord::class);
    }
    
    public function getFeatured(): self
    {
        return $this->filter(fn(ProductRecord $product) => $product->isFeatured);
    }
}

// Utilisation
$products = new ProductRecordCollection();
$products->add($product1, $product2, $product3);
$featured = $products->getFeatured();
```

**Collections utilitaires prédéfinies :**
- `StringTypedCollection`
- `IntTypedCollection`
- `FloatTypedCollection`
- `BoolTypedCollection`
- `NumberTypedCollection` (int|float)

👉 **[Documentation complète des Typed Collections](concepts/TYPED_COLLECTIONS.md)**

---

### 5. [DataObject](concepts/DATA_OBJECTS.md)

DataObject est un **normalisateur d'accès aux données** qui sert de pont entre les sources externes et le système d'hydratation.

```php
use AndyDefer\DomainStructures\Utils\DataObject;

// Source externe (snake_case)
$apiData = [
    'user_id' => 123,
    'first_name' => 'John',
    'last_name' => 'Doe'
];

// Normalisation
$normalized = DataObject::from($apiData);

// Accès indifférent camelCase/snake_case
echo $normalized->userId;      // 123
echo $normalized->first_name;  // "John"
echo $normalized->lastName;    // "Doe"

// Transformation immuable
$updated = $normalized->with('email', 'john@example.com');
```

**Caractéristiques :**
- ✅ Normalisation de l'accès (camelCase/snake_case)
- ✅ Conversion récursive des tableaux
- ✅ Méthodes `with()`, `merge()`, `without()`
- ✅ Intégration avec Hydratable
- ❌ Pas d'immutabilité stricte

👉 **[Documentation complète de DataObject](concepts/DATA_OBJECTS.md)**

---

## 🔧 Systèmes transverses

### [Hydratation automatique (Hydratable)](concepts/HYDRATABLE.md)

Le trait `Hydratable` analyse le constructeur d'une classe et l'hydrate automatiquement depuis n'importe quelle source.

```php
use AndyDefer\DomainStructures\Traits\Hydratable;

final class ProductRecord extends AbstractRecord
{
    use Hydratable;
    
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly float $price
    ) {}
}

// Une méthode pour toutes les sources
$product = ProductRecord::from($array);      // Tableau
$product = ProductRecord::from($object);     // Objet
$product = ProductRecord::fromJson($json);   // JSON (recommandé)

// Collection d'objets
$products = ProductRecord::collect($sources);
```

**Support :**
- ✅ Types scalaires (int, float, string, bool)
- ✅ Enums (BackedEnum)
- ✅ Unions types
- ✅ Transformable (hydratation récursive)
- ✅ Valeurs par défaut
- ✅ Nullabilité

👉 **[Documentation complète de Hydratable](concepts/HYDRATABLE.md)**

---

### [Normalisation](concepts/NORMALIZATION.md)

Le système de normalisation convertit récursivement les objets complexes en structures simples (tableaux, scalaires).

```php
use AndyDefer\DomainStructures\Normalizers\NormalizerChain;

$user = new UserRecord(id: 123, name: 'John', email: EmailAddress::from('john@example.com'));

// Normalisation automatique
$normalized = NormalizerChain::get()->normalize($user);
// Résultat : ['id' => 123, 'name' => 'John', 'email' => 'john@example.com']

// JSON direct
$json = json_encode($normalized);
```

**Normaliseurs disponibles :**
- `NullNormalizer` → null
- `ScalarNormalizer` → scalaires
- `EnumNormalizer` → valeur ou nom
- `RecordNormalizer` → tableau (camelCase → snake_case)
- `ValueObjectNormalizer` → valeur brute
- `DataNormalizer` → tableau (conserve camelCase)
- `TypedCollectionNormalizer` → tableau indexé
- `DataObjectNormalizer` → tableau associatif
- `ArrayNormalizer` → récursif

👉 **[Documentation complète de la Normalisation](concepts/NORMALIZATION.md)**

---

## 🚀 Utilisation

### Exemple complet

```php
// 1. Définir les Value Objects
final class EmailAddress extends AbstractValueObject { /* ... */ }
final class Iso8601DateTime extends AbstractValueObject { /* ... */ }

// 2. Définir l'Enum
enum UserRole: string { case ADMIN = 'admin'; case USER = 'user'; }

// 3. Définir le Record
final class UserRecord extends AbstractRecord
{
    use Hydratable;
    
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

// 5. Utilisation dans un Repository
class UserRepository
{
    public function find(int $id): ?UserRecord
    {
        $row = $this->db->fetchAssoc('SELECT * FROM users WHERE id = ?', [$id]);
        return $row ? UserRecord::from($row) : null;
    }
    
    public function findAll(): UserRecordCollection
    {
        $rows = $this->db->fetchAllAssoc('SELECT * FROM users');
        return UserRecord::collect($rows, UserRecordCollection::class);
    }
}

// 6. Utilisation dans un Controller
class UserController
{
    public function show(int $id): JsonResponse
    {
        $user = $this->userRepository->find($id);
        
        // Normalisation automatique pour l'API
        return response()->json(NormalizerChain::get()->normalize($user));
    }
}
```

---

## 💡 Bonnes pratiques

### 1. Value Objects

```php
// ✅ BON - Validation centralisée
final class EmailAddress extends AbstractValueObject { /* validation */ }

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
// ✅ BON - Collection spécialisée
public readonly ProductRecordCollection $products;

// ❌ MAUVAIS - TypedCollection générique
public readonly TypedCollection $products;
```

### 5. Hydratation

```php
// ✅ BON - fromJson() pour le JSON
$user = UserRecord::fromJson($jsonResponse);

// ✅ BON - from() pour les tableaux
$user = UserRecord::from($array);

// ❌ MAUVAIS - from() avec JSON (traité comme string)
$user = UserRecord::from($jsonResponse);
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
- **Contact** : Équipe de développement

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