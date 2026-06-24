<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Collections\Utility;

/**
 * Collection représentant un ensemble d'éléments uniques.
 *
 * Un set représente un ensemble. Ici, l'ordre n'est plus la question centrale.
 * Ce qui compte, c'est l'existence. L'important n'est pas "où est l'élément",
 * mais "est-il présent ou absent ?".
 *
 * @template T
 */
final class SetCollection
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
            $this->items[$this->getKey($item)] = $item;
        }
    }

    private function getKey(mixed $item): string|int
    {
        if (is_scalar($item) || $item === null) {
            return (string) $item;
        }

        return spl_object_hash($item);
    }

    public function add(mixed $item): self
    {
        $key = $this->getKey($item);
        if (array_key_exists($key, $this->items)) {
            return $this;
        }

        $new = clone $this;
        $new->items[$key] = $item;

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
        return array_key_exists($this->getKey($item), $this->items);
    }

    public function remove(mixed $item): self
    {
        $key = $this->getKey($item);
        if (! array_key_exists($key, $this->items)) {
            return $this;
        }

        $new = clone $this;
        unset($new->items[$key]);

        return $new;
    }

    public function filter(callable $callback): self
    {
        $filtered = array_filter($this->items, $callback);

        return new self(array_values($filtered));
    }

    public function map(callable $callback): self
    {
        $mapped = array_map($callback, $this->items);

        return new self(array_values($mapped));
    }

    public function reduce(callable $callback, mixed $initial = null): mixed
    {
        return array_reduce($this->items, $callback, $initial);
    }

    public function union(self $other): self
    {
        $new = clone $this;
        foreach ($other->toArray() as $item) {
            $key = $this->getKey($item);
            if (! array_key_exists($key, $new->items)) {
                $new->items[$key] = $item;
            }
        }

        return $new;
    }

    public function intersect(self $other): self
    {
        $result = [];
        foreach ($this->items as $item) {
            if ($other->contains($item)) {
                $result[] = $item;
            }
        }

        return new self($result);
    }

    public function diff(self $other): self
    {
        $result = [];
        foreach ($this->items as $item) {
            if (! $other->contains($item)) {
                $result[] = $item;
            }
        }

        return new self($result);
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
        return array_values($this->items);
    }

    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR);
    }

    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->toArray());
    }

    public function __toString(): string
    {
        return $this->toJson();
    }
}
