# Data DTO (Data Transfer Object) - Documentation (Version finale)

## 1. Définition

Une **Data DTO** (Data Transfer Object) est une classe **pure** et **immutable** qui représente une structure de données **uniquement pour les réponses HTTP**.

> ⚠️ **Les Data sont exclusivement pour les réponses API. Pour la communication interne (Services, Repositories, Tasks), utilisez les Records ou les Value Objects.**

```
Record (interne) → Data DTO (API) → Réponse JSON
Value Object (interne) → Data DTO (API) → Réponse JSON
```

---

## 2. Record vs Value Object vs Data : Les différences fondamentales

| Aspect | Record | Value Object | Data DTO |
|--------|--------|--------------|----------|
| **Usage principal** | Communication interne (Services, Repositories) | Concepts métier | Réponses HTTP |
| **Logique métier** | ❌ Aucune | ✅ Peut en avoir | ❌ Transformation uniquement |
| **Validation** | Optionnelle | OBLIGATOIRE | Optionnelle |
| **Constructeur** | Public | Privé (factory) | Public |
| **Création** | `new Record(...)` | `VO::fromXxx(...)` | `Data::fromRecord()` ou `Data::fromValueObject()` |
| **Destination** | Base de données / Services | Logique métier | Client (JSON) |
| **Peut contenir** | VO, Records, TypedCollection | VO, scalaires, TypedCollection | Records, VO, Data, TypedCollection |
| **Nommage** | `UserRecord` | `EmailAddress`, `Money` | `UserData` |
| **Type de clés** | `snake_case` | `camelCase` | `camelCase` |

---

## 3. Problématique à laquelle les Data répondent

### 3.1. Le problème de la sérialisation

Dans une application moderne, l'API peut être consommée par différents clients :

| Client | Langage / Framework |
|--------|---------------------|
| Application mobile | Kotlin (Android), Swift (iOS) |
| Application desktop | Rust, C#, Python |
| Frontend web | TypeScript, JavaScript, React, Vue |
| Microservices | Go, Java, Rust |

**Sans une structure de données standardisée, chaque client doit deviner :**
- La structure exacte de la réponse
- Les types des champs (string, int, bool, enum)
- Les champs optionnels vs obligatoires
- Les conventions de nommage (camelCase, snake_case)

### 3.2. La solution : Les Data DTO

> **Les Data DTO fournissent un contrat explicite entre le serveur et tous ses clients, quel que soit le langage.**

```php
use AndyDefer\DomainStructures\AbstractData;

final class UserData extends AbstractData
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $email,
        public readonly UserRole $role,
        public readonly array $recentPosts,
        public readonly string $createdAt,
    ) {}
}

enum UserRole: string
{
    case ADMIN = 'admin';
    case USER = 'user';
}
```

**Réponse JSON générée :**
```json
{
    "id": "123",
    "name": "John Doe",
    "email": "john@example.com",
    "role": "admin",
    "recentPosts": [...],
    "createdAt": "2024-01-15T10:30:00Z"
}
```

### 3.3. Structure miroir dans les clients

Grâce à la standardisation des Data DTO, les clients peuvent créer des **structures miroir** parfaitement alignées.

#### TypeScript (Frontend)

```typescript
// Structure miroir exacte du backend
interface UserData {
    id: string;
    name: string;
    email: string;
    role: 'admin' | 'user';
    recentPosts: PostData[];
    createdAt: string;  // ISO 8601
}

// Utilisation type-safe
async function fetchUser(id: string): Promise<UserData> {
    const response = await fetch(`/api/users/${id}`);
    const data: UserData = await response.json();
    return data;  // TypeScript garantit la structure !
}
```

#### Kotlin (Android)

```kotlin
// Structure miroir
data class UserData(
    val id: String,
    val name: String,
    val email: String,
    val role: UserRole,
    val recentPosts: List<PostData>,
    val createdAt: String,
)

enum class UserRole {
    ADMIN, USER
}

// Utilisation type-safe
val user: UserData = api.getUser(1)
when (user.role) {
    UserRole.ADMIN -> showAdminPanel()
    UserRole.USER -> showUserPanel()
}
```

#### Swift (iOS)

```swift
// Structure miroir
struct UserData: Codable {
    let id: String
    let name: String
    let email: String
    let role: UserRole
    let recentPosts: [PostData]
    let createdAt: String
}

enum UserRole: String, Codable {
    case admin = "admin"
    case user = "user"
}
```

