<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Hydration\Strategy;

use AndyDefer\DomainStructures\Interfaces\Transformable;
use ReflectionClass;

/**
 * Hydration strategy for classes with zero-parameter constructors.
 *
 * Creates a new instance without passing any arguments, ignoring the source data.
 * This is useful for classes like TypedCollection that have no constructor parameters
 * and manage their own internal state.
 *
 * @example
 * $strategy = new ZeroParameterStrategy();
 * $collection = $strategy->hydrate(StringTypedCollection::class, ['a', 'b', 'c']);
 * // Creates new instance, source is ignored
 */
final class ZeroParameterStrategy implements HydrationStrategyInterface
{
    public function supports(string $className, mixed $source): bool
    {
        $reflection = new ReflectionClass($className);
        $constructor = $reflection->getConstructor();

        // Support if no constructor OR constructor has zero parameters
        return ! $constructor || $constructor->getNumberOfParameters() === 0;
    }

    public function hydrate(string $className, mixed $source): object
    {
        if (is_subclass_of($className, Transformable::class)) {
            return $className::from($source);
        }

        // Sinon, créer une instance vide (source ignorée)
        return new $className;
    }
}
