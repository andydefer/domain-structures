# Records - Documentation du Package

## Table des matières

1. [Définition et concepts fondamentaux](#1-définition-et-concepts-fondamentaux)
2. [Record vs Value Object vs Data](#2-record-vs-value-object-vs-data)
3. [Pourquoi utiliser des Records ?](#3-pourquoi-utiliser-des-records-)
4. [Record vs DTO traditionnel](#4-record-vs-dto-traditionnel)
5. [Installation et prérequis](#5-installation-et-prérequis)
6. [Créer son premier Record](#6-créer-son-premier-record)
7. [Les types autorisés dans un Record](#7-les-types-autorisés-dans-un-record)
8. [Hydratation avec HydrationService](#8-hydratation-avec-hydrationservice)
9. [Collections de Records](#9-collections-de-records)
10. [Normalisation : snake_case pour la base de données](#10-normalisation--snake_case-pour-la-base-de-données)
11. [Les Value Objects au cœur des Records](#11-les-value-objects-au-cœur-des-records)
12. [Les collections typées avec des Records](#12-les-collections-typées-avec-des-records)
13. [Nettoyage des valeurs null : `toArrayWithoutNulls()`](#13-nettoyage-des-valeurs-null--toarraywithoutnulls)
14. [Bonnes pratiques](#14-bonnes-pratiques)
15. [Récapitulatif des contraintes](#15-récapitulatif-des-contraintes)
16. [Exemple complet](#16-exemple-complet)

---

## 1. Définition et concepts fondamentaux

Un **Record** est une structure de données **immutable** conçue pour le **transport et la communication interne** entre les couches de votre application. Contrairement aux Value Objects qui encapsulent de la logique métier, les Records sont de **simples conteneurs de données** typées.

```
Record → Transport de données → Communication interne → Immutable → Typé
```

> 💡 **Un Record est idéal pour :**
> - La communication entre Repository et Service
> - Le mapping avec une base de données
> - Les résultats de requêtes
> - L'échange de données entre couches

### Caractéristiques essentielles

| Caractéristique | Description |
|-----------------|-------------|
| **Immutable** | Une fois créé, ne peut jamais être modifié |
| **Type-safe** | Toutes les propriétés sont typées |
| **Sans logique métier** | Ne contient que des données |
| **Hydratable** | Peut être créé depuis n'importe quelle source via HydrationService |
| **Normalisable** | S'exporte en tableau (snake_case) |

---

## 2. Record vs Value Object vs Data

| Aspect | Record | Value Object | Data DTO |
|--------|--------|--------------|----------|
| **Usage principal** | Communication interne | Concepts métier | Réponses HTTP |
| **Logique métier** | ❌ Aucune | ✅ **Peut en avoir** | ❌ Transformation uniquement |
| **Validation** | ❌ Optionnelle | ✅ **OBLIGATOIRE** | ❌ Optionnelle |
| **Constructeur** | Public | **Privé (factory)** | Public |
| **Effets de bord** | ❌ Interdit | ❌ **Interdit** | ❌ Interdit |
| **Peut contenir** | VO, scalaires, TypedCollection, Enum | **VO, scalaires, TypedCollection, Enums** | Data, TypedCollection |
| **Peut contenir des Records** | ✅ Oui | ❌ **Interdit** | ✅ Oui |
| **Nommage** | `UserRecord` | `EmailAddress`, `Money` | `UserData` |
| **Normalisation** | `snake_case` | `mixed` (selon type) | `camelCase` |

---

## 3. Pourquoi utiliser des Records ?

### 3.1. Type-safety à travers l'application

```php
use AndyDefer\DomainStructures\Services\HydrationService;

$hydration = new HydrationService();

// ❌ Tableau associatif non typé
$user = $repository->find(1);
echo $user['name']; // Pas d'autocomplétion

// ✅ Record typé
$user = $repository->find(1);
echo $user->name; // ✅ Autocomplétion IDE
```

### 3.2. Communication claire entre couches

```php
// ❌ Tableau non documenté
public function createUser(array $data): UserRecord
{
    // On ne sait pas ce que contient $data
}

// ✅ Record auto-documenté
public function createUser(UserRecord $record): UserRecord
{
    // Le type parle de lui-même
}
```

### 3.3. Hydratation automatique avec HydrationService

```php
use AndyDefer\DomainStructures\Services\HydrationService;

$hydration = new HydrationService();

// Depuis un tableau
$record = $hydration->hydrate(UserRecord::class, $array);

// Depuis JSON
$record = $hydration->hydrateFromJson(UserRecord::class, $json);

// Depuis un objet existant
$record = $hydration->hydrate(UserRecord::class, $object);
```

---

## 4. Record vs DTO traditionnel

```php
// ❌ DTO traditionnel (mutable, sans typage fort)
class UserDTO
{
    public $id;
    public $name;
    public $email;
}

// ✅ Record (immutable, type-safe, propriétés en snake_case)
final class UserRecord extends AbstractRecord
{
    public function __construct(
        public readonly ?int $user_id,
        public readonly string $name,
        public readonly EmailAddress $email_address,
    ) {}
}
```

| Aspect | DTO traditionnel | Record |
|--------|------------------|--------|
| **Mutabilité** | Mutable | Immutable (`readonly`) |
| **Typage** | Optionnel | Fort (type hint) |
| **Hydratation** | Manuelle | Automatique (HydrationService) |
| **Normalisation** | Manuelle | Automatique (`NormalizerChain`) |
| **Collection** | Tableau générique | TypedCollection |

---

## 5. Installation et prérequis

```bash
composer require andydefer/domain-structures
```

**Prérequis :**
- PHP 8.1 ou supérieur
- Extension JSON activée

---

## 6. Créer son premier Record

```php
<?php

declare(strict_types=1);

namespace App\Records;

use AndyDefer\DomainStructures\Abstracts\AbstractRecord;
use AndyDefer\DomainStructures\Traits\Hydratable;
use App\ValueObjects\EmailAddress;
use App\ValueObjects\Iso8601DateTime;

final class UserRecord extends AbstractRecord
{
    use Hydratable;
    
    public function __construct(
        public readonly ?int $user_id,
        public readonly string $name,
        public readonly EmailAddress $email_address,
        public readonly UserRole $role,
        public readonly Iso8601DateTime $created_at,
    ) {}
}
```

---

## 7. Les types autorisés dans un Record

Les Records ne peuvent contenir que des types spécifiques, garantissant ainsi la cohérence et la sérialisabilité.

### Types scalaires

```php
public readonly ?int $user_id;      // Entier
public readonly string $name;       // Chaîne
public readonly float $price;       // Flottant
public readonly bool $active;       // Booléen
```

### Types objets autorisés

```php
// ✅ Value Objects
public readonly EmailAddress $email_address;        // VO
public readonly Iso8601DateTime $created_at; // VO
public readonly Money $price;               // VO

// ✅ Enums (UnitEnum)
public readonly UserRole $role;             // Enum
public readonly OrderStatus $status;        // Enum

// ✅ Collections typées
public readonly TagCollection $tags;        // TypedCollection

// ✅ Autres Records
public readonly UserRecord $parent;         // Record
```

### Types objets INTERDITS

```php
// ❌ DataObject (réservé aux APIs)
public readonly DataObject $metadata;

// ❌ AbstractData (réservé aux réponses HTTP)
public readonly UserData $userData;

// ❌ DateTime / DateTimeImmutable (utiliser Iso8601DateTime VO)
public readonly \DateTimeImmutable $created_at;
```

---

## 8. Hydratation avec HydrationService

### 8.1. Hydratation d'un seul Record

```php
use AndyDefer\DomainStructures\Services\HydrationService;

$hydration = new HydrationService();

// Depuis un tableau (clés snake_case)
$record = $hydration->hydrate(UserRecord::class, [
    'user_id' => 1,
    'name' => 'John Doe',
    'email_address' => 'john@example.com',
    'role' => 'admin',
    'created_at' => '2024-01-01T12:00:00+00:00'
]);

// Depuis JSON
$json = '{"user_id":1,"name":"John Doe","email_address":"john@example.com","role":"admin","created_at":"2024-01-01T12:00:00+00:00"}';
$record = $hydration->hydrateFromJson(UserRecord::class, $json);
```

### 8.2. Dans une Action

```php
use AndyDefer\DomainStructures\Services\HydrationService;
use AndyDefer\Actions\Http\ResponseFactory;

final class CreateUserAction extends AbstractAction
{
    private HydrationService $hydration;
    
    public function __construct(
        private readonly UserRepository $repository,
    ) {
        $this->hydration = new HydrationService();
    }
    
    protected function handle(AbstractRecord $request): ResponseFactory
    {
        /** @var CreateUserRecord $request */
        
        $record = $this->hydration->hydrate(UserRecord::class, $request->toArray());
        $saved = $this->repository->save($record);
        
        return ResponseFactory::json(
            $this->hydration->hydrate(UserData::class, $saved->toArray()),
            201
        );
    }
}
```

### 8.3. Gestion des types imbriqués

```php
use AndyDefer\DomainStructures\Services\HydrationService;

$hydration = new HydrationService();

// Le Record hydrate automatiquement les Value Objects
$record = $hydration->hydrate(UserRecord::class, [
    'name' => 'John Doe',
    'email_address' => 'john@example.com',      // Devient EmailAddress
    'created_at' => '2024-01-01T12:00:00+00:00'  // Devient Iso8601DateTime
]);

echo $record->email_address->getDomain();        // 'example.com'
echo $record->created_at->toDateTime();   // DateTime object
```

---

## 9. Collections de Records

### 9.1. Hydrater une collection

```php
use AndyDefer\DomainStructures\Services\HydrationService;

$hydration = new HydrationService();

// À partir d'un tableau de données
$users = $hydration->collect([
    ['user_id' => 1, 'name' => 'Alice', 'email_address' => 'alice@example.com'],
    ['user_id' => 2, 'name' => 'Bob', 'email_address' => 'bob@example.com'],
], UserCollection::class);

// Depuis JSON
$json = '[{"user_id":1,"name":"Alice"},{"user_id":2,"name":"Bob"}]';
$users = $hydration->collectFromJson($json, UserCollection::class);
```

### 9.2. Avec une collection personnalisée

```php
// Définir une collection spécifique
final class UserRecordCollection extends AbstractTypedCollection
{
    public function __construct()
    {
        parent::__construct(UserRecord::class);
    }
    
    public function active(): self
    {
        return $this->filter(fn(UserRecord $user) => $user->status === UserStatus::ACTIVE);
    }
}

// Utilisation
$hydration = new HydrationService();
$users = $hydration->collect($dbResults, UserRecordCollection::class);
$activeUsers = $users->active();
```

---

## 10. Normalisation : snake_case pour la base de données

Les Records se normalisent automatiquement en `snake_case` pour faciliter le mapping avec les bases de données :

```php
use AndyDefer\DomainStructures\Services\HydrationService;
use AndyDefer\DomainStructures\Normalizers\NormalizerChain;

$hydration = new HydrationService();

$record = $hydration->hydrate(UserRecord::class, [
    'user_id' => 1,
    'name' => 'John Doe',
    'email_address' => 'john@example.com',
    'email_verified_at' => '2024-01-01T12:00:00+00:00',
    'created_at' => '2024-01-01T12:00:00+00:00'
]);

$normalized = NormalizerChain::get()->normalize($record);
// Résultat :
// [
//     'user_id' => 1,
//     'name' => 'John Doe',
//     'email_address' => 'john@example.com',
//     'email_verified_at' => '2024-01-01T12:00:00+00:00',
//     'created_at' => '2024-01-01T12:00:00+00:00'
// ]
```

### Utilisation avec une base de données

```php
use AndyDefer\DomainStructures\Services\HydrationService;
use AndyDefer\DomainStructures\Normalizers\NormalizerChain;

$hydration = new HydrationService();

// Enregistrer en base
$record = $hydration->hydrate(UserRecord::class, $formData);
$db->insert('users', NormalizerChain::get()->normalize($record));

// Lire depuis la base
$row = $db->fetch('SELECT * FROM users WHERE user_id = 1');
$record = $hydration->hydrate(UserRecord::class, $row);
```

---

## 11. Les Value Objects au cœur des Records

La vraie puissance des Records vient de leur capacité à utiliser des Value Objects pour les propriétés complexes :

```php
final class UserRecord extends AbstractRecord
{
    use Hydratable;
    
    public function __construct(
        public readonly ?int $user_id,
        public readonly string $name,
        public readonly EmailAddress $email_address,        // VO
        public readonly PhoneNumber $phone_number,          // VO
        public readonly Password $password,                 // VO
        public readonly Address $address,                   // VO
        public readonly Iso8601DateTime $created_at,        // VO
        public readonly Iso8601DateTime $updated_at,        // VO
        public readonly UserRole $role,                     // Enum
        public readonly UserStatus $status,                 // Enum
    ) {}
}
```

### Avantages

```php
use AndyDefer\DomainStructures\Services\HydrationService;

$hydration = new HydrationService();

// ✅ L'email est GARANTI valide
$record = $hydration->hydrate(UserRecord::class, [
    'email_address' => 'john@example.com'  // Validé par EmailAddress
]);

// ✅ La date est GARANTIE au format ISO 8601
$record = $hydration->hydrate(UserRecord::class, [
    'created_at' => '2024-01-01T12:00:00+00:00'
]);

// ✅ Comportement disponible
$domain = $record->email_address->getDomain();  // 'example.com'
$isAfter = $record->created_at->isAfter($otherDate);
```

---

## 12. Les collections typées avec des Records

```php
// Définir une collection spécifique
final class UserRecordCollection extends AbstractTypedCollection
{
    public function __construct()
    {
        parent::__construct(UserRecord::class);
    }
    
    public function active(): self
    {
        return $this->filter(fn(UserRecord $user) => $user->status === UserStatus::ACTIVE);
    }
    
    public function withRole(UserRole $role): self
    {
        return $this->filter(fn(UserRecord $user) => $user->role === $role);
    }
    
    public function createdAfter(Iso8601DateTime $date): self
    {
        return $this->filter(fn(UserRecord $user) => $user->created_at->isAfter($date));
    }
}

// Utilisation avec HydrationService
$hydration = new HydrationService();
$users = $hydration->collect($dbResults, UserRecordCollection::class);
$activeAdmins = $users->active()->withRole(UserRole::ADMIN);
```

### Avantages des collections typées

| Sans collection typée | Avec collection typée |
|----------------------|----------------------|
| `array<UserRecord>` | `UserRecordCollection` |
| Pas de méthodes de filtrage | `filter()`, `map()`, `reduce()` |
| Pas de type-safety | Type-safety garantie |
| Pas d'immutabilité | Collections immutables |

---

## 13. Nettoyage des valeurs null : `toArrayWithoutNulls()`

Lorsque vous insérez ou mettez à jour des données en base de données, vous souhaiterez souvent exclure les champs avec des valeurs `null` pour ne mettre à jour que les champs réellement modifiés.

### 13.1. Utilisation de base

```php
use AndyDefer\DomainStructures\Services\HydrationService;

$hydration = new HydrationService();

$record = $hydration->hydrate(UserRecord::class, [
    'user_id' => 1,
    'name' => 'John Doe',
    'email_address' => 'john@example.com',
    'phone_number' => null,
    'address' => null,
    'status' => 'active'
]);

$cleaned = $record->toArrayWithoutNulls();
// Résultat :
// [
//     'user_id' => 1,
//     'name' => 'John Doe',
//     'email_address' => 'john@example.com',
//     'status' => 'active'
// ]

// Idéal pour les mises à jour partielles
$db->update('users', $cleaned, ['user_id' => 1]);
```

### 13.2. Mode récursif (par défaut)

Par défaut, la méthode supprime récursivement les valeurs `null` dans les tableaux et collections imbriqués :

```php
$tags = new TypedCollection('string');
$tags->add('premium', 'vip', 'gold');

$record = $hydration->hydrate(UserRecord::class, [
    'name' => 'John Doe',
    'email_address' => 'john@example.com',
    'tags' => $tags,
    'metadata' => [
        'key1' => 'value1',
        'key2' => null,
        'key3' => [
            'nested1' => 'deep',
            'nested2' => null
        ]
    ]
]);

$cleaned = $record->toArrayWithoutNulls();
// Résultat :
// [
//     'name' => 'John Doe',
//     'email_address' => 'john@example.com',
//     'tags' => ['premium', 'vip', 'gold'],
//     'metadata' => [
//         'key1' => 'value1',
//         'key3' => ['nested1' => 'deep']
//     ]
// ]
```

### 13.3. Cas d'usage : mises à jour partielles

```php
use AndyDefer\DomainStructures\Services\HydrationService;

$hydration = new HydrationService();

// Formulaire de mise à jour - seuls certains champs sont modifiés
$updateData = $hydration->hydrate(UserUpdateRecord::class, [
    'name' => 'Jane Doe',
    'email_address' => null,     // Non modifié
    'phone_number' => null       // Non modifié
]);

$cleaned = $updateData->toArrayWithoutNulls();
// Résultat : ['name' => 'Jane Doe']

// Seul le champ 'name' sera mis à jour
$db->update('users', $cleaned, ['user_id' => 1]);
```

### 13.4. Préservation des valeurs "falsy"

Les valeurs suivantes sont conservées (elles ne sont PAS considérées comme `null`) :

```php
$record = $hydration->hydrate(UserRecord::class, [
    'user_id' => 0,           // Conservé
    'name' => '',             // Conservé (chaîne vide)
    'active' => false,        // Conservé
    'score' => 0.0,           // Conservé
    'tags' => [],             // Conservé (tableau vide)
    'status' => null          // Supprimé
]);

$cleaned = $record->toArrayWithoutNulls();
// Résultat : ['user_id' => 0, 'name' => '', 'active' => false, 'score' => 0.0, 'tags' => []]
```

### 13.5. Tableau récapitulatif

| Valeur | Est supprimée ? | Raison |
|--------|-----------------|--------|
| `null` | ✅ Oui | Valeur null explicite |
| `0` | ❌ Non | Entier zéro est une valeur légitime |
| `''` | ❌ Non | Chaîne vide est une valeur légitime |
| `false` | ❌ Non | Booléen faux est une valeur légitime |
| `[]` | ❌ Non | Tableau vide représente une collection vide |
| `0.0` | ❌ Non | Float zéro est une valeur légitime |

---

## 14. Bonnes pratiques

### 14.1. Toujours utiliser HydrationService

```php
// ✅ Bon
$hydration = new HydrationService();
$record = $hydration->hydrate(UserRecord::class, $data);

// ❌ Mauvais - perte de l'hydratation automatique
$record = new UserRecord(...);
```

### 14.2. Combiner Records et Value Objects

```php
// ✅ Bon - validation déléguée aux VOs
final class UserRecord extends AbstractRecord
{
    use Hydratable;
    
    public function __construct(
        public readonly EmailAddress $email_address,  // Validation intégrée
        public readonly Iso8601DateTime $created_at,  // Validation intégrée
    ) {}
}
```

### 14.3. Utiliser des enums pour les champs à valeurs fixes

```php
// ✅ Bon - Enum pour valeurs fixes
enum UserRole: string
{
    case ADMIN = 'admin';
    case USER = 'user';
}

final class UserRecord extends AbstractRecord
{
    use Hydratable;
    
    public function __construct(
        public readonly UserRole $role,
    ) {}
}
```

### 14.4. Utiliser `toArrayWithoutNulls()` pour les mises à jour

```php
// ✅ Bon - mise à jour partielle
$updateData = $hydration->hydrate(UserUpdateRecord::class, $request->all());
$db->update('users', $updateData->toArrayWithoutNulls(), ['user_id' => $userId]);
```

### 14.5. Utiliser le snake_case pour les noms de propriétés

```php
// ✅ Bon - propriétés en snake_case
final class UserRecord extends AbstractRecord
{
    use Hydratable;
    
    public function __construct(
        public readonly ?int $user_id,
        public readonly string $first_name,
        public readonly string $last_name,
        public readonly EmailAddress $email_address,
        public readonly Iso8601DateTime $created_at,
    ) {}
}
```

---

## 15. Récapitulatif des contraintes

| Contrainte | Règle |
|------------|-------|
| **Héritage** | Étend `AbstractRecord` |
| **Trait** | Utilise `Hydratable` |
| **Constructeur** | Public avec `readonly` |
| **Propriétés** | `public readonly` |
| **Nommage des propriétés** | **`snake_case`** (obligatoire) |
| **Types scalaires autorisés** | `int`, `string`, `float`, `bool`, `null` |
| **Types objets autorisés** | `ValueObject`, `Enum`, `TypedCollection`, `Record` |
| **Types INTERDITS** | `DataObject`, `AbstractData`, `DateTime`, `DateTimeImmutable` |
| **Logique métier** | ❌ **INTERDITE** |
| **Validation** | Délégation aux VOs |
| **Hydratation** | `HydrationService::hydrate()`, `hydrateFromJson()` |
| **Collection** | `HydrationService::collect()`, `collectFromJson()` |
| **Normalisation** | Automatique (`snake_case`) via `NormalizerChain` |
| **Nettoyage des nulls** | `toArrayWithoutNulls()` |

---

## 16. Exemple complet

```php
use AndyDefer\DomainStructures\Services\HydrationService;
use AndyDefer\DomainStructures\Normalizers\NormalizerChain;

// 1. Définir les Value Objects
final class EmailAddress extends AbstractValueObject { /* ... */ }
final class Iso8601DateTime extends AbstractValueObject { /* ... */ }

// 2. Définir les Enums
enum UserRole: string { case ADMIN = 'admin'; case USER = 'user'; }
enum UserStatus: string { case ACTIVE = 'active'; case INACTIVE = 'inactive'; }

// 3. Définir le Record (propriétés en snake_case)
final class UserRecord extends AbstractRecord
{
    use Hydratable;
    
    public function __construct(
        public readonly ?int $user_id,
        public readonly string $name,
        public readonly EmailAddress $email_address,
        public readonly UserRole $role,
        public readonly UserStatus $status,
        public readonly Iso8601DateTime $created_at,
        public readonly ?Iso8601DateTime $updated_at,
    ) {}
}

// 4. Définir un Record de mise à jour
final class UserUpdateRecord extends AbstractRecord
{
    use Hydratable;
    
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?EmailAddress $email_address = null,
        public readonly ?UserRole $role = null,
        public readonly ?UserStatus $status = null,
    ) {}
}

// 5. Définir une collection
final class UserRecordCollection extends AbstractTypedCollection
{
    public function __construct()
    {
        parent::__construct(UserRecord::class);
    }
    
    public function active(): self
    {
        return $this->filter(fn(UserRecord $user) => $user->status === UserStatus::ACTIVE);
    }
}

// 6. Utilisation complète
$hydration = new HydrationService();

// Création
$record = $hydration->hydrate(UserRecord::class, [
    'name' => 'John Doe',
    'email_address' => 'john@example.com',
    'role' => 'admin',
    'status' => 'active',
    'created_at' => '2024-01-01T12:00:00+00:00'
]);

// Sauvegarde
$db->insert('users', NormalizerChain::get()->normalize($record));

// Mise à jour partielle
$updateData = $hydration->hydrate(UserUpdateRecord::class, [
    'name' => 'Jane Smith',
    'role' => 'user'
]);

$db->update('users', $updateData->toArrayWithoutNulls(), ['user_id' => 1]);

// Collection
$users = $hydration->collect($db->fetchAll('SELECT * FROM users'), UserRecordCollection::class);
$activeUsers = $users->active();

// Transformation en Data pour API
$userData = $hydration->hydrate(UserData::class, $record->toArray());
```

---