---

## 4. Les classes fondamentales

### 4.1. DataInterface

```php
<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures;

interface DataInterface
{
    public function toArray(): array;
    public static function collect(TypedCollection $collection): array;
}
```

### 4.2. AbstractData

La classe abstraite que **toute Data DTO doit étendre** :

```php
<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures;

use AndyDefer\DomainStructures\Collections\TypedCollection;
use Illuminate\Support\Collection as LaravelCollection;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionProperty;
use UnitEnum;

abstract class AbstractData implements DataInterface
{
    public function toArray(): array
    {
        $reflection = new ReflectionClass($this);
        $properties = $reflection->getProperties(ReflectionProperty::IS_PUBLIC);
        $result = [];

        foreach ($properties as $property) {
            $value = $property->getValue($this);
            $key = $property->getName();
            $result[$key] = $this->transformValue($value);
        }

        return $result;
    }

    /**
     * Transforme une TypedCollection en tableau de Data DTOs.
     * 
     * ⚠️ La TypedCollection doit contenir exclusivement des Records, 
     * des Value Objects, ou une combinaison des deux.
     * 
     * @param TypedCollection $collection Collection d'entrées (Records ou VOs)
     * @return array<int, static> Tableau de Data DTOs
     * 
     * @throws InvalidArgumentException Si un élément de la collection n'est 
     *         ni un Record ni un Value Object
     */
    public static function collect(TypedCollection $collection): array
    {
        $result = [];

        foreach ($collection->all() as $item) {
            if ($item instanceof AbstractRecord) {
                $result[] = static::fromRecord($item);
            } elseif ($item instanceof AbstractValueObject) {
                $result[] = static::fromValueObject($item);
            } else {
                throw new InvalidArgumentException(
                    sprintf(
                        'TypedCollection must contain only AbstractRecord or AbstractValueObject. Got: %s',
                        is_object($item) ? $item::class : gettype($item)
                    )
                );
            }
        }

        return $result;
    }

    private function transformValue(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof UnitEnum) {
            return $this->transformEnum($value);
        }

        if (is_array($value)) {
            return array_map(fn($item) => $this->transformValue($item), $value);
        }

        if ($value instanceof LaravelCollection) {
            return $value->map(fn($item) => $this->transformValue($item))->toArray();
        }

        if ($value instanceof DataInterface) {
            return $value->toArray();
        }

        if ($value instanceof ValueObjectInterface) {
            return $value->toArray();
        }

        if ($value instanceof AbstractRecord) {
            return $value->toArray();
        }

        if ($value instanceof TypedCollection) {
            return $value->toArray();
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d\TH:i:s\Z');
        }

        return $value;
    }

    private function transformEnum(UnitEnum $enum): string|int
    {
        if ($enum instanceof \BackedEnum) {
            return $enum->value;
        }

        return $enum->name;
    }

    private static function extractPublicProperties(object $object): array
    {
        $reflection = new ReflectionClass($object);
        $properties = $reflection->getProperties(ReflectionProperty::IS_PUBLIC);
        $result = [];

        foreach ($properties as $property) {
            $result[$property->getName()] = $property->getValue($object);
        }

        return $result;
    }
}
```

### 4.3. Ce qu'offre AbstractData

| Méthode | Description | Exemple |
|---------|-------------|---------|
| `toArray()` | Convertit récursivement la Data en tableau | `$userData->toArray()` |
| `collect(TypedCollection $collection)` | Transforme une TypedCollection en tableau de Data DTOs | `UserData::collect($userRecords)` |

**Comportement de `toArray()` :**
- ✅ Garde les noms de propriétés en **camelCase**
- ✅ Convertit les Enums en leur valeur (`string`/`int`)
- ✅ Convertit récursivement les Data imbriquées
- ✅ Convertit les Value Objects via `toArray()`
- ✅ Convertit les Records via `toArray()`
- ✅ Convertit les `TypedCollection` en `array`
- ✅ Convertit les `Collection` Laravel en `array`
- ✅ Convertit `DateTime`/`Carbon` en **ISO 8601** (`Y-m-d\TH:i:s\Z`)

