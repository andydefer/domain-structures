# Value Objects - Documentation du Package

---

## 1. Définition et concepts fondamentaux

Un **Value Object (VO)** est une structure de données **immutable**, **stateless** et **auto-validante** qui représente un **concept métier** avec son propre comportement.

```
Value Object → Concept métier → Validation OBLIGATOIRE → Pas d'identité propre → Immutable
```

> ⚠️ **Un Value Object est STRICTEMENT stateless et ne peut contenir aucune logique avec effets de bord (cache, base de données, HTTP, logs).**

### Caractéristiques essentielles

| Caractéristique | Description |
|-----------------|-------------|
| **Immutable** | Une fois créé, ne peut jamais être modifié |
| **Sans identifiant** | Pas de propriété `id`, l'identité est définie par ses valeurs |
| **Auto-validant** | Se valide à la construction, jamais d'état invalide |
| **Égalité par valeur** | Deux VOs sont égaux si toutes leurs propriétés sont égales |
| **Comportement métier** | Encapsule la logique liée au concept |

---

## 2. Record vs Value Object vs Data

| Aspect | Record | Value Object | Data DTO |
|--------|--------|--------------|----------|
| **Usage principal** | Communication interne | Concepts métier | Réponses HTTP |
| **Logique métier** | ❌ Aucune | ✅ **Peut en avoir** | ❌ Transformation uniquement |
| **Validation** | ❌ Optionnelle | ✅ **OBLIGATOIRE** | ❌ Optionnelle |
| **Constructeur** | Public | **Privé (factory)** | Public |
| **Effets de bord** | ❌ Interdit | ❌ **Interdit** | ❌ Interdit |
| **Peut contenir** | VO, scalaires, TypedCollection | **VO, scalaires, TypedCollection, Enums** | Data, TypedCollection |
| **Peut contenir des Records** | ✅ Oui | ❌ **Interdit** | ✅ Oui |
| **Nommage** | `UserRecord` | `EmailAddress`, `Money` | `UserData` |

---

## 3. Pourquoi utiliser des Value Objects ?

### 3.1. Le problème des types primitifs

```php
// ❌ On ne sait pas ce qu'est cette chaîne
function sendEmail(string $email): void
{
    // L'email n'est pas validé
    // Pas de comportement attaché
}

// ✅ Le type explicite le concept
function sendEmail(EmailAddress $email): void
{
    // L'email est GARANTI valide
    // On peut appeler $email->getDomain()
}
```

### 3.2. Validation centralisée

```php
// ❌ Validation dispersée dans plusieurs services
class UserService {
    public function updateEmail(string $email): void {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { ... }
    }
}

// ✅ Validation centralisée dans le Value Object
final class EmailAddress extends AbstractValueObject
{
    public static function from(mixed $source): static
    {
        if (!filter_var($source, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("Invalid email: {$source}");
        }
        return new self($source);
    }
}
```

### 3.3. Comportement attaché à la donnée

```php
// ❌ Logique métier dispersée
if (str_ends_with($email, '@gmail.com')) {
    // ...
}

// ✅ Comportement attaché au concept
if ($email->isGmail()) {
    // ...
}
```

---

## 4. Enum vs Value Object

