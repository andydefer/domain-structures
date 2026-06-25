<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Utils;

use AndyDefer\DomainStructures\Interfaces\Transformable;
use AndyDefer\DomainStructures\Normalizers\NormalizerChain;

/**
 * Collection représentant une séquence ordonnée d'éléments.
 *
 * Une liste organise le temps ou l'ordre. Les éléments y vivent dans un ordre précis,
 * comme des pas dans une marche. On ne demande pas "est-ce que cet élément existe ?"
 * en priorité, mais plutôt "quel est le premier, le suivant, le dernier ?".
 *
 * @template T
 *
 * @implements \ArrayAccess<int, T>
 * @implements \IteratorAggregate<int, T>
 */
final class ListCollection implements \ArrayAccess, \Countable, \IteratorAggregate, \JsonSerializable, \Stringable, Transformable
{
    /**
     * @var array<int, T>
     */
    private array $items;

    /**
     * @var array<int, class-string|null>
     */
    private array $itemTypes;

    /**
     * @param  array<int, T>  $items
     */
    public function __construct(array $items = [])
    {
        $this->items = [];
        $this->itemTypes = [];

        foreach ($items as $item) {
            $this->items[] = $item;
            $this->itemTypes[] = $this->detectType($item);
        }
    }

    private function detectType(mixed $item): ?string
    {
        if (is_object($item)) {
            return get_class($item);
        }

        return null;
    }