**Comportement de `collect()` :**
- ⚠️ **Accepte UNIQUEMENT une `TypedCollection`**
- ⚠️ **La collection doit contenir exclusivement des `AbstractRecord` ou `AbstractValueObject`**
- ✅ Appelle automatiquement `fromRecord()` ou `fromValueObject()` selon le type
- ❌ **Ne peut pas prendre de `array` brut, de `Collection` Laravel, ou d'itérable générique**

---

## 5. Création des Data DTO

### 5.1. Règle fondamentale

> **Une Data DTO doit être créée à partir d'un Record ou d'un Value Object via les méthodes `fromRecord()` ou `fromValueObject()`.**

### 5.2. Les deux méthodes de création

```php
final class UserData extends AbstractData
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $email,
        public readonly string $createdAt,
    ) {}
    
    // Méthode 1 : Création à partir d'un Record
    public static function fromRecord(UserRecord $record): self
    {
        return new self(
            id: (string) $record->id,
            name: $record->name,
            email: $record->email,
            createdAt: $record->createdAt,
        );
    }
    
    // Méthode 2 : Création à partir d'un Value Object
    public static function fromValueObject(UserProfile $profile): self
    {
        return new self(
            id: (string) $profile->userId->value,
            name: $profile->name->value,
            email: $profile->email->value,
            createdAt: $profile->createdAt->format('Y-m-d\TH:i:s\Z'),
        );
    }
}
```

### 5.3. Ce que `fromRecord()` et `fromValueObject()` doivent faire

```php
// ✅ BON - Uniquement transformation de types
public static function fromRecord(UserRecord $record): self
{
    return new self(
        id: (string) $record->id,      // cast
        name: $record->name,           // simple passage
        email: $record->email,         // simple passage
        createdAt: $record->createdAt, // déjà string ISO
        role: $record->role,           // Enum
    );
}

// ❌ INTERDIT - Logique conditionnelle ou calcul
public static function fromRecord(UserRecord $record): self
{
    if ($record->role->isAdmin()) {  // ❌
        return new self(...);
    }
    return new self(...);
}
```

---

## 6. Collections typées pour les Data (NOUVEAU)

### 6.1. Principe

> **Pour transformer une collection d'enregistrements en collection de Data, vous devez utiliser une `TypedCollection` personnalisée qui étend `TypedCollection` et restreint le type aux Data souhaitées.**

### 6.2. Création d'une collection typée pour les Data

```php
use AndyDefer\DomainStructures\Collections\TypedCollection;

// Collection qui n'accepte QUE des UserData
final class UserTypedCollection extends TypedCollection
{
    public function __construct()
    {
        parent::__construct(UserData::class);
    }
    
    // Méthodes utilitaires spécifiques aux utilisateurs
    public function getAdminEmails(): array
    {
        return $this->filter(fn(UserData $user) => $user->role === UserRole::ADMIN)
                    ->map(fn(UserData $user) => $user->email)
                    ->toArray();
    }
    
    public function getActiveUsers(): self
    {
        return $this->filter(fn(UserData $user) => $user->isActive === true);
    }
}
```

### 6.3. Transformation d'une collection de Records en collection de Data

```php
final class ListUsersAction extends AbstractAction
{
    protected function handle(Recordable $request): JsonResponse
    {
        /** @var ListUsersRecord $request */
        $usersRecord = $this->userService->getUsers($request);
        
        // $usersRecord->users est une TypedCollection<UserRecord>
        
        // Étape 1 : Créer une collection vide de UserData
        $userDataCollection = new UserTypedCollection();
        
        // Étape 2 : Transformer chaque UserRecord en UserData
        foreach ($usersRecord->users->all() as $userRecord) {
            $userDataCollection->add(UserData::fromRecord($userRecord));
        }
        
        // Étape 3 : Utiliser collect() sur la collection typée
        // ⚠️ collect() accepte UNIQUEMENT une TypedCollection
        $usersData = UserData::collect($userDataCollection);
        
        return $this->json($usersData);
    }
}
```

### 6.4. Version simplifiée avec une méthode de transformation

Pour éviter la boucle manuelle, vous pouvez ajouter une méthode statique à votre collection :

