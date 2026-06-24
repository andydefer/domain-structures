<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Abstracts;

use AndyDefer\DomainStructures\Collections\Core\TypedCollection;
use AndyDefer\DomainStructures\Interfaces\Transformable;

/**
 * AbstractAssociative - Classe de base immutable pour les objets de données associatifs.
 *
 * Une fois construit, on ne peut pas le modifier.
 * Pour "modifier", on crée une nouvelle instance avec with(), merge() ou without().
 * Supporte l'accès par propriété (->) et par tableau ([]).
 *
 * @template T
 */
abstract class AbstractAssociative extends AbstractDataObject
{
    /**
     * @var array<string|int, mixed>
     */
    protected array $data = [];

    /**
     * @param  array<string|int, mixed>  $data
     */
    public function __construct(array $data = [])
    {
        foreach ($data as $key => $value) {
            if (is_string($key)) {
                $this->data[$this->normalizeKey($key)] = $value;
            } else {
                $this->data[$key] = $value;
            }
        }
    }

    /**
     * Normalise une clé string.
     * À surcharger dans les classes filles pour changer le comportement.
     */
    abstract protected function normalizeKey(string $key): string;

    /**
     * Crée une nouvelle instance avec une propriété modifiée.
     */
    public function with(string $key, mixed $value): static
    {
        $newData = $this->data;
        $normalizedKey = $this->normalizeKey($key);
        $newData[$normalizedKey] = $value;

        return new static($newData);
    }

    /**
     * Crée une nouvelle instance en fusionnant avec un tableau.
     *
     * @param  array<string|int, mixed>  $data
     */
    public function merge(array $data): static
    {
        return new static([...$this->data, ...$data]);
    }

    /**
     * Crée une nouvelle instance sans certaines clés.
     */
    public function without(string ...$keys): static
    {
        $newData = $this->data;
        foreach ($keys as $key) {
            $normalizedKey = $this->normalizeKey($key);
            unset($newData[$normalizedKey]);
        }

        return new static($newData);
    }

    // ========== MAGIC GETTER ==========

    public function __get(string $name): mixed
    {
        $normalizedKey = $this->normalizeKey($name);

        return $this->convertValue($this->data[$normalizedKey] ?? null);
    }

    /**
     * Vérifie si une propriété existe (même si sa valeur est null).
     */
    public function __isset(string $name): bool
    {
        $normalizedKey = $this->normalizeKey($name);

        return array_key_exists($normalizedKey, $this->data);
    }

    /**
     * ❌ INTERDIT - AbstractDataObject est immutable !
     *
     * @throws \RuntimeException
     */
    public function __set(string $name, mixed $value): void
    {
        throw new \RuntimeException(get_class($this).' is immutable. Use with() or merge() to create a new instance.');
    }

    // ========== ARRAYACCESS (READ ONLY) ==========

    public function offsetExists(mixed $offset): bool
    {
        if (is_int($offset)) {
            return array_key_exists($offset, $this->data);
        }
        if (is_string($offset)) {
            return array_key_exists($this->normalizeKey($offset), $this->data);
        }

        return false;
    }

    public function offsetGet(mixed $offset): mixed
    {
        if (is_int($offset)) {
            return $this->convertValue($this->data[$offset] ?? null);
        }
        if (is_string($offset)) {
            return $this->convertValue($this->data[$this->normalizeKey($offset)] ?? null);
        }

        return null;
    }

    /**
     * ❌ INTERDIT - AbstractDataObject est immutable !
     *
     * @throws \RuntimeException
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new \RuntimeException(get_class($this).' is immutable. Use with() or merge() to create a new instance.');
    }

    /**
     * ❌ INTERDIT - AbstractDataObject est immutable !
     *
     * @throws \RuntimeException
     */
    public function offsetUnset(mixed $offset): void
    {
        throw new \RuntimeException(get_class($this).' is immutable. Use without() to create a new instance.');
    }

    // ========== MÉTHODES PROTECTED ==========

    /**
     * Convertit récursivement les valeurs.
     */
    protected function convertValue(mixed $value): mixed
    {
        if ($value instanceof static) {
            return $value;
        }

        if (is_array($value)) {
            if ($this->isAssociativeArray($value)) {
                return new static($value);
            }

            return array_map(fn ($item) => $this->convertValue($item), $value);
        }

        return $value;
    }

    /**
     * Vérifie si un tableau est associatif.
     *
     * @param  array<mixed>  $array
     */
    protected function isAssociativeArray(array $array): bool
    {
        if (empty($array)) {
            return false;
        }

        return array_keys($array) !== range(0, count($array) - 1);
    }

    /**
     * Convertit snake_case en camelCase.
     */
    protected function snakeToCamel(string $string): string
    {
        return lcfirst(str_replace('_', '', ucwords($string, '_')));
    }

