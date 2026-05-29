# Value Objects - Documentation du Package domain-structures
---

## Table des matières

1. [Définition et concepts fondamentaux](#1-définition-et-concepts-fondamentaux)
2. [Value Object vs Record vs Data](#2-value-object-vs-record-vs-data)
3. [Installation](#3-installation)
4. [Créer son premier Value Object](#4-créer-son-premier-value-object)
5. [Les types supportés par l'hydratation](#5-les-types-supportés-par-lhydratation)
6. [Les méthodes fondamentales](#6-les-méthodes-fondamentales)
7. [Accès direct aux propriétés](#7-accès-direct-aux-propriétés)
8. [Hydratation : `from()`](#8-hydratation--from)
9. [Hydratation JSON : `fromJson()`](#9-hydratation-json--fromjson)
10. [Collections de Value Objects : `collect()`](#10-collections-de-value-objects--collect)
11. [Normalisation](#11-normalisation)
12. [Égalité : `equals()`](#12-égalité--equals)
13. [Règles de validation](#13-règles-de-validation)
14. [Bonnes pratiques](#14-bonnes-pratiques)
15. [Récapitulatif des contraintes](#15-récapitulatif-des-contraintes)

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
| **Accès direct aux propriétés** | Via le trait `HasPropertiesAccess` |

---

## 2. Value Object vs Record vs Data

| Aspect | Value Object | Record | Data DTO |
|--------|--------------|--------|----------|
| **Usage principal** | Concepts métier | Communication interne | Réponses HTTP |
| **Logique métier** | ✅ **Peut en avoir** | ❌ Aucune | ❌ Transformation uniquement |
| **Validation** | ✅ **OBLIGATOIRE** | ❌ Optionnelle | ❌ Optionnelle |
| **Constructeur** | **Public avec validation** | Public | Public |
| **Peut contenir** | VO, scalaires, TypedCollection, Enums | VO, scalaires, TypedCollection, Enum | Data, TypedCollection |
| **Peut contenir des Records** | ❌ **Interdit** | ✅ Oui | ✅ Oui |
| **Nommage** | `EmailAddress`, `Money` | `UserRecord` | `UserData` |
| **Normalisation** | `mixed` (selon type) | `snake_case` | `camelCase` |

---

## 3. Installation

```bash
composer require andydefer/domain-structures
```

**Prérequis :**
- PHP 8.1 ou supérieur
- Extension JSON activée

---

## 4. Créer son premier Value Object

```php
<?php

declare(strict_types=1);

namespace App\ValueObjects;

use AndyDefer\DomainStructures\Abstracts\AbstractValueObject;
use InvalidArgumentException;

final class EmailAddress extends AbstractValueObject
{
    public function __construct(public readonly string $value)
    {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("Invalid email: {$value}");
        }
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

// Utilisation
$email = EmailAddress::from('john@example.com');
echo $email->value;           // 'john@example.com'
echo $email->getDomain();     // 'example.com'
```

---

## 5. Les types supportés par l'hydratation

Le système d'hydratation (`from()`) supporte les types suivants :

### Types PHP natifs (scalaires)
- `int` / `integer`
- `float` / `double`
- `string`
- `bool` / `boolean`
- `null`

### Types spécifiques du domaine
- `UnitEnum` - Énumérations PHP
- `AbstractValueObject` - Value Objects
- `AbstractTypedCollection` - Collections typées
- `AbstractData` - Data DTO
- `AbstractRecord` - Records
- `DataObject` - Objets de données flexibles

> ⚠️ **Important :** Pour certains types complexes, l'hydratation via le constructeur peut être difficile. Utilisez toujours la méthode `from()` qui gère automatiquement tous ces cas.

---

## 6. Les méthodes fondamentales

Tous les Value Objects héritent de `AbstractValueObject` et bénéficient de :

### 6.1. `from(mixed $source): static`

Point d'entrée unique pour créer des Value Objects :

```php
$email = EmailAddress::from('john@example.com');
$money = Money::from(['amount' => 99.99, 'currency' => 'EUR']);
```

### 6.2. `fromJson(string $json): static`

Hydratation depuis JSON :

```php
$email = EmailAddress::fromJson('"john@example.com"');
$money = Money::fromJson('{"amount":99.99,"currency":"EUR"}');
```

### 6.3. `collect(iterable $sources, string $collectionClass): AbstractTypedCollection`

Collection typée de Value Objects :

```php
$emails = EmailAddress::collect([
    'john@example.com',
    'jane@example.com',
    'bob@example.com'
]);
```

### 6.4. `getValue(): mixed`

Retourne la valeur brute du Value Object :

```php
$email = EmailAddress::from('john@example.com');
$value = $email->getValue(); // 'john@example.com'
```

### 6.5. `equals(self $other): bool`

Compare deux Value Objects :

```php
$email1 = EmailAddress::from('john@example.com');
$email2 = EmailAddress::from('john@example.com');
$email1->equals($email2); // true
```

### 6.6. `__toString(): string`

Convertit automatiquement en JSON :

```php
echo $email; // "john@example.com"
```

---

## 7. Accès direct aux propriétés

`AbstractValueObject` intègre le trait `HasPropertiesAccess`, permettant l'accès direct aux propriétés privées/protected :

```php
final class Money extends AbstractValueObject
{
    public function __construct(
        private readonly float $amount,
        private readonly Currency $currency
    ) {
        if ($amount <= 0) {
            throw new InvalidArgumentException("Amount must be positive");
        }
    }
    
    public function add(self $other): self
    {
        return new self($this->amount + $other->amount, $this->currency);
    }
}

// Utilisation
$money = Money::from(['amount' => 99.99, 'currency' => 'EUR']);
echo $money->amount;     // 99.99 (accès direct !)
echo $money->currency;   // Currency::EUR (accès direct !)

// Pas besoin de getters !
```

---

## 8. Hydratation : `from()`

La méthode `from()` accepte de multiples formats :

```php
// 1. Depuis une chaîne simple
$email = EmailAddress::from('john@example.com');

// 2. Depuis un tableau
$money = Money::from(['amount' => 99.99, 'currency' => 'EUR']);

// 3. Depuis un objet
$profile = UserProfile::from($someObject);

// 4. Depuis un autre Value Object (retourne l'original)
$email2 = EmailAddress::from($email);
```

### Garanties

1. **Validation** : Le format est vérifié dans le constructeur
2. **Immutabilité** : L'objet ne pourra jamais être modifié
3. **Exception** : En cas d'invalidité, une `InvalidArgumentException` est levée

---

## 9. Hydratation JSON : `fromJson()`

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

```php
$emails = EmailAddress::collect([
    'john@example.com',
    'jane@example.com',
    'bob@example.com'
]);

foreach ($emails as $email) {
    echo $email->value;
}
```

### Collection personnalisée

```php
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

$collection = EmailAddress::collect($sources, EmailCollection::class);
$gmailEmails = $collection->fromDomain('gmail.com');
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

---

## 12. Égalité : `equals()`

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
```

---

## 13. Règles de validation

Les Value Objects doivent se valider dans le **constructeur** :

```php
final class Age extends AbstractValueObject
{
    public function __construct(public readonly int $value)
    {
        if ($value < 0) {
            throw new InvalidArgumentException('Age cannot be negative');
        }
        
        if ($value > 150) {
            throw new InvalidArgumentException('Age cannot exceed 150');
        }
    }
    
    public function canVote(): bool { return $this->value >= 18; }
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

## 14. Bonnes pratiques

### 14.1. Validation dans le constructeur

```php
// ✅ Bon - validation immédiate
public function __construct(public readonly string $value)
{
    if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
        throw new InvalidArgumentException("Invalid email");
    }
}
```

### 14.2. Utiliser l'accès direct aux propriétés

```php
// ✅ Bon - accès direct
echo $money->amount;

// ❌ Mauvais - getters superflus
echo $money->getAmount();
```

### 14.3. Toujours utiliser `from()`

```php
// ✅ Bon
$email = EmailAddress::from('john@example.com');

// ❌ Mauvais - contourne l'hydratation
$email = new EmailAddress('john@example.com');
```

### 14.4. Profiter de l'immutabilité

```php
// ✅ Bon - crée une nouvelle instance
$newMoney = $money->add($otherMoney);

// ❌ Mauvais - readonly property
$money->amount += $otherMoney->amount;
```

---

## 15. Récapitulatif des contraintes

| Contrainte | Règle |
|------------|-------|
| **Package** | `andydefer/domain-structures` |
| **Héritage** | Étend `AbstractValueObject` |
| **Constructeur** | ✅ **PUBLIC** avec validation |
| **Validation** | ✅ **OBLIGATOIRE** dans le constructeur |
| **Effets de bord** | ❌ **STRICTEMENT INTERDITS** |
| **État mutable** | ❌ Interdit (`readonly` obligatoire) |
| **Peut contenir des Records** | ❌ **INTERDIT** |
| **Accès propriétés** | Direct via `HasPropertiesAccess` (intégré) |
| **Point d'entrée** | `from()` - hérité |
| **JSON** | `fromJson()` - hérité |
| **Collection** | `collect()` - hérité |

---

## Exemple complet

```php
// 1. Définir le Value Object
final class EmailAddress extends AbstractValueObject
{
    public function __construct(public readonly string $value)
    {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("Invalid email: {$value}");
        }
    }
    
    public function getDomain(): string
    {
        return substr(strrchr($this->value, '@'), 1);
    }
}

// 2. Utilisation
$email = EmailAddress::from('john@example.com');
echo $email->value;           // 'john@example.com'
echo $email->getDomain();     // 'example.com'

// 3. Collection
$emails = EmailAddress::collect([
    'john@example.com',
    'jane@example.com'
]);

// 4. Normalisation
$normalized = NormalizerChain::get()->normalize($email);
// 'john@example.com'
```

---

## Support

- **Infrastructure (Value Objects, Records, Data)** : `andydefer/domain-structures`
- **Value Objects réutilisables (Email, Money, etc.)** : `andydefer/php-vo`
