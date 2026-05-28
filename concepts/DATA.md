# Data DTO (Data Transfer Object) - Documentation du Package

## Table des matières

1. [Définition et concepts fondamentaux](#1-définition-et-concepts-fondamentaux)
2. [Record vs Value Object vs Data](#2-record-vs-value-object-vs-data)
3. [Pourquoi utiliser des Data DTO ?](#3-pourquoi-utiliser-des-data-dto-)
4. [Les types autorisés dans une Data](#4-les-types-autorisés-dans-une-data)
5. [La philosophie : tout est concept, rien n'est primitif](#5-la-philosophie--tout-est-concept-rien-nest-primitif)
6. [Les classes fondamentales](#6-les-classes-fondamentales)
7. [Créer sa première Data DTO](#7-créer-sa-première-data-dto)
8. [Les collections typées pour les Data](#8-les-collections-typées-pour-les-data)
9. [Les méthodes fondamentales](#9-les-méthodes-fondamentales)
10. [Hydratation : `from()` et `fromJson()`](#10-hydratation--from-et-fromjson)
11. [Collections de Data : `collect()`](#11-collections-de-data--collect)
12. [Normalisation : camelCase pour l'API](#12-normalisation--camelcase-pour-lapi)
13. [Consommation par les clients](#13-consommation-par-les-clients)
    - [13.1. TypeScript (Frontend Web)](#131-typescript-frontend-web)
    - [13.2. Kotlin (Android)](#132-kotlin-android)
    - [13.3. Swift (iOS)](#133-swift-ios)
14. [Bonnes pratiques](#14-bonnes-pratiques)
15. [Récapitulatif des contraintes](#15-récapitulatif-des-contraintes)

---

## 1. Définition et concepts fondamentaux

Une **Data DTO** (Data Transfer Object) est une structure **pure**, **immutable** et **type-safe** qui représente une réponse API. Elle garantit un contrat explicite entre le serveur et tous ses clients (mobile, frontend, microservices).

```
Record (interne) → Data DTO (API) → Réponse JSON → Clients (TypeScript, Kotlin, Swift)
Value Object (interne) → Data DTO (API) → Réponse JSON → Clients
```

> ⚠️ **Les Data sont EXCLUSIVEMENT pour les réponses API. Pour la communication interne (Services, Repositories, Tasks), utilisez les Records.**

---

## 2. Record vs Value Object vs Data

| Aspect | Record | Value Object | Data DTO |
|--------|--------|--------------|----------|
| **Usage principal** | Communication interne | Concepts métier | Réponses HTTP |
| **Logique métier** | ❌ Aucune | ✅ Peut en avoir | ❌ Transformation uniquement |
| **Validation** | Optionnelle | OBLIGATOIRE | Optionnelle |
| **Constructeur** | Public | Privé (factory) | Public |
| **Types autorisés** | VOs, Enums, Collections, Records | VOs, Enums, Collections | VOs, Enums, Data, **Collections typées concrètes** |
| **Peut contenir des Records** | ✅ Oui | ❌ Interdit | ✅ Oui |
| **Peut contenir des Data** | ❌ Interdit | ❌ Interdit | ✅ Oui |
| **Nommage des clés** | `snake_case` | `camelCase` | `camelCase` |
| **Destination** | Base de données / Services | Logique métier | Client (JSON) |

---

## 3. Pourquoi utiliser des Data DTO ?

### 3.1. Le problème des types primitifs

```php
// ❌ Mauvaise pratique - types primitifs non explicites
final class UserData extends AbstractData
{
    public function __construct(
        public readonly int $id,           // Qu'est-ce que c'est ?
        public readonly string $name,      // Une chaîne ?
        public readonly string $email,     // Une chaîne aussi ?
        public readonly array $products,   // Quoi ? Quels produits ?
    ) {}
}

// ✅ Bonne pratique - tous les concepts sont explicités
final class UserData extends AbstractData
{
    public function __construct(
        public readonly UserId $id,                    // Concept : identifiant
        public readonly PersonName $name,              // Concept : nom
        public readonly EmailAddress $email,           // Concept : email validé
        public readonly ProductDataCollection $products, // Collection typée ! On sait que c'est ProductData
    ) {}
}
```

### 3.2. Les collections doivent être typées

> **⚠️ RÈGLE IMPORTANTE : On n'utilise JAMAIS `TypedCollection` directement dans une Data. On utilise TOUJOURS une collection spécialisée qui étend `TypedCollection`.**

```php
// ❌ MAUVAIS - On ne sait pas ce que contient la collection
public readonly TypedCollection $products;

// ✅ BON - La collection dit explicitement ce qu'elle contient
public readonly ProductDataCollection $products;
public readonly UserDataCollection $users;
public readonly OrderDataCollection $orders;
```

### 3.3. Exemple de collection spécialisée

```php
<?php

declare(strict_types=1);

namespace App\Collections;

use AndyDefer\DomainStructures\Collections\Core\TypedCollection;
use App\Data\ProductData;

/**
 * Collection qui ne peut contenir QUE des ProductData.
 * 
 * @extends TypedCollection<ProductData>
 */
final class ProductDataCollection extends TypedCollection
{
    public function __construct()
    {
        parent::__construct(ProductData::class);
    }
    
    // Méthodes utilitaires spécifiques aux produits
    public function getFeatured(): self
    {
        return $this->filter(fn(ProductData $product) => $product->isFeatured === true);
    }
    
    public function getTotalPrice(): Price
    {
        $total = $this->reduce(fn($carry, ProductData $product) => $carry + $product->price->getAmount(), 0);
        return Price::from(['amount' => $total, 'currency' => 'EUR']);
    }
}
```

---

## 4. Les types autorisés dans une Data

### 4.1. Vue d'ensemble

Les **seuls** types autorisés dans une Data sont :

```php
// 1. Enums
UnitEnum::class

// 2. Value Objects
AbstractValueObject::class

// 3. Autres Data (imbrication)
AbstractData::class

// 4. Collections typées CONCRÈTES (qui étendent TypedCollection)
// ⚠️ JAMAIS TypedCollection directement !
```

### 4.2. Ce qui est STRICTEMENT INTERDIT

```php
// ❌ AUCUN TYPE PRIMITIF N'EST AUTORISÉ

public readonly int $id;                // Interdit !
public readonly string $name;           // Interdit !
public readonly float $price;           // Interdit !
public readonly bool $isActive;         // Interdit !
public readonly array $list;            // Interdit !

// ❌ TYPEDCOLLECTION GÉNÉRIQUE INTERDITE
public readonly TypedCollection $items; // Interdit ! On ne sait pas ce qu'il contient

// ✅ TOUT DOIT ÊTRE UN CONCEPT

public readonly UserId $id;                     // Concept
public readonly PersonName $name;               // Concept
public readonly Price $price;                   // Concept
public readonly IsActive $isActive;             // Concept
public readonly ProductDataCollection $products; // Collection typée concrète
public readonly UserDataCollection $users;      // Collection typée concrète
```

### 4.3. Exemple concret

```php
use AndyDefer\DomainStructures\Abstracts\AbstractData;
use AndyDefer\DomainStructures\Abstracts\AbstractValueObject;
use UnitEnum;

final class UserData extends AbstractData
{
    public function __construct(
        // ✅ Enums
        public readonly UserRole $role,
        public readonly UserStatus $status,
        
        // ✅ Value Objects
        public readonly UserId $id,
        public readonly PersonName $name,
        public readonly EmailAddress $email,
        public readonly Price $price,
        public readonly Iso8601DateTime $createdAt,
        
        // ✅ Autre Data (imbrication)
        public readonly AddressData $address,
        
        // ✅ Collections typées CONCRÈTES
        public readonly ProductDataCollection $purchasedProducts,
        public readonly OrderDataCollection $orders,
        public readonly UserDataCollection $friends,
    ) {}
}
```

---

## 5. La philosophie : tout est concept, rien n'est primitif

> **Dans une Data DTO, on ne manipule JAMAIS de types primitifs. Chaque donnée est représentée par un concept explicite.**

| Au lieu de... | Utilisez... |
|---------------|-------------|
| `int $id` | `UserId $id` ou `Uuid $id` |
| `string $name` | `PersonName $name` ou `ProductName $name` |
| `string $email` | `EmailAddress $email` |
| `float $price` | `Price $price` |
| `string $date` | `Iso8601DateTime $date` |
| `array $products` | `ProductDataCollection $products` |
| `TypedCollection $items` | `ProductDataCollection` ou `UserDataCollection` |

---

## 6. Les classes fondamentales

### 6.1. AbstractData

La classe abstraite que **toute Data DTO doit étendre** :

```php
<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Abstracts;

use AndyDefer\DomainStructures\Interfaces\Transformable;
use AndyDefer\DomainStructures\Normalizers\NormalizerChain;
use AndyDefer\DomainStructures\Traits\Hydratable;

abstract class AbstractData implements Transformable
{
    use Hydratable;

    public function __toString(): string
    {
        return json_encode(NormalizerChain::get()->normalize($this), JSON_THROW_ON_ERROR);
    }
}
```

### 6.2. DataCollection (collection de base pour les Data)

```php
<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Collections\Core;

use AndyDefer\DomainStructures\Abstracts\AbstractData;
use AndyDefer\DomainStructures\Abstracts\AbstractTypedCollection;

/**
 * Type-safe collection that can ONLY contain AbstractData objects or its concrete implementations.
 *
 * @template TData of AbstractData
 * @extends AbstractTypedCollection<TData>
 */
final class DataCollection extends AbstractTypedCollection
{
    /**
     * @param  class-string<TData>  ...$allowedConcreteTypes
     */
    public function __construct(string ...$allowedConcreteTypes)
    {
        if (empty($allowedConcreteTypes)) {
            throw new \InvalidArgumentException('At least one concrete Data class must be provided');
        }

        foreach ($allowedConcreteTypes as $type) {
            if (!is_subclass_of($type, AbstractData::class)) {
                throw new \InvalidArgumentException(sprintf(
                    'Type "%s" must be a subclass of %s',
                    $type,
                    AbstractData::class
                ));
            }
        }

        parent::__construct(...$allowedConcreteTypes);
    }
}
```

---

## 7. Créer sa première Data DTO

### 7.1. Définir les Value Objects et Enums

```php
// Value Objects
final class UserId extends AbstractValueObject
{
    private function __construct(public readonly string $value) {}
    
    public static function from(mixed $source): static
    {
        if ($source instanceof self) return $source;
        if (!is_string($source) || !preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $source)) {
            throw new InvalidArgumentException('Invalid UUID');
        }
        return new self($source);
    }
    
    public function getValue(): string { return $this->value; }
}

final class PersonName extends AbstractValueObject
{
    private function __construct(public readonly string $value) {}
    
    public static function from(mixed $source): static
    {
        if ($source instanceof self) return $source;
        if (!is_string($source) || strlen($source) < 2 || strlen($source) > 100) {
            throw new InvalidArgumentException('Invalid name length');
        }
        return new self($source);
    }
    
    public function getValue(): string { return $this->value; }
}

final class EmailAddress extends AbstractValueObject { /* ... */ }
final class Iso8601DateTime extends AbstractValueObject { /* ... */ }
final class Price extends AbstractValueObject { /* ... */ }

// Enums
enum UserRole: string
{
    case ADMIN = 'admin';
    case USER = 'user';
    case GUEST = 'guest';
}
```

### 7.2. Définir les collections typées

```php
<?php

declare(strict_types=1);

namespace App\Collections;

use AndyDefer\DomainStructures\Collections\Core\DataCollection;
use App\Data\ProductData;

/**
 * Collection qui ne peut contenir QUE des ProductData.
 * 
 * @extends DataCollection<ProductData>
 */
final class ProductDataCollection extends DataCollection
{
    public function __construct()
    {
        parent::__construct(ProductData::class);
    }
    
    public function getFeatured(): self
    {
        return $this->filter(fn(ProductData $product) => $product->isFeatured === true);
    }
    
    public function getTotalPrice(): Price
    {
        $total = $this->reduce(fn($carry, ProductData $product) => $carry + $product->price->getAmount(), 0);
        return Price::from(['amount' => $total, 'currency' => 'EUR']);
    }
}
```

### 7.3. Définir la Data

```php
<?php

declare(strict_types=1);

namespace App\Data;

use AndyDefer\DomainStructures\Abstracts\AbstractData;
use App\Collections\ProductDataCollection;
use App\ValueObjects\EmailAddress;
use App\ValueObjects\Iso8601DateTime;
use App\ValueObjects\PersonName;
use App\ValueObjects\Price;
use App\ValueObjects\UserId;

final class UserData extends AbstractData
{
    public function __construct(
        public readonly UserId $id,
        public readonly PersonName $name,
        public readonly EmailAddress $email,
        public readonly Price $totalSpent,
        public readonly Iso8601DateTime $createdAt,
        public readonly UserRole $role,
        public readonly ProductDataCollection $purchasedProducts,
    ) {}
}
```

---

## 8. Les collections typées pour les Data

### 8.1. Principe

> **⚠️ On n'utilise JAMAIS `TypedCollection` ou `DataCollection` directement dans une Data. On crée TOUJOURS une collection spécialisée.**

```php
// ❌ MAUVAIS - On ne sait pas ce qu'il contient
public readonly DataCollection $products;

// ✅ BON - La collection dit explicitement ce qu'elle contient
public readonly ProductDataCollection $products;
public readonly UserDataCollection $users;
```

### 8.2. Créer une collection spécialisée

```php
<?php

declare(strict_types=1);

namespace App\Collections;

use AndyDefer\DomainStructures\Collections\Core\DataCollection;
use App\Data\UserData;

/**
 * @extends DataCollection<UserData>
 */
final class UserDataCollection extends DataCollection
{
    public function __construct()
    {
        parent::__construct(UserData::class);
    }
    
    public function getAdmins(): self
    {
        return $this->filter(fn(UserData $user) => $user->role === UserRole::ADMIN);
    }
    
    public function getActive(): self
    {
        return $this->filter(fn(UserData $user) => $user->status === UserStatus::ACTIVE);
    }
}
```

### 8.3. Utilisation

```php
final class ListUsersAction extends AbstractAction
{
    protected function handle(Recordable $request): JsonResponse
    {
        $usersRecord = $this->userService->getUsers($request);
        
        // Transformation : UserRecord → UserData
        $userDataCollection = new UserDataCollection();
        foreach ($usersRecord->users->all() as $userRecord) {
            $userDataCollection->add(UserData::fromRecord($userRecord));
        }
        
        $admins = $userDataCollection->getAdmins();
        
        return $this->json(UserData::collect($admins));
    }
}
```

---

## 9. Les méthodes fondamentales

### 9.1. `from(mixed $source): static`

Hydrate une Data depuis n'importe quelle source :

```php
$userData = UserData::from([
    'id' => '123e4567-e89b-12d3-a456-426614174000',
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'total_spent' => ['amount' => 1500.00, 'currency' => 'EUR'],
    'created_at' => '2024-01-01T12:00:00+00:00',
    'role' => 'admin',
    'purchased_products' => [...]
]);
```

### 9.2. `fromJson(string $json): static`

```php
$json = '{"id":"123...","name":"John Doe","email":"john@example.com","totalSpent":{"amount":1500,"currency":"EUR"},"createdAt":"2024-01-01T12:00:00+00:00","role":"admin","purchasedProducts":[...]}';
$userData = UserData::fromJson($json);
```

### 9.3. `collect(iterable $sources, string $collectionClass): AbstractTypedCollection`

```php
$sources = [
    ['id' => '...', 'name' => 'John', 'email' => 'john@example.com'],
    ['id' => '...', 'name' => 'Jane', 'email' => 'jane@example.com'],
];

$users = UserData::collect($sources, UserDataCollection::class);
// $users est un UserDataCollection
```

---

## 10. Normalisation : camelCase pour l'API

```php
$userData = new UserData(
    id: UserId::from('123...'),
    name: PersonName::from('John Doe'),
    email: EmailAddress::from('john@example.com'),
    totalSpent: Price::from(['amount' => 1500, 'currency' => 'EUR']),
    createdAt: Iso8601DateTime::from('2024-01-01T12:00:00+00:00'),
    role: UserRole::ADMIN,
    purchasedProducts: new ProductDataCollection()
);

$normalized = NormalizerChain::get()->normalize($userData);
// Résultat :
// {
//     "id": "123...",
//     "name": "John Doe",
//     "email": "john@example.com",
//     "totalSpent": {"amount": 1500, "currency": "EUR"},
//     "createdAt": "2024-01-01T12:00:00+00:00",
//     "role": "admin",
//     "purchasedProducts": [...]
// }
```

---

## 11. Consommation par les clients

### 11.1. TypeScript (Frontend Web)

```typescript
// Value Objects miroir
type UserId = string;  // UUID
type PersonName = string;
type EmailAddress = string;
type Iso8601DateTime = string;

// Price est un objet
interface Price {
    amount: number;
    currency: 'EUR' | 'USD' | 'GBP';
}

// Enum miroir
enum UserRole {
    ADMIN = 'admin',
    USER = 'user',
    GUEST = 'guest'
}

// Collection miroir (tableau typé)
type ProductDataCollection = ProductData[];

// Data miroir
interface UserData {
    id: UserId;
    name: PersonName;
    email: EmailAddress;
    totalSpent: Price;
    createdAt: Iso8601DateTime;
    role: UserRole;
    purchasedProducts: ProductDataCollection;
}

// Utilisation
async function fetchUser(id: string): Promise<UserData> {
    const response = await fetch(`/api/users/${id}`);
    const data: UserData = await response.json();
    
    console.log(data.name); // "John Doe"
    console.log(data.role); // "admin"
    console.log(data.totalSpent.amount); // 1500
    
    if (data.role === UserRole.ADMIN) {
        console.log('Admin user');
    }
    
    return data;
}
```

### 11.2. Kotlin (Android)

```kotlin
// Value Objects miroir
typealias UserId = String
typealias PersonName = String
typealias EmailAddress = String
typealias Iso8601DateTime = String

// Price est une data class
data class Price(
    val amount: Double,
    val currency: Currency
)

enum class Currency {
    EUR, USD, GBP
}

// Enum miroir
enum class UserRole {
    ADMIN, USER, GUEST
}

// Collection miroir
typealias ProductDataCollection = List<ProductData>

// Data miroir
data class UserData(
    val id: UserId,
    val name: PersonName,
    val email: EmailAddress,
    val totalSpent: Price,
    val createdAt: Iso8601DateTime,
    val role: UserRole,
    val purchasedProducts: ProductDataCollection
)

// Service API
interface ApiService {
    @GET("/users/{id}")
    suspend fun getUser(@Path("id") id: String): UserData
}

// Utilisation
class UserViewModel(
    private val apiService: ApiService
) : ViewModel() {
    fun loadUser(id: String) {
        viewModelScope.launch {
            val user = apiService.getUser(id)
            
            println(user.name) // "John Doe"
            println(user.role) // ADMIN
            
            when (user.role) {
                UserRole.ADMIN -> showAdminPanel()
                UserRole.USER -> showUserPanel()
                UserRole.GUEST -> showGuestPanel()
            }
            
            user.purchasedProducts.forEach { product ->
                println(product.name)
            }
        }
    }
}
```

### 11.3. Swift (iOS)

```swift
// Value Objects miroir
typealias UserId = String
typealias PersonName = String
typealias EmailAddress = String
typealias Iso8601DateTime = String

// Price est une struct
struct Price: Codable {
    let amount: Double
    let currency: Currency
}

enum Currency: String, Codable {
    case eur = "EUR"
    case usd = "USD"
    case gbp = "GBP"
}

// Enum miroir
enum UserRole: String, Codable {
    case admin = "admin"
    case user = "user"
    case guest = "guest"
}

// Collection miroir
typealias ProductDataCollection = [ProductData]

// Data miroir
struct UserData: Codable {
    let id: UserId
    let name: PersonName
    let email: EmailAddress
    let totalSpent: Price
    let createdAt: Iso8601DateTime
    let role: UserRole
    let purchasedProducts: ProductDataCollection
}

// Service API
class APIService {
    func getUser(id: String) async throws -> UserData {
        let url = URL(string: "https://api.example.com/users/\(id)")!
        let (data, _) = try await URLSession.shared.data(from: url)
        return try JSONDecoder().decode(UserData.self, from: data)
    }
}

// Utilisation
class UserViewModel: ObservableObject {
    @Published var user: UserData?
    
    func loadUser(id: String) {
        Task {
            let user = try await apiService.getUser(id: id)
            await MainActor.run {
                self.user = user
                
                print(user.name) // "John Doe"
                print(user.role.rawValue) // "admin"
                
                switch user.role {
                case .admin:
                    showAdminPanel()
                case .user:
                    showUserPanel()
                case .guest:
                    showGuestPanel()
                }
            }
        }
    }
}
```

---

## 12. Bonnes pratiques

### 12.1. Toujours utiliser des Value Objects

```php
// ✅ BON
public readonly EmailAddress $email;
public readonly Iso8601DateTime $createdAt;
public readonly Price $price;

// ❌ MAUVAIS
public readonly string $email;
public readonly string $createdAt;
public readonly float $price;
```

### 12.2. Toujours utiliser des collections typées concrètes

```php
// ✅ BON
public readonly ProductDataCollection $products;
public readonly UserDataCollection $users;

// ❌ MAUVAIS
public readonly TypedCollection $products;
public readonly DataCollection $users;
public readonly array $products;
```

### 12.3. Nommer les propriétés en camelCase

```php
// ✅ BON
public readonly Iso8601DateTime $createdAt;
public readonly Iso8601DateTime $emailVerifiedAt;

// ❌ MAUVAIS
public readonly Iso8601DateTime $created_at;
```

### 12.4. Utiliser des Enums pour les valeurs fixes

```php
// ✅ BON
public readonly UserRole $role;

// ❌ MAUVAIS
public readonly string $role;
```

---

## 13. Récapitulatif des contraintes

| Contrainte | Règle |
|------------|-------|
| **Héritage** | ✅ DOIT étendre `AbstractData` |
| **Usage** | ✅ Réponses API uniquement |
| **Types autorisés** | ✅ Enums, Value Objects, Data, **Collections typées concrètes** |
| **Types INTERDITS** | ❌ int, string, float, bool, null, array, DateTime, **TypedCollection**, **DataCollection** |
| **Collections** | ✅ TOUJOURS une classe concrète (`ProductDataCollection`, `UserDataCollection`) |
| **Propriétés** | `public readonly` |
| **Nommage** | `camelCase` |
| **Logique** | ❌ Pas de logique métier |

---

## En résumé : La règle d'or

> **Dans une Data DTO, on ne manipule JAMAIS de types primitifs ni de collections génériques. Chaque donnée est représentée par un concept explicite (Value Object, Enum, Data, ou Collection typée concrète).**

```php
// La Data parfaite - AUCUN type primitif, AUCUNE collection générique !
final class UserData extends AbstractData
{
    public function __construct(
        public readonly UserId $id,
        public readonly PersonName $name,
        public readonly EmailAddress $email,
        public readonly Price $totalSpent,
        public readonly Iso8601DateTime $createdAt,
        public readonly UserRole $role,
        public readonly UserStatus $status,
        public readonly AddressData $address,
        public readonly ProductDataCollection $purchasedProducts,  // Collection typée concrète
        public readonly OrderDataCollection $orders,              // Collection typée concrète
        public readonly UserDataCollection $friends,              // Collection typée concrète
    ) {}
}
```

**Résultat :** Une API documentée, type-safe, prédictible, et consommable par n'importe quel client sans ambiguïté.