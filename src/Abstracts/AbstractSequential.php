<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Abstracts;

use AndyDefer\DomainStructures\Interfaces\Transformable;
use AndyDefer\DomainStructures\Normalizers\NormalizerChain;
use AndyDefer\DomainStructures\Utils\Sequential;

/**
 * AbstractSequential - Classe de base immutable pour les collections séquentielles plates.
 *
 * C'est la version non-associative (indexée) de AbstractDataObject.
 * Les éléments sont ordonnés et accessibles par leur position (index 0, 1, 2, ...).
 *
 * Contrairement à AbstractTypedCollection qui est typée (un seul type d'éléments),
 * AbstractSequential accepte n'importe quel type d'éléments et les "applatit".
 *
 * Une fois construit, on ne peut pas le modifier.
 * Pour "modifier", on crée une nouvelle instance avec add(), insert(), remove(), etc.
 *
 * Implémente Transformable pour l'hydratation et la normalisation.
 *
 * @template T
 */
abstract class AbstractSequential implements \ArrayAccess, \Countable, \IteratorAggregate, \JsonSerializable, Transformable
{
    /**
     * @var array<int, mixed> Les éléments de la séquence
     */
    protected array $items = [];

    /**
     * @param  array<mixed>  $items  Les éléments de la séquence
     */
    public function __construct(array $items = [])
    {
        $normalizedItems = [];
        foreach ($items as $item) {
            $normalizedItems[] = $this->normalize($item);
        }
        $this->items = array_values($normalizedItems);
    }

    /**
     * Normalise une valeur via NormalizerChain.
     *
     * @param  mixed  $value  La valeur à normaliser
     * @return mixed La valeur normalisée
     */
    protected function normalize(mixed $value): mixed
    {
        return NormalizerChain::get()->normalize($value);
    }

    /**
     * Retourne tous les éléments.
     *
     * @return array<int, mixed>
     */
    public function toArray(): array
    {
        return $this->items;
    }

    /**
     * Ajoute un élément à la fin de la séquence.
     *
     * @param  mixed  $item  L'élément à ajouter (sera normalisé)
     * @return static Nouvelle instance avec l'élément ajouté
     */
    public function add(mixed $item): static
    {
        $normalized = $this->normalize($item);
        $newItems = $this->items;
        $newItems[] = $normalized;

        return new static($newItems);
    }

    /**
     * Ajoute un élément au début de la séquence.
     *
     * @param  mixed  $item  L'élément à ajouter (sera normalisé)
     * @return static Nouvelle instance avec l'élément ajouté
     */
    public function prepend(mixed $item): static
    {
        $normalized = $this->normalize($item);
        $newItems = [$normalized];
        $newItems = array_merge($newItems, $this->items);

        return new static($newItems);
    }

    /**
     * Insère un élément à une position spécifique.
     *
     * @param  int  $index  La position (0-based)
     * @param  mixed  $item  L'élément à insérer (sera normalisé)
     * @return static Nouvelle instance avec l'élément inséré
     *
     * @throws \InvalidArgumentException Si l'index est hors limites
     */
    public function insert(int $index, mixed $item): static
    {
        $count = count($this->items);

        if ($index < 0 || $index > $count) {
            throw new \InvalidArgumentException(sprintf(
                'Index %d is out of range (0-%d)',
                $index,
                $count
            ));
        }

        $normalized = $this->normalize($item);
        $newItems = $this->items;
        array_splice($newItems, $index, 0, [$normalized]);

        return new static($newItems);
    }

    /**
     * Retire un élément à une position spécifique.
     *
     * @param  int  $index  La position (0-based)
     * @return static Nouvelle instance sans l'élément
     *
     * @throws \InvalidArgumentException Si l'index est hors limites
     */
    public function remove(int $index): static
    {
        if ($index < 0 || $index >= count($this->items)) {
            throw new \InvalidArgumentException(sprintf(
                'Index %d is out of range (0-%d)',
                $index,
                count($this->items) - 1
            ));
        }

        $newItems = $this->items;
        array_splice($newItems, $index, 1);

        return new static($newItems);
    }

    /**
     * Retire la première occurrence d'un élément.
     *
     * @param  mixed  $item  L'élément à retirer (sera normalisé pour la comparaison)
     * @return static Nouvelle instance sans l'élément
     */
    public function removeElement(mixed $item): static
    {
        $normalized = $this->normalize($item);
        $index = $this->indexOfNormalized($normalized);

        if ($index === null) {
            return $this;
        }

        return $this->remove($index);
    }

