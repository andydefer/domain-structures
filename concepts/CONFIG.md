# Config - Documentation Complète (Version Finale)

## Table des matières

1. [Définition et concepts](#1-définition-et-concepts)
2. [Pourquoi une Config POO ?](#2-pourquoi-une-config-poo-)
3. [AbstractConfig - Classe de base](#3-abstractconfig---classe-de-base)
4. [Créer sa première Config](#4-créer-sa-première-config)
5. [Méthodes disponibles](#5-méthodes-disponibles)
6. [Méthodes utilitaires](#6-méthodes-utilitaires)
7. [Chargement depuis l'environnement](#7-chargement-depuis-lenvironnement)
8. [Cas d'utilisation](#8-cas-dutilisation)
9. [Exemples concrets](#9-exemples-concrets)
10. [Bonnes pratiques](#10-bonnes-pratiques)
11. [Récapitulatif](#11-récapitulatif)

---

## 1. Définition et concepts

Une **Config** est une classe fermée qui expose des valeurs de configuration via des méthodes. Elle est **immuable**, **sans état interne**, **sans constructeur paramétré** et **auto-documentée**.

```
Config → Classe fermée → Aucun état interne → Aucune propriété → Auto-documentée
```

### 1.1. Principes fondamentaux

| Principe | Description |
|----------|-------------|
| **Constructeur final** | Empêche l'instanciation avec paramètres |
| **Aucune propriété** | Interdiction formelle de toute propriété (même private) |
| **Aucun état interne** | La classe ne stocke rien entre les appels |
| **Méthodes immuables** | Chaque méthode retourne une valeur fixe ou calculée |
| **Auto-documentée** | Les noms de méthodes décrivent la configuration |
| **Testable** | Peut être mockée comme toute classe |

### 1.2. Règle fondamentale

> **⚠️ Une Config n'a AUCUNE propriété. Pas de `private string $host`, pas de `private array $config`. RIEN. Uniquement des méthodes.**

```php
// ❌ MAUVAIS - Une Config avec des propriétés
final class BadConfig extends AbstractConfig
{
    private string $host;  // ❌ INTERDIT
    private int $port;      // ❌ INTERDIT
    
    public function host(): string
    {
        return $this->host;  // ❌ Dépend d'un état interne
    }
}

// ✅ BON - Une Config sans propriétés
final class GoodConfig extends AbstractConfig
{
    public function host(): string
    {
        return 'localhost';  // ✅ Valeur directe ou getenv()
    }
}
```

---

## 2. Pourquoi une Config POO ?

### 2.1. Le problème des approches traditionnelles

| Approche | Problème |
|----------|----------|
| `config('app.key')` | String magique, non typé, pas d'autocomplétion |
| `$_ENV['KEY']` | Non structuré, non typé, global |
| `array $config` | Tableau brut, aucune garantie de structure |
| `new Config(['key' => 'value'])` | Doctrine des clés, pas de validation |

### 2.2. Ce que Config POO résout

| Problème | Solution |
|----------|----------|
| Clés magiques | Méthodes nommées (`host()`, `port()`) |
| Non typé | Types de retour explicites (`: string`, `: int`) |
| Pas d'autocomplétion | L'IDE connaît toutes les méthodes |
| Mutabilité | Classe sans état, méthodes immuables |

---

## 3. AbstractConfig - Classe de base

### 3.1. Code source

```php
<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Abstracts;

/**
 * Abstract base class for configuration classes.
 *
 * Forces child classes to have no constructor parameters.
 * All configuration values must be hardcoded in methods or loaded from environment.
 * Config classes MUST have NO properties - only methods.
 */
abstract class AbstractConfig
{
    /**
     * Final constructor prevents any parameters.
     */
    final public function __construct()
    {
        // No validation, no logic - just prevents parameters
    }
}
```

### 3.2. Caractéristiques

| Caractéristique | Description |
|-----------------|-------------|
| **Constructeur final** | Empêche l'instanciation avec paramètres |
| **Aucune propriété** | Pas de stockage d'état |
| **Aucune validation** | La validation se fait dans les Services |

---

## 4. Créer sa première Config

### 4.1. Config simple

```php
<?php

declare(strict_types=1);

namespace App\Configs;

use AndyDefer\DomainStructures\Abstracts\AbstractConfig;

final class DatabaseConfig extends AbstractConfig
{
    public function driver(): string
    {
        return 'mysql';
    }
    
    public function host(): string
    {
        return 'localhost';
    }
    
    public function port(): int
    {
        return 3306;
    }
    
    public function database(): string
    {
        return 'my_app';
    }
    
    public function username(): string
    {
        return 'root';
    }
    
    public function password(): string
    {
        return 'secret';
    }
    
    public function charset(): string
    {
        return 'utf8mb4';
    }
    
    public function strict(): bool
    {
        return true;
    }
}
```

### 4.2. Utilisation

```php
// ✅ Correct - pas de paramètres
$config = new DatabaseConfig();

// ❌ Erreur - le constructeur ne prend pas de paramètres
$config = new DatabaseConfig('mysql', 'localhost', 3306); // Impossible

// Utilisation des valeurs
echo $config->host();      // 'localhost'
echo $config->port();      // 3306
echo $config->database();  // 'my_app'
```

### 4.3. Injection dans un Service

```php
final class DatabaseConnectionService
{
    public function __construct(
        private readonly DatabaseConfig $config,
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
```

---

## 5. Méthodes disponibles

### 5.1. Méthodes retournant des scalaires

```php
final class AppConfig extends AbstractConfig
{
    public function name(): string { return 'My Application'; }
    public function env(): string { return 'production'; }
    public function debug(): bool { return false; }
    public function port(): int { return 8080; }
    public function timeout(): ?int { return null; }  // Valeur nullable
}
```

### 5.2. Méthodes retournant des enums

```php
enum LogLevel: string
{
    case DEBUG = 'debug';
    case INFO = 'info';
    case WARNING = 'warning';
    case ERROR = 'error';
}

final class LoggerConfig extends AbstractConfig
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

### 5.3. Méthodes retournant des Value Objects

```php
final class EmailConfig extends AbstractConfig
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
        return new EmailAddress(
            getenv('MAIL_FROM') ?: 'noreply@example.com'
        );
    }
}
```

### 5.4. Méthodes retournant des Records

```php
final class DatabaseConfig extends AbstractConfig
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

### 5.5. Méthodes retournant des TypedCollection

```php
final class ApiConfig extends AbstractConfig
{
    public function baseUrl(): string { return getenv('API_BASE_URL') ?: 'https://api.example.com'; }
    public function apiKey(): string { return getenv('API_KEY') ?: ''; }
    
    public function defaultHeaders(): HeaderCollection
    {
        $headers = new HeaderCollection();
        $headers->add(new HeaderRecord('Authorization', 'Bearer ' . $this->apiKey()));
        $headers->add(new HeaderRecord('Accept', 'application/json'));
        $headers->add(new HeaderRecord('Content-Type', 'application/json'));
        
        return $headers;
    }
    
    public function retryStatuses(): StatusCodeCollection
    {
        $statuses = new StatusCodeCollection();
        $statuses->add(429, 500, 502, 503, 504);
        
        return $statuses;
    }
}
```

---

## 6. Méthodes utilitaires

> **⚠️ IMPORTANT : Une Config peut avoir des méthodes utilitaires qui ne correspondent pas directement à une valeur de configuration, mais qui facilitent l'utilisation des valeurs. Ces méthodes doivent retourner exclusivement : des scalaires, des enums, des Value Objects, des Records ou des TypedCollection. JAMAIS de tableaux bruts.**

### 6.1. Méthodes de formatage

```php
final class DatabaseConfig extends AbstractConfig
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
    
    public function connectionUrl(): ConnectionUrl
    {
        return new ConnectionUrl(
            sprintf('mysql:host=%s;port=%d;dbname=%s', 
                $this->host(), 
                $this->port(), 
                $this->database()
            )
        );
    }
}
```

### 6.2. Méthodes de question

```php
final class AppConfig extends AbstractConfig
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
    
    public function shouldShowErrors(): bool
    {
        return $this->debug() || $this->isLocal();
    }
    
    public function shouldCache(): bool
    {
        return !$this->isLocal() && !$this->debug();
    }
}
```

### 6.3. Méthodes de transformation

```php
final class RedisConfig extends AbstractConfig
{
    public function host(): string { return getenv('REDIS_HOST') ?: 'localhost'; }
    public function port(): int { return (int) (getenv('REDIS_PORT') ?: 6379); }
    public function password(): ?string { return getenv('REDIS_PASSWORD') ?: null; }
    public function database(): int { return (int) (getenv('REDIS_DATABASE') ?: 0); }
    
    public function connectionParameters(): RedisConnectionRecord
    {
        return new RedisConnectionRecord(
            host: $this->host(),
            port: $this->port(),
            password: $this->password(),
            database: $this->database(),
        );
    }
    
    public function dsn(): RedisDsn
    {
        if ($this->password()) {
            return new RedisDsn(
                sprintf('redis://:%s@%s:%d/%d',
                    $this->password(),
                    $this->host(),
                    $this->port(),
                    $this->database()
                )
            );
        }
        
        return new RedisDsn(
            sprintf('redis://%s:%d/%d',
                $this->host(),
                $this->port(),
                $this->database()
            )
        );
    }
}
```

### 6.4. Règle pour les méthodes utilitaires

| Type de méthode utilitaire | Type de retour autorisé | Exemple |
|---------------------------|----------------------|---------|
| Formatage | Record, Value Object | `dsn(): DsnRecord` |
| Transformation | Record, Value Object, TypedCollection | `connectionParameters(): ConnectionRecord` |
| Question | Scalaire (bool, string, int) | `isProduction(): bool` |
| Construction | Record, Value Object, TypedCollection | `clientConfig(): ClientConfigRecord` |
| **Retour de tableau brut** | ❌ **INTERDIT** | ~~`toArray(): array`~~ |
| **Logique métier complexe** | ❌ **INTERDIT** | ~~`calculateTotal()`~~ |
| **Effets de bord** | ❌ **INTERDIT** | ~~`saveToFile()`~~ |

---

## 7. Chargement depuis l'environnement

### 7.1. Utilisation des variables d'environnement

```php
final class DatabaseConfig extends AbstractConfig
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
        return getenv('DB_PASSWORD') ?: 'secret';
    }
    
    public function charset(): string
    {
        return getenv('DB_CHARSET') ?: 'utf8mb4';
    }
    
    public function strict(): bool
    {
        return getenv('DB_STRICT') === 'true';
    }
}
```

### 7.2. Avec préfixe

```php
final class AppConfig extends AbstractConfig
{
    private string $prefix;
    
    public function __construct(string $prefix = 'APP_')
    {
        $this->prefix = $prefix;
        parent::__construct();
    }
    
    public function name(): string
    {
        return getenv($this->prefix . 'NAME') ?: 'Application';
    }
    
    public function env(): string
    {
        return getenv($this->prefix . 'ENV') ?: 'production';
    }
    
    public function debug(): bool
    {
        return getenv($this->prefix . 'DEBUG') === 'true';
    }
    
    public function url(): string
    {
        return getenv($this->prefix . 'URL') ?: 'http://localhost';
    }
}
```

---

## 8. Cas d'utilisation

### 8.1. Configuration base de données

```php
final class DatabaseConfig extends AbstractConfig
{
    public function host(): string { return getenv('DB_HOST') ?: 'localhost'; }
    public function port(): int { return (int) (getenv('DB_PORT') ?: 3306); }
    public function database(): string { return getenv('DB_DATABASE') ?: 'app'; }
    public function username(): string { return getenv('DB_USERNAME') ?: 'root'; }
    public function password(): string { return getenv('DB_PASSWORD') ?: ''; }
    
    public function dsn(): string
    {
        return sprintf(
            'mysql:host=%s;port=%d;dbname=%s',
            $this->host(),
            $this->port(),
            $this->database()
        );
    }
}
```

### 8.2. Configuration API externe

```php
final class ApiConfig extends AbstractConfig
{
    public function baseUrl(): string
    {
        return getenv('API_BASE_URL') ?: 'https://api.example.com';
    }
    
    public function timeout(): int
    {
        return (int) (getenv('API_TIMEOUT') ?: 30);
    }
    
    public function retryAttempts(): int
    {
        return (int) (getenv('API_RETRY_ATTEMPTS') ?: 3);
    }
    
    public function retryDelay(): int
    {
        return (int) (getenv('API_RETRY_DELAY') ?: 100);
    }
    
    public function apiKey(): string
    {
        return getenv('API_KEY') ?: '';
    }
}
```

---

## 9. Exemples concrets

### 9.1. Configuration complète d'application

```php
final class AppConfig extends AbstractConfig
{
    // Application
    public function name(): string { return getenv('APP_NAME') ?: 'MyApp'; }
    public function env(): string { return getenv('APP_ENV') ?: 'local'; }
    public function debug(): bool { return getenv('APP_DEBUG') === 'true'; }
    public function url(): string { return getenv('APP_URL') ?: 'http://localhost'; }
    public function timezone(): string { return getenv('APP_TIMEZONE') ?: 'UTC'; }
    public function locale(): string { return getenv('APP_LOCALE') ?: 'en'; }
    
    // Database
    public function dbHost(): string { return getenv('DB_HOST') ?: 'localhost'; }
    public function dbPort(): int { return (int) (getenv('DB_PORT') ?: 3306); }
    public function dbName(): string { return getenv('DB_NAME') ?: 'app'; }
    public function dbUser(): string { return getenv('DB_USER') ?: 'root'; }
    public function dbPassword(): string { return getenv('DB_PASSWORD') ?: ''; }
    
    // Redis
    public function redisHost(): string { return getenv('REDIS_HOST') ?: 'localhost'; }
    public function redisPort(): int { return (int) (getenv('REDIS_PORT') ?: 6379); }
    public function redisPassword(): ?string { return getenv('REDIS_PASSWORD') ?: null; }
    
    // Mail
    public function mailHost(): string { return getenv('MAIL_HOST') ?: 'smtp.mailtrap.io'; }
    public function mailPort(): int { return (int) (getenv('MAIL_PORT') ?: 2525); }
    public function mailUser(): string { return getenv('MAIL_USER') ?: ''; }
    public function mailPassword(): string { return getenv('MAIL_PASSWORD') ?: ''; }
    public function mailFrom(): string { return getenv('MAIL_FROM') ?: 'noreply@example.com'; }
    
    // Méthodes utilitaires
    public function dbDsn(): string
    {
        return sprintf(
            'mysql:host=%s;port=%d;dbname=%s',
            $this->dbHost(),
            $this->dbPort(),
            $this->dbName()
        );
    }
    
    public function redisDsn(): string
    {
        $dsn = sprintf('redis://%s:%d', $this->redisHost(), $this->redisPort());
        
        if ($this->redisPassword() !== null) {
            $dsn = sprintf('redis://:%s@%s:%d', $this->redisPassword(), $this->redisHost(), $this->redisPort());
        }
        
        return $dsn;
    }
    
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

### 9.2. Utilisation dans un Service

```php
final class DatabaseService
{
    public function __construct(
        private readonly AppConfig $config,
    ) {}
    
    public function getConnection(): PDO
    {
        return new PDO(
            $this->config->dbDsn(),
            $this->config->dbUser(),
            $this->config->dbPassword()
        );
    }
}

// Utilisation
$config = new AppConfig();
$dbService = new DatabaseService($config);
```

---

## 10. Bonnes pratiques

### 10.1. Nommage des méthodes

```php
// ✅ BON - Noms clairs et explicites
public function host(): string { ... }
public function port(): int { ... }
public function database(): string { ... }

// ❌ MAUVAIS - Noms vagues
public function get(): string { ... }
public function val(): int { ... }
```

### 10.2. Regrouper par domaine

```php
// ✅ BON - Config séparées par domaine
$dbConfig = new DatabaseConfig();
$cacheConfig = new CacheConfig();
$mailConfig = new MailConfig();

// ❌ MAUVAIS - Une seule config pour tout
$config = new AppConfig();  // 50 méthodes mélangées
```

### 10.3. Valeurs par défaut explicites

```php
// ✅ BON - Default explicite via opérateur ?:
public function host(): string
{
    return getenv('DB_HOST') ?: 'localhost';
}

// ❌ MAUVAIS - Default caché
public function host(): string
{
    $host = getenv('DB_HOST');
    if ($host === false) {
        $host = 'localhost';
    }
    return $host;
}
```

### 10.4. Jamais de tableau brut

```php
// ❌ MAUVAIS - Retourne un tableau brut
public function toArray(): array { ... }
public function getValues(): array { ... }

// ✅ BON - Retourne un Record, Value Object ou TypedCollection
public function toRecord(): ConfigRecord { ... }
public function getValues(): ValueCollection { ... }
```

### 10.5. La validation se fait dans les Services, pas dans les Configs

```php
// ❌ MAUVAIS - Validation dans la Config
final class BadConfig extends AbstractConfig
{
    public function port(): int
    {
        $port = (int) (getenv('PORT') ?: 3306);
        if ($port <= 0) {
            throw new InvalidArgumentException('Invalid port');
        }
        return $port;
    }
}

// ✅ BON - La Config retourne la valeur brute
final class GoodConfig extends AbstractConfig
{
    public function port(): int
    {
        return (int) (getenv('PORT') ?: 3306);
    }
}

// ✅ BON - Validation dans le Service qui utilise la Config
final class DatabaseService
{
    public function __construct(private readonly DatabaseConfig $config) {}
    
    public function getConnection(): PDO
    {
        $port = $this->config->port();
        
        if ($port <= 0 || $port > 65535) {
            throw new InvalidArgumentException("Invalid port: {$port}");
        }
        
        return new PDO(...);
    }
}
```

---

## 11. Récapitulatif

### 11.1. Caractéristiques principales

| Caractéristique | Description |
|-----------------|-------------|
| **Constructeur final** | Empêche l'instanciation avec paramètres |
| **Aucune propriété** | Interdiction formelle de toute propriété |
| **Aucun état interne** | La classe ne stocke rien |
| **Méthodes immuables** | Chaque appel retourne la même valeur |
| **Auto-documentée** | Les noms de méthodes sont la documentation |
| **Testable** | Peut être mockée facilement |
| **Aucune validation** | La validation est dans les Services |

### 11.2. Types de retour autorisés

| Type | Exemple |
|------|---------|
| Scalaire (string, int, float, bool, null) | `public function host(): string` |
| Enum | `public function level(): LogLevel` |
| Value Object | `public function credentials(): SmtpCredentials` |
| Record | `public function dsn(): DsnRecord` |
| TypedCollection | `public function headers(): HeaderCollection` |
| **Tableau brut** | ❌ **INTERDIT** |

### 11.3. Ce qu'une Config peut faire

| Action | Autorisé | Exemple |
|--------|----------|---------|
| Retourner des scalaires | ✅ | `return 'localhost'` |
| Retourner des enums | ✅ | `return LogLevel::INFO` |
| Retourner des Value Objects | ✅ | `return new EmailAddress(...)` |
| Retourner des Records | ✅ | `return new DsnRecord(...)` |
| Retourner des TypedCollection | ✅ | `return new HeaderCollection()` |
| Lire l'environnement | ✅ | `getenv('KEY')` |
| Avoir des méthodes utilitaires | ✅ | `isProduction(): bool` |
| Avoir un constructeur avec paramètres | ❌ | Bloqué par constructeur final |
| Avoir des propriétés privées | ❌ | État interne interdit |
| Être mutable | ❌ | Méthodes sans setters |
| Avoir des effets de bord | ❌ | Config pure, pas d'IO |
| Retourner des tableaux bruts | ❌ | Non typé, violation des principes |
| Contenir de la logique métier complexe | ❌ | Violation SRP |
| Contenir de la validation | ❌ | La validation est dans les Services |

### 11.4. Règle d'or

```php
// ✅ La Config parfaite
final class PerfectConfig extends AbstractConfig
{
    // Méthodes simples retournant des scalaires
    public function host(): string 
    { 
        return getenv('HOST') ?: 'localhost'; 
    }
    
    public function port(): int 
    { 
        return (int) (getenv('PORT') ?: 8080); 
    }
    
    // Méthode utilitaire retournant un Record
    public function url(): UrlRecord 
    { 
        return new UrlRecord(
            sprintf('http://%s:%d', $this->host(), $this->port())
        ); 
    }
    
    // Méthode utilitaire retournant un booléen
    public function isProduction(): bool
    {
        return getenv('APP_ENV') === 'production';
    }
}

// Utilisation
$config = new PerfectConfig();
echo $config->host();           // 'localhost'
echo $config->url()->toString(); // 'http://localhost:8080'

if ($config->isProduction()) {
    // Comportement spécifique à la production
}
```