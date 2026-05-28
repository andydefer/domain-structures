```markdown
# Record - Documentation

## 1. Définition

Un **Record** est une structure de données **pure**, **immutable** et **stateless** utilisée pour la communication **interne** entre les couches de l'application (Services, Repositories, Tasks). Il remplace les tableaux bruts (`array`) par des structures **typées**, **prévisibles** et **testables**.

```
Record → Transporteur de données typé pour la communication interne
```

> ⚠️ **Un Record est STRICTEMENT réservé à l'usage interne. Il ne peut en aucun cas être retourné comme réponse HTTP.**

---

## 2. Record vs Value Object vs Data : Quand utiliser quoi ?

| Aspect | Record | Value Object | Data DTO |
|--------|--------|--------------|----------|
| **Usage principal** | Communication interne (Services, Repositories) | Concepts métier avec comportements | Réponses HTTP |
| **Logique métier** | ❌ Aucune | ✅ Peut en avoir (calculs, validation) | ❌ Transformation uniquement |
| **Méthodes** | ❌ Aucune (sauf héritées) | ✅ Oui (comportements métier) | ❌ Seulement `fromRecord()` / `fromValueObject()` |
| **Validation** | ❌ Optionnelle | ✅ OBLIGATOIRE (constructeur privé) | ❌ Optionnelle |
| **Constructeur** | Public | Privé (factory methods) | Public |
| **Peut contenir** | VO, scalaires, TypedCollection | VO, scalaires, TypedCollection | Records, VO, Data, TypedCollection |
| **Peut contenir des Models** | ❌ INTERDIT | ❌ INTERDIT | ❌ INTERDIT |
| **Peut contenir du Cache/Log** | ❌ INTERDIT | ❌ INTERDIT | ❌ INTERDIT |
| **Effets de bord** | ❌ INTERDIT | ❌ INTERDIT | ❌ INTERDIT |
| **Testabilité** | ✅ Excellente (pure) | ✅ Excellente (pure) | ✅ Excellente (pure) |
| **Nommage** | `{Description}Record` | `{Description}` | `{Description}Data` |

---

## 3. Pourquoi les Records ?

### 3.1. Le problème des tableaux bruts

```php
// ❌ MAUVAIS - On ne sait pas ce qu'il y a dans le tableau
function updateUser(array $credentials): void
{
    // Que contient $credentials ?
    // ['email' => '...', 'password' => '...'] ?
    // ['name' => '...', 'id' => ...] ?
    // On n'en sait rien !
}

// ✅ BON - On sait exactement ce qu'on reçoit
function updateUser(UserCredentialsRecord $credentials): void
{
    // $credentials->email est un string
    // $credentials->password est un string
    // Le type est explicite et garanti
}
```

### 3.2. Le Record : Un transporteur bête mais type-safe

```php
// Un Record est "bête" : il ne fait que transporter des données
final class UserCredentialsRecord extends AbstractRecord
{
    public function __construct(
        public readonly ?string $email = null,
        public readonly ?string $password = null,
        public readonly ?UserRole $role = null,
    ) {}
}

// Pas de méthodes métier
// Pas de validation
// Pas d'effets de bord
// Juste des données typées
```

---

## 4. Règles fondamentales

### 4.1. Un Record est STATELESS

> **⚠️ RÈGLE ABSOLUE : Un Record ne doit JAMAIS avoir d'état mutable ni d'effets de bord.**

```php
// ✅ BON - Record pur, sans état mutable
final class UserRecord extends AbstractRecord
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
    ) {}
}

// ❌ MAUVAIS - Record avec état mutable
final class BadRecord extends AbstractRecord
{
    private int $counter = 0;  // ← État mutable !
    