```php
final class UserTypedCollection extends TypedCollection
{
    public function __construct()
    {
        parent::__construct(UserData::class);
    }
    
    /**
     * Crée une collection de UserData à partir d'une collection de UserRecord.
     */
    public static function fromRecordCollection(TypedCollection $userRecords): self
    {
        $collection = new self();
        
        foreach ($userRecords->all() as $userRecord) {
            if (!$userRecord instanceof UserRecord) {
                throw new InvalidArgumentException('Expected UserRecord');
            }
            $collection->add(UserData::fromRecord($userRecord));
        }
        
        return $collection;
    }
    
    // Méthodes utilitaires...
}

// Utilisation simplifiée
final class ListUsersAction extends AbstractAction
{
    protected function handle(Recordable $request): JsonResponse
    {
        $usersRecord = $this->userService->getUsers($request);
        
        $userDataCollection = UserTypedCollection::fromRecordCollection($usersRecord->users);
        $usersData = UserData::collect($userDataCollection);
        
        return $this->json($usersData);
    }
}
```

### 6.5. Collection mixte : Records + Value Objects

```php
// Collection qui accepte des UserRecord ET des EmailAddress
final class UserSourceCollection extends TypedCollection
{
    public function __construct()
    {
        parent::__construct(UserRecord::class, EmailAddress::class);
    }
    
    public function toUserDataCollection(): UserTypedCollection
    {
        $result = new UserTypedCollection();
        
        foreach ($this->all() as $item) {
            if ($item instanceof UserRecord) {
                $result->add(UserData::fromRecord($item));
            } elseif ($item instanceof EmailAddress) {
                $result->add(UserData::fromEmailAddress($item));
            }
        }
        
        return $result;
    }
}
```

---

## 7. Exemples complets

### 7.1. Data simple à partir d'un Record

```php
final class UserData extends AbstractData
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $email,
        public readonly ?string $avatar = null,
        public readonly ?UserRole $role = null,
    ) {}
    
    public static function fromRecord(UserRecord $record): self
    {
        return new self(
            id: (string) $record->id,
            name: $record->name,
            email: $record->email,
            avatar: $record->avatar,
            role: $record->role,
        );
    }
}

// Collection typée pour UserData
final class UserTypedCollection extends TypedCollection
{
    public function __construct()
    {
        parent::__construct(UserData::class);
    }
}

final class ShowUserAction extends AbstractAction
{
    protected function handle(Recordable $request): JsonResponse
    {
        /** @var ShowUserRecord $request */
        $record = $this->userService->getUser($request);
        
        if ($record === null) {
            return $this->json(null, 404);
        }
        
        // Pour un seul élément, on n'utilise pas collect()
        $data = UserData::fromRecord($record);
        
        return $this->json($data);
    }
}

final class ListUsersAction extends AbstractAction
{
    protected function handle(Recordable $request): JsonResponse
    {
        /** @var ListUsersRecord $request */
        $usersRecord = $this->userService->getUsers($request);
        
        // Pour une liste, on utilise collect() avec une TypedCollection
        $userDataCollection = new UserTypedCollection();
        foreach ($usersRecord->users->all() as $user) {
            $userDataCollection->add(UserData::fromRecord($user));
        }
        
        $usersData = UserData::collect($userDataCollection);
        
        return $this->json($usersData);
    }
}
```

### 7.2. Data avec Value Object

```php
final class MoneyData extends AbstractData
{
    public function __construct(
        public readonly float $amount,
        public readonly string $currency,
        public readonly string $formatted,
    ) {}
    
    public static function fromValueObject(Money $money): self
    {
        return new self(
            amount: $money->amount,
            currency: $money->currency->value,
            formatted: $money->format(),
        );
    }
}

final class MoneyTypedCollection extends TypedCollection
{
    public function __construct()
    {
        parent::__construct(MoneyData::class);
    }
}

final class ListPricesAction extends AbstractAction
{
    protected function handle(Recordable $request): JsonResponse
    {
        /** @var ListPricesRecord $request */
        $pricesRecord = $this->priceService->getPrices($request);
        
        // $pricesRecord->prices est une TypedCollection<Money>
        $moneyDataCollection = new MoneyTypedCollection();
        foreach ($pricesRecord->prices->all() as $money) {
            $moneyDataCollection->add(MoneyData::fromValueObject($money));
        }
        
        $pricesData = MoneyData::collect($moneyDataCollection);
        
        return $this->json($pricesData);
    }
}
```

### 7.3. Data paginée avec TypedCollection

