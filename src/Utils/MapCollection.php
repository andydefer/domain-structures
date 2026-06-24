<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Utils;

use AndyDefer\DomainStructures\Interfaces\Transformable;
use AndyDefer\DomainStructures\Normalizers\NormalizerChain;

/**
 * Collection représentant une relation clé → valeur.
 *
 * Une map représente une relation. Chaque élément est une réponse à une clé.
 * Ce n'est plus une suite ni un groupe, mais une correspondance.
 * Une clé ouvre une valeur. C'est une structure de sens :
 * "si tu connais ceci, alors tu obtiens cela".
 *
 * @template TKey
 * @template TValue
 *
 * @implements \ArrayAccess<TKey, TValue>
 * @implements \IteratorAggregate<TKey, TValue>
 */
final class MapCollection implements \ArrayAccess, \Countable, \IteratorAggregate, \JsonSerializable, \Stringable, Transformable
{
    /**
     * @var array<TKey, TValue>
     */
    private array $items;

    /**
     * @param  array<TKey, TValue>  $items
     */
    public function __construct(array $items = [])
    {
        $normalized = [];
        foreach ($items as $key => $value) {
            $normalized[$this->normalizeKey($key)] = $this->normalize($value);
        }
        $this->items = $normalized;
    }

    /**
     * Normalise une clé pour éviter les conversions implicites dépréciées.
     *
     * Les floats sont convertis en strings pour préserver la précision.
     *
     * @param  mixed  $key  La clé à normaliser
     * @return mixed La clé normalisée
     */
    private function normalizeKey(mixed $key): mixed
    {
        if (is_float($key)) {
            return (string) $key;
        }

        return $key;
    }

    private function normalize(mixed $value): mixed
    {
        return NormalizerChain::get()->normalize($value);
    }

    // ==================== TRANSFORMABLE ====================

    public static function from(mixed $source): static
    {
        if ($source instanceof self) {
            return $source;
        }

        if (is_array($source)) {
            return new self($source);
        }

        if (is_object($source)) {
            if ($source instanceof Transformable) {
                $normalized = NormalizerChain::get()->normalize($source);
                if (is_array($normalized)) {
                    return new self($normalized);
                }

                return new self([$normalized]);
            }

            $vars = get_object_vars($source);
            if (empty($vars)) {
                throw new \InvalidArgumentException(sprintf(
                    'Cannot create %s from %s. Object has no public properties.',
                    self::class,
                    get_class($source)
                ));
            }

            return new self($vars);
        }

        if (is_scalar($source) || $source instanceof \UnitEnum) {
            return new self([$source]);
        }

        throw new \InvalidArgumentException(sprintf(
            'Cannot create %s from %s. Expected array, Transformable object, scalar, enum, or iterable.',
            self::class,
            gettype($source)
        ));
    }

    public static function fromJson(string $json): static
    {
        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException(sprintf(
                'Invalid JSON: %s',
                json_last_error_msg()
            ));
        }

        if (! is_array($data)) {
            throw new \InvalidArgumentException('JSON must decode to an array');
        }

        return new static($data);
    }

    public static function collect(iterable $sources, string $collectionClass = Sequential::class)
    {
        $items = [];
        foreach ($sources as $source) {
            if ($source instanceof static) {
                $items = array_merge($items, $source->toArray());
            } elseif ($source instanceof Transformable) {
                $normalized = NormalizerChain::get()->normalize($source);
                if (is_array($normalized)) {
                    $items = array_merge($items, $normalized);
                } else {
                    $items[] = $normalized;
                }
            } elseif (is_array($source)) {
                $items = array_merge($items, $source);
            } elseif (is_object($source)) {
                $vars = get_object_vars($source);
                if (empty($vars)) {
                    throw new \InvalidArgumentException(sprintf(
                        'Cannot collect %s. Object has no public properties.',
                        get_class($source)
                    ));
                }
                $items = array_merge($items, $vars);
            } else {
                $items[] = $source;
            }
        }

        return new static($items);
    }

    public function toArray(): array
    {
        return $this->items;
    }

    // ==================== BASIC METHODS ====================

    public function put(mixed $key, mixed $value): self
    {
        $new = clone $this;
        $new->items[$this->normalizeKey($key)] = $this->normalize($value);

        return $new;
    }

    public function putAll(array $items): self
    {
        $new = clone $this;
        foreach ($items as $key => $value) {
            $new->items[$this->normalizeKey($key)] = $this->normalize($value);
        }

        return $new;
    }

    public function get(mixed $key): mixed
    {
        return $this->items[$this->normalizeKey($key)] ?? null;
    }

    public function hasKey(mixed $key): bool
    {
        return array_key_exists($this->normalizeKey($key), $this->items);
    }

    public function hasValue(mixed $value): bool
    {
        return in_array($value, $this->items, true);
    }

    public function remove(mixed $key): self
    {
        $normalizedKey = $this->normalizeKey($key);

        if (! $this->hasKey($normalizedKey)) {
            return $this;
        }

        $new = clone $this;
        unset($new->items[$normalizedKey]);

        return $new;
    }

    // ==================== KEYS / VALUES ====================

    public function keys(): ListCollection
    {
        return new ListCollection(array_keys($this->items));
    }

    public function values(): ListCollection
    {
        return new ListCollection(array_values($this->items));
    }

    // ==================== TRANSFORMATIONS ====================

    public function filter(callable $callback): self
    {
        $filtered = [];
        foreach ($this->items as $key => $value) {
            if ($callback($value, $key)) {
                $filtered[$key] = $value;
            }
        }

        return new static($filtered);
    }

    public function map(callable $callback): self
    {
        $mapped = [];
        foreach ($this->items as $key => $value) {
            $mapped[$key] = $callback($value, $key);
        }

        return new static($mapped);
    }

    public function reduce(callable $callback, mixed $initial = null): mixed
    {
        $result = $initial;
        foreach ($this->items as $key => $value) {
            $result = $callback($result, $value, $key);
        }

        return $result;
    }

    // ==================== MERGE ====================

    public function merge(self $other): self
    {
        return new static(array_merge($this->items, $other->toArray()));
    }

    public function mergeArray(array $items): self
    {
        return new static(array_merge($this->items, $items));
    }

    // ==================== UTILITY ====================

    public function isEmpty(): bool
    {
        return empty($this->items);
    }

    public function isNotEmpty(): bool
    {
        return ! $this->isEmpty();
    }

    public function count(): int
    {
        return count($this->items);
    }

    // ==================== JSON ====================

    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR);
    }

    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }

    public function __toString(): string
    {
        return $this->toJson();
    }

    // ==================== ITERATOR ====================

    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->items);
    }

    // ==================== ARRAY ACCESS ====================

    public function offsetExists(mixed $offset): bool
    {
        return $this->hasKey($offset);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->get($offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new \RuntimeException(static::class.' is immutable. Use put() to create a new instance.');
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new \RuntimeException(static::class.' is immutable. Use remove() to create a new instance.');
    }
}
