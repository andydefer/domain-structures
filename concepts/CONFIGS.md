# Config - Documentation Complète (Version Finale)

## Table des matières

1. [Définition et concepts](#1-définition-et-concepts)
2. [Pourquoi une Config POO ?](#2-pourquoi-une-config-poo-)
3. [L'Interface comme contrat - Architecture découplée](#3-linterface-comme-contrat---architecture-découplée)
4. [Ségrégation des interfaces (ISP) - Principe fondamental](#4-ségrégation-des-interfaces-isp---principe-fondamental)
5. [Créer sa première Config](#5-créer-sa-première-config)
6. [Types de retour autorisés](#6-types-de-retour-autorisés)
7. [Méthodes utilitaires](#7-méthodes-utilitaires)
8. [Chargement depuis l'environnement](#8-chargement-depuis-lenvironnement)
9. [Avantages architecturaux](#9-avantages-architecturaux)
10. [Exemples concrets](#10-exemples-concrets)
11. [Bonnes pratiques](#11-bonnes-pratiques)
12. [Récapitulatif](#12-récapitulatif)
13. [Règle d'or](#13-règle-dor)

---

## 1. Définition et concepts

Une **Config** est une **interface** qui expose des valeurs de configuration via des méthodes. Les implémentations concrètes sont **immuables**, **sans état interne** et **auto-documentées**.

```
Interface Config → Contrat → Implémentations concrètes → Découplage total
```

### 1.1. Principes fondamentaux

| Principe | Description |
|----------|-------------|
| **Interface comme contrat** | Les Services dépendent de l'interface, pas de l'implémentation |
| **Aucune propriété** | Interdiction formelle de toute propriété (même private) |
| **Aucun état interne** | La classe ne stocke rien entre les appels |
| **Méthodes immuables** | Chaque méthode retourne une valeur fixe ou calculée |
| **Auto-documentée** | Les noms de méthodes décrivent la configuration |
| **Testable** | Peut être mockée facilement (contrairement aux classes concrètes) |
| **Découplée** | Ne dépend d'aucun framework (Laravel, Symfony, etc.) |

---

## 2. Pourquoi une Config POO ?

### 2.1. Le problème des approches traditionnelles

| Approche | Problème |
|----------|----------|
| `config('app.key')` | String magique, non typé, pas d'autocomplétion |
| `$_ENV['KEY']` | Non structuré, non typé, global |
| `array $config` | Tableau brut, aucune garantie de structure |

### 2.2. Ce que Config POO résout

| Problème | Solution |
|----------|----------|
| Clés magiques | Méthodes nommées (`host()`, `port()`) |
| Non typé | Types de retour explicites (`: string`, `: int`) |
| Pas d'autocomplétion | L'IDE connaît toutes les méthodes |
| Mutabilité | Immuabilité garantie |
| Couplage au framework | Config pure, fonctionne partout |

---

## 3. L'Interface comme contrat - Architecture découplée

### 3.1. Pourquoi une Interface plutôt qu'une classe abstraite ?

> **⚠️ Le principe fondamental est de respecter le DIP (Dependency Inversion Principle) : dépendre d'une abstraction (interface), pas d'une implémentation concrète.**

| Problème avec les classes concrètes | Solution avec Interface |
|-------------------------------------|------------------------|
| Couplage fort à une classe spécifique | Découplage total via l'interface |
| Impossible de changer de source de config | Multiples implémentations possibles |
| Tests difficiles (véritable classe chargée) | Tests faciles (mock de l'interface) |
| Héritage unique bloqué | Une classe peut implémenter plusieurs interfaces |

### 3.2. Interface de base (optionnelle)

```php
<?php

declare(strict_types=1);

namespace App\Contracts\Configs;

interface ConfigInterface
{
    // Interface marker - toutes les configs peuvent implémenter cette interface
}
```

---

## 4. Ségrégation des interfaces (ISP) - Principe fondamental

> **⚠️ C'est l'avantage MAJEUR des interfaces : on peut découper une grosse configuration en plusieurs petites interfaces. Une implémentation peut implémenter plusieurs interfaces, et chaque Service ne prend que l'interface qui lui est nécessaire.**

### 4.1. Le problème d'une grosse interface unique

```php
// ❌ MAUVAIS - Une seule grosse interface avec tout
interface AppConfigInterface
{
    // Database
    public function dbHost(): string;
    public function dbPort(): int;
    public function dbName(): string;
    public function dbUser(): string;
    public function dbPassword(): string;
    
    // Cache
    public function cacheHost(): string;
    public function cachePort(): int;
    
    // Mail
    public function mailHost(): string;
    public function mailPort(): int;
    public function mailUser(): string;
    public function mailPassword(): string;
    
    // API
    public function apiBaseUrl(): string;
    public function apiKey(): string;
    public function apiTimeout(): int;
}

// ❌ Problème : Le service Database n'a besoin QUE des méthodes DB
// Mais il dépend de TOUTE l'interface (violation ISP)
final class DatabaseService
{
    public function __construct(
        private readonly AppConfigInterface $config  // ← Dépend de TOUTES les méthodes
    ) {}
    
    public function getConnection(): PDO
    {
        // N'utilise QUE dbHost, dbPort, dbName, dbUser, dbPassword
        // Mais dépend aussi de cacheHost, mailHost, apiBaseUrl, etc.
        return new PDO(...);
    }
}
```

### 4.2. La solution : Ségrégation des interfaces

```php
// ✅ BON - Interfaces petites et spécialisées
interface DatabaseConfigInterface
{
    public function dbHost(): string;
    public function dbPort(): int;
    public function dbName(): string;
    public function dbUser(): string;
    public function dbPassword(): string;
}

interface CacheConfigInterface
{
    public function cacheHost(): string;
    public function cachePort(): int;
}

interface MailConfigInterface
{
    public function mailHost(): string;
    public function mailPort(): int;
    public function mailUser(): string;
    public function mailPassword(): string;
}

interface ApiConfigInterface
{
    public function apiBaseUrl(): string;
    public function apiKey(): string;
    public function apiTimeout(): int;
}
```

### 4.3. Implémentation unique qui implémente TOUTES les interfaces

```php
// ✅ Une seule classe implémente toutes les interfaces
final class EnvAppConfig implements 
    DatabaseConfigInterface,
    CacheConfigInterface,
    MailConfigInterface,
    ApiConfigInterface
{
    // Database
    public function dbHost(): string { return getenv('DB_HOST') ?: 'localhost'; }
    public function dbPort(): int { return (int) (getenv('DB_PORT') ?: 3306); }
    public function dbName(): string { return getenv('DB_NAME') ?: 'app'; }
    public function dbUser(): string { return getenv('DB_USER') ?: 'root'; }
    public function dbPassword(): string { return getenv('DB_PASSWORD') ?: ''; }
    
    // Cache
    public function cacheHost(): string { return getenv('REDIS_HOST') ?: 'localhost'; }
    public function cachePort(): int { return (int) (getenv('REDIS_PORT') ?: 6379); }
    
    // Mail
    public function mailHost(): string { return getenv('MAIL_HOST') ?: 'smtp.mailtrap.io'; }
    public function mailPort(): int { return (int) (getenv('MAIL_PORT') ?: 2525); }
    public function mailUser(): string { return getenv('MAIL_USER') ?: ''; }
    public function mailPassword(): string { return getenv('MAIL_PASSWORD') ?: ''; }
    
    // API
    public function apiBaseUrl(): string { return getenv('API_BASE_URL') ?: 'https://api.example.com'; }
    public function apiKey(): string { return getenv('API_KEY') ?: ''; }
    public function apiTimeout(): int { return (int) (getenv('API_TIMEOUT') ?: 30); }
}
```

### 4.4. Chaque Service ne prend que l'interface dont il a besoin

```php
// ✅ DatabaseService ne dépend QUE de DatabaseConfigInterface
final class DatabaseService
{
    public function __construct(
        private readonly DatabaseConfigInterface $config  // ← UNIQUEMENT ce dont il a besoin
    ) {}
    
    public function getConnection(): PDO
    {
        return new PDO(
            "mysql:host={$this->config->dbHost()};port={$this->config->dbPort()};dbname={$this->config->dbName()}",
            $this->config->dbUser(),
            $this->config->dbPassword()
        );
    }
}

// ✅ CacheService ne dépend QUE de CacheConfigInterface
final class CacheService
{
    public function __construct(
        private readonly CacheConfigInterface $config  // ← UNIQUEMENT ce dont il a besoin
    ) {}
    
    public function getClient(): Redis
    {
        return new Redis($this->config->cacheHost(), $this->config->cachePort());
    }
}
```

### 4.5. Injection avec la même implémentation concrète

```php
// En production - une seule instance sert tous les services
$appConfig = new EnvAppConfig();  // Implémente TOUTES les interfaces

// Chaque service reçoit la même instance MAIS ne voit que son interface
$dbService = new DatabaseService($appConfig);      // ✅ Voit DatabaseConfigInterface seulement
$cacheService = new CacheService($appConfig);      // ✅ Voit CacheConfigInterface seulement  
$mailService = new MailService($appConfig);        // ✅ Voit MailConfigInterface seulement
$apiService = new ApiService($appConfig);          // ✅ Voit ApiConfigInterface seulement
```

### 4.6. Avantage : Facilité de séparation future

```php
// Aujourd'hui : une seule classe implémente tout
final class EnvAppConfig implements 
    DatabaseConfigInterface,
    CacheConfigInterface,
    MailConfigInterface,
    ApiConfigInterface
{
    // ...
}

// Demain : on peut séparer en plusieurs classes SANS impacter les Services !
final class EnvDatabaseConfig implements DatabaseConfigInterface { ... }
final class EnvCacheConfig implements CacheConfigInterface { ... }
final class EnvMailConfig implements MailConfigInterface { ... }
final class EnvApiConfig implements ApiConfigInterface { ... }

// ✅ Les Services restent IDENTIQUES - ils ne changent PAS
// DatabaseService dépend toujours de DatabaseConfigInterface
// CacheService dépend toujours de CacheConfigInterface

// Seule l'injection change
$dbService = new DatabaseService(new EnvDatabaseConfig());
$cacheService = new CacheService(new EnvCacheConfig());

// ✅ Les Services n'ont PAS été modifiés !
// C'est la puissance de l'ISP (Interface Segregation Principle)
```

---

## 5. Créer sa première Config

### 5.1. Définir l'interface

```php
<?php

declare(strict_types=1);

namespace App\Contracts\Configs;

interface DatabaseConfigInterface
{
    public function driver(): string;
    public function host(): string;
    public function port(): int;
    public function database(): string;
    public function username(): string;
    public function password(): string;
}
```

### 5.2. Implémenter avec des variables d'environnement

```php
<?php

declare(strict_types=1);

namespace App\Configs;

use App\Contracts\Configs\DatabaseConfigInterface;

final class EnvDatabaseConfig implements DatabaseConfigInterface
{
    public function driver(): string
    {
        return getenv('DB_DRIVER') ?: 'mysql';
    }
    
    public function host(): string
    {
        return getenv('DB_HOST') ?: 'localhost';
    }
    
    public function port(): int
    {
        return (int) (getenv('DB_PORT') ?: 3306);
    }
    
    public function database(): string
    {
        return getenv('DB_DATABASE') ?: 'my_app';
    }
    
    public function username(): string
    {
        return getenv('DB_USERNAME') ?: 'root';
    }
    
    public function password(): string
    {
        return getenv('DB_PASSWORD') ?: '';
    }
}
```

### 5.3. Utilisation dans un Service

```php
final class DatabaseConnectionService
{
    public function __construct(
        private readonly DatabaseConfigInterface $config,  // ← Dépend de l'interface
    ) {}
    
    public function getConnection(): PDO
    {
        return new PDO(
            $this->config->dsn(),
            $this->config->username(),
            $this->config->password()
        );
    }
}

// En production
$config = new EnvDatabaseConfig();
$service = new DatabaseConnectionService($config);

// En test (mock)
$mockConfig = $this->createMock(DatabaseConfigInterface::class);
$service = new DatabaseConnectionService($mockConfig);
```

---

## 6. Types de retour autorisés

### 6.1. Scalaires

```php
interface AppConfigInterface
{
    public function name(): string;
    public function env(): string;
    public function debug(): bool;
    public function port(): int;
    public function timeout(): ?int;
}
```

### 6.2. Enums

```php
enum LogLevel: string
{
    case DEBUG = 'debug';
    case INFO = 'info';
    case WARNING = 'warning';
    case ERROR = 'error';
}

interface LoggerConfigInterface
{
    public function level(): LogLevel;
}

final class EnvLoggerConfig implements LoggerConfigInterface
{
    public function level(): LogLevel
    {
        $level = getenv('LOG_LEVEL') ?: 'info';
        
        return match ($level) {
            'debug' => LogLevel::DEBUG,
            'info' => LogLevel::INFO,
            'warning' => LogLevel::WARNING,
            'error' => LogLevel::ERROR,
            default => LogLevel::INFO,
        };
    }
}
```

### 6.3. Value Objects

```php
interface EmailConfigInterface
{
    public function host(): string;
    public function port(): int;
    public function credentials(): SmtpCredentials;
    public function from(): EmailAddress;
}

final class EnvEmailConfig implements EmailConfigInterface
{
    public function host(): string { return getenv('SMTP_HOST') ?: 'smtp.example.com'; }
    public function port(): int { return (int) (getenv('SMTP_PORT') ?: 587); }
    
    public function credentials(): SmtpCredentials
    {
        return new SmtpCredentials(
            username: getenv('SMTP_USER') ?: '',
            password: getenv('SMTP_PASSWORD') ?: '',
        );
    }
    
    public function from(): EmailAddress
    {
        return new EmailAddress(getenv('MAIL_FROM') ?: 'noreply@example.com');
    }
}
```

### 6.4. Records

```php
interface DatabaseConfigInterface
{
    public function host(): string;
    public function port(): int;
    public function database(): string;
    
    public function connectionParameters(): DatabaseConnectionRecord;
}

final class EnvDatabaseConfig implements DatabaseConfigInterface
{
    public function host(): string { return getenv('DB_HOST') ?: 'localhost'; }
    public function port(): int { return (int) (getenv('DB_PORT') ?: 3306); }
    public function database(): string { return getenv('DB_DATABASE') ?: 'my_app'; }
    
    public function connectionParameters(): DatabaseConnectionRecord
    {
        return new DatabaseConnectionRecord(
            driver: 'mysql',
            host: $this->host(),
            port: $this->port(),
            database: $this->database(),
            username: getenv('DB_USERNAME') ?: 'root',
            password: getenv('DB_PASSWORD') ?: '',
            charset: 'utf8mb4',
        );
    }
}
```

---

## 7. Méthodes utilitaires

> **⚠️ IMPORTANT : Une Config peut avoir des méthodes utilitaires qui ne correspondent pas directement à une valeur de configuration, mais qui facilitent l'utilisation des valeurs. Ces méthodes doivent retourner exclusivement : des scalaires, des enums, des Value Objects, des Records.**

### 7.1. Méthodes de formatage

```php
interface DatabaseConfigInterface
{
    public function host(): string;
    public function port(): int;
    public function database(): string;
    
    public function dsn(): DsnRecord;  // ← Méthode utilitaire
}

final class EnvDatabaseConfig implements DatabaseConfigInterface
{
    public function host(): string { return getenv('DB_HOST') ?: 'localhost'; }
    public function port(): int { return (int) (getenv('DB_PORT') ?: 3306); }
    public function database(): string { return getenv('DB_DATABASE') ?: 'my_app'; }
    
    public function dsn(): DsnRecord
    {
        return new DsnRecord(
            driver: 'mysql',
            host: $this->host(),
            port: $this->port(),
            database: $this->database(),
        );
    }
}
```

### 7.2. Méthodes de question

```php
interface AppConfigInterface
{
    public function env(): string;
    public function debug(): bool;
    
    public function isProduction(): bool;  // ← Méthode utilitaire
    public function isLocal(): bool;       // ← Méthode utilitaire
    public function shouldCache(): bool;   // ← Méthode utilitaire
}

final class EnvAppConfig implements AppConfigInterface
{
    public function env(): string { return getenv('APP_ENV') ?: 'local'; }
    public function debug(): bool { return getenv('APP_DEBUG') === 'true'; }
    
    public function isProduction(): bool
    {
        return $this->env() === 'production';
    }
    
    public function isLocal(): bool
    {
        return $this->env() === 'local';
    }
    
    public function shouldCache(): bool
    {
        return !$this->isLocal() && !$this->debug();
    }
}
```

### 7.3. Règle pour les méthodes utilitaires

| Type de méthode utilitaire | Type de retour autorisé | Exemple |
|---------------------------|----------------------|---------|
| Formatage | Record, Value Object | `dsn(): DsnRecord` |
| Transformation | Record, Value Object | `connectionParameters(): ConnectionRecord` |
| Question | Scalaire (bool, string, int) | `isProduction(): bool` |
| **Tableau brut** | ❌ **INTERDIT** | ~~`toArray(): array`~~ |
| **Logique métier complexe** | ❌ **INTERDIT** | ~~`calculateTotal()`~~ |
| **Effets de bord** | ❌ **INTERDIT** | ~~`saveToFile()`~~ |

---

## 8. Chargement depuis l'environnement

### 8.1. Valeurs par défaut explicites

```php
final class EnvDatabaseConfig implements DatabaseConfigInterface
{
    // ✅ BON - Default explicite via opérateur ?:
    public function host(): string
    {
        return getenv('DB_HOST') ?: 'localhost';
    }
    
    // ❌ MAUVAIS - Default caché
    public function badHost(): string
    {
        $host = getenv('DB_HOST');
        if ($host === false) {
            $host = 'localhost';
        }
        return $host;
    }
}
```

### 8.2. Gestion des types

```php
final class EnvDatabaseConfig implements DatabaseConfigInterface
{
    // string
    public function host(): string
    {
        return getenv('DB_HOST') ?: 'localhost';
    }
    
    // int (avec cast explicite)
    public function port(): int
    {
        return (int) (getenv('DB_PORT') ?: 3306);
    }
    
    // bool (comparaison stricte)
    public function strict(): bool
    {
        return getenv('DB_STRICT') === 'true';
    }
    
    // nullable
    public function password(): ?string
    {
        $password = getenv('DB_PASSWORD');
        return $password !== false ? $password : null;
    }
}
```

---

## 9. Avantages architecturaux

### 9.1. Testabilité parfaite

```php
// Test unitaire d'un Service qui dépend d'une Config
final class DatabaseServiceTest extends TestCase
{
    public function test_get_connection_returns_pdo(): void
    {
        // ✅ Mock de l'interface - facile, rapide, isolé
        $mockConfig = $this->createMock(DatabaseConfigInterface::class);
        $mockConfig->method('host')->willReturn('localhost');
        $mockConfig->method('port')->willReturn(3306);
        $mockConfig->method('database')->willReturn('test_db');
        
        $service = new DatabaseService($mockConfig);
        $connection = $service->getConnection();
        
        $this->assertInstanceOf(PDO::class, $connection);
    }
}
```

### 9.2. Faible couplage

```php
// ✅ Le Service ne connaît PAS l'implémentation concrète
final class DatabaseService
{
    public function __construct(
        private readonly DatabaseConfigInterface $config,  // ← Interface uniquement
    ) {}
}

// ✅ On peut changer l'implémentation sans toucher au Service
// En production
$service = new DatabaseService(new EnvDatabaseConfig());

// Pour un client spécifique
$service = new DatabaseService(new FileDatabaseConfig('/client/config.php'));

// Pour un test
$service = new DatabaseService(new TestDatabaseConfig());
```

### 9.3. Ségrégation des interfaces (ISP)

```php
// ✅ Une grosse classe peut implémenter plusieurs interfaces
final class EnvAppConfig implements 
    DatabaseConfigInterface,
    CacheConfigInterface,
    MailConfigInterface,
    ApiConfigInterface
{
    // Toutes les méthodes des 4 interfaces
}

// ✅ Chaque Service ne prend que l'interface dont il a besoin
$appConfig = new EnvAppConfig();

$dbService = new DatabaseService($appConfig);      // ← Ne voit QUE DatabaseConfigInterface
$cacheService = new CacheService($appConfig);      // ← Ne voit QUE CacheConfigInterface
$mailService = new MailService($appConfig);        // ← Ne voit QUE MailConfigInterface
$apiService = new ApiService($appConfig);          // ← Ne voit QUE ApiConfigInterface
```

### 9.4. Portabilité entre frameworks

```php
// ✅ La même Config fonctionne PARTOUT
final class PortableConfig implements DatabaseConfigInterface
{
    private array $values;
    
    public function __construct(array $values)  // ✅ Injection des valeurs
    {
        $this->values = $values;
    }
    
    public function host(): string
    {
        return $this->values['host'] ?? getenv('DB_HOST') ?: 'localhost';
    }
}

// Dans Laravel
$config = new PortableConfig([
    'host' => config('database.connections.mysql.host'),
]);

// Dans Symfony
$config = new PortableConfig([
    'host' => $this->getParameter('database.host'),
]);

// Dans un test
$config = new PortableConfig([
    'host' => 'test-host',
]);

// ✅ La même classe fonctionne partout !
```

### 9.5. Résumé des avantages

| Avantage | Explication |
|----------|-------------|
| **Découplage** | Le Service ne connaît pas l'implémentation concrète |
| **Testabilité** | On peut mocker l'interface facilement |
| **Flexibilité** | On peut changer la source de config sans modifier le Service |
| **Respect du DIP** | On dépend des abstractions, pas des détails |
| **Stabilité** | L'interface est le contrat - stable dans le temps |
| **ISP** | Chaque Service ne dépend que des méthodes dont il a besoin |
| **Évolutivité** | On peut séparer une grosse config en plusieurs sans impacter les Services |
| **Portabilité** | La même Config fonctionne dans n'importe quel framework |

---

## 10. Exemples concrets

### 10.1. Interface ségréguée pour application complète

```php
// Interfaces
interface DatabaseConfigInterface
{
    public function host(): string;
    public function port(): int;
    public function name(): string;
    public function user(): string;
    public function password(): string;
    public function dsn(): string;
}

interface CacheConfigInterface
{
    public function host(): string;
    public function port(): int;
}

interface MailConfigInterface
{
    public function host(): string;
    public function port(): int;
    public function user(): string;
    public function password(): string;
}

// Implémentation unique - 100% découplée
final class EnvAppConfig implements 
    DatabaseConfigInterface,
    CacheConfigInterface,
    MailConfigInterface
{
    public function host(): string { return getenv('DB_HOST') ?: 'localhost'; }
    public function port(): int { return (int) (getenv('DB_PORT') ?: 3306); }
    public function name(): string { return getenv('DB_NAME') ?: 'app'; }
    public function user(): string { return getenv('DB_USER') ?: 'root'; }
    public function password(): string { return getenv('DB_PASSWORD') ?: ''; }
    public function dsn(): string { return "mysql:host={$this->host()};port={$this->port()};dbname={$this->name()}"; }
    
    public function cacheHost(): string { return getenv('REDIS_HOST') ?: 'localhost'; }
    public function cachePort(): int { return (int) (getenv('REDIS_PORT') ?: 6379); }
    
    public function mailHost(): string { return getenv('MAIL_HOST') ?: 'smtp.mailtrap.io'; }
    public function mailPort(): int { return (int) (getenv('MAIL_PORT') ?: 2525); }
    public function mailUser(): string { return getenv('MAIL_USER') ?: ''; }
    public function mailPassword(): string { return getenv('MAIL_PASSWORD') ?: ''; }
}

// Services - chacun ne prend que son interface
final class DatabaseService
{
    public function __construct(private readonly DatabaseConfigInterface $config) {}
    public function getConnection(): PDO { return new PDO($this->config->dsn(), $this->config->user(), $this->config->password()); }
}

final class CacheService
{
    public function __construct(private readonly CacheConfigInterface $config) {}
    public function getClient(): Redis { return new Redis($this->config->host(), $this->config->port()); }
}

// Injection
$config = new EnvAppConfig();
$dbService = new DatabaseService($config);      // ✅ Ne voit que DatabaseConfigInterface
$cacheService = new CacheService($config);      // ✅ Ne voit que CacheConfigInterface
```

### 10.2. Test avec mock

```php
final class DatabaseServiceTest extends TestCase
{
    public function test_get_connection_uses_correct_credentials(): void
    {
        // ✅ Mock UNIQUEMENT de l'interface nécessaire
        $mockConfig = $this->createMock(DatabaseConfigInterface::class);
        $mockConfig->method('dsn')->willReturn('mysql:host=test;port=3306;dbname=test');
        $mockConfig->method('user')->willReturn('test_user');
        $mockConfig->method('password')->willReturn('test_pass');
        
        $service = new DatabaseService($mockConfig);
        $connection = $service->getConnection();
        
        $this->assertInstanceOf(PDO::class, $connection);
    }
}
```

---

## 11. Bonnes pratiques

### 11.1. Nommage des méthodes

```php
// ✅ BON - Noms clairs et explicites
public function host(): string { ... }
public function port(): int { ... }
public function database(): string { ... }

// ❌ MAUVAIS - Noms vagues
public function get(): string { ... }
public function val(): int { ... }
```

### 11.2. Nommage des interfaces

```php
// ✅ BON - Suffixe Interface
interface DatabaseConfigInterface { ... }
interface ApiConfigInterface { ... }
interface CacheConfigInterface { ... }
```

### 11.3. Regrouper par domaine, mais ségréguer les interfaces

```php
// ✅ BON - Interfaces séparées par domaine
interface DatabaseConfigInterface { ... }
interface CacheConfigInterface { ... }
interface MailConfigInterface { ... }

// ❌ MAUVAIS - Une seule interface pour tout (violation ISP)
interface AppConfigInterface { ... }  // 50 méthodes mélangées
```

### 11.4. Injection dans les Services

```php
// ✅ BON - Injection de l'interface spécifique
final class DatabaseService
{
    public function __construct(
        private readonly DatabaseConfigInterface $config,  // ← Interface spécifique
    ) {}
}

// ❌ MAUVAIS - Injection d'une grosse interface
final class BadService
{
    public function __construct(
        private readonly AppConfigInterface $config,  // ← Dépend de TOUT
    ) {}
}
```

### 11.5. Pas de fonctions framework

```php
// ❌ MAUVAIS - Dépendance au framework
final class BadConfig implements DatabaseConfigInterface
{
    public function host(): string
    {
        return config('database.host');  // ❌ Helper Laravel
    }
}

// ✅ BON - Valeur injectée ou getenv()
final class GoodConfig implements DatabaseConfigInterface
{
    private string $host;
    
    public function __construct(string $host)
    {
        $this->host = $host;
    }
    
    public function host(): string
    {
        return $this->host;
    }
}
```

---

## 12. Récapitulatif

### 12.1. Caractéristiques principales

| Caractéristique | Règle |
|-----------------|-------|
| **Contrat** | Interface (pas de classe abstraite) |
| **Propriétés** | ❌ **AUCUNE** propriété (même private) |
| **État interne** | ❌ INTERDIT |
| **Méthodes** | ✅ Oui (publiques uniquement) |
| **Validation** | ❌ INTERDITE (dans les Services) |
| **Tableaux bruts** | ❌ INTERDITS |
| **Logique métier** | ❌ INTERDITE |
| **Effets de bord** | ❌ INTERDITS |

### 12.2. Types de retour autorisés

| Type | Exemple |
|------|---------|
| Scalaire | `public function host(): string` |
| Enum | `public function level(): LogLevel` |
| Value Object | `public function credentials(): SmtpCredentials` |
| Record | `public function dsn(): DsnRecord` |
| **Tableau brut** | ❌ **INTERDIT** |

### 12.3. Récapitulatif des contraintes

| Action | Autorisé |
|--------|----------|
| Définir une interface | ✅ |
| Implémenter plusieurs interfaces | ✅ |
| Retourner des scalaires | ✅ |
| Retourner des enums | ✅ |
| Retourner des Value Objects | ✅ |
| Retourner des Records | ✅ |
| Lire l'environnement (getenv) | ✅ |
| Avoir des méthodes utilitaires | ✅ |
| Injecter l'interface spécifique dans un Service | ✅ |
| Avoir un constructeur pour la source (fichier, URL) | ✅ |
| Avoir des propriétés pour stocker des valeurs | ❌ |
| Stocker de l'état interne | ❌ |
| Avoir des effets de bord | ❌ |
| Retourner des tableaux bruts | ❌ |
| Contenir de la logique métier | ❌ |
| Contenir de la validation | ❌ |
| Injecter l'implémentation concrète | ❌ |
| Faire dépendre un Service d'une grosse interface | ❌ |

---

## 13. Règle d'or

> **Une Config est une INTERFACE qui sert de contrat. Les Services dépendent de l'interface spécifique, jamais de l'implémentation concrète ni d'une grosse interface.**
>
> **⚠️ Une Config (interface) ne contient :**
> - ❌ PAS de propriétés
> - ❌ PAS de logique métier
> - ❌ PAS de validation
> - ❌ PAS de tableaux bruts
> - ❌ PAS d'effets de bord
>
> **✅ Une implémentation de Config peut :**
> - ✅ Implémenter plusieurs interfaces (ISP)
> - ✅ Lire les variables d'environnement (getenv)
> - ✅ Lire des fichiers de configuration
> - ✅ Avoir un constructeur pour configurer la SOURCE des données
> - ✅ Retourner des scalaires, enums, Value Objects, Records
> - ✅ Avoir des méthodes utilitaires (formatage, transformation, questions)
>
> **La validation et la logique métier appartiennent aux Services.**
>
> **Une seule classe peut implémenter plusieurs interfaces.**
>
> **Chaque Service ne prend que l'interface dont il a besoin.**
>
> **Le changement d'implémentation ne doit JAMAIS impacter le Service.**

```php
// ✅ L'interface - Contrat stable et ségrégué
interface DatabaseConfigInterface
{
    public function host(): string;
    public function port(): int;
    public function dsn(): string;
}

interface CacheConfigInterface
{
    public function host(): string;
    public function port(): int;
}

// ✅ Une seule classe implémente TOUTES les interfaces - 100% découplée
final class EnvAppConfig implements DatabaseConfigInterface, CacheConfigInterface
{
    public function host(): string { return getenv('DB_HOST') ?: 'localhost'; }
    public function port(): int { return (int) (getenv('DB_PORT') ?: 3306); }
    public function dsn(): string { return "mysql:host={$this->host()};port={$this->port()}"; }
    
    public function cacheHost(): string { return getenv('REDIS_HOST') ?: 'localhost'; }
    public function cachePort(): int { return (int) (getenv('REDIS_PORT') ?: 6379); }
}

// ✅ Services - Chacun ne prend que SON interface
final class DatabaseService
{
    public function __construct(private readonly DatabaseConfigInterface $config) {}
    public function getConnection(): PDO { return new PDO($this->config->dsn()); }
}

final class CacheService
{
    public function __construct(private readonly CacheConfigInterface $config) {}
    public function getClient(): Redis { return new Redis($this->config->host(), $this->config->port()); }
}

// Injection - une seule instance, mais chaque service ne voit que son interface
$config = new EnvAppConfig();
$dbService = new DatabaseService($config);   // ✅ Ne voit que DatabaseConfigInterface
$cacheService = new CacheService($config);   // ✅ Ne voit que CacheConfigInterface

// Demain : on peut séparer en plusieurs classes SANS impacter les Services !
// $dbService = new DatabaseService(new EnvDatabaseConfig());
// $cacheService = new CacheService(new EnvCacheConfig());

// ✅ Les Services n'ont PAS changé !

// Dans un test - mock simple
$mockConfig = $this->createMock(DatabaseConfigInterface::class);
$service = new DatabaseService($mockConfig);

// ✅ La même Config fonctionne PARTOUT, SANS framework !
```
---