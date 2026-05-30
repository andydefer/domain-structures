# EnumStrategy - Référence Technique

## Description

Stratégie d'hydratation pour les énumérations PHP 8.1+ qui convertit les sources variées (scalaires, tableaux, objets) en instances d'énumération valides.

## Hiérarchie

```
HydrationStrategyInterface
    └── EnumStrategy
```

## Rôle principal

Assure la création d'instances d'énumérations (`UnitEnum` ou `BackedEnum`) à partir de données brutes, en supportant à la fois les enums à valeur scalaire (int/string) et les enums purs (sans valeur).

## API

### `supports(string $className, mixed $source): bool`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$className` | `string` | Nom complet de la classe à vérifier |
| `$source` | `mixed` | Source (non utilisée pour la vérification) |

**Retourne :** `bool` - `true` si la classe est une énumération PHP

**Exemple :**
```php
$strategy = new EnumStrategy();
$supports = $strategy->supports(UserStatus::class, 'active'); // true
$supports = $strategy->supports(\stdClass::class, null);      // false
```

### `hydrate(string $className, mixed $source): object`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$className` | `string` | Nom complet de la classe enum |
| `$source` | `mixed` | Source à hydrater |

**Retourne :** `\UnitEnum|\BackedEnum` - Instance de l'énumération

**Exceptions :** `InvalidArgumentException` - Si l'hydratation échoue

**Exemple :**
```php
$status = $strategy->hydrate(UserStatus::class, 'active');
// $status === UserStatus::ACTIVE
```

## Cas d'utilisation

### Cas 1 : Backed enum (string)

```php
enum UserStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
}

$status = $strategy->hydrate(UserStatus::class, 'active');
echo $status->value; // 'active'
```

### Cas 2 : Backed enum (int) avec conversion automatique

```php
enum UserGrade: int
{
    case BRONZE = 1;
    case SILVER = 2;
}

// La string '1' est automatiquement convertie en int 1
$grade = $strategy->hydrate(UserGrade::class, '1');
echo $grade->value; // 1
```

### Cas 3 : Pure enum (sans valeur)

```php
enum UserRole
{
    case ADMIN;
    case USER;
}

$role = $strategy->hydrate(UserRole::class, 'ADMIN');
echo $role->name; // 'ADMIN'
```

### Cas 4 : Hydratation depuis un tableau

```php
$data = ['value' => 'active'];
$status = $strategy->hydrate(UserStatus::class, $data);

$data = ['name' => 'ADMIN'];
$role = $strategy->hydrate(UserRole::class, $data);
```

## Flux d'exécution

```
$source → hydrate()
    │
    ├── Déjà une instance ? → retourne l'instance
    │
    ├── is_scalar() → hydrateFromScalar()
    │       ├── BackedEnum → hydrateBackedEnum()
    │       │       ├── normalizeBackingValue() (int/string)
    │       │       └── tryFrom() → retourne enum
    │       └── PureEnum → hydratePureEnum()
    │               └── constant() → retourne enum
    │
    ├── is_array() → hydrateFromArray()
    │       ├── clé 'value' → hydrate($className, $source['value'])
    │       └── clé 'name' → hydrate($className, $source['name'])
    │
    ├── is_object() → hydrateFromObject()
    │       ├── propriété 'value' → hydrate($className, $source->value)
    │       └── propriété 'name' → hydrate($className, $source->name)
    │
    └── default → Exception
```

## Normalisation des valeurs

Pour les enums à base d'entier (`int`), les valeurs fournies sous forme de chaîne numérique sont automatiquement converties :

```php
// Source: '1' (string)
// Valeur normalisée: 1 (int)

enum Grade: int { case BRONZE = 1; }
$grade = $strategy->hydrate(Grade::class, '1');
// Grade::BRONZE
```

## Gestion des erreurs

| Situation | Exception | Message |
|-----------|-----------|---------|
| Backed enum - valeur invalide | `InvalidArgumentException` | `Invalid value "X" for enum Y` |
| Pure enum - case inexistante | `InvalidArgumentException` | `Invalid value "X" for enum Y` |
| Tableau sans clé 'value' ou 'name' | `InvalidArgumentException` | `Cannot hydrate enum Y from array without "value" or "name" key` |
| Objet sans propriété 'value' ou 'name' | `InvalidArgumentException` | `Cannot hydrate enum Y from object without "value" or "name" property` |
| Type de source non supporté | `InvalidArgumentException` | `Cannot hydrate enum Y from source type: Z` |

## Intégration

Cette stratégie est automatiquement utilisée par l'hydrateur central `Hydrator` pour tous les paramètres de type énumération :

```php
final class UserRecord extends AbstractRecord
{
    public function __construct(
        public readonly UserStatus $status,  // ← hydraté par EnumStrategy
        public readonly UserRole $role,      // ← hydraté par EnumStrategy
    ) {}
}

$user = UserRecord::from([
    'status' => 'active',   // string → UserStatus::ACTIVE
    'role' => 'ADMIN',      // string → UserRole::ADMIN
]);
```

## Ordre dans la chaîne de stratégies

`EnumStrategy` est exécutée dans l'ordre suivant :

1. `InstanceStrategy` (déjà une instance)
2. **`EnumStrategy`** ← position actuelle
3. `SingleParameterStrategy`
4. `MultiParameterStrategy`

## Performance

- `enum_exists()` : O(1) - très rapide
- `ReflectionEnum` : utilisé une fois par hydratation, léger
- `tryFrom()` : O(1) - accès direct au tableau interne
- `constant()` : O(1) - accès direct

Aucun cache interne nécessaire.

## Compatibilité

| Version PHP | Support |
|-------------|---------|
| PHP 8.1+ | ✅ Complet (enums natifs) |
| PHP 8.0 | ❌ Enums non disponibles |

## Exemple complet

```php
<?php

declare(strict_types=1);

use AndyDefer\DomainStructures\Hydration\Strategy\EnumStrategy;

// Définition des enums
enum Status: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
}

enum Grade: int
{
    case BRONZE = 1;
    case SILVER = 2;
}

enum Role
{
    case ADMIN;
    case USER;
}

$strategy = new EnumStrategy();

// Backed enum (string)
$status = $strategy->hydrate(Status::class, 'active');
echo $status->value; // 'active'

// Backed enum (int) avec conversion automatique
$grade = $strategy->hydrate(Grade::class, '2');
echo $grade->value; // 2

// Pure enum
$role = $strategy->hydrate(Role::class, 'ADMIN');
echo $role->name; // 'ADMIN'

// Depuis un tableau
$status = $strategy->hydrate(Status::class, ['value' => 'inactive']);
$role = $strategy->hydrate(Role::class, ['name' => 'USER']);

// Instance existante
$existing = Status::ACTIVE;
$same = $strategy->hydrate(Status::class, $existing);
var_dump($same === $existing); // bool(true)
```

## Voir aussi

- `InstanceStrategy` - Stratégie pour les instances existantes
- `SingleParameterStrategy` - Stratégie pour les constructeurs à un paramètre
- `MultiParameterStrategy` - Stratégie pour les constructeurs multi-paramètres
- `Hydrator` - Hydrateur central
- [PHP Enums documentation](https://www.php.net/manual/en/language.enumerations.php)