> **Enum = ensemble FIXE de valeurs (fini, connu à l'avance)**
> **Value Object = concept OUVERT avec validation (infini, règles métier)**

```php
// ENUM : valeurs FIXES et CONNUES (3 rôles possibles)
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
    public static function from(mixed $source): static
    {
        if (!filter_var($source, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("Invalid email");
        }
        return new self($source);
    }
    
    public function getDomain(): string { ... }
    public function isGmail(): bool { ... }
}
```

### Quand utiliser Enum vs Value Object ?

| Situation | Solution | Exemple |
|-----------|----------|---------|
| **Valeurs FIXES et CONNUES** | **Enum** | `UserRole`, `OrderStatus` |
| **Valeurs ILLIMITÉES avec validation** | **Value Object** | `EmailAddress`, `PhoneNumber` |
| **Concept métier avec comportement** | **Value Object** | `Money`, `Age` |

---

## 5. Installation et prérequis

```bash
composer require andydefer/domain-structures
```

**Prérequis :**
- PHP 8.1 ou supérieur
- Extension JSON activée

---

## 6. Créer son premier Value Object

```php
<?php

declare(strict_types=1);

namespace App\ValueObjects;

use AndyDefer\DomainStructures\Abstracts\AbstractValueObject;
use InvalidArgumentException;

final class EmailAddress extends AbstractValueObject
{
    private function __construct(public readonly string $value) {}
    
    public static function from(mixed $source): static
    {
        if ($source instanceof self) {
            return $source;
        }
        
        if (!is_string($source)) {
            throw new InvalidArgumentException('Email must be a string');
        }
        
        if (!filter_var($source, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("Invalid email: {$source}");
        }
        
        return new self($source);
    }
    
    public function getValue(): string
    {
        return $this->value;
    }
    
    public function getDomain(): string
    {
        return substr(strrchr($this->value, '@'), 1);
    }
    
    public function isGmail(): bool
    {
        return $this->getDomain() === 'gmail.com';
    }
}
```

---

## 7. Les méthodes fondamentales

Tous les Value Objects héritent de `AbstractValueObject` et bénéficient de ces méthodes :

### 7.1. `getValue(): mixed`

Retourne la valeur brute du Value Object :

```php
$email = EmailAddress::from('john@example.com');
$value = $email->getValue(); // 'john@example.com' (string)

$money = Money::from(['amount' => 99.99, 'currency' => 'EUR']);
$value = $money->getValue(); // MoneyRecord (objet)
```

### 7.2. `equals(self $other): bool`

Compare deux Value Objects :

```php
$email1 = EmailAddress::from('john@example.com');
$email2 = EmailAddress::from('john@example.com');

$email1->equals($email2); // true
```

### 7.3. `__toString(): string`

Convertit automatiquement en JSON :

```php
echo $email; // "john@example.com"
echo $money; // {"amount":99.99,"currency":"EUR"}
```

---

## 8. Hydratation : le point d'entrée unique `from()`

La méthode `from()` est le **point d'entrée unique** pour créer des Value Objects. Elle accepte de multiples formats :

### Sources supportées

```php
// 1. Depuis une chaîne simple
$email = EmailAddress::from('john@example.com');

// 2. Depuis un tableau
$money = Money::from(['amount' => 99.99, 'currency' => 'EUR']);

// 3. Depuis un objet
$profile = UserProfile::from($someObject);

// 4. Depuis un autre Value Object (retourne l'original)
$email2 = EmailAddress::from($email);

// 5. Depuis un DataObject
$dataObject = DataObject::from(['lat' => 48.8566, 'lng' => 2.3522]);
$coords = Coordinates::from($dataObject);
```

### Garanties

1. **Validation** : Le format est vérifié à la construction
2. **Immutabilité** : L'objet ne pourra jamais être modifié
3. **Exception** : En cas d'invalidité, une `InvalidArgumentException` est levée

---

## 9. Hydratation JSON : `fromJson()`

La méthode `fromJson()` permet d'hydrater directement depuis une chaîne JSON :

```php
$json = '"john@example.com"';
$email = EmailAddress::fromJson($json);

$json = '{"amount": 99.99, "currency": "EUR"}';
$money = Money::fromJson($json);
```

**Avantages :**
- Une seule ligne pour décoder et hydrater
- Gestion automatique des erreurs JSON
- Comportement cohérent avec `from()`

---

## 10. Collections de Value Objects : `collect()`

La méthode `collect()` hydrate une collection typée de Value Objects :

```php
$emails = EmailAddress::collect([
    'john@example.com',
    'jane@example.com',
    'bob@example.com'
]);

// $emails est un TypedCollection<EmailAddress>
foreach ($emails as $email) {
    echo $email->getValue(); // john@example.com, etc.
}
```

### Collection personnalisée

```php
// Créer une collection spécialisée
final class EmailCollection extends AbstractTypedCollection
{
    public function __construct()
    {
        parent::__construct(EmailAddress::class);
    }
    
    public function fromDomain(string $domain): self
    {
        return $this->filter(fn(EmailAddress $email) => $email->getDomain() === $domain);
    }
}

// Utilisation
$collection = EmailAddress::collect($sources, EmailCollection::class);
$gmailEmails = $collection->fromDomain('gmail.com');
```

### Cas d'usage typiques

```php
// Depuis une API
$emails = EmailAddress::collect(json_decode($apiResponse, true));

// Depuis une base de données
$emails = EmailAddress::collect($dbResults);

// Depuis un fichier CSV
$emails = EmailAddress::collect(array_column($csvData, 'email'));
```

---

## 11. Normalisation

Les Value Objects se normalisent automatiquement via le `NormalizerChain` :

```php
$email = EmailAddress::from('john@example.com');
$normalized = NormalizerChain::get()->normalize($email);
// 'john@example.com' (string)

$money = Money::from(['amount' => 99.99, 'currency' => 'EUR']);
$normalized = NormalizerChain::get()->normalize($money);
// ['amount' => 99.99, 'currency' => 'EUR'] (array)
```

La méthode `__toString()` utilise cette normalisation :

```php
echo $email; // "john@example.com"
echo $money; // {"amount":99.99,"currency":"EUR"}
```

---

## 12. Égalité : la méthode `equals()`

```php
public function equals(self $other): bool
```

**Règles de comparaison :**

1. Les objets doivent être de la même classe
2. Pour les valeurs scalaires : comparaison stricte (`===`)
3. Pour les valeurs objets : comparaison par valeur (`==`)

```php
$email1 = EmailAddress::from('john@example.com');
$email2 = EmailAddress::from('john@example.com');
$email3 = EmailAddress::from('jane@example.com');

$email1->equals($email2); // true
$email1->equals($email3); // false
$email1->equals(new PostalCode('75001')); // false (type différent)
```

---

## 13. Immutable par conception

```php
// ❌ PAS de setters - impossible de modifier
$email->setValue('new@example.com'); // N'existe pas !

// ✅ Pour "modifier", on crée une nouvelle instance
$money = Money::from(['amount' => 10.00, 'currency' => 'EUR']);
$total = $money->add(Money::from(['amount' => 5.00, 'currency' => 'EUR']));

// $money reste inchangé (10.00)
// $total est une nouvelle instance (15.00)
```

---

## 14. Ce qu'un Value Object peut contenir

| Type | Exemple | Autorisation |
|------|---------|--------------|
| `int`, `string`, `float`, `bool` | `public readonly int $value` | ✅ Oui |
| `Enum` | `public readonly Currency $currency` | ✅ Oui |
| `Value Object` | `public readonly EmailAddress $email` | ✅ Oui |
| `TypedCollection` | `public readonly TypedCollection $tags` | ✅ Oui |

### Ce qu'un Value Object NE PEUT PAS contenir

| Type interdit | Alternative |
|---------------|-------------|
| `Record` | Utiliser un VO ou scalaire |
| `Data` | Utiliser un VO |
| `Model` Eloquent / Doctrine | Utiliser un Record |
| `DateTime` / `DateTimeImmutable` | `string` ISO 8601 via `Iso8601DateTime` VO |
| Services, Cache, DB, HTTP, Logs | Injecter via des services externes |

---

## 15. Règles de validation

Les Value Objects doivent se valider à la construction. Toute valeur invalide doit lever une exception :

```php
final class Age extends AbstractValueObject
{
    private function __construct(public readonly int $value) {}
    
    public static function from(mixed $source): static
    {
        if (!is_int($source) && !is_numeric($source)) {
            throw new InvalidArgumentException('Age must be a number');
        }
        
        $age = (int) $source;
        
        if ($age < 0) {
            throw new InvalidArgumentException('Age cannot be negative');
        }
        
        if ($age > 150) {
            throw new InvalidArgumentException('Age cannot exceed 150');
        }
        
        return new self($age);
    }
    
    public function getValue(): int { return $this->value; }
    
    // Règles métier
    public function canVote(): bool { return $this->value >= 18; }
    public function canDrive(): bool { return $this->value >= 16; }
}

// Utilisation
$age = Age::from(25);
$age->canVote(); // true

try {
    $invalidAge = Age::from(-5);
} catch (InvalidArgumentException $e) {
    echo $e->getMessage(); // 'Age cannot be negative'
}
```

---

## 16. Exemples concrets

### 16.1. EmailAddress

```php
final class EmailAddress extends AbstractValueObject
{
    private function __construct(public readonly string $value) {}
    
    public static function from(mixed $source): static
    {
        if ($source instanceof self) return $source;
        if (!is_string($source)) throw new InvalidArgumentException('Email must be a string');
        if (!filter_var($source, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("Invalid email: {$source}");
        }
        return new self($source);
    }
    
    public function getValue(): string { return $this->value; }
    public function getDomain(): string { return substr(strrchr($this->value, '@'), 1); }
    public function isGmail(): bool { return $this->getDomain() === 'gmail.com'; }
    public function obfuscate(): string { /* ... */ }
}

// Utilisation
$email = EmailAddress::from('john@gmail.com');
echo $email->isGmail();   // true
echo $email->getDomain(); // 'gmail.com'
echo $email;              // "john@gmail.com"
```

### 16.2. Money

```php
final class Money extends AbstractValueObject
{
    private function __construct(public readonly MoneyRecord $value) {}
    
    public static function from(mixed $source): static
    {
        if ($source instanceof self) return $source;
        
        $data = is_array($source) ? $source : json_decode($source, true);
        $amount = (float) ($data['amount'] ?? 0);
        $currency = Currency::from($data['currency'] ?? 'EUR');
        
        if ($amount < 0) {
            throw new InvalidArgumentException('Amount must be positive');
        }
        
        return new self(new MoneyRecord($amount, $currency));
    }
    
    public function getValue(): MoneyRecord { return $this->value; }
    public function add(self $other): self { /* ... */ }
    public function format(): string { return $this->value->currency->symbol() . number_format($this->value->amount, 2); }
}
```

### 16.3. Coordinates

```php
final class Coordinates extends AbstractValueObject
{
    private function __construct(
        public readonly float $latitude,
        public readonly float $longitude
    ) {}
    
    public static function from(mixed $source): static
    {
        if ($source instanceof self) return $source;
        
        if (is_string($source) && str_contains($source, ',')) {
            [$lat, $lng] = array_map('floatval', explode(',', $source));
        } elseif (is_array($source)) {
            $lat = (float) ($source['lat'] ?? $source['latitude'] ?? 0);
            $lng = (float) ($source['lng'] ?? $source['longitude'] ?? 0);
        } else {
            throw new InvalidArgumentException('Invalid coordinates source');
        }
        
        if ($lat < -90 || $lat > 90) {
            throw new InvalidArgumentException("Latitude {$lat} out of range");
        }
        if ($lng < -180 || $lng > 180) {
            throw new InvalidArgumentException("Longitude {$lng} out of range");
        }
        
        return new self($lat, $lng);
    }
    
    public function getValue(): array { return ['latitude' => $this->latitude, 'longitude' => $this->longitude]; }
    public function distanceTo(self $other): float { /* Haversine formula */ }
}
```

---

## 17. Bonnes pratiques

### 17.1. Toujours utiliser `from()` pour créer des Value Objects

```php
// ✅ Bon
$email = EmailAddress::from('john@example.com');

// ❌ Mauvais - le constructeur est privé de toute façon
$email = new EmailAddress('john@example.com');
```

### 17.2. Laisser le NormalizerChain gérer la normalisation

```php
// ✅ Bon
$normalized = NormalizerChain::get()->normalize($email);

// ❌ Mauvais
$normalized = $email->getValue(); // Pas toujours le comportement attendu
```

### 17.3. Utiliser `collect()` pour les lots

```php
// ✅ Bon - une ligne
$emails = EmailAddress::collect($apiResponse);

// ❌ Mauvais - boucle manuelle
$emails = [];
foreach ($apiResponse as $item) {
    $emails[] = EmailAddress::from($item);
}
```

### 17.4. Profiter de l'immutabilité

```php
// ✅ Bon
$newMoney = $money->add($otherMoney);

// ❌ Mauvais - n'existe pas
$money->addToSelf($otherMoney);
```

### 17.5. Valider tôt, valider souvent

```php
// ✅ Bon - validation au point d'entrée
public function updateUser(EmailAddress $email, Age $age): void
{
    // email et age sont DÉJÀ valides
}

// ❌ Mauvais - validation repoussée
public function updateUser(string $email, int $age): void
{
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { ... }
    if ($age < 0 || $age > 150) { ... }
}
```

---

## 18. Récapitulatif des contraintes

| Contrainte | Règle |
|------------|-------|
| **Héritage** | Étend `AbstractValueObject` |
| **Constructeur** | ✅ **PRIVÉ** |
| **Validation** | ✅ **OBLIGATOIRE** dans `from()` |
| **Effets de bord** | ❌ **STRICTEMENT INTERDITS** |
| **État mutable** | ❌ Interdit (`readonly` obligatoire) |
| **Peut contenir** | VO, scalaires, TypedCollection, Enums |
| **Peut contenir des Records/Data** | ❌ **INTERDIT** |
| **Point d'entrée** | `from()` - unique |
| **JSON** | `fromJson()` |
| **Collection** | `collect()` |
| **Testabilité** | ✅ Excellente (pas de mocks) |

---

## En résumé : Quand utiliser quoi ?

| Situation | Solution | Exemple |
|-----------|----------|---------|
| **Valeurs FIXES et CONNUES** | **Enum** | `UserRole`, `OrderStatus` |
| **Valeurs ILLIMITÉES avec validation** | **Value Object** | `EmailAddress`, `PhoneNumber` |
| **Concept métier avec comportement** | **Value Object** | `Money`, `Age`, `Coordinates` |
| **Transport de données interne** | **Record** | `UserRecord`, `OrderRecord` |
| **Réponse API** | **Data** | `UserData`, `OrderData` |

---

## Support

Pour toute question ou suggestion, n'hésitez pas à :
- Ouvrir une issue sur GitHub
- Consulter la documentation complète
- Contacter l'équipe de développement