    /**
     * Remplace un élément à une position spécifique.
     *
     * @param  int  $index  La position (0-based)
     * @param  mixed  $item  Le nouvel élément (sera normalisé)
     * @return static Nouvelle instance avec l'élément remplacé
     *
     * @throws \InvalidArgumentException Si l'index est hors limites
     */
    public function replace(int $index, mixed $item): static
    {
        if ($index < 0 || $index >= count($this->items)) {
            throw new \InvalidArgumentException(sprintf(
                'Index %d is out of range (0-%d)',
                $index,
                count($this->items) - 1
            ));
        }

        $normalized = $this->normalize($item);
        $newItems = $this->items;
        $newItems[$index] = $normalized;

        return new static($newItems);
    }

    /**
     * Récupère un élément par son index.
     *
     * @param  int  $index  La position (0-based)
     * @return mixed|null L'élément ou null si non trouvé
     */
    public function get(int $index): mixed
    {
        return $this->items[$index] ?? null;
    }

    /**
     * Récupère le premier élément.
     *
     * @return mixed|null Le premier élément ou null si vide
     */
    public function first(): mixed
    {
        return $this->items[0] ?? null;
    }

    /**
     * Récupère le dernier élément.
     *
     * @return mixed|null Le dernier élément ou null si vide
     */
    public function last(): mixed
    {
        $count = count($this->items);

        return $count > 0 ? $this->items[$count - 1] : null;
    }

    /**
     * Trouve l'index d'un élément (normalisé).
     *
     * @param  mixed  $item  L'élément à chercher (sera normalisé)
     * @return int|null L'index ou null si non trouvé
     */
    public function indexOf(mixed $item): ?int
    {
        $normalized = $this->normalize($item);

        return $this->indexOfNormalized($normalized);
    }

    /**
     * Trouve l'index d'un élément déjà normalisé.
     *
     * @param  mixed  $normalized  L'élément normalisé à chercher
     * @return int|null L'index ou null si non trouvé
     */
    private function indexOfNormalized(mixed $normalized): ?int
    {
        $index = array_search($normalized, $this->items, true);

        return $index !== false ? $index : null;
    }

    /**
     * Vérifie si un élément existe (normalisé).
     *
     * @param  mixed  $item  L'élément à vérifier (sera normalisé)
     * @return bool True si présent, false sinon
     */
    public function contains(mixed $item): bool
    {
        $normalized = $this->normalize($item);

        return in_array($normalized, $this->items, true);
    }

    /**
     * Récupère une tranche de la séquence.
     *
     * @param  int  $start  Index de début
     * @param  int|null  $length  Nombre d'éléments (null = jusqu'à la fin)
     * @return static Nouvelle instance avec la tranche
     */
    public function slice(int $start, ?int $length = null): static
    {
        $items = array_slice($this->items, $start, $length);

        return new static($items);
    }

    /**
     * Prend les n premiers éléments.
     *
     * @param  int  $n  Nombre d'éléments à prendre
     * @return static Nouvelle instance avec les n premiers éléments
     */
    public function take(int $n): static
    {
        return new static(array_slice($this->items, 0, $n));
    }

    /**
     * Saute les n premiers éléments.
     *
     * @param  int  $n  Nombre d'éléments à sauter
     * @return static Nouvelle instance sans les n premiers éléments
     */
    public function skip(int $n): static
    {
        return new static(array_slice($this->items, $n));
    }

    public function filter(callable $callback): static
    {
        return new static(array_values(array_filter($this->items, $callback)));
    }

    /**
     * Transforme les éléments.
     *
     * @param  callable  $callback  Fonction de transformation
     * @return static Nouvelle instance avec les éléments transformés
     */
    public function map(callable $callback): static
    {
        return new static(array_values(array_map($callback, $this->items)));
    }

    /**
     * Réduit la séquence à une seule valeur.
     *
     * @param  callable  $callback  Fonction de réduction
     * @param  mixed  $initial  Valeur initiale
     * @return mixed La valeur réduite
     */
    public function reduce(callable $callback, mixed $initial = null): mixed
    {
        return array_reduce($this->items, $callback, $initial);
    }

    /**
     * Inverse l'ordre des éléments.
     *
     * @return static Nouvelle instance avec l'ordre inversé
     */
    public function reverse(): static
    {
        return new static(array_reverse($this->items));
    }

    /**
     * Trie les éléments.
     *
     * @param  callable|null  $callback  Fonction de comparaison
     * @return static Nouvelle instance triée
     */
    public function sort(?callable $callback = null): static
    {
        $newItems = $this->items;

        if ($callback === null) {
            sort($newItems);
        } else {
            usort($newItems, $callback);
        }

        return new static($newItems);
    }

    /**
     * Fusionne avec une autre séquence.
     *
     * @param  AbstractSequential  $other  L'autre séquence
     * @return static Nouvelle instance avec les éléments fusionnés
     */
    public function merge(self $other): static
    {
        return new static(array_merge($this->items, $other->toArray()));
    }

