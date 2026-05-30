<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Traits;

use AndyDefer\DomainStructures\Abstracts\AbstractTypedCollection;
use AndyDefer\DomainStructures\Collections\Core\TypedCollection;
use AndyDefer\DomainStructures\Hydration\Hydrator;
use InvalidArgumentException;
use RuntimeException;

/**
 * Trait for automatic hydration of objects.
 *
 * Provides from(), fromJson(), and collect() methods that use the Hydrator.
 *
 * @example
 * final class EmailAddress extends AbstractValueObject
 * {
 *     use Hydratable;
 *
 *     public function __construct(public readonly string $value)
 *     {
 *         if (!filter_var($this->value, FILTER_VALIDATE_EMAIL)) {
 *             throw new InvalidArgumentException("Invalid email");
 *         }
 *     }
 *
 *     public function getValue(): string { return $this->value; }
 * }
 *
 * // Usage
 * $email = EmailAddress::from('user@example.com');
 * $email = EmailAddress::fromJson('"user@example.com"');
 * $collection = EmailAddress::collect(['a@b.com', 'c@d.com']);
 */
trait Hydratable
{
    /**
     * Creates an instance from a source.
     *
     * @param  mixed  $source  The source data (string, array, object, DataObject, or JSON)
     *
     * @throws RuntimeException|InvalidArgumentException
     */
    public static function from(mixed $source): static
    {
        return Hydrator::hydrate(static::class, $source);
    }

    /**
     * Creates an instance from a JSON string.
     *
     * @param  string  $json  JSON string
     *
     * @throws RuntimeException If JSON is invalid
     */
    public static function fromJson(string $json): static
    {
        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException(sprintf(
                'Invalid JSON: %s',
                json_last_error_msg()
            ));
        }

        return static::from($data);
    }

    /**
     * Hydrates a collection of sources into a typed collection.
     *
     * @template TCollection of AbstractTypedCollection
     *
     * @param  iterable<mixed>  $sources
     * @param  class-string<TCollection>  $collectionClass
     * @return TCollection
     *
     * @throws InvalidArgumentException
     */
    public static function collect(iterable $sources, string $collectionClass = TypedCollection::class): AbstractTypedCollection
    {
        if (! is_subclass_of($collectionClass, AbstractTypedCollection::class)) {
            throw new InvalidArgumentException(sprintf(
                'Collection class "%s" must extend %s',
                $collectionClass,
                AbstractTypedCollection::class
            ));
        }

        $allowedTypes = method_exists(static::class, 'getAllowedTypes')
            ? static::getAllowedTypes()
            : [static::class];

        $collection = new $collectionClass(...$allowedTypes);

        foreach ($sources as $source) {
            $collection->add(static::from($source));
        }

        return $collection;
    }
}