    private function normalize(mixed $value): mixed
    {
        return NormalizerChain::get()->normalize($value);
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

            $items = [];
            foreach ($vars as $value) {
                $items[] = NormalizerChain::get()->normalize($value);
            }
            $flattened = [];
            foreach ($items as $item) {
                if (is_array($item)) {
                    $flattened = array_merge($flattened, $item);
                } else {
                    $flattened[] = $item;
                }
            }

            return new self($flattened);
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
                    $items[] = $normalized;
                } else {
                    $items[] = $normalized;
                }
            } elseif (is_array($source)) {
                $items[] = new static($source);
            } elseif (is_object($source)) {
                $vars = get_object_vars($source);
                if (empty($vars)) {
                    throw new \InvalidArgumentException(sprintf(
                        'Cannot collect %s. Object has no public properties.',
                        get_class($source)
                    ));
                }
                $items[] = new static($vars);
            } else {
                $items[] = $source;
            }
        }

        return new static($items);
    }

    public function toArray(): array
    {
        // On hydrate les éléments avant de les retourner
        $result = [];
        foreach ($this->items as $index => $item) {
            $result[] = $this->hydrate($item, $this->itemTypes[$index] ?? null);
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

    public function first(): mixed
    {
        $item = $this->items[0] ?? null;
        $type = $this->itemTypes[0] ?? null;

        return $this->hydrate($item, $type);
    }

    public function last(): mixed
    {
        $count = count($this->items);
        if ($count === 0) {
            return null;
        }
        $item = $this->items[$count - 1];
        $type = $this->itemTypes[$count - 1] ?? null;

        return $this->hydrate($item, $type);
    }

    public function get(int $index): mixed
    {
        $item = $this->items[$index] ?? null;
        $type = $this->itemTypes[$index] ?? null;

        return $this->hydrate($item, $type);
    }

    public function indexOf(mixed $item): ?int
    {
        // On cherche dans les items bruts
        $index = array_search($item, $this->items, true);

        return $index !== false ? $index : null;
    }

    public function contains(mixed $item): bool
    {
        return in_array($item, $this->items, true);
    }

    // ==================== ADD / REMOVE ====================

    public function add(mixed $item): self
    {
        $new = clone $this;
        $new->items[] = $item;
        $new->itemTypes[] = $this->detectType($item);

        return $new;
    }

    public function prepend(mixed $item): self
    {
        $new = clone $this;
        array_unshift($new->items, $item);
        array_unshift($new->itemTypes, $this->detectType($item));

        return $new;
    }

    public function insert(int $index, mixed $item): self
    {
        if ($index < 0 || $index > count($this->items)) {
            throw new \InvalidArgumentException(sprintf(
                'Index %d is out of range (0-%d)',
                $index,
                count($this->items)
            ));
        }

        $new = clone $this;
        array_splice($new->items, $index, 0, [$item]);
        array_splice($new->itemTypes, $index, 0, [$this->detectType($item)]);

        return $new;
    }

    public function removeAt(int $index): self
    {
        if ($index < 0 || $index >= count($this->items)) {
            throw new \InvalidArgumentException(sprintf(
                'Index %d is out of range (0-%d)',
                $index,
                count($this->items) - 1
            ));
        }

        $new = clone $this;
        array_splice($new->items, $index, 1);
        array_splice($new->itemTypes, $index, 1);

        return $new;
    }

    public function remove(mixed $item): self
    {
        $index = $this->indexOf($item);
        if ($index === null) {
            return $this;
        }

        return $this->removeAt($index);
    }

    public function replace(int $index, mixed $item): self
    {
        if ($index < 0 || $index >= count($this->items)) {
            throw new \InvalidArgumentException(sprintf(
                'Index %d is out of range (0-%d)',
                $index,
                count($this->items) - 1
            ));
        }

        $new = clone $this;
        $new->items[$index] = $item;
        $new->itemTypes[$index] = $this->detectType($item);

        return $new;
    }

    // ==================== TRANSFORMATIONS ====================

    public function filter(callable $callback): self
    {
        $filtered = [];
        $types = [];
        foreach ($this->items as $index => $item) {
            $hydrated = $this->hydrate($item, $this->itemTypes[$index] ?? null);
            if ($callback($hydrated, $index)) {
                $filtered[] = $item;
                $types[] = $this->itemTypes[$index];
            }
        }
        $new = clone $this;
        $new->items = array_values($filtered);
        $new->itemTypes = array_values($types);

        return $new;
    }

    public function map(callable $callback): self
    {
        $mapped = [];
        $types = [];
        foreach ($this->items as $index => $item) {
            $hydrated = $this->hydrate($item, $this->itemTypes[$index] ?? null);
            $result = $callback($hydrated, $index);
            $mapped[] = $result;
            $types[] = $this->detectType($result);
        }
        $new = clone $this;
        $new->items = array_values($mapped);
        $new->itemTypes = array_values($types);

        return $new;
    }

    public function reduce(callable $callback, mixed $initial = null): mixed
    {
        $carry = $initial;
        foreach ($this->items as $index => $item) {
            $hydrated = $this->hydrate($item, $this->itemTypes[$index] ?? null);
            $carry = $callback($carry, $hydrated, $index);
        }

        return $carry;
    }

    public function reverse(): self
    {
        $new = clone $this;
        $new->items = array_reverse($this->items);
        $new->itemTypes = array_reverse($this->itemTypes);

        return $new;
    }

    public function sort(?callable $callback = null): self
    {
        $items = $this->items;
        $types = $this->itemTypes;

        if ($callback === null) {
            sort($items);
        } else {
            usort($items, function ($a, $b) use ($callback) {
                $indexA = array_search($a, $this->items, true);
                $indexB = array_search($b, $this->items, true);
                $hydratedA = $this->hydrate($a, $this->itemTypes[$indexA] ?? null);
                $hydratedB = $this->hydrate($b, $this->itemTypes[$indexB] ?? null);

                return $callback($hydratedA, $hydratedB);
            });
        }

        $new = clone $this;
        $new->items = $items;
        $new->itemTypes = $types;

        return $new;
    }

    // ==================== SLICE / TAKE / SKIP ====================

    public function slice(int $start, ?int $length = null): self
    {
        $new = clone $this;
        $new->items = array_slice($this->items, $start, $length);
        $new->itemTypes = array_slice($this->itemTypes, $start, $length);

        return $new;
    }

    public function take(int $n): self
    {
        return $this->slice(0, $n);
    }

    public function skip(int $n): self
    {
        return $this->slice($n);
    }

    // ==================== MERGE ====================

    public function merge(self $other): self
    {
        $new = clone $this;
        $new->items = array_merge($this->items, $other->items);
        $new->itemTypes = array_merge($this->itemTypes, $other->itemTypes);

        return $new;
    }

    public function mergeArray(array $items): self
    {
        $new = clone $this;
        foreach ($items as $item) {
            $new->items[] = $item;
            $new->itemTypes[] = $this->detectType($item);
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
        // On normalise les items pour le JSON
        $normalized = [];
        foreach ($this->items as $item) {
            $normalized[] = $this->normalize($item);
        }

        return json_encode($normalized, JSON_THROW_ON_ERROR);
    }

    public function jsonSerialize(): mixed
    {
        $normalized = [];
        foreach ($this->items as $item) {
            $normalized[] = $this->normalize($item);
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
        foreach ($this->items as $index => $item) {
            $hydrated[] = $this->hydrate($item, $this->itemTypes[$index] ?? null);
        }

        return new \ArrayIterator($hydrated);
    }

    // ==================== ARRAY ACCESS ====================

    public function offsetExists(mixed $offset): bool
    {
        return is_int($offset) && array_key_exists($offset, $this->items);
    }

    public function offsetGet(mixed $offset): mixed
    {
        if (! is_int($offset)) {
            return null;
        }
        $item = $this->items[$offset] ?? null;
        $type = $this->itemTypes[$offset] ?? null;

        return $this->hydrate($item, $type);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new \RuntimeException(static::class.' is immutable. Use add() or insert() to create a new instance.');
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new \RuntimeException(static::class.' is immutable. Use removeAt() to create a new instance.');
    }
}
