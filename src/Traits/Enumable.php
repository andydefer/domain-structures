<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Traits;

use AndyDefer\DomainStructures\Services\EnumService;

/**
 * Provides common utility methods for PHP 8.1+ Enums.
 *
 * @deprecated 2.0.0 Use EnumService instead. This trait will be removed in future versions.
 * @see EnumService
 *
 * Ce trait est déprécié car il impose une approche par héritage.
 * La nouvelle approche privilégie la composition via EnumService.
 *
 * @example
 * // ✅ Nouvelle approche - Composition
 * $enumService = new EnumService();
 * $values = $enumService->values(UserStatus::class);
 * $enum = $enumService->from(UserStatus::class, 'active');
 *
 * // ❌ Ancienne approche - Trait (déprécié)
 * $values = UserStatus::values();
 * $enum = UserStatus::from('active');
 *
 * Pourquoi ce changement ?
 * - Composition > Inheritance : On injecte EnumService plutôt que de l'hériter
 * - Testabilité : EnumService peut être mocké, un trait non
 * - Séparation des responsabilités : Les enums restent des enums, la logique de manipulation est externalisée
 * - Réutilisabilité : EnumService fonctionne avec TOUS les enums sans modifier leur définition
 */
trait Enumable
{
    /**
     * @deprecated Use EnumService::values() instead
     */
    public static function values(): array
    {
        return (new EnumService)->values(static::class);
    }

    /**
     * @deprecated Use EnumService::names() instead
     */
    public static function names(): array
    {
        return (new EnumService)->names(static::class);
    }

    /**
     * @deprecated Use EnumService::cases() instead
     */
    public static function typesInOrder(): array
    {
        return (new EnumService)->cases(static::class);
    }

    /**
     * @deprecated Use EnumService::isValid() instead
     */
    public static function isValid(string|int $value): bool
    {
        return (new EnumService)->isValid(static::class, $value);
    }

    /**
     * @deprecated Use EnumService::fromValue() instead
     */
    public static function fromValue(string|int $value): ?self
    {
        return (new EnumService)->fromValue(static::class, $value);
    }

    /**
     * @deprecated Use EnumService::from() instead
     */
    public static function from(mixed $source): self
    {
        return (new EnumService)->from(static::class, $source);
    }
}
