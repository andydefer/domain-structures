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
     * @var array<string|int, class-string|null>
     */
    private array $itemTypes = [];

    /**
     * @param  array<int, T>  $items
     */
    public function __construct(array $items = [])
    {
        foreach ($items as $item) {
            $key = $this->getKey($item);
            $this->items[$key] = $item;
            $this->itemTypes[$key] = $this->detectType($item);
        }
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

    /**
     * Génère une clé unique pour un élément.
     *
     * Pour les ValueObjects, on utilise la valeur normalisée + le type.
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

        // Objets Transformable → on utilise la valeur normalisée + le type
        if (is_object($item) && $item instanceof Transformable) {
            $normalized = $this->normalize($item);
            $type = get_class($item);

            return $type.'_'.(string) $normalized;
        }

        // Autres objets → hash d'objet
        if (is_object($item)) {
            return spl_object_hash($item);
        }

        // Scalaires et null → string
        if (is_scalar($item) || $item === null) {
            return (string) $item;
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
        $result = [];
        foreach ($this->items as $key => $item) {
            $result[] = $this->hydrate($item, $this->itemTypes[$key] ?? null);
        }

        return $result;
    }

    /**
     * Retourne les données brutes normalisées (scalaires)
     * Utilisé pour le débogage et l'export
     */
    public function toRawArray(): array
    {
        $result = [];
        foreach ($this->items as $item) {
            $result[] = $this->normalize($item);
        }

        return $result;
    }

    /**
     * Retourne les données brutes avec préservation des types
     * Utilisé pour les opérations internes
     */
    public function toRawTypedArray(): array
    {
        return array_values($this->items);
    }

    // ==================== BASIC METHODS ====================

    public function add(mixed $item): self
    {
        $key = $this->getKey($item);

        if (array_key_exists($key, $this->items)) {
            return $this;
        }

        $new = clone $this;
        $new->items[$key] = $item;
        $new->itemTypes[$key] = $this->detectType($item);

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
        $key = $this->getKey($item);

        return array_key_exists($key, $this->items);
    }

    public function remove(mixed $item): self
    {
        $key = $this->getKey($item);

        if (! array_key_exists($key, $this->items)) {
            return $this;
        }

        $new = clone $this;
        unset($new->items[$key]);
        unset($new->itemTypes[$key]);

        return $new;
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
            $newKey = $this->getKey($result);
            $mapped[$newKey] = $result;
            $types[$newKey] = $this->detectType($result);
        }

        $new = clone $this;
        $new->items = $mapped;
        $new->itemTypes = $types;

        return $new;
    }

    public function reduce(callable $callback, mixed $initial = null): mixed
    {
        $carry = $initial;
        foreach ($this->items as $key => $item) {
            $hydrated = $this->hydrate($item, $this->itemTypes[$key] ?? null);
            $carry = $callback($carry, $hydrated, $key);
        }

        return $carry;
    }

    // ==================== SET OPERATIONS ====================

    public function union(self $other): self
    {
        $new = clone $this;
        foreach ($other->items as $key => $value) {
            if (! array_key_exists($key, $new->items)) {
                $new->items[$key] = $value;
                $new->itemTypes[$key] = $other->itemTypes[$key] ?? null;
            }
        }

        return $new;
    }

    public function intersect(self $other): self
    {
        $result = [];
        $types = [];
        foreach ($this->items as $key => $value) {
            if (array_key_exists($key, $other->items)) {
                $result[$key] = $value;
                $types[$key] = $this->itemTypes[$key];
            }
        }

        $new = clone $this;
        $new->items = $result;
        $new->itemTypes = $types;

        return $new;
    }

    public function diff(self $other): self
    {
        $result = [];
        $types = [];
        foreach ($this->items as $key => $value) {
            if (! array_key_exists($key, $other->items)) {
                $result[$key] = $value;
                $types[$key] = $this->itemTypes[$key];
            }
        }

        $new = clone $this;
        $new->items = $result;
        $new->itemTypes = $types;

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
        foreach ($this->items as $key => $item) {
            $hydrated[] = $this->hydrate($item, $this->itemTypes[$key] ?? null);
        }

        return new \ArrayIterator($hydrated);
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
        $values = array_keys($this->items);
        if (! isset($values[$offset])) {
            return null;
        }
        $key = $values[$offset];
        $item = $this->items[$key] ?? null;
        $type = $this->itemTypes[$key] ?? null;

        return $this->hydrate($item, $type);
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
