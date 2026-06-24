<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Collections\Utility;

/**
 * Collection représentant une séquence ordonnée d'éléments.
 *
 * Une liste organise le temps ou l'ordre. Les éléments y vivent dans un ordre précis,
 * comme des pas dans une marche. On ne demande pas "est-ce que cet élément existe ?"
 * en priorité, mais plutôt "quel est le premier, le suivant, le dernier ?".
 *
 * @template T
 */
final class ListCollection
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
        $this->items = array_values($items);
    }

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

    public function add(mixed $item): self
    {
        $new = clone $this;
        $new->items[] = $item;

        return $new;
    }

    public function prepend(mixed $item): self
    {
        $new = clone $this;
        array_unshift($new->items, $item);

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
        $new->items[$index] = $item;

        return $new;
    }

    public function filter(callable $callback): self
    {
        return new self(array_values(array_filter($this->items, $callback)));
    }

    public function map(callable $callback): self
    {
        return new self(array_values(array_map($callback, $this->items)));
    }

    public function reduce(callable $callback, mixed $initial = null): mixed
    {
        return array_reduce($this->items, $callback, $initial);
    }

    public function reverse(): self
    {
        return new self(array_reverse($this->items));
    }

    public function sort(?callable $callback = null): self
    {
        $items = $this->items;
        if ($callback === null) {
            sort($items);
        } else {
            usort($items, $callback);
        }

        return new self($items);
    }

    public function slice(int $start, ?int $length = null): self
    {
        return new self(array_slice($this->items, $start, $length));
    }

    public function take(int $n): self
    {
        return new self(array_slice($this->items, 0, $n));
    }

    public function skip(int $n): self
    {
        return new self(array_slice($this->items, $n));
    }

    public function merge(self $other): self
    {
        return new self(array_merge($this->items, $other->toArray()));
    }

    public function mergeArray(array $items): self
    {
        return new self(array_merge($this->items, $items));
    }

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

    public function toArray(): array
    {
        return $this->items;
    }

    public function toJson(): string
    {
        return json_encode($this->items, JSON_THROW_ON_ERROR);
    }

    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->items);
    }

    public function __toString(): string
    {
        return $this->toJson();
    }
}
