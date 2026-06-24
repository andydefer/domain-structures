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
     * @param  array<int, T>  $items
     */
    public function __construct(array $items = [])
    {
        $normalized = [];
        foreach ($items as $item) {
            $normalized[] = $this->normalize($item);
        }
        $this->items = array_values($normalized);
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

                // ✅ Garder comme un seul élément
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
        return $this->items;
    }

    // ==================== BASIC METHODS ====================

    public function first(): mixed
    {
        return $this->items[0] ?? null;
    }

    public function last(): mixed
    {
        $count = count($this->items);

        return $count > 0 ? $this->items[$count - 1] : null;
    }

    public function get(int $index): mixed
    {
        return $this->items[$index] ?? null;
    }

    public function indexOf(mixed $item): ?int
    {
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
        $new->items[] = $this->normalize($item);

        return $new;
    }

    public function prepend(mixed $item): self
    {
        $new = clone $this;
        array_unshift($new->items, $this->normalize($item));

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
        array_splice($new->items, $index, 0, [$this->normalize($item)]);

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
        $new->items[$index] = $this->normalize($item);

        return $new;
    }

    // ==================== TRANSFORMATIONS ====================

    public function filter(callable $callback): self
    {
        return new static(array_values(array_filter($this->items, $callback)));
    }

    public function map(callable $callback): self
    {
        return new static(array_values(array_map($callback, $this->items)));
    }

    public function reduce(callable $callback, mixed $initial = null): mixed
    {
        return array_reduce($this->items, $callback, $initial);
    }

    public function reverse(): self
    {
        return new static(array_reverse($this->items));
    }

    public function sort(?callable $callback = null): self
    {
        $items = $this->items;
        if ($callback === null) {
            sort($items);
        } else {
            usort($items, $callback);
        }

        return new static($items);
    }

    // ==================== SLICE / TAKE / SKIP ====================

    public function slice(int $start, ?int $length = null): self
    {
        return new static(array_slice($this->items, $start, $length));
    }

    public function take(int $n): self
    {
        return new static(array_slice($this->items, 0, $n));
    }

    public function skip(int $n): self
    {
        return new static(array_slice($this->items, $n));
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
        return is_int($offset) && array_key_exists($offset, $this->items);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return is_int($offset) ? ($this->items[$offset] ?? null) : null;
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
