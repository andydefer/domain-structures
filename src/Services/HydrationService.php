<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Services;

use AndyDefer\DomainStructures\Abstracts\AbstractTypedCollection;
use AndyDefer\DomainStructures\Collections\Core\TypedCollection;
use AndyDefer\DomainStructures\Hydration\Hydrator;
use AndyDefer\DomainStructures\Normalizers\NormalizerChain;
use InvalidArgumentException;
use RuntimeException;

/**
 * Service d'hydratation pour les objets du domaine.
 * 
 * Remplace le trait Hydratable et le singleton Hydrator.
 * 
 * ✅ Pas d'état interne
 * ✅ Dépendances injectées dans le constructeur
 * ✅ Toutes les données arrivent en paramètres
 * ✅ Testable et mockable
 * 
 * @author Andy Defer
 */
final class HydrationService
{
    /**
     * Creates an instance from a source.
     *
     * @param  mixed  $source  The source data (string, array, object, DataObject, or JSON)
     * @throws RuntimeException|InvalidArgumentException
     */
    public function hydrate(string $className, mixed $source): object
    {
        return Hydrator::hydrate($className, $source);
    }

    /**
     * Creates an instance from a JSON string.
     *
     * @param  string  $json  JSON string
     * @throws RuntimeException If JSON is invalid
     */
    public function hydrateFromJson(string $className, string $json): object
    {
        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException(sprintf('Invalid JSON: %s', json_last_error_msg()));
        }

        return $this->hydrate($className, $data);
    }

    /**
     * Hydrates a collection of sources into a typed collection.
     *
     * @template TCollection of AbstractTypedCollection
     *
     * @param  string  $itemClass
     * @param  iterable<mixed>  $sources
     * @param  class-string<TCollection>  $collectionClass
     * @return TCollection
     *
     * @throws InvalidArgumentException
     */
    public function collect(
        string $itemClass,
        iterable $sources,
        string $collectionClass = TypedCollection::class
    ): AbstractTypedCollection {
        if (!is_subclass_of($collectionClass, AbstractTypedCollection::class)) {
            throw new InvalidArgumentException(sprintf(
                'Collection class "%s" must extend %s',
                $collectionClass,
                AbstractTypedCollection::class
            ));
        }

        $allowedTypes = method_exists($itemClass, 'getAllowedTypes')
            ? $itemClass::getAllowedTypes()
            : [$itemClass];

        $collection = new $collectionClass(...$allowedTypes);

        foreach ($sources as $source) {
            $collection->add($this->hydrate($itemClass, $source));
        }

        return $collection;
    }

    /**
     * Hydrates a collection from a JSON string.
     *
     * @template TCollection of AbstractTypedCollection
     *
     * @param  string  $itemClass
     * @param  string  $json
     * @param  class-string<TCollection>  $collectionClass
     * @return TCollection
     *
     * @throws RuntimeException If JSON is invalid
     * @throws InvalidArgumentException
     */
    public function collectFromJson(
        string $itemClass,
        string $json,
        string $collectionClass = TypedCollection::class
    ): AbstractTypedCollection {
        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException(sprintf('Invalid JSON: %s', json_last_error_msg()));
        }

        return $this->collect($itemClass, $data, $collectionClass);
    }
}
