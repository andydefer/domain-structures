Tu as raison ! Voici la version corrigée SANS TypedCollection :

```markdown
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
8. [Hydratation avec HydrationService](#8-hydratation-avec-hydrationservice)
9. [Collections de Value Objects](#9-collections-de-value-objects)
10. [Normalisation](#10-normalisation)
11. [Égalité : `equals()`](#11-égalité--equals)
12. [Règles de validation](#12-règles-de-validation)
13. [Bonnes pratiques](#13-bonnes-pratiques)
14. [Récapitulatif des contraintes](#14-récapitulatif-des-contraintes)

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
| **Peut contenir** | VO, scalaires, Enums | VO, scalaires, Enum | Data |
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
$email = new EmailAddress('john@example.com');
echo $email->value;           // 'john@example.com'
echo $email->getDomain();     // 'example.com'
```

---

## 5. Les types supportés par l'hydratation

Le système d'hydratation via `HydrationService` supporte les types suivants :

### Types PHP natifs (scalaires)
- `int` / `integer`
- `float` / `double`
- `string`
- `bool` / `boolean`
- `null`

### Types spécifiques du domaine
- `UnitEnum` - Énumérations PHP
- `AbstractValueObject` - Value Objects
- `AbstractData` - Data DTO
- `AbstractRecord` - Records
- `DataObject` - Objets de données flexibles

---

## 6. Les méthodes fondamentales

Tous les Value Objects héritent de `AbstractValueObject` et bénéficient de :

### 6.1. `getValue(): mixed`

Retourne la valeur brute du Value Object :

```php
$email = new EmailAddress('john@example.com');
$value = $email->getValue(); // 'john@example.com'
```

### 6.2. `equals(self $other): bool`

Compare deux Value Objects :

```php
$email1 = new EmailAddress('john@example.com');
$email2 = new EmailAddress('john@example.com');
$email1->equals($email2); // true
```

### 6.3. `__toString(): string`

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
$money = new Money(99.99, Currency::EUR);
echo $money->amount;     // 99.99 (accès direct !)
echo $money->currency;   // Currency::EUR (accès direct !)

// Pas besoin de getters !
```

---

## 8. Hydratation avec HydrationService

### 8.1. Utilisation du constructeur

```php
// Pour un Value Object à un seul paramètre
$email = new EmailAddress('john@example.com');

// Pour un Value Object à plusieurs paramètres
$money = new Money(99.99, Currency::EUR);
```

### 8.2. Via HydrationService

```php
use AndyDefer\DomainStructures\Services\HydrationService;

$hydration = new HydrationService();

// Depuis un tableau
$money = $hydration->hydrate(Money::class, [
    'amount' => 99.99,
    'currency' => 'EUR'
]);

// Depuis JSON
$json = '{"amount": 99.99, "currency": "EUR"}';
$money = $hydration->hydrateFromJson(Money::class, $json);

// Depuis une valeur simple
$email = $hydration->hydrate(EmailAddress::class, 'john@example.com');
```

### 8.3. Garanties

1. **Validation** : Le format est vérifié dans le constructeur
2. **Immutabilité** : L'objet ne pourra jamais être modifié
3. **Exception** : En cas d'invalidité, une `InvalidArgumentException` est levée

---

## 9. Collections de Value Objects

### 9.1. Avec un tableau simple

```php
$emails = [
    new EmailAddress('john@example.com'),
    new EmailAddress('jane@example.com'),
    new EmailAddress('bob@example.com')
];

foreach ($emails as $email) {
    echo $email->value;
}
```

### 9.2. Avec HydrationService

```php
use AndyDefer\DomainStructures\Services\HydrationService;

$hydration = new HydrationService();

$sources = [
    'john@example.com',
    'jane@example.com',
    'bob@example.com'
];

