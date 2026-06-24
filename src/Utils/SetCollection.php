<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Utils;

use AndyDefer\DomainStructures\Interfaces\Transformable;
use AndyDefer\DomainStructures\Normalizers\NormalizerChain;

/**
 * Collection représentant un ensemble d'éléments uniques.
 *
 * Un set représente un ensemble. Ici, l'ordre n'est plus la question centrale.
 * Ce qui compte, c'est l'existence. L'important n'est pas "où est l'élément",
 * mais "est-il présent ou absent ?".
 *
 * @template T
 *
 * @implements \ArrayAccess<int, T>
 * @implements \IteratorAggregate<int, T>
 */
final class SetCollection implements \ArrayAccess, \Countable, \IteratorAggregate, \JsonSerializable, \Stringable, Transformable
{
    /**
     * @var array<string|int, T>
     */
    private array $items = [];

    /**
     * @param  array<int, T>  $items
     */
    public function __construct(array $items = [])
    {
        foreach ($items as $item) {
            $normalized = $this->normalize($item);
            $this->items[$this->getKey($normalized)] = $normalized;
        }
    }

    private function normalize(mixed $value): mixed
    {
        return NormalizerChain::get()->normalize($value);
    }

    /**
     * Génère une clé unique pour un élément.
     *
     * Les floats sont convertis en strings pour éviter les dépréciations.
     * Les tableaux sont sérialisés pour éviter les warnings "Array to string conversion".
     */
    private function getKey(mixed $item): string|int
    {
        // Floats → string pour éviter la conversion implicite dépréciée
        if (is_float($item)) {
            return (string) $item;
        }

        // Tableaux → hash unique
        if (is_array($item)) {
            return 'array_'.md5(serialize($item));
        }

        // Scalaires et null → string
        if (is_scalar($item) || $item === null) {
            return (string) $item;
        }

        // Objets → hash d'objet
        if (is_object($item)) {
            return spl_object_hash($item);
        }

        // Fallback (ressource, etc.)
        return (string) $item;
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
        return array_values($this->items);
    }

    // ==================== BASIC METHODS ====================

    public function add(mixed $item): self
    {
        $normalized = $this->normalize($item);
        $key = $this->getKey($normalized);

        if (array_key_exists($key, $this->items)) {
            return $this;
        }

        $new = clone $this;
        $new->items[$key] = $normalized;

        return $new;
    }

    public function addAll(array $items): self
    {
        $new = $this;
        foreach ($items as $item) {
            $new = $new->add($item);
        }

        return $new;
    }

    public function contains(mixed $item): bool
    {
        $normalized = $this->normalize($item);

        return array_key_exists($this->getKey($normalized), $this->items);
    }

    public function remove(mixed $item): self
    {
        $normalized = $this->normalize($item);
        $key = $this->getKey($normalized);

        if (! array_key_exists($key, $this->items)) {
            return $this;
        }

        $new = clone $this;
        unset($new->items[$key]);

        return $new;
    }

    // ==================== TRANSFORMATIONS ====================

    public function filter(callable $callback): self
    {
        return new static(array_values(array_filter($this->items, $callback)));
    }

    public function map(callable $callback): self
    {
        $mapped = array_map($callback, $this->items);

        return new static(array_values($mapped));
    }

    public function reduce(callable $callback, mixed $initial = null): mixed
    {
        return array_reduce($this->items, $callback, $initial);
    }

    // ==================== SET OPERATIONS ====================

    public function union(self $other): self
    {
        $new = clone $this;
        foreach ($other->items as $key => $value) {
            if (! array_key_exists($key, $new->items)) {
                $new->items[$key] = $value;
            }
        }

        return $new;
    }

    public function intersect(self $other): self
    {
        $result = [];
        foreach ($this->items as $key => $value) {
            if (array_key_exists($key, $other->items)) {
                $result[] = $value;
            }
        }

        return new static($result);
    }

    public function diff(self $other): self
    {
        $result = [];
        foreach ($this->items as $key => $value) {
            if (! array_key_exists($key, $other->items)) {
                $result[] = $value;
            }
        }

        return new static($result);
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
        return new \ArrayIterator($this->toArray());
    }

    // ==================== ARRAY ACCESS ====================

    public function offsetExists(mixed $offset): bool
    {
        if (! is_int($offset)) {
            return false;
        }
        $values = array_values($this->items);

        return array_key_exists($offset, $values);
    }

    public function offsetGet(mixed $offset): mixed
    {
        if (! is_int($offset)) {
            return null;
        }
        $values = array_values($this->items);

        return $values[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new \RuntimeException(static::class.' is immutable. Use add() to create a new instance.');
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new \RuntimeException(static::class.' is immutable. Use remove() to create a new instance.');
    }
}