```php
final class PaginatedData extends AbstractData
{
    public function __construct(
        public readonly UserTypedCollection $data,
        public readonly int $currentPage,
        public readonly int $perPage,
        public readonly int $total,
        public readonly int $lastPage,
    ) {}
}

final class ListUsersAction extends AbstractAction
{
    protected function handle(Recordable $request): JsonResponse
    {
        /** @var ListUsersRecord $request */
        $usersRecord = $this->userService->getUsers($request);
        
        // Transformation des UserRecord en UserData
        $userDataCollection = new UserTypedCollection();
        foreach ($usersRecord->users->all() as $user) {
            $userDataCollection->add(UserData::fromRecord($user));
        }
        
        $paginatedData = new PaginatedData(
            data: $userDataCollection,  // ← UserTypedCollection
            currentPage: $usersRecord->currentPage,
            perPage: $usersRecord->perPage,
            total: $usersRecord->total,
            lastPage: $usersRecord->lastPage,
        );
        
        // Pas besoin de collect() ici car PaginatedData contient déjà la collection
        return $this->json($paginatedData);
    }
}
```

### 7.4. Data avec TypedCollection personnalisée avec méthodes métier

```php
final class OrderData extends AbstractData
{
    public function __construct(
        public readonly string $id,
        public readonly float $total,
        public readonly string $status,
        public readonly string $createdAt,
    ) {}
    
    public static function fromRecord(OrderRecord $record): self
    {
        return new self(
            id: (string) $record->id,
            total: $record->total,
            status: $record->status->value,
            createdAt: $record->createdAt,
        );
    }
}

final class OrderTypedCollection extends TypedCollection
{
    public function __construct()
    {
        parent::__construct(OrderData::class);
    }
    
    public function getTotalAmount(): float
    {
        return $this->sum(fn($order) => $order->total);
    }
    
    public function getPending(): self
    {
        return $this->filter(fn($order) => $order->status === 'pending');
    }
    
    public function getCompleted(): self
    {
        return $this->filter(fn($order) => $order->status === 'completed');
    }
    
    public static function fromRecordCollection(TypedCollection $orderRecords): self
    {
        $collection = new self();
        
        foreach ($orderRecords->all() as $orderRecord) {
            if (!$orderRecord instanceof OrderRecord) {
                throw new InvalidArgumentException('Expected OrderRecord');
            }
            $collection->add(OrderData::fromRecord($orderRecord));
        }
        
        return $collection;
    }
}

final class DashboardData extends AbstractData
{
    public function __construct(
        public readonly OrderTypedCollection $recentOrders,
        public readonly float $totalRevenue,
        public readonly int $pendingOrdersCount,
    ) {}
}

final class DashboardAction extends AbstractAction
{
    protected function handle(Recordable $request): JsonResponse
    {
        $ordersRecord = $this->orderService->getRecentOrders($request);
        
        $orders = OrderTypedCollection::fromRecordCollection($ordersRecord->orders);
        
        $dashboardData = new DashboardData(
            recentOrders: $orders,
            totalRevenue: $orders->getTotalAmount(),
            pendingOrdersCount: $orders->getPending()->count(),
        );
        
        return $this->json($dashboardData);
    }
}
```

### 7.5. Exemple avec des Value Objects uniquement

```php
final class EmailAddressData extends AbstractData
{
    public function __construct(
        public readonly string $email,
        public readonly string $domain,
        public readonly bool $isGmail,
    ) {}
    
    public static function fromValueObject(EmailAddress $email): self
    {
        return new self(
            email: $email->value,
            domain: $email->getDomain(),
            isGmail: $email->isGmail(),
        );
    }
}

final class EmailTypedCollection extends TypedCollection
{
    public function __construct()
    {
        parent::__construct(EmailAddressData::class);
    }
    
    public static function fromValueObjectCollection(TypedCollection $emailVOs): self
    {
        $collection = new self();
        
        foreach ($emailVOs->all() as $emailVO) {
            if (!$emailVO instanceof EmailAddress) {
                throw new InvalidArgumentException('Expected EmailAddress');
            }
            $collection->add(EmailAddressData::fromValueObject($emailVO));
        }
        
        return $collection;
    }
    
    public function getGmails(): self
    {
        return $this->filter(fn($data) => $data->isGmail === true);
    }
}

final class ListEmailsAction extends AbstractAction
{
    protected function handle(Recordable $request): JsonResponse
    {
        /** @var ListEmailsRecord $request */
        $emailsRecord = $this->emailService->getEmails($request);
        
        // $emailsRecord->emails est une TypedCollection<EmailAddress>
        $emailDataCollection = EmailTypedCollection::fromValueObjectCollection($emailsRecord->emails);
        
        // Optionnel : filtrer les Gmail avant la réponse
        $gmailsOnly = $emailDataCollection->getGmails();
        
        $emailsData = EmailAddressData::collect($gmailsOnly);
        
        return $this->json($emailsData);
    }
}
```