    public function increment(): void  // ← Méthode qui modifie l'état !
    {
        $this->counter++;
    }
}
```

### 4.2. Ce qu'un Record peut contenir

| Type | Exemple | Autorisation |
|------|---------|--------------|
| `int` | `public readonly int $id` | ✅ Oui |
| `string` | `public readonly string $name` | ✅ Oui |
| `float` | `public readonly float $price` | ✅ Oui |
| `bool` | `public readonly bool $isActive` | ✅ Oui |
| `null` | `public readonly ?string $value` | ✅ Oui |
| `Enum` | `public readonly UserRole $role` | ✅ Oui |
| `Value Object` | `public readonly EmailAddress $email` | ✅ Oui |
| `Record` | `public readonly AddressRecord $address` | ✅ Oui |
| `TypedCollection` | `public readonly TypedCollection $items` | ✅ Oui |

### 4.3. Ce qu'un Record NE PEUT PAS contenir (⚠️ RÈGLE STRICTISSIME)

| Type interdit | Raison | Alternative |
|---------------|--------|-------------|
| `array` brut | On ne sait pas ce qu'il contient | `TypedCollection` |
| `Model` (Eloquent) | Contient de la logique et des relations | `UserId` (VO) ou `UserRecord` |
| `Collection` Laravel | Structure non typée | `TypedCollection` |
| `Carbon` / `DateTime` | Contient de la logique et des comportements | `string` ISO 8601 |
| `Cache`, `Log`, `DB`, `Http` | Effets de bord, appels statiques | Injecter des services |
| `mixed` | Pas de typage | Type explicite |
| `object` | Pas de typage | Type explicite |

```php
// ✅ BON - Record valide
final class OrderRecord extends AbstractRecord
{
    public function __construct(
        public readonly int $id,
        public readonly Money $total,           // ← Value Object
        public readonly EmailAddress $email,    // ← Value Object
        public readonly TypedCollection $items, // ← TypedCollection<OrderItemRecord>
        public readonly string $createdAt,      // ← ISO 8601 string
    ) {}
}

// ❌ MAUVAIS - Record invalide
final class BadOrderRecord extends AbstractRecord
{
    public function __construct(
        public array $items,                    // ❌ array brut
        public Order $order,                    // ❌ Model Eloquent
        public Collection $products,            // ❌ Collection Laravel
        public Carbon $createdAt,               // ❌ Carbon
        public Cache $cache,                    // ❌ Cache (effet de bord)
    ) {}
}
```

### 4.4. Pourquoi interdire les Models ?

```php
// ❌ MAUVAIS - Record qui contient un Model
final class OrderRecord extends AbstractRecord
{
    public function __construct(
        public readonly Order $order,  // ← Model Eloquent avec DB
    ) {}
}

// Problèmes :
// 1. Impossible de tester sans base de données
// 2. Le Model a des méthodes (save(), delete(), etc.) → violation de la pureté
// 3. Le Record n'est plus un simple transporteur
// 4. Couplage fort avec la couche de persistance

// ✅ BON - Record qui utilise des Value Objects
final class OrderRecord extends AbstractRecord
{
    public function __construct(
        public readonly OrderId $orderId,      // ← Value Object
        public readonly Money $total,          // ← Value Object
        public readonly OrderStatus $status,   // ← Enum
        public readonly string $createdAt,     // ← ISO string
    ) {}
}
```

### 4.5. Pourquoi interdire les effets de bord ?

```php
// ❌ MAUVAIS - Record qui utilise Cache
final class UserRecord extends AbstractRecord
{
    public function __construct(
        public readonly int $id,
    ) {}
    
    public function getCachedData(): mixed  // ❌ Effet de bord !
    {
        return Cache::get('user_' . $this->id);
    }
}

// ❌ MAUVAIS - Record qui appelle une API
final class WeatherRecord extends AbstractRecord
{
    public function getTemperature(): float  // ❌ Appel HTTP !
    {
        return Http::get('api.weather.com')->json('temp');
    }
}

// ✅ BON - La logique est dans un Service, pas dans le Record
final class WeatherService
{
    public function getTemperature(LocationRecord $location): float
    {
        // Le Service fait l'appel HTTP, pas le Record
        return Http::get('api.weather.com', ['lat' => $location->lat])->json('temp');
    }
}
```

---

## 5. Avantages pour la testabilité

### 5.1. Testabilité parfaite

```php
// Un Record est une simple structure de données
final class UserUpdateRecord extends AbstractRecord
{
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?string $email = null,
        public readonly ?UserRole $role = null,
    ) {}
}

// Test unitaire ultra simple - pas de mocks, pas de DB !
final class UserUpdateRecordTest extends TestCase
{
    public function test_can_create_record_with_partial_data(): void
    {
        $record = new UserUpdateRecord(name: 'John');
        
        $this->assertSame('John', $record->name);
        $this->assertNull($record->email);
        $this->assertNull($record->role);
    }
    
    public function test_can_create_record_with_all_data(): void
    {
        $record = new UserUpdateRecord(
            name: 'John',
            email: 'john@example.com',
            role: UserRole::ADMIN,
        );
        
        $this->assertSame('John', $record->name);
        $this->assertSame('john@example.com', $record->email);
        $this->assertSame(UserRole::ADMIN, $record->role);
    }
}
```

### 5.2. Test des Services avec des Records

```php
final class UserServiceTest extends TestCase
{
    public function test_update_user_with_valid_record(): void
    {
        // Création simple du Record - pas besoin de base de données
        $record = new UserUpdateRecord(
            name: 'John Doe',
            email: 'john@example.com',
        );
        
        $repository = $this->createMock(UserRepository::class);
        $repository->expects($this->once())
            ->method('update')
            ->with($this->callback(function (UserUpdateRecord $record) {
                return $record->name === 'John Doe'
                    && $record->email === 'john@example.com';
            }));
        
        $service = new UserService($repository);
        $service->updateUser(1, $record);
    }
}
```

### 5.3. Test des Repositories avec des Records

```php
final class UserRepositoryTest extends TestCase
{
    public function test_create_user_from_record(): void
    {
        // Record simple à créer
        $record = new UserCreateRecord(
            name: 'John Doe',
            email: 'john@example.com',
            role: UserRole::USER,
        );
        
        $repository = new UserRepository();
        $user = $repository->create($record);
        
        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'role' => 'user',
        ]);
    }
}
```

---

## 6. Record avec Value Objects (La combinaison gagnante)

```php
// Value Object : Concept métier avec validation
final class EmailAddress extends AbstractValueObject
{
    private function __construct(public readonly string $value) {}
    
    public static function fromString(string $email): self
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("Invalid email: {$email}");
        }
        return new self($email);
    }
}

// Record : Transporteur qui utilise le VO
final class UserRecord extends AbstractRecord
{
    public function __construct(
        public readonly EmailAddress $email,  // ← VO déjà validé !
        public readonly string $name,
    ) {}
}

// Service : Pas besoin de valider l'email, le VO l'a déjà fait
final class UserService
{
    public function createUser(UserRecord $record): void
    {
        // L'email est déjà garanti valide !
        $this->repository->create($record);
    }
}

// Test : Création simple du Record avec VO
final class UserServiceTest extends TestCase
{
    public function test_create_user(): void
    {
        // On peut créer le VO séparément ou directement
        $email = EmailAddress::fromString('john@example.com');
        $record = new UserRecord($email, 'John Doe');
        
        $service = new UserService();
        $service->createUser($record);
    }
}
```

---

## 7. Exemples concrets

### 7.1. Record simple pour création

```php
final class UserCreateRecord extends AbstractRecord
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly UserRole $role = UserRole::USER,
    ) {}
}

// Utilisation
$record = new UserCreateRecord(
    name: 'John Doe',
    email: 'john@example.com',
    role: UserRole::ADMIN,
);
```

### 7.2. Record pour mise à jour (champs optionnels)

```php
final class UserUpdateRecord extends AbstractRecord
{
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?string $email = null,
        public readonly ?UserRole $role = null,
    ) {}
}

// Utilisation : on ne met à jour que le nom
$record = new UserUpdateRecord(name: 'Jane Doe');

// Utilisation : mise à jour partielle
$record = new UserUpdateRecord(
    name: 'Jane Doe',
    email: 'jane@example.com',
);
```

### 7.3. Record avec Value Objects

```php
final class OrderRecord extends AbstractRecord
{
    public function __construct(
        public readonly OrderId $orderId,
        public readonly Money $total,
        public readonly EmailAddress $customerEmail,
        public readonly TypedCollection $items,
    ) {}
}
```

### 7.4. Record pour filtres de recherche

```php
final class UserFiltersRecord extends AbstractRecord
{
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?string $email = null,
        public readonly ?UserRole $role = null,
        public readonly ?UserStatus $status = null,
    ) {}
}

// Utilisation dans un Repository
final class UserRepository
{
    public function findBy(UserFiltersRecord $filters): TypedCollection
    {
        $query = User::query();
        
        if ($filters->name !== null) {
            $query->where('name', 'like', "%{$filters->name}%");
        }
        
        if ($filters->email !== null) {
            $query->where('email', $filters->email);
        }
        
        if ($filters->role !== null) {
            $query->where('role', $filters->role->value);
        }
        
        return UserRecord::collect($query->get());
    }
}
```

### 7.5. Record pour pagination

```php
final class PaginationRecord extends AbstractRecord
{
    public function __construct(
        public readonly int $perPage = 15,
        public readonly int $page = 1,
        public readonly ?string $sortBy = null,
        public readonly string $sortDir = 'asc',
    ) {}
}

// Utilisation
$pagination = new PaginationRecord(perPage: 20, page: 2, sortBy: 'created_at');
$users = $userRepository->paginate($pagination);
```

---

## 8. Ce qu'un Record ne doit PAS faire (RAPPEL)

| Interdit | Pourquoi | Alternative |
|----------|----------|-------------|
| `Cache::get()` | Effet de bord | Injecter un service |
| `Log::info()` | Effet de bord | Injecter un logger |
| `Http::get()` | Effet de bord | Injecter un client HTTP |
| `DB::table()` | Effet de bord + DB | Repository |
| `Mail::send()` | Effet de bord | Task dédiée |
| `Queue::push()` | Effet de bord | Task dédiée |
| `new User::find(1)` | Appel DB + Model | Repository |
| `array` brut | Non typé | `TypedCollection` |

```php
// ❌ MAUVAIS - Record avec effets de bord
final class BadRecord extends AbstractRecord
{
    public function process(): void
    {
        Cache::put('key', 'value');     // ❌
        Log::info('processing');        // ❌
        Http::get('api.com');           // ❌
        Mail::send($email);             // ❌
        Queue::push(new Job());         // ❌
        $user = User::find(1);          // ❌
    }
}

// ✅ BON - La logique est dans un Service ou une Task
final class ProcessService
{
    public function __construct(
        private readonly CacheInterface $cache,
        private readonly LoggerInterface $logger,
        private readonly HttpClient $http,
        private readonly MailerInterface $mailer,
        private readonly QueueInterface $queue,
        private readonly UserRepository $userRepository,
    ) {}
    
    public function process(ProcessRecord $record): void
    {
        $this->cache->set('key', 'value');
        $this->logger->info('processing');
        $this->http->get('api.com');
        $this->mailer->send($email);
        $this->queue->push(new Job());
        $user = $this->userRepository->find(1);
    }
}
```

---

## 9. Organisation des dossiers

```
app/
├── Records/
│   ├── UserCreateRecord.php
│   ├── UserUpdateRecord.php
│   ├── UserFiltersRecord.php
│   ├── PaginationRecord.php
│   └── OrderRecord.php
├── ValueObjects/
│   ├── EmailAddress.php
│   ├── Money.php
│   └── UserId.php
├── Collections/
│   └── OrderCollection.php
├── Services/
│   └── UserService.php
├── Repositories/
│   └── UserRepository.php
└── Tasks/
    └── SendEmailTask.php
```

---

## 10. Récapitulatif des contraintes

| Contrainte | Règle |
|------------|-------|
| **Nommage** | `{Description}Record` |
| **Héritage** | Étend `AbstractRecord` |
| **Propriétés** | `public readonly` |
| **Types autorisés** | `int`, `string`, `float`, `bool`, `null`, `Enum`, `ValueObject`, `Record`, `TypedCollection` |
| **Types interdits** | `array` brut, `Model`, `Collection`, `Carbon`, `DateTime`, `mixed`, `object`, `Cache`, `Log`, `Http`, `DB`, `Mail`, `Queue` |
| **Logique métier** | ❌ AUCUNE |
| **Méthodes** | ❌ AUCUNE (sauf héritées) |
| **Effets de bord** | ❌ AUCUN |
| **État mutable** | ❌ AUCUN |
| **Appels statiques** | ❌ AUCUN |
| **Utilisation** | Communication interne UNIQUEMENT (pas de réponse HTTP) |
| **Testabilité** | ✅ Doit être testable unitairement (pas de mocks complexes) |

---

## 11. Règle d'or

> **Un Record est un transporteur de données bête, purement passif, sans aucune logique métier. Il ne fait que transporter des données typées d'un point A à un point B. Il ne peut pas interagir avec la base de données, le cache, les logs, ou quoi que ce soit d'externe. Sa seule responsabilité est de garantir le typage des données.**

```php
// Le Record parfait (bête et efficace)
final class UserRecord extends AbstractRecord
{
    public function __construct(
        public readonly UserId $id,           // ← Value Object
        public readonly string $name,         // ← scalaire
        public readonly EmailAddress $email,  // ← Value Object
        public readonly UserRole $role,       // ← Enum
        public readonly TypedCollection $tags, // ← TypedCollection<string>
        public readonly string $createdAt,    // ← ISO string
    ) {}
}
```