# Records - Documentation du Package

## Table des matières

1. [Définition et concepts fondamentaux](#1-définition-et-concepts-fondamentaux)
2. [Record vs Value Object vs Data](#2-record-vs-value-object-vs-data)
3. [Pourquoi utiliser des Records ?](#3-pourquoi-utiliser-des-records-)
4. [Record vs DTO traditionnel](#4-record-vs-dto-traditionnel)
5. [Installation et prérequis](#5-installation-et-prérequis)
6. [Créer son premier Record](#6-créer-son-premier-record)
7. [Les types autorisés dans un Record](#7-les-types-autorisés-dans-un-record)
8. [Les méthodes fondamentales](#8-les-méthodes-fondamentales)
9. [Hydratation : le point d'entrée unique `from()`](#9-hydratation--le-point-dentrée-unique-from)
10. [Hydratation JSON : `fromJson()`](#10-hydratation-json--fromjson)
11. [Collections de Records : `collect()`](#11-collections-de-records--collect)
12. [Normalisation : snake_case pour la base de données](#12-normalisation--snake_case-pour-la-base-de-données)
13. [Les Value Objects au cœur des Records](#13-les-value-objects-au-cœur-des-records)
14. [Les collections typées avec des Records](#14-les-collections-typées-avec-des-records)
15. [Nettoyage des valeurs null : `toArrayWithoutNulls()`](#15-nettoyage-des-valeurs-null--toarraywithoutnulls)
16. [Bonnes pratiques](#16-bonnes-pratiques)
17. [Récapitulatif des contraintes](#17-récapitulatif-des-contraintes)
18. [Exemple complet](#18-exemple-complet)

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
| **Hydratable** | Peut être créé depuis n'importe quelle source |
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
// ❌ Tableau associatif non typé
$user = $repository->find(1);
echo $user['name']; // Pas d'autocomplétion
$user['email'] = 'invalid'; // Pas de validation

// ✅ Record typé
$user = $repository->find(1);
echo $user->name; // ✅ Autocomplétion IDE
$user->email = 'invalid'; // ❌ Impossible (readonly)
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

// Utilisation
$record = UserRecord::from([
    'name' => 'John Doe',
    'email' => 'john@example.com'
]);
```

### 3.3. Hydratation automatique depuis n'importe quelle source

```php
// Depuis un tableau
$record = UserRecord::from($array);

// Depuis un objet
$record = UserRecord::from($object);

// Depuis un DataObject
$record = UserRecord::from($dataObject);

// Depuis une requête SQL
$record = UserRecord::from($dbRow);
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
        public readonly ?int $id,
        public readonly string $name,
        public readonly EmailAddress $email_address,
    ) {}
}
```

| Aspect | DTO traditionnel | Record |
|--------|------------------|--------|
| **Mutabilité** | Mutable | Immutable (`readonly`) |
| **Typage** | Optionnel | Fort (type hint) |
| **Hydratation** | Manuelle | Automatique (`from()`) |
| **Normalisation** | Manuelle | Automatique (`normalize()`) |
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
use App\ValueObjects\EmailAddress;
use App\ValueObjects\Iso8601DateTime;

final class UserRecord extends AbstractRecord
{
    public function __construct(
        public readonly ?int $id,
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
public readonly ?int $id;      // Entier
public readonly string $name;  // Chaîne
public readonly float $price;  // Flottant
public readonly bool $active;  // Booléen
public readonly ?null $value;  // Null
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

### Pourquoi `\DateTimeImmutable` est interdit ?

```php
// ❌ Mauvais
public readonly \DateTimeImmutable $created_at;

// ✅ Bon - Utiliser le Value Object Iso8601DateTime
public readonly Iso8601DateTime $created_at;
```

**Raisons :**
1. `\DateTimeImmutable` n'implémente pas `Transformable`
2. Ne peut pas être hydraté automatiquement depuis une string
3. Le format de sérialisation n'est pas standardisé
4. `Iso8601DateTime` garantit un format ISO 8601 valide

---

## 8. Les méthodes fondamentales

Tous les Records héritent de `AbstractRecord` et bénéficient de ces méthodes via le trait `Hydratable` :

### 8.1. `from(mixed $source): static`

Crée un Record depuis n'importe quelle source :

```php
$record = UserRecord::from([
    'id' => 1,
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'role' => 'admin',
    'created_at' => '2024-01-01T12:00:00+00:00'
]);
```

### 8.2. `fromJson(string $json): static`

Crée un Record depuis une chaîne JSON :

```php
$json = '{"id":1,"name":"John Doe","email":"john@example.com","role":"admin","created_at":"2024-01-01T12:00:00+00:00"}';
$record = UserRecord::fromJson($json);
```

### 8.3. `collect(iterable $sources, string $collectionClass): TypedCollection`

Crée une collection typée de Records :

```php
$users = UserRecord::collect($dbResults);
$activeUsers = $users->filter(fn($user) => $user->status === 'active');
```

### 8.4. `__toString(): string`

Convertit automatiquement en JSON (snake_case) :

```php
echo $record; // JSON avec clés snake_case
```

---

## 9. Hydratation : le point d'entrée unique `from()`

La méthode `from()` accepte de multiples formats :

```php
// 1. Depuis un tableau
$record = UserRecord::from([
    'id' => 1,
    'name' => 'John',
    'email' => 'john@example.com'
]);

// 2. Depuis un objet
$record = UserRecord::from($someObject);

// 3. Depuis un DataObject
$dataObject = DataObject::from(['name' => 'John', 'email' => 'john@example.com']);
$record = UserRecord::from($dataObject);

// 4. Depuis un autre Record (retourne l'original)
$record2 = UserRecord::from($record);
```

### Gestion des types imbriqués

```php
// Le Record hydrate automatiquement les Value Objects
$record = UserRecord::from([
    'name' => 'John Doe',
    'email' => 'john@example.com',      // Devient EmailAddress
    'created_at' => '2024-01-01T12:00:00+00:00'  // Devient Iso8601DateTime
]);

echo $record->email_address->getDomain();        // 'example.com'
echo $record->created_at->toDateTime();   // DateTime object
```

---

## 10. Hydratation JSON : `fromJson()`

```php
$json = '{"id":1,"name":"John Doe","email":"john@example.com","role":"admin"}';
$record = UserRecord::fromJson($json);
```

**Gestion des erreurs JSON :**

```php
try {
    $record = UserRecord::fromJson('{invalid json}');
} catch (RuntimeException $e) {
    echo $e->getMessage(); // 'Invalid JSON: Syntax error'
}
```

---

## 11. Collections de Records : `collect()`

```php
// À partir d'un tableau de données
$users = UserRecord::collect([
    ['id' => 1, 'name' => 'Alice', 'email' => 'alice@example.com'],
    ['id' => 2, 'name' => 'Bob', 'email' => 'bob@example.com'],
]);

// À partir d'une collection existante
$filtered = UserRecord::collect($users->toArray())
    ->filter(fn($user) => $user->name === 'Alice');

// Avec une collection personnalisée
$collection = UserRecord::collect($sources, UserRecordCollection::class);
```

### Cas d'usage typiques

```php
// Résultat de base de données
$users = UserRecord::collect($db->fetchAll());

// Réponse d'API
$users = UserRecord::collect(json_decode($apiResponse, true));

// Traitement par lots
$batch = UserRecord::collect($chunk);
$batch->each(fn($user) => $this->process($user));
```

---

## 12. Normalisation : snake_case pour la base de données

Les Records se normalisent automatiquement en `snake_case` pour faciliter le mapping avec les bases de données :

```php
$record = UserRecord::from([
    'id' => 1,
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'email_verified_at' => '2024-01-01T12:00:00+00:00',
    'created_at' => '2024-01-01T12:00:00+00:00'
]);

$normalized = NormalizerChain::get()->normalize($record);
// Résultat :
// [
//     'id' => 1,
//     'name' => 'John Doe',
//     'email' => 'john@example.com',
//     'email_verified_at' => '2024-01-01T12:00:00+00:00',  // snake_case
//     'created_at' => '2024-01-01T12:00:00+00:00'          // snake_case
// ]
```

### Utilisation avec une base de données

```php
// Enregistrer en base
$record = UserRecord::from($formData);
$db->insert('users', NormalizerChain::get()->normalize($record));

// Lire depuis la base
$row = $db->fetch('SELECT * FROM users WHERE id = 1');
$record = UserRecord::from($row);
```

---

## 13. Les Value Objects au cœur des Records

La vraie puissance des Records vient de leur capacité à utiliser des Value Objects pour les propriétés complexes :

```php
final class UserRecord extends AbstractRecord
{
    public function __construct(
        public readonly ?int $id,
        public readonly string $name,
        public readonly EmailAddress $email_address,        // VO
        public readonly PhoneNumber $phone,         // VO
        public readonly Password $password,         // VO
        public readonly Address $address,           // VO
        public readonly Iso8601DateTime $created_at, // VO pour les dates
        public readonly Iso8601DateTime $updated_at, // VO pour les dates
        public readonly UserRole $role,             // Enum
        public readonly UserStatus $status,         // Enum
    ) {}
}
```

### Avantages

```php
// ✅ L'email est GARANTI valide
$record = UserRecord::from([
    'email' => 'john@example.com'  // Validé par EmailAddress::from()
]);

// ✅ La date est GARANTIE au format ISO 8601
$record = UserRecord::from([
    'created_at' => '2024-01-01T12:00:00+00:00'  // Validé par Iso8601DateTime::from()
]);

// ✅ Comportement disponible
$domain = $record->email_address->getDomain();                    // 'example.com'
$isAfter = $record->created_at->isAfter($otherDate);      // Comparaison

// ✅ Pas de duplication de validation
// La validation est centralisée dans les Value Objects
```

---

## 14. Les collections typées avec des Records

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

// Utilisation
$users = UserRecordCollection::from($dbResults);
$activeAdmins = $users->active()->withRole(UserRole::ADMIN);
$recentUsers = $users->createdAfter(Iso8601DateTime::from('2024-01-01T00:00:00+00:00'));
```

### Avantages des collections typées

| Sans collection typée | Avec collection typée |
|----------------------|----------------------|
| `array<UserRecord>` | `TypedCollection<UserRecord>` |
| Pas de méthodes de filtrage | `filter()`, `map()`, `reduce()`, etc. |
| Pas de type-safety | Type-safety garantie |
| Pas d'immutabilité | Collections immutables |

---

## 15. Nettoyage des valeurs null : `toArrayWithoutNulls()`

Lorsque vous insérez ou mettez à jour des données en base de données, vous souhaiterez souvent exclure les champs avec des valeurs `null` pour ne mettre à jour que les champs réellement modifiés.

La méthode `toArrayWithoutNulls()` vous permet d'obtenir une représentation normalisée du Record sans les valeurs `null`.

### 15.1. Utilisation de base

```php
$record = UserRecord::from([
    'id' => 1,
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'phone' => null,
    'address' => null,
    'status' => 'active'
]);

$cleaned = $record->toArrayWithoutNulls();
// Résultat :
// [
//     'id' => 1,
//     'name' => 'John Doe',
//     'email' => 'john@example.com',
//     'status' => 'active'
// ]

// Idéal pour les mises à jour partielles
$db->update('users', $cleaned, ['id' => 1]);
```

### 15.2. Mode récursif (par défaut)

Par défaut, la méthode supprime récursivement les valeurs `null` dans les tableaux et collections imbriqués :

```php
$tags = new TypedCollection('string', 'null');
$tags->add('premium', null, 'vip', null, 'gold');

$record = UserRecord::from([
    'name' => 'John Doe',
    'email' => 'john@example.com',
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
//     'email' => 'john@example.com',
//     'tags' => ['premium', 'vip', 'gold'],
//     'metadata' => [
//         'key1' => 'value1',
//         'key3' => ['nested1' => 'deep']
//     ]
// ]
```

### 15.3. Mode non récursif

Pour supprimer les `null` uniquement au premier niveau (sans toucher aux structures imbriquées) :

```php
$cleaned = $record->toArrayWithoutNulls(false);
```

### 15.4. Conservation des tableaux vides

Les collections vides sont conservées car elles représentent des données légitimes (ex: un utilisateur sans tags) :

```php
$emptyTags = new StringTypedCollection;

$record = UserRecord::from([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'tags' => $emptyTags
]);

$cleaned = $record->toArrayWithoutNulls();
// Résultat : ['name' => 'John Doe', 'email' => 'john@example.com', 'tags' => []]
```

### 15.5. Cas d'usage : mises à jour partielles

```php
// Formulaire de mise à jour - seuls certains champs sont modifiés
$updateData = UserUpdateRecord::from([
    'name' => 'Jane Doe',
    'email' => null,     // Non modifié
    'phone' => null      // Non modifié
]);

$cleaned = $updateData->toArrayWithoutNulls();
// Résultat : ['name' => 'Jane Doe']

// Seul le champ 'name' sera mis à jour
$db->update('users', $cleaned, ['id' => 1]);
```

### 15.6. Immutabilité garantie

La méthode ne modifie pas l'objet original :

```php
$original = UserRecord::from([
    'name' => 'John Doe',
    'email' => null
]);

$cleaned = $original->toArrayWithoutNulls();

// L'original reste inchangé
$originalNormalized = NormalizerChain::get()->normalize($original);
// Résultat : ['name' => 'John Doe', 'email' => null]

// Le nettoyé n'a pas le champ email
// Résultat : ['name' => 'John Doe']
```

### 15.7. Préservation des valeurs "falsy"

Les valeurs suivantes sont conservées (elles ne sont PAS considérées comme `null`) :

```php
$record = UserRecord::from([
    'id' => 0,           // Conservé
    'name' => '',        // Conservé (chaîne vide)
    'active' => false,   // Conservé
    'score' => 0.0,      // Conservé
    'tags' => [],        // Conservé (tableau vide)
    'status' => null     // Supprimé
]);

$cleaned = $record->toArrayWithoutNulls();
// Résultat : ['id' => 0, 'name' => '', 'active' => false, 'score' => 0.0, 'tags' => []]
```

### 15.8. Tableau récapitulatif

| Valeur | Est supprimée ? | Raison |
|--------|-----------------|--------|
| `null` | ✅ Oui | Valeur null explicite |
| `0` | ❌ Non | Entier zéro est une valeur légitime |
| `''` | ❌ Non | Chaîne vide est une valeur légitime |
| `false` | ❌ Non | Booléen faux est une valeur légitime |
| `[]` | ❌ Non | Tableau vide représente une collection vide |
| `0.0` | ❌ Non | Float zéro est une valeur légitime |

### 15.9. Exemple complet avec base de données

```php
// Récupération d'un utilisateur existant
$existingUser = $repository->find(1);

// Formulaire de mise à jour (seul le nom change)
$updateData = UserUpdateRecord::from([
    'name' => 'Jane Smith',
    'email' => null,
    'phone' => null
]);

// Nettoyer les nulls pour la mise à jour
$cleaned = $updateData->toArrayWithoutNulls();

// Seul le champ 'name' sera mis à jour
$db->update('users', $cleaned, ['id' => $existingUser->id]);

// Alternative avec fluide API
$db->update('users', 
    UserUpdateRecord::from($request->all())->toArrayWithoutNulls(),
    ['id' => $userId]
);
```

### 15.10. Performance

La méthode est optimisée et ne normalise l'objet qu'une seule fois. Elle convient parfaitement pour une utilisation dans des boucles ou des traitements par lots.

```php
// Traitement par lots efficace
$updates = [];
foreach ($records as $record) {
    $updates[] = $record->toArrayWithoutNulls();
}
$db->batchInsert('users', $updates);
```

---

## 16. Bonnes pratiques

### 16.1. Toujours utiliser `from()` pour créer des Records

```php
// ✅ Bon
$record = UserRecord::from($data);

// ❌ Mauvais - on perd l'hydratation automatique
$record = new UserRecord(...);
```

### 16.2. Combiner Records et Value Objects

```php
// ✅ Bon - validation déléguée aux VOs
final class UserRecord extends AbstractRecord
{
    public function __construct(
        public readonly EmailAddress $email_address,        // Validation intégrée
        public readonly Iso8601DateTime $created_at, // Validation intégrée
        public readonly Password $password,         // Validation intégrée
    ) {}
}

// ❌ Mauvais - validation dans le Record
final class UserRecord extends AbstractRecord
{
    public function __construct(
        public readonly string $email,      // Validation à faire ailleurs
        public readonly string $created_at,  // Pas de garantie de format
        public readonly string $password,   // Pas de validation
    ) {}
}
```

### 16.3. Utiliser des enums pour les champs à valeurs fixes

```php
// ✅ Bon - Enum pour valeurs fixes
enum UserRole: string
{
    case ADMIN = 'admin';
    case USER = 'user';
    case DOCTOR = 'doctor';
}

final class UserRecord extends AbstractRecord
{
    public function __construct(
        public readonly UserRole $role,  // Valeurs limitées et connues
    ) {}
}
```

### 16.4. Toujours utiliser `Iso8601DateTime` pour les dates

```php
// ✅ Bon
final class UserRecord extends AbstractRecord
{
    public function __construct(
        public readonly Iso8601DateTime $created_at,
        public readonly Iso8601DateTime $updated_at,
    ) {}
}

// ❌ Mauvais
final class UserRecord extends AbstractRecord
{
    public function __construct(
        public readonly string $created_at,           // Format non garanti
        public readonly \DateTimeImmutable $updated_at, // Non hydratable
    ) {}
}
```

### 16.5. Profiter des collections typées

```php
// ✅ Bon
$activeUsers = UserRecord::collect($dbResults)
    ->filter(fn($user) => $user->status === UserStatus::ACTIVE)
    ->map(fn($user) => $user->name);

// ❌ Mauvais
$activeUsers = [];
foreach ($dbResults as $row) {
    $user = UserRecord::from($row);
    if ($user->status === UserStatus::ACTIVE) {
        $activeUsers[] = $user->name;
    }
}
```

### 16.6. Normaliser pour la base de données

```php
// ✅ Bon
$record = UserRecord::from($formData);
$db->insert('users', NormalizerChain::get()->normalize($record));

// ❌ Mauvais - accès direct aux propriétés (snake_case)
$db->insert('users', [
    'id' => $record->id,
    'created_at' => $record->created_at,  // ✅ Déjà en snake_case
]);
```

### 16.7. Utiliser `toArrayWithoutNulls()` pour les mises à jour

```php
// ✅ Bon - mise à jour partielle
$updateData = UserUpdateRecord::from($request->all());
$db->update('users', $updateData->toArrayWithoutNulls(), ['id' => $userId]);

// ❌ Mauvais - risque d'écraser des champs avec null
$db->update('users', NormalizerChain::get()->normalize($updateData), ['id' => $userId]);
```

### 16.8. Utiliser le snake_case pour les noms de propriétés

```php
// ✅ Bon - propriétés en snake_case
final class UserRecord extends AbstractRecord
{
    public function __construct(
        public readonly ?int $id,
        public readonly string $first_name,
        public readonly string $last_name,
        public readonly EmailAddress $email_address,
        public readonly Iso8601DateTime $created_at,
        public readonly ?Iso8601DateTime $updated_at,
        public readonly ?Iso8601DateTime $deleted_at,
    ) {}
}

// ❌ Mauvais - propriétés en camelCase
final class UserRecord extends AbstractRecord
{
    public function __construct(
        public readonly ?int $id,
        public readonly string $firstName,     // ❌ camelCase
        public readonly string $lastName,      // ❌ camelCase
        public readonly EmailAddress $emailAddress,  // ❌ camelCase
        public readonly Iso8601DateTime $createdAt,  // ❌ camelCase
    ) {}
}
```

---

## 17. Récapitulatif des contraintes

| Contrainte | Règle |
|------------|-------|
| **Héritage** | Étend `AbstractRecord` |
| **Constructeur** | Public (mais `readonly`) |
| **Propriétés** | `public readonly` |
| **Nommage des propriétés** | **`snake_case`** (obligatoire) |
| **Types scalaires autorisés** | `int`, `string`, `float`, `bool`, `null` |
| **Types objets autorisés** | `ValueObject`, `Enum`, `TypedCollection`, `Record` |
| **Types INTERDITS** | `DataObject`, `AbstractData`, `DateTime`, `DateTimeImmutable` |
| **Logique métier** | ❌ **INTERDITE** |
| **Validation** | Délégation aux VOs |
| **Hydratation** | `from()`, `fromJson()`, `collect()` |
| **Normalisation** | Automatique (`snake_case`) |
| **Nettoyage des nulls** | `toArrayWithoutNulls()` |

---

## 18. Exemple complet

```php
// 1. Définir les Value Objects
final class EmailAddress extends AbstractValueObject { /* ... */ }
final class Iso8601DateTime extends AbstractValueObject { /* ... */ }

// 2. Définir les Enums
enum UserRole: string { case ADMIN = 'admin'; case USER = 'user'; }
enum UserStatus: string { case ACTIVE = 'active'; case INACTIVE = 'inactive'; }

// 3. Définir le Record (propriétés en snake_case)
final class UserRecord extends AbstractRecord
{
    public function __construct(
        public readonly ?int $id,
        public readonly string $name,
        public readonly EmailAddress $email_address,
        public readonly UserRole $role,
        public readonly UserStatus $status,
        public readonly Iso8601DateTime $created_at,
        public readonly ?Iso8601DateTime $updated_at,
    ) {}
}

// 4. Définir un Record de mise à jour (toutes propriétés nullables, en snake_case)
final class UserUpdateRecord extends AbstractRecord
{
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?EmailAddress $email_address = null,
        public readonly ?UserRole $role = null,
        public readonly ?UserStatus $status = null,
    ) {}
}

// 5. Utilisation complète
$updateData = UserUpdateRecord::from([
    'name' => 'Jane Smith',
    'role' => 'admin'
]);

$cleaned = $updateData->toArrayWithoutNulls();
// Résultat : ['name' => 'Jane Smith', 'role' => 'admin']

$db->update('users', $cleaned, ['id' => 1]);

// 6. Collection
$users = UserRecord::collect($dbResults);
$activeAdmins = $users
    ->filter(fn($u) => $u->status === UserStatus::ACTIVE)
    ->filter(fn($u) => $u->role === UserRole::ADMIN)
    ->filter(fn($u) => $u->created_at->isAfter(Iso8601DateTime::from('2024-01-01T00:00:00+00:00')));

// 7. Normalisation pour la base de données
$db->insert('users', NormalizerChain::get()->normalize($record));
```

---

## Support

Pour toute question ou suggestion, n'hésitez pas à :
- Ouvrir une issue sur GitHub
- Consulter la documentation complète
- Contacter l'équipe de développement
```
