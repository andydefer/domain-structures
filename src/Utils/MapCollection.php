<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Collections\Utility;

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
 */
final class MapCollection
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
        $this->items = $items;
    }

    public function put(mixed $key, mixed $value): self
    {
        $new = clone $this;
        $new->items[$key] = $value;

        return $new;
    }

    public function putAll(array $items): self
    {
        $new = clone $this;
        foreach ($items as $key => $value) {
            $new->items[$key] = $value;
        }

        return $new;
    }

    public function get(mixed $key): mixed
    {
        return $this->items[$key] ?? null;
    }

    public function hasKey(mixed $key): bool
    {
        return array_key_exists($key, $this->items);
    }

    public function hasValue(mixed $value): bool
    {
        return in_array($value, $this->items, true);
    }

    public function remove(mixed $key): self
    {
        if (! $this->hasKey($key)) {
            return $this;
        }

        $new = clone $this;
        unset($new->items[$key]);

        return $new;
    }

    public function keys(): ListCollection
    {
        return new ListCollection(array_keys($this->items));
    }

    public function values(): ListCollection
    {
        return new ListCollection(array_values($this->items));
    }

    public function filter(callable $callback): self
    {
        $filtered = [];
        foreach ($this->items as $key => $value) {
            if ($callback($value, $key)) {
                $filtered[$key] = $value;
            }
        }

        return new self($filtered);
    }

    public function map(callable $callback): self
    {
        $mapped = [];
        foreach ($this->items as $key => $value) {
            $mapped[$key] = $callback($value, $key);
        }

        return new self($mapped);
    }

    public function reduce(callable $callback, mixed $initial = null): mixed
    {
        $result = $initial;
        foreach ($this->items as $key => $value) {
            $result = $callback($result, $value, $key);
        }

        return $result;
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