---

## 8. Organisation des dossiers

```
app/
├── Actions/
│   ├── Api/
│   │   └── Users/
│   │       ├── ListUsersAction.php
│   │       └── ShowUserAction.php
│   └── Web/
│       └── Dashboard/
│           └── ShowDashboardAction.php
├── Data/
│   ├── UserData.php
│   ├── OrderData.php
│   ├── PaginatedData.php
│   └── DashboardData.php
├── Records/
│   ├── UserRecord.php
│   └── ListUsersRecord.php
├── ValueObjects/
│   ├── EmailAddress.php
│   └── Money.php
├── Collections/
│   ├── TypedCollection.php           ← Classe de base
│   ├── UserTypedCollection.php       ← Collection de UserData
│   ├── OrderTypedCollection.php      ← Collection de OrderData
│   └── EmailTypedCollection.php      ← Collection de EmailAddressData
├── Services/
│   └── UserService.php
└── Tasks/
    └── ValidateUserAccessTask.php
```

---

## 9. Tests unitaires

```php
final class UserDataTest extends UnitTestCase
{
    public function test_fromRecord_creates_data_from_record(): void
    {
        $record = new UserRecord(
            id: 1,
            name: 'John Doe',
            email: 'john@example.com',
            createdAt: '2024-01-15T10:30:00Z',
        );
        
        $data = UserData::fromRecord($record);
        
        $this->assertSame('1', $data->id);
        $this->assertSame('John Doe', $data->name);
        $this->assertSame('john@example.com', $data->email);
        $this->assertSame('2024-01-15T10:30:00Z', $data->createdAt);
    }
    
    public function test_collect_accepts_only_typed_collection(): void
    {
        $userRecord1 = new UserRecord(id: 1, name: 'John', email: 'john@example.com');
        $userRecord2 = new UserRecord(id: 2, name: 'Jane', email: 'jane@example.com');
        
        // ✅ BON - TypedCollection de UserRecord
        $recordCollection = new TypedCollection(UserRecord::class);
        $recordCollection->add($userRecord1, $userRecord2);
        
        // Transformation en UserTypedCollection
        $userDataCollection = new UserTypedCollection();
        foreach ($recordCollection->all() as $record) {
            $userDataCollection->add(UserData::fromRecord($record));
        }
        
        // collect() accepte la TypedCollection
        $dataArray = UserData::collect($userDataCollection);
        
        $this->assertCount(2, $dataArray);
        $this->assertInstanceOf(UserData::class, $dataArray[0]);
        
        // ❌ MAUVAIS - array brut non accepté
        $this->expectException(\TypeError::class);
        UserData::collect([$userRecord1, $userRecord2]);
    }
    
    public function test_collect_throws_exception_for_invalid_items(): void
    {
        $invalidCollection = new TypedCollection('string');
        $invalidCollection->add('not a record');
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('TypedCollection must contain only AbstractRecord or AbstractValueObject');
        
        UserData::collect($invalidCollection);
    }
    
    public function test_toArray_returns_camel_case_keys(): void
    {
        $data = new UserData(
            id: '1',
            name: 'John Doe',
            email: 'john@example.com',
            createdAt: '2024-01-15T10:30:00Z',
        );
        
        $array = $data->toArray();
        
        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('name', $array);
        $this->assertArrayHasKey('email', $array);
        $this->assertArrayHasKey('createdAt', $array);
        $this->assertArrayNotHasKey('created_at', $array);
        $this->assertArrayNotHasKey('user_id', $array);
    }
    
    public function test_typed_collection_works_with_data(): void
    {
        $userData = new UserData(id: '1', name: 'John', email: 'john@example.com');
        
        $collection = new UserTypedCollection();
        $collection->add($userData);
        
        $this->assertCount(1, $collection);
        $this->assertInstanceOf(UserData::class, $collection->firstItem());
    }
}
```