    /**
     * Convertit camelCase en snake_case.
     */
    protected function camelToSnake(string $string): string
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $string));
    }

    // ========== IMPLÉMENTATION DE TRANSFORMABLE ==========

    /**
     * Crée une instance à partir d'une source.
     *
     * @deprecated Cette méthode est dépréciée depuis la version 2.0.0.
     *             Elle sera supprimée dans la version 3.0.0.
     *             Utilisez le constructeur directement : new static($source)
     *
     * @throws \InvalidArgumentException
     */
    public static function from(mixed $source): static
    {
        @trigger_error(
            sprintf(
                '%s::from() est dépréciée depuis la version 2.0.0. '.
                    'Cette méthode sera supprimée dans la version 3.0.0. '.
                    'Utilisez le constructeur "new %s()" à la place.',
                static::class,
                static::class
            ),
            E_USER_DEPRECATED
        );

        if ($source instanceof static) {
            return $source;
        }
        if (is_array($source)) {
            return new static($source);
        }
        if (is_object($source)) {
            return new static(get_object_vars($source));
        }
        throw new \InvalidArgumentException(sprintf(
            'Cannot create %s from %s. Expected array or object.',
            static::class,
            gettype($source)
        ));
    }

    /**
     * Retourne le tableau original.
     *
     * @return array<string|int, mixed>
     */
    public function toArray(): array
    {
        $result = [];
        foreach ($this->data as $key => $value) {
            if ($value instanceof static) {
                $result[$key] = $value->toArray();
            } elseif (is_array($value)) {
                $result[$key] = array_map(function ($item) {
                    return $item instanceof static ? $item->toArray() : $item;
                }, $value);
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * Crée une instance à partir de JSON.
     *
     * @deprecated Cette méthode est dépréciée depuis la version 2.0.0.
     *             Elle sera supprimée dans la version 3.0.0.
     *             Utilisez le constructeur avec json_decode() : new static(json_decode($json, true))
     */
    public static function fromJson(string $json): static
    {
        @trigger_error(
            sprintf(
                '%s::fromJson() est dépréciée depuis la version 2.0.0. '.
                    'Cette méthode sera supprimée dans la version 3.0.0. '.
                    'Utilisez "new %s(json_decode($json, true))" à la place.',
                static::class,
                static::class
            ),
            E_USER_DEPRECATED
        );

        $data = json_decode($json, true);

        return new static($data ?? []);
    }

    /**
     * Hydrates a collection of sources into a typed collection.
     *
     * @deprecated Cette méthode est dépréciée depuis la version 2.0.0.
     *             Elle sera supprimée dans la version 3.0.0.
     *             Utilisez CollectionHydrationService::collect() à la place.
     *
     * @template TCollection of \AndyDefer\DomainStructures\Abstracts\AbstractTypedCollection
     *
     * @param  iterable<mixed>  $sources
     * @param  class-string<TCollection>  $collectionClass
     * @return TCollection
     *
     * @throws \InvalidArgumentException
     */
    public static function collect(iterable $sources, string $collectionClass = TypedCollection::class): AbstractTypedCollection
    {
        @trigger_error(
            sprintf(
                '%s::collect() est dépréciée depuis la version 2.0.0. '.
                    'Cette méthode sera supprimée dans la version 3.0.0. '.
                    'Utilisez CollectionHydrationService::collect() à la place.',
                static::class
            ),
            E_USER_DEPRECATED
        );

        if (! is_subclass_of($collectionClass, AbstractTypedCollection::class)) {
            throw new \InvalidArgumentException(sprintf(
                'Collection class "%s" must extend %s',
                $collectionClass,
                AbstractTypedCollection::class
            ));
        }

        $collection = new $collectionClass(static::class);

        foreach ($sources as $source) {
            $collection->add(static::from($source));
        }

        return $collection;
    }

    /**
     * Obtient une propriété avec valeur par défaut.
     * Si la clé existe (même avec valeur null), retourne la valeur.
     * Sinon, retourne la valeur par défaut.
     */
    public function get(string $name, mixed $default = null): mixed
    {
        $normalizedKey = $this->normalizeKey($name);

        if (array_key_exists($normalizedKey, $this->data)) {
            return $this->convertValue($this->data[$normalizedKey]);
        }

        return $default;
    }

    /**
     * Vérifie si une propriété existe (même si sa valeur est null).
     */
    public function has(string $name): bool
    {
        $normalizedKey = $this->normalizeKey($name);

        return array_key_exists($normalizedKey, $this->data);
    }

    /**
     * Représentation JSON.
     */
    public function __toString(): string
    {
        return json_encode($this->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
}
