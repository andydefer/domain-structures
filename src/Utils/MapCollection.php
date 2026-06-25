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
     * @var array<TKey, class-string|null>
     */
    private array $itemTypes;

    /**
     * @param  array<TKey, TValue>  $items
     */
    public function __construct(array $items = [])
    {
        $this->items = [];
        $this->itemTypes = [];

        foreach ($items as $key => $value) {
            $normalizedKey = $this->normalizeKey($key);
            $this->items[$normalizedKey] = $value;
            $this->itemTypes[$normalizedKey] = $this->detectType($value);
        }
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

    private function detectType(mixed $item): ?string
    {
        if (is_object($item)) {
            return get_class($item);
        }

        return null;
    }

    private function hydrate(mixed $item, ?string $type): mixed
    {
        if ($type !== null && class_exists($type) && method_exists($type, 'from')) {
            try {
                return $type::from($item);
            } catch (\Throwable $e) {
                return $item;
            }
        }

        return $item;
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
        $result = [];
        foreach ($this->items as $key => $item) {
            $result[$key] = $this->hydrate($item, $this->itemTypes[$key] ?? null);
        }

        return $result;
    }

    /**
     * Retourne les données brutes (non hydratées)
     */
    public function toRawArray(): array
    {
        return NormalizerChain::get()->normalize($this->items);

    }

    // ==================== BASIC METHODS ====================

    public function put(mixed $key, mixed $value): self
    {
        $normalizedKey = $this->normalizeKey($key);
        $new = clone $this;
        $new->items[$normalizedKey] = $value;
        $new->itemTypes[$normalizedKey] = $this->detectType($value);

        return $new;
    }

    public function putAll(array $items): self
    {
        $new = clone $this;
        foreach ($items as $key => $value) {
            $normalizedKey = $this->normalizeKey($key);
            $new->items[$normalizedKey] = $value;
            $new->itemTypes[$normalizedKey] = $this->detectType($value);
        }

        return $new;
    }

    public function get(mixed $key): mixed
    {
        $normalizedKey = $this->normalizeKey($key);
        $item = $this->items[$normalizedKey] ?? null;
        $type = $this->itemTypes[$normalizedKey] ?? null;

        return $this->hydrate($item, $type);
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
        unset($new->itemTypes[$normalizedKey]);

        return $new;
    }

    // ==================== KEYS / VALUES ====================

    public function keys(): ListCollection
    {
        return new ListCollection(array_keys($this->items));
    }

    public function values(): ListCollection
    {
        $values = [];
        foreach ($this->items as $key => $item) {
            $values[] = $this->hydrate($item, $this->itemTypes[$key] ?? null);
        }

        return new ListCollection($values);
    }

    // ==================== TRANSFORMATIONS ====================

    public function filter(callable $callback): self
    {
        $filtered = [];
        $types = [];
        foreach ($this->items as $key => $item) {
            $hydrated = $this->hydrate($item, $this->itemTypes[$key] ?? null);
            if ($callback($hydrated, $key)) {
                $filtered[$key] = $item;
                $types[$key] = $this->itemTypes[$key];
            }
        }

        $new = clone $this;
        $new->items = $filtered;
        $new->itemTypes = $types;

        return $new;
    }

    public function map(callable $callback): self
    {
        $mapped = [];
        $types = [];
        foreach ($this->items as $key => $item) {
            $hydrated = $this->hydrate($item, $this->itemTypes[$key] ?? null);
            $result = $callback($hydrated, $key);
            $mapped[$key] = $result;
            $types[$key] = $this->detectType($result);
        }

        $new = clone $this;
        $new->items = $mapped;
        $new->itemTypes = $types;

        return $new;
    }

    public function reduce(callable $callback, mixed $initial = null): mixed
    {
        $result = $initial;
        foreach ($this->items as $key => $item) {
            $hydrated = $this->hydrate($item, $this->itemTypes[$key] ?? null);
            $result = $callback($result, $hydrated, $key);
        }

        return $result;
    }

    // ==================== MERGE ====================

    public function merge(self $other): self
    {
        $new = clone $this;
        foreach ($other->items as $key => $value) {
            $new->items[$key] = $value;
            $new->itemTypes[$key] = $other->itemTypes[$key] ?? null;
        }

        return $new;
    }

    public function mergeArray(array $items): self
    {
        $new = clone $this;
        foreach ($items as $key => $value) {
            $normalizedKey = $this->normalizeKey($key);
            $new->items[$normalizedKey] = $value;
            $new->itemTypes[$normalizedKey] = $this->detectType($value);
        }

        return $new;
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
        $normalized = [];
        foreach ($this->items as $key => $item) {
            $normalized[$key] = $this->normalize($item);
        }

        return json_encode($normalized, JSON_THROW_ON_ERROR);
    }

    public function jsonSerialize(): mixed
    {
        $normalized = [];
        foreach ($this->items as $key => $item) {
            $normalized[$key] = $this->normalize($item);
        }

        return $normalized;
    }

    public function __toString(): string
    {
        return $this->toJson();
    }

    // ==================== ITERATOR ====================

    public function getIterator(): \ArrayIterator
    {
        $hydrated = [];
        foreach ($this->items as $key => $item) {
            $hydrated[$key] = $this->hydrate($item, $this->itemTypes[$key] ?? null);
        }

        return new \ArrayIterator($hydrated);
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
