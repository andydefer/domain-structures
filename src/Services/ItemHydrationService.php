<?php
// src/Services/ItemHydrationService.php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Services;

use AndyDefer\DomainStructures\Abstracts\AbstractData;
use AndyDefer\DomainStructures\Abstracts\AbstractDataObject;
use AndyDefer\DomainStructures\Abstracts\AbstractRecord;
use AndyDefer\DomainStructures\Abstracts\AbstractValueObject;
use AndyDefer\DomainStructures\Enums\PhpType;
use AndyDefer\DomainStructures\Hydration\Hydrator;
use InvalidArgumentException;
use RuntimeException;
use UnitEnum;

final class ItemHydrationService
{
    /**
     * Hydrate un item à partir d'une source.
     *
     * @param class-string<AbstractRecord|AbstractValueObject|AbstractData|AbstractDataObject|UnitEnum|string|int|float|bool> $className
     * @param mixed $source
     * @return object|string|int|float|bool|null
     * @throws InvalidArgumentException|RuntimeException
     */
    public function hydrate(string $className, mixed $source): object|string|int|float|bool|null
    {
        // Si la source est déjà du bon type
        if (is_object($source) && $source instanceof $className) {
            return $source;
        }

        // Gérer les types scalaires
        if (in_array($className, PhpType::getScalarTypeNames(), true)) {
            return match ($className) {
                'int' => (int) $source,
                'string' => (string) $source,
                'float' => (float) $source,
                'bool' => (bool) $source,
                'null' => null,
                default => $source,
            };
        }

        // Pour tout le reste, utiliser l'hydrateur
        return Hydrator::hydrate($className, $source);
    }

    public function hydrateFromJson(string $className, string $json): object|string|int|float|bool|null
    {
        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException(sprintf('Invalid JSON: %s', json_last_error_msg()));
        }

        return $this->hydrate($className, $data);
    }
}