---

## 10. Récapitulatif des contraintes

| Contrainte | Règle |
|------------|-------|
| **Héritage** | ✅ DOIT étendre `AbstractData` |
| **Usage** | ✅ Réponses API uniquement |
| **Communication interne** | ❌ Interdit (utiliser `Record` ou `ValueObject`) |
| **Propriétés** | `public readonly` |
| **Nommage** | `camelCase` |
| **Dates** | `string` ISO 8601 (pas `Carbon`) |
| **Nullables** | ✅ Valeur par défaut obligatoire (`= null` ou `= []`) |
| **Création** | `fromRecord()` ou `fromValueObject()` |
| **Logique** | ❌ Pas de calcul ou condition dans les méthodes `from*()` |
| **collect()** | ⚠️ Accepte UNIQUEMENT une `TypedCollection` |
| **TypedCollection** | ✅ Doit être utilisée pour les listes d'éléments |
| **Collections personnalisées** | ✅ Étendre `TypedCollection` et restreindre le type aux Data |
| **Tests unitaires** | ✅ Oui (test de `toArray()` et `collect()`) |

---

## 11. Règle d'or

> **Une Data est une structure pure et immutable pour les réponses API. Elle ne contient aucune logique métier. Elle est créée uniquement via `fromRecord()` ou `fromValueObject()`.**
>
> **⚠️ La méthode `collect()` accepte UNIQUEMENT une `TypedCollection` contenant des Records ou des Value Objects.**
>
> **Pour les listes de Data, utilisez des collections personnalisées qui étendent `TypedCollection` et restreignent le type aux Data souhaitées.**

```php
// La Data parfaite
final class UserData extends AbstractData
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $email,
        public readonly ?string $avatar = null,
        public readonly ?UserRole $role = null,
        public readonly string $createdAt,
    ) {}
    
    public static function fromRecord(UserRecord $record): self
    {
        return new self(
            id: (string) $record->id,
            name: $record->name,
            email: $record->email,
            avatar: $record->avatar,
            role: $record->role,
            createdAt: $record->createdAt,
        );
    }
}

// La collection typée associée
final class UserTypedCollection extends TypedCollection
{
    public function __construct()
    {
        parent::__construct(UserData::class);
    }
    
    public static function fromRecordCollection(TypedCollection $userRecords): self
    {
        $collection = new self();
        foreach ($userRecords->all() as $record) {
            $collection->add(UserData::fromRecord($record));
        }
        return $collection;
    }
}

// Utilisation dans l'Action
final class ListUsersAction extends AbstractAction
{
    protected function handle(Recordable $request): JsonResponse
    {
        /** @var ListUsersRecord $request */
        $usersRecord = $this->userService->getUsers($request);
        
        // Transformation : TypedCollection<UserRecord> → UserTypedCollection
        $userDataCollection = UserTypedCollection::fromRecordCollection($usersRecord->users);
        
        // collect() accepte la TypedCollection
        $usersData = UserData::collect($userDataCollection);
        
        return $this->json($usersData);
    }
}
```

---

## 12. Avantages pour les clients (TypeScript, Kotlin, Swift)

Grâce à cette standardisation, chaque client peut créer des **structures miroir** parfaitement alignées :

### TypeScript (Frontend)
```typescript
interface UserData {
    id: string;
    name: string;
    email: string;
    avatar?: string;
    role?: 'admin' | 'user';
    createdAt: string;
}

// La réponse est toujours un tableau typé
type UsersResponse = UserData[];
```

### Kotlin (Android)
```kotlin
data class UserData(
    val id: String,
    val name: String,
    val email: String,
    val avatar: String? = null,
    val role: UserRole? = null,
    val createdAt: String,
)

// La réponse est toujours une List<UserData>
typealias UsersResponse = List<UserData>
```

### Swift (iOS)
```swift
struct UserData: Codable {
    let id: String
    let name: String
    let email: String
    let avatar: String?
    let role: UserRole?
    let createdAt: String
}

// La réponse est toujours [UserData]
typealias UsersResponse = [UserData]
```

**Résultat :** Une API documentée, type-safe, et consommable par n'importe quel client sans ambiguïté.