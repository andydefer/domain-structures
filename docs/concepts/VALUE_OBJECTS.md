# Value Object - Documentation (Version finale)

## 1. Définition

Un **Value Object (VO)** est une structure de données **immutable**, **stateless** et **auto-validante** qui représente un **concept métier** avec son propre comportement. Contrairement au Record (simple transporteur de données), un Value Object encapsule de la **logique métier** et garantit sa **validité** à l'instantiation.

```
Value Object → Concept métier avec comportement → Validation OBLIGATOIRE → Pas d'identité propre
```

> ⚠️ **Un Value Object est STRICTEMENT stateless et ne peut contenir aucune logique avec effets de bord (cache, DB, HTTP, logs).**

---

## 2. Record vs Value Object vs Data : Différences fondamentales

| Aspect | Record | Value Object | Data DTO |
|--------|--------|--------------|----------|
| **Usage principal** | Communication interne | Concepts métier | Réponses HTTP |
| **Logique métier** | ❌ Aucune | ✅ **Peut en avoir** | ❌ Transformation uniquement |
| **Validation** | ❌ Optionnelle | ✅ **OBLIGATOIRE** | ❌ Optionnelle |
| **Constructeur** | Public | **Privé (factory)** | Public |
| **Effets de bord** | ❌ Interdit | ❌ **Interdit** | ❌ Interdit |
| **Peut contenir** | VO, scalaires, TypedCollection | **VO, scalaires, TypedCollection, Enums** | Data, TypedCollection |
| **Peut contenir des Records** | ✅ Oui | ❌ **Interdit** | ✅ Oui |
| **Peut contenir des Data** | ❌ Interdit | ❌ **Interdit** | ✅ Oui |
| **Nommage** | `UserRecord` | `EmailAddress`, `Money` | `UserData` |
| **Testabilité** | ✅ Excellente | ✅ **Excellente** | ✅ Excellente |

---

## 3. Pourquoi les Value Objects ?

### 3.1. Le problème des types primitifs

```php
// ❌ MAUVAIS - On ne sait pas ce qu'est cette chaîne
function sendEmail(string $email): void
{
    // L'email n'est pas validé
    // Pas de comportement attaché
}

// ✅ BON - Le type explicite le concept
function sendEmail(EmailAddress $email): void
{
    // L'email est GARANTI valide
    // On peut appeler $email->getDomain()
}
```

### 3.2. Validation centralisée

```php
// ❌ MAUVAIS - Validation dispersée
class UserService {
    public function updateEmail(string $email): void {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { ... }
    }
}
class OrderService {
    public function notify(string $email): void {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { ... }
    }
}

// ✅ BON - Validation dans le VO
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
    
    public function getDomain(): string { ... }
    public function isGmail(): bool { ... }
}
```

---

## 4. Enum vs Value Object : La différence fondamentale (⚠️ IMPORTANT)