$emails = [];
foreach ($sources as $source) {
    $emails[] = $hydration->hydrate(EmailAddress::class, $source);
}
```

---

## 10. Normalisation

Les Value Objects se normalisent automatiquement via le `NormalizerChain` :

```php
use AndyDefer\DomainStructures\Normalizers\NormalizerChain;

$email = new EmailAddress('john@example.com');
$normalized = NormalizerChain::get()->normalize($email);
// 'john@example.com' (string)

$money = new Money(99.99, Currency::EUR);
$normalized = NormalizerChain::get()->normalize($money);
// ['amount' => 99.99, 'currency' => 'EUR'] (array)
```

---

## 11. Égalité : `equals()`

```php
public function equals(self $other): bool
```

**Règles de comparaison :**
1. Les objets doivent être de la même classe
2. Pour les valeurs scalaires : comparaison stricte (`===`)
3. Pour les valeurs objets : comparaison par valeur (`==`)

```php
$email1 = new EmailAddress('john@example.com');
$email2 = new EmailAddress('john@example.com');
$email3 = new EmailAddress('jane@example.com');

$email1->equals($email2); // true
$email1->equals($email3); // false
```

---

## 12. Règles de validation

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
$age = new Age(25);
$age->canVote(); // true

try {
    $invalidAge = new Age(-5);
} catch (InvalidArgumentException $e) {
    echo $e->getMessage(); // 'Age cannot be negative'
}
```

---

## 13. Bonnes pratiques

### 13.1. Validation dans le constructeur

```php
// ✅ Bon - validation immédiate
public function __construct(public readonly string $value)
{
    if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
        throw new InvalidArgumentException("Invalid email");
    }
}
```

### 13.2. Utiliser l'accès direct aux propriétés

```php
// ✅ Bon - accès direct
echo $money->amount;

// ❌ Mauvais - getters superflus
echo $money->getAmount();
```

### 13.3. Utiliser le constructeur directement

```php
// ✅ Bon - constructeur direct
$email = new EmailAddress('john@example.com');

// ✅ Bon - HydrationService pour les sources complexes
$email = $hydration->hydrate(EmailAddress::class, 'john@example.com');
```

### 13.4. Profiter de l'immutabilité

```php
// ✅ Bon - crée une nouvelle instance
$newMoney = $money->add($otherMoney);

// ❌ Mauvais - readonly property
$money->amount += $otherMoney->amount;
```

### 13.5. Utiliser `equals()` pour la comparaison

```php
// ✅ Bon
if ($email1->equals($email2)) {
    // ...
}

// ❌ Mauvais - comparaison d'objets
if ($email1 === $email2) {
    // Ne fonctionne que si c'est la même instance
}
```

---

## 14. Récapitulatif des contraintes

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
| **Création** | `new ValueObject(...)` ou `HydrationService::hydrate()` |
| **JSON** | `HydrationService::hydrateFromJson()` |
| **Normalisation** | Automatique via `NormalizerChain` |

---

## Exemple complet

```php
use AndyDefer\DomainStructures\Services\HydrationService;
use AndyDefer\DomainStructures\Normalizers\NormalizerChain;

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

// 2. Utilisation avec constructeur direct
$email = new EmailAddress('john@example.com');
echo $email->value;           // 'john@example.com'
echo $email->getDomain();     // 'example.com'

// 3. Utilisation avec HydrationService
$hydration = new HydrationService();
$email = $hydration->hydrate(EmailAddress::class, 'john@example.com');

// 4. Collection simple
$emails = [
    new EmailAddress('john@example.com'),
    new EmailAddress('jane@gmail.com'),
    new EmailAddress('bob@yahoo.com')
];

// 5. Filtrage manuel
$gmailEmails = array_filter($emails, fn($email) => $email->getDomain() === 'gmail.com');

// 6. Normalisation
$normalized = NormalizerChain::get()->normalize($email);
// 'john@example.com'

// 7. Égalité
$email1 = new EmailAddress('john@example.com');
$email2 = new EmailAddress('john@example.com');
$email1->equals($email2); // true
```

---
