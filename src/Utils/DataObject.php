<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Utils;


use AndyDefer\DomainStructures\Interfaces\Transformable;

/**
 * DataObject IMMUTABLE - Représente une source de données flexible.
 * 
 * Une fois construit, on ne peut pas le modifier.
 * Pour "modifier", on crée une nouvelle instance avec with(), merge() ou without().
 * Supporte l'accès par propriété (->) et par tableau ([]).
 * Supporte camelCase et snake_case.
 * 
 * @template T
 */
class DataObject implements Transformable, \ArrayAccess
{
    /**
     * @var array<string|int, mixed>
     */
    protected array $data = [];

    /**
     * @param array<string|int, mixed> $data
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
     * Crée une nouvelle instance avec une propriété modifiée.
     * 
     * @param string $key
     * @param mixed $value
     * @return static
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
     * @param array<string|int, mixed> $data
     * @return static
     */
    public function merge(array $data): static
    {
        return new static([...$this->data, ...$data]);
    }

    /**
     * Crée une nouvelle instance sans certaines clés.
     * 
     * @param string ...$keys
     * @return static
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

    /**
     * @param string $name
     * @return mixed
     */
    public function __get(string $name): mixed
    {
        $normalizedKey = $this->normalizeKey($name);
        return $this->convertValue($this->data[$normalizedKey] ?? null);
    }

    /**
     * Vérifie si une propriété existe (même si sa valeur est null).
     * 
     * @param string $name
     * @return bool
     */
    public function __isset(string $name): bool
    {
        $normalizedKey = $this->normalizeKey($name);
        return array_key_exists($normalizedKey, $this->data);
    }

    /**
     * ❌ INTERDIT - DataObject est immutable !
     * 
     * @param string $name
     * @param mixed $value
     * @throws \RuntimeException
     */
    public function __set(string $name, mixed $value): void
    {
        throw new \RuntimeException('DataObject is immutable. Use with() or merge() to create a new instance.');
    }

    // ========== ARRAYACCESS (READ ONLY) ==========

    /**
     * @param mixed $offset
     * @return bool
     */
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

    /**
     * @param mixed $offset
     * @return mixed
     */
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
     * ❌ INTERDIT - DataObject est immutable !
     * 
     * @param mixed $offset
     * @param mixed $value
     * @throws \RuntimeException
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new \RuntimeException('DataObject is immutable. Use with() or merge() to create a new instance.');
    }

    /**
     * ❌ INTERDIT - DataObject est immutable !
     * 
     * @param mixed $offset
     * @throws \RuntimeException
     */
    public function offsetUnset(mixed $offset): void
    {
        throw new \RuntimeException('DataObject is immutable. Use without() to create a new instance.');
    }

    // ========== MÉTHODES PROTECTED ==========

    /**
     * Convertit récursivement les valeurs.
     * 
     * @param mixed $value
     * @return mixed
     */
    protected function convertValue(mixed $value): mixed
    {
        if ($value instanceof self) {
            return $value;
        }

        if (is_array($value)) {
            if ($this->isAssociativeArray($value)) {
                return new static($value);
            }
            return array_map(fn($item) => $this->convertValue($item), $value);
        }

        return $value;
    }

    /**
     * Vérifie si un tableau est associatif.
     * 
     * @param array<mixed> $array
     * @return bool
     */
    protected function isAssociativeArray(array $array): bool
    {
        if (empty($array)) {
            return false;
        }
        return array_keys($array) !== range(0, count($array) - 1);
    }

    /**
     * Normalise une clé string en camelCase.
     * 
     * @param string $key
     * @return string
     */
    protected function normalizeKey(string $key): string
    {
        return $this->snakeToCamel($key);
    }

    /**
     * Convertit snake_case en camelCase.
     * 
     * @param string $string
     * @return string
     */
    protected function snakeToCamel(string $string): string
    {
        return lcfirst(str_replace('_', '', ucwords($string, '_')));
    }

    /**
     * Convertit camelCase en snake_case.
     * 
     * @param string $string
     * @return string
     */
    protected function camelToSnake(string $string): string
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $string));
    }

    // ========== IMPLÉMENTATION DE TRANSFORMABLE ==========

    /**
     * Crée une instance à partir d'une source.
     * 
     * @param mixed $source
     * @return static
     * @throws \InvalidArgumentException
     */
    public static function from(mixed $source): static
    {
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
            'Cannot create DataObject from %s. Expected array or object.',
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
            if ($value instanceof self) {
                $result[$key] = $value->toArray();
            } elseif (is_array($value)) {
                $result[$key] = array_map(function ($item) {
                    return $item instanceof self ? $item->toArray() : $item;
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
     * @param string $json
     * @return static
     */
    public static function fromJson(string $json): static
    {
        $data = json_decode($json, true);
        return new static($data ?? []);
    }

    /**
     * Obtient une propriété avec valeur par défaut.
     * 
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function get(string $name, mixed $default = null): mixed
    {
        $value = $this->__get($name);
        return $value !== null ? $value : $default;
    }

    /**
     * Vérifie si une propriété existe (même si sa valeur est null).
     * 
     * @param string $name
     * @return bool
     */
    public function has(string $name): bool
    {
        $normalizedKey = $this->normalizeKey($name);
        return array_key_exists($normalizedKey, $this->data);
    }

    /**
     * Représentation JSON.
     * 
     * @return string
     */
    public function __toString(): string
    {
        return json_encode($this->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
}