> **Enum = ensemble FIXE de valeurs (fini, connu à l'avance)**
> **Value Object = concept OUVERT avec validation (infini, règles métier)**

```php
// ENUM : Quand les valeurs sont LIMITÉES et CONNUES à l'avance
enum UserRole: string
{
    case ADMIN = 'admin';
    case USER = 'user';
    case DOCTOR = 'doctor';
    
    public function getLabel(): string
    {
        return match($this) {
            self::ADMIN => 'Administrateur',
            self::USER => 'Utilisateur',
            self::DOCTOR => 'Médecin',
        };
    }
    
    public function isAdmin(): bool
    {
        return $this === self::ADMIN;
    }
}

// VALUE OBJECT : Quand les valeurs sont ILLIMITÉES mais suivent des RÈGLES
final class EmailAddress extends AbstractValueObject
{
    private function __construct(public readonly string $value) {}
    
    public static function fromString(string $email): self
    {
        // Des MILLIARDS de valeurs possibles !
        // Mais TOUTES doivent suivre cette règle
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("Invalid email: {$email}");
        }
        return new self($email);
    }
    
    public function getDomain(): string { ... }
    public function isGmail(): bool { ... }
}
```

### 4.1. Exemple concret : Les pays

```php
// ENUM : Les pays de l'UE sont FIXES et CONNUS
enum EuCountry: string
{
    case FR = 'FR';
    case DE = 'DE';
    case ES = 'ES';
    case IT = 'IT';
    case NL = 'NL';
    case BE = 'BE';
    case LU = 'LU';
    
    public function getLabel(): string
    {
        return match($this) {
            self::FR => 'France',
            self::DE => 'Allemagne',
            self::ES => 'Espagne',
            self::IT => 'Italie',
            self::NL => 'Pays-Bas',
            self::BE => 'Belgique',
            self::LU => 'Luxembourg',
        };
    }
    
    public static function getAllCodes(): array
    {
        return array_column(self::cases(), 'value');
    }
}

// VALUE OBJECT : Un pays peut être N'IMPORTE LEQUEL des 197 pays du monde
// Pas un nombre fixe, juste des règles de validation
final class Country extends AbstractValueObject
{
    private function __construct(public readonly string $code) {}
    
    public static function fromCode(string $code): self
    {
        // Règle : code ISO 3166-1 alpha-2 (2 lettres)
        if (!preg_match('/^[A-Z]{2}$/', $code)) {
            throw new InvalidArgumentException("Invalid country code: {$code}");
        }
        return new self($code);
    }
    
    public function isInEuropeanUnion(): bool
    {
        // Délégation à l'Enum pour la liste des pays de l'UE
        return in_array($this->code, EuCountry::getAllCodes());
    }
    
    public function getLabel(): string
    {
        // Délégation à l'Enum si c'est un pays de l'UE
        $euCountry = EuCountry::tryFrom($this->code);
        if ($euCountry) {
            return $euCountry->getLabel();
        }
        
        // Sinon, mapping externe ou API
        return GeoService::getCountryName($this->code);
    }
}

// Utilisation
$country = Country::fromCode('FR');
if ($country->isInEuropeanUnion()) {  // true
    echo $country->getLabel();  // 'France'
}

$country = Country::fromCode('US');  // Valide (US existe)
if (!$country->isInEuropeanUnion()) {  // true
    echo $country->getLabel();  // 'États-Unis' (via service)
}
```

### 4.2. Quand utiliser Enum vs Value Object ?

| Situation | Solution | Exemple |
|-----------|----------|---------|
| **Valeurs FIXES et CONNUES à l'avance** | **Enum** | `UserRole`, `OrderStatus`, `EuCountry` |
| **Valeurs ILLIMITÉES mais avec RÈGLES** | **Value Object** | `EmailAddress`, `PhoneNumber`, `Age` |
| **Concept métier avec validation complexe** | **Value Object** | `Money`, `Password`, `ZipCode` |
| **Ensemble restreint de valeurs** | **Enum** | `Currency`, `PaymentMethod`, `Language` |

```php
// ENUM : ensemble FIXE (3 valeurs possibles)
enum UserRole: string
{
    case ADMIN = 'admin';
    case USER = 'user';
    case DOCTOR = 'doctor';
    
    public function getLabel(): string { ... }
}

// VALUE OBJECT : nombre INFINI de valeurs possibles
final class EmailAddress extends AbstractValueObject
{
    // Des MILLIONS d'emails possibles !
    public static function fromString(string $email): self { ... }
    public function getDomain(): string { ... }
}

// VALUE OBJECT : nombre INFINI mais avec règle (0-150)
final class Age extends AbstractValueObject
{
    public static function fromInt(int $age): self
    {
        if ($age < 0 || $age > 150) {
            throw new InvalidArgumentException("Invalid age: {$age}");
        }
        return new self($age);
    }
    public function canVote(): bool { return $this->value >= 18; }
}
```

---

## 5. Règles fondamentales

### 5.1. Un Value Object est STATELESS

> **Un Value Object ne doit JAMAIS avoir d'effets de bord.**

```php
// ✅ BON - Pur, sans effets de bord
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
    
    public function getDomain(): string
    {
        return substr(strrchr($this->value, "@"), 1);
    }
}

// ❌ MAUVAIS - Effets de bord interdits !
final class BadEmail extends AbstractValueObject
{
    public function isUnique(): bool
    {
        return User::where('email', $this->value)->count() === 0;  // ❌ DB
    }
    
    public function sendWelcome(): void
    {
        Mail::send($this->value);  // ❌ Email
    }
}
```

### 5.2. Ce qu'un Value Object peut contenir

| Type | Exemple | Autorisation |
|------|---------|--------------|
| `int`, `string`, `float`, `bool` | `public readonly int $value` | ✅ Oui |
| `Enum` | `public readonly Currency $currency` | ✅ Oui |
| `Value Object` | `public readonly EmailAddress $email` | ✅ Oui |
| `TypedCollection` | `public readonly TypedCollection $tags` | ✅ Oui |

### 5.3. Ce qu'un Value Object NE PEUT PAS contenir

| Type interdit | Alternative |
|---------------|-------------|
| `Record` | Utiliser un VO ou scalaire |
| `Data` | Utiliser un VO |
| `Model` Eloquent | Utiliser un Record |
| `Carbon` / `DateTime` | `string` ISO 8601 |
| `Cache`, `DB`, `Http`, `Log` | Injecter des services |

---

## 6. Value Objects pour renforcer les types scalaires

### 6.1. Le problème : les types primitifs ne veulent rien dire

```php
// ❌ Faible typage : n'importe quelle chaîne peut passer
function register(string $email, string $phone, string $zipCode): void
{
    // Rien ne garantit la validité
}
```

### 6.2. La solution : chaque concept a son propre type

```php
// ✅ Typage fort : chaque concept est explicite
function register(EmailAddress $email, PhoneNumber $phone, ZipCode $zipCode): void
{
    // Les objets sont GARANTIS valides à l'instanciation
    // L'IDE peut autocompléter les méthodes spécifiques
}

// Exemple complet
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
    
    public function getDomain(): string
    {
        return substr(strrchr($this->value, "@"), 1);
    }
    
    public function isGmail(): bool
    {
        return $this->getDomain() === 'gmail.com';
    }
    
    public function obfuscate(): string
    {
        $parts = explode('@', $this->value);
        $local = $parts[0];
        $hidden = substr($local, 0, 2) . str_repeat('*', max(3, strlen($local) - 4)) . substr($local, -2);
        return $hidden . '@' . $parts[1];
    }
}
```

---

## 7. Logique métier dans les Value Objects

### 7.1. Calculs et transformations (stateless)

```php
final class Money extends AbstractValueObject
{
    public function __construct(
        public readonly float $amount,
        public readonly Currency $currency,
    ) {}
    
    // ✅ Addition (retourne un nouveau Money)
    public function add(Money $other): self
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException('Cannot add different currencies');
        }
        return new Money($this->amount + $other->amount, $this->currency);
    }
    
    // ✅ Multiplication
    public function multiply(float $factor): self
    {
        return new Money($this->amount * $factor, $this->currency);
    }
    
    // ✅ Formatage
    public function format(): string
    {
        return $this->currency->getSymbol() . number_format($this->amount, 2);
    }
}
```

### 7.2. Validation métier

```php
final class Age extends AbstractValueObject
{
    private function __construct(public readonly int $value) {}
    
    public static function fromInt(int $age): self
    {
        if ($age < 0) {
            throw new InvalidArgumentException("Age cannot be negative");
        }
        if ($age > 150) {
            throw new InvalidArgumentException("Age cannot exceed 150");
        }
        return new self($age);
    }
    
    // ✅ Règles métier
    public function canVote(): bool
    {
        return $this->value >= 18;
    }
    
    public function canDrink(string $country): bool
    {
        $drinkingAge = match($country) {
            'US' => 21,
            default => 18,
        };
        return $this->value >= $drinkingAge;
    }
}
```

---

## 8. Value Objects + Records : La combinaison gagnante

```php
// Value Objects : validation et comportement
final class EmailAddress extends AbstractValueObject { ... }
final class Password extends AbstractValueObject { ... }

// Record : transport de données (utilise les VOs)
final class UserCredentialsRecord extends AbstractRecord
{
    public function __construct(
        public readonly EmailAddress $email,  // ← VO déjà validé
        public readonly Password $password,   // ← VO déjà validé
        public readonly UserRole $role = UserRole::USER,
    ) {}
}

// Service : plus besoin de valider !
final class RegisterUserService
{
    public function register(UserCredentialsRecord $record): void
    {
        // L'email et le password sont DÉJÀ valides
        // Le Record garantit le typage, les VOs garantissent la validité
        $this->repository->create($record);
    }
}
```

---

## 9. Avantages pour la testabilité

```php
final class EmailAddressTest extends TestCase
{
    public function test_valid_email_creates_value_object(): void
    {
        $email = EmailAddress::fromString('john@example.com');
        
        $this->assertSame('john@example.com', $email->value);
        $this->assertSame('example.com', $email->getDomain());
        $this->assertFalse($email->isGmail());
    }
    
    public function test_invalid_email_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        EmailAddress::fromString('not-an-email');
    }
    
    public function test_obfuscate_hides_email(): void
    {
        $email = EmailAddress::fromString('john.doe@example.com');
        $this->assertSame('jo*****oe@example.com', $email->obfuscate());
    }
}
```

---

## 10. Patterns de création

```php
// Factory simple
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

// Factory avec normalisation
final class PhoneNumber extends AbstractValueObject
{
    private function __construct(public readonly string $value) {}
    
    public static function fromString(string $phone): self
    {
        $cleaned = preg_replace('/[^0-9+]/', '', $phone);
        if (!preg_match('/^\+?[0-9]{10,15}$/', $cleaned)) {
            throw new InvalidArgumentException("Invalid phone: {$phone}");
        }
        return new self($cleaned);
    }
}

// Factory optionnelle (nullable)
final class Avatar extends AbstractValueObject
{
    private function __construct(public readonly string $url) {}
    
    public static function fromString(?string $url): ?self
    {
        if ($url === null || $url === '') {
            return null;
        }
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException("Invalid URL: {$url}");
        }
        return new self($url);
    }
}
```

---

## 11. Exemples concrets

### 11.1. EmailAddress

```php
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
    
    public function getDomain(): string
    {
        return substr(strrchr($this->value, "@"), 1);
    }
    
    public function isGmail(): bool
    {
        return $this->getDomain() === 'gmail.com';
    }
    
    public function obfuscate(): string
    {
        $parts = explode('@', $this->value);
        $local = $parts[0];
        $hidden = substr($local, 0, 2) . str_repeat('*', max(3, strlen($local) - 4)) . substr($local, -2);
        return $hidden . '@' . $parts[1];
    }
}
```

### 11.2. Money

```php
final class Money extends AbstractValueObject
{
    public function __construct(
        public readonly float $amount,
        public readonly Currency $currency,
    ) {}
    
    public function add(Money $other): self
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException('Different currencies');
        }
        return new Money($this->amount + $other->amount, $this->currency);
    }
    
    public function format(): string
    {
        return $this->currency->getSymbol() . number_format($this->amount, 2);
    }
}
```

### 11.3. Age (avec règles métier)

```php
final class Age extends AbstractValueObject
{
    private function __construct(public readonly int $value) {}
    
    public static function fromInt(int $age): self
    {
        if ($age < 0 || $age > 150) {
            throw new InvalidArgumentException("Invalid age: {$age}");
        }
        return new self($age);
    }
    
    public function canVote(): bool { return $this->value >= 18; }
    public function canDrive(): bool { return $this->value >= 16; }
    public function canRetire(): bool { return $this->value >= 62; }
}
```

---

## 12. Ce qu'un Value Object ne doit PAS faire (RAPPEL)

| Interdit | Alternative |
|----------|-------------|
| `Cache::get()`, `Log::info()` | Service avec injection |
| `Http::get()`, `DB::table()` | Repository |
| `Mail::send()`, `Queue::push()` | Task dédiée |
| `User::find(1)` (Model) | Repository |
| Contenir des `Record` ou `Data` | Utiliser VO ou scalaire |
| Constructeur public | Constructeur privé |
| État mutable (`private int $counter`) | `readonly` properties |

```php
// ❌ MAUVAIS
final class BadEmail extends AbstractValueObject
{
    public function isUnique(): bool
    {
        return User::where('email', $this->value)->count() === 0;  // ❌ DB
    }
}

// ✅ BON
final class UserService
{
    public function isEmailUnique(EmailAddress $email): bool
    {
        return $this->repository->findByEmail($email) === null;
    }
}
```

---

## 13. Récapitulatif des contraintes

| Contrainte | Règle |
|------------|-------|
| **Héritage** | Étend `AbstractValueObject` |
| **Constructeur** | ✅ **PRIVÉ** |
| **Validation** | ✅ **OBLIGATOIRE** dans les factory methods |
| **Effets de bord** | ❌ **STRICTEMENT INTERDITS** |
| **État mutable** | ❌ Interdit (`readonly` obligatoire) |
| **Peut contenir** | VO, scalaires, TypedCollection, Enums |
| **Peut contenir des Records/Data** | ❌ **INTERDIT** |
| **Factory methods** | `fromString()`, `fromInt()`, `fromArray()` |
| **Testabilité** | ✅ Excellente (pas de mocks) |

---

## 14. Règle d'or

> **Enum quand les valeurs sont FIXES et CONNUES à l'avance.**
> **Value Object quand les valeurs sont ILLIMITÉES mais suivent des RÈGLES.**

```php
// ENUM : valeurs FIXES (3 rôles possibles)
enum UserRole: string
{
    case ADMIN = 'admin';
    case USER = 'user';
    case DOCTOR = 'doctor';
    
    public function getLabel(): string { ... }
}

// VALUE OBJECT : valeurs ILLIMITÉES mais avec RÈGLES
final class EmailAddress extends AbstractValueObject
{
    // Des MILLIONS d'emails possibles !
    public static function fromString(string $email): self
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("Invalid email");
        }
        return new self($email);
    }
    
    public function getDomain(): string { ... }
    public function isGmail(): bool { ... }
}

// Utilisation
$email = EmailAddress::fromString('john@gmail.com');
if ($email->isGmail()) {
    echo "Gmail user";
}

$role = UserRole::ADMIN;
if ($role->isAdmin()) {
    echo "Administrator";
}
```

---

## 15. En résumé : Quand utiliser quoi ?

| Situation | Solution | Exemple |
|-----------|----------|---------|
| **Valeurs FIXES et CONNUES** | **Enum** | `UserRole`, `OrderStatus`, `EuCountry` |
| **Valeurs ILLIMITÉES avec validation** | **Value Object** | `EmailAddress`, `PhoneNumber`, `Age` |
| **Concept métier avec comportement** | **Value Object** | `Money`, `Password`, `ZipCode` |
| **Transport de données interne** | **Record** | `UserRecord`, `OrderRecord` |
| **Réponse API** | **Data** | `UserData`, `OrderData` |