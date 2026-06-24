<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Abstracts;

/**
 * @deprecated Cette classe est dépréciée
 *
 *             Raison : L'approche par classe abstraite crée un couplage fort
 *             et ne respecte pas le Dependency Inversion Principle (DIP).
 *
 *             ✅ NOUVELLE APPROCHE : Utilisez des interfaces pour les Configs.
 *
 *             Les Services doivent dépendre des interfaces de configuration,
 *             pas d'une classe abstraite concrète.
 * @see https://fr.wikipedia.org/wiki/Principe_d%27inversion_des_d%C3%A9pendances
 *
 * @example
 * // ❌ À ÉVITER (déprécié)
 * final class DatabaseConfig extends AbstractConfig
 * {
 *     public function host(): string
 *     {
 *         return getenv('DB_HOST') ?: 'localhost';
 *     }
 * }
 *
 * // ✅ RECOMMANDÉ (nouvelle approche)
 * interface DatabaseConfigInterface
 * {
 *     public function host(): string;
 *     public function port(): int;
 * }
 *
 * final class EnvDatabaseConfig implements DatabaseConfigInterface
 * {
 *     public function host(): string
 *     {
 *         return getenv('DB_HOST') ?: 'localhost';
 *     }
 *
 *     public function port(): int
 *     {
 *         return (int) (getenv('DB_PORT') ?: 3306);
 *     }
 * }
 *
 * final class DatabaseService
 * {
 *     public function __construct(
 *         private readonly DatabaseConfigInterface $config,  // ← Dépend de l'interface, pas de la classe abstraite
 *     ) {}
 * }
 *
 * @author Andy Defer
 *
 * @deprecated since 2.0.0, will be removed in 3.0.0
 */
abstract class AbstractConfig
{
    /**
     * Final constructor prevents any constructor parameters.
     *
     * @deprecated Le constructeur final n'est plus pertinent.
     *             Les implémentations de Config via interfaces
     *             n'ont pas besoin de constructeur forcé.
     */
    final public function __construct()
    {
        // No validation, no logic - just prevents parameters
    }
}