    /**
     * Fusionne avec un tableau.
     *
     * @param  array<mixed>  $items  Les éléments à fusionner
     * @return static Nouvelle instance avec les éléments fusionnés
     */
    public function mergeArray(array $items): static
    {
        return new static(array_merge($this->items, $items));
    }

    /**
     * Vérifie si la séquence est vide.
     *
     * @return bool True si vide, false sinon
     */
    public function isEmpty(): bool
    {
        return empty($this->items);
    }

    /**
     * Vérifie si la séquence n'est pas vide.
     *
     * @return bool True si non vide, false sinon
     */
    public function isNotEmpty(): bool
    {
        return ! $this->isEmpty();
    }

    /**
     * Compte les éléments.
     *
     * @return int Nombre d'éléments
     */
    public function count(): int
    {
        return count($this->items);
    }

    /**
     * Récupère l'itérateur.
     *
     * @return \ArrayIterator<int, mixed>
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->items);
    }

    // ========== ARRAYACCESS (READ ONLY) ==========

    public function offsetExists(mixed $offset): bool
    {
        return is_int($offset) && array_key_exists($offset, $this->items);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return is_int($offset) ? ($this->items[$offset] ?? null) : null;
    }

    /**
     * ❌ INTERDIT - AbstractSequential est immutable !
     *
     * @throws \RuntimeException
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new \RuntimeException(get_class($this).' is immutable. Use add() or insert() to create a new instance.');
    }

    /**
     * ❌ INTERDIT - AbstractSequential est immutable !
     *
     * @throws \RuntimeException
     */
    public function offsetUnset(mixed $offset): void
    {
        throw new \RuntimeException(get_class($this).' is immutable. Use remove() to create a new instance.');
    }

    // ========== TRANSFORMABLE ==========

    public static function from(mixed $source): static
    {
        if ($source instanceof static) {
            return $source;
        }

        if (is_array($source)) {
            return new static($source);
        }

        if (is_object($source)) {
            if ($source instanceof Transformable) {
                $normalized = NormalizerChain::get()->normalize($source);
                if (is_array($normalized)) {
                    return new static($normalized);
                }

                return new static([$normalized]);
            }

            $vars = get_object_vars($source);
            if (empty($vars)) {
                throw new \InvalidArgumentException(sprintf(
                    'Cannot create %s from %s. Object has no public properties.',
                    static::class,
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

            return new static($flattened);
        }

        if (is_scalar($source) || $source instanceof \UnitEnum) {
            return new static([$source]);
        }

        throw new \InvalidArgumentException(sprintf(
            'Cannot create %s from %s. Expected array, Transformable object, scalar, enum, or iterable.',
            static::class,
            gettype($source)
        ));
    }

    /**
     * Crée une instance à partir de JSON.
     *
     * @param  string  $json  JSON string
     * @return static Nouvelle instance
     *
     * @throws \InvalidArgumentException Si le JSON est invalide
     */
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

    /**
     * Collecte des sources et les transforme en une séquence.
     *
     * Cette méthode prend un itérable de sources et les convertit en une séquence
     * où chaque source devient un élément distinct.
     *
     * @param  iterable  $sources  Les sources à collecter
     * @param  class-string<AbstractSequential>  $sequentialClass  La classe de séquence à utiliser
     * @return static La séquence contenant les sources collectées
     *
     * @throws \InvalidArgumentException Si la classe séquentielle est invalide ou si un objet sans propriétés est fourni
     *
     * @example
     * // Collecter des tableaux
     * $collection = Sequential::collect([[1, 2], [3, 4]]);
     * // Résultat : [[1, 2], [3, 4]]
     * @example
     * // Collecter des objets transformables
     * $record = new TestUserRecord(name: 'John');
     * $collection = Sequential::collect([$record]);
     * // Résultat : [['name' => 'John']]
     * @example
     * // Collecter des scalaires
     * $collection = Sequential::collect([1, 2, 3]);
     * // Résultat : [1, 2, 3]
     */
    public static function collect(iterable $sources, string $sequentialClass = Sequential::class)
    {
        if (! is_subclass_of($sequentialClass, AbstractSequential::class)) {
            throw new \InvalidArgumentException(sprintf(
                'Sequential class "%s" must extend %s',
                $sequentialClass,
                AbstractSequential::class
            ));
        }

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

        return new $sequentialClass($items);
    }

    /**
     * Convertit la séquence en chaîne JSON.
     *
     * @return string La représentation JSON de la séquence
     */
    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR);
    }

    /**
     * Représentation JSON.
     *
     * @return string La représentation JSON
     */
    public function __toString(): string
    {
        return $this->toJson();
    }

    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }
}
