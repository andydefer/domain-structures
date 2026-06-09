<?php
// src/Services/HydrationService.php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Services;

use AndyDefer\DomainStructures\Abstracts\AbstractTypedCollection;
use InvalidArgumentException;
use RuntimeException;

/**
 * Service d'hydratation principal.
 * 
 * Ce service compose ItemHydrationService et CollectionHydrationService
 * pour offrir une interface unifiée pour toutes les opérations d'hydratation.
 * 
 * @author Andy Defer
 */
final class HydrationService
{
    private ItemHydrationService $itemHydration;
    private CollectionHydrationService $collectionHydration;

    public function __construct()
    {
        $this->itemHydration = new ItemHydrationService();
        $this->collectionHydration = new CollectionHydrationService($this->itemHydration);
    }

    /**
     * Hydrate un seul item.
     * 
     * @param class-string $className
     * @param mixed $source
     * @return object|string|int|float|bool|null
     * @throws InvalidArgumentException|RuntimeException
     */
    public function hydrate(string $className, mixed $source): object|string|int|float|bool|null
    {
        return $this->itemHydration->hydrate($className, $source);
    }

    /**
     * Hydrate un seul item depuis du JSON.
     * 
     * @param class-string $className
     * @param string $json
     * @return object|string|int|float|bool|null
     * @throws RuntimeException|InvalidArgumentException
     */
    public function hydrateFromJson(string $className, string $json): object|string|int|float|bool|null
    {
        return $this->itemHydration->hydrateFromJson($className, $json);
    }

    /**
     * Hydrate une collection d'items.
     * 
     * @param iterable $sources
     * @param class-string<AbstractTypedCollection> $collectionClass
     * @return AbstractTypedCollection
     * @throws InvalidArgumentException
     */
    public function collect(
        iterable $sources,
        string $collectionClass = AbstractTypedCollection::class
    ): AbstractTypedCollection {
        return $this->collectionHydration->collect($sources, $collectionClass);
    }

    /**
     * Hydrate une collection depuis du JSON.
     * 
     * @param string $json
     * @param class-string<AbstractTypedCollection> $collectionClass
     * @return AbstractTypedCollection
     * @throws RuntimeException|InvalidArgumentException
     */
    public function collectFromJson(
        string $json,
        string $collectionClass = AbstractTypedCollection::class
    ): AbstractTypedCollection {
        return $this->collectionHydration->collectFromJson($json, $collectionClass);
    }
}
