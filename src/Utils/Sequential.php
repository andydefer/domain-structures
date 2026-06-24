<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Utils;

use AndyDefer\DomainStructures\Abstracts\AbstractSequential;

/**
 * CaseInsensitiveSequential - Collection séquentielle insensible à la casse.
 *
 * Les opérations de recherche (contains, indexOf) sont insensibles à la casse
 * sur les valeurs de type string.
 * Les éléments sont indexés par position (0, 1, 2, ...).
 *
 * @template T
 */
class Sequential extends AbstractSequential
{
    /**
     * Normalise une valeur pour la comparaison (insensible à la casse).
     *
     * @param  mixed  $value  La valeur à normaliser
     * @return mixed La valeur normalisée
     */
    private function normalizeValue(mixed $value): mixed
    {
        return is_string($value) ? strtolower($value) : $value;
    }

    /**
     * Vérifie si un élément existe (insensible à la casse pour les strings).
     *
     * @param  mixed  $item  L'élément à vérifier
     * @return bool True si présent, false sinon
     */
    public function contains(mixed $item): bool
    {
        $normalizedItem = $this->normalizeValue($item);

        foreach ($this->items as $value) {
            if ($this->normalizeValue($value) === $normalizedItem) {
                return true;
            }
        }

        return false;
    }

    /**
     * Trouve l'index d'un élément (insensible à la casse pour les strings).
     *
     * @param  mixed  $item  L'élément à chercher
     * @return int|null L'index ou null si non trouvé
     */
    public function indexOf(mixed $item): ?int
    {
        $normalizedItem = $this->normalizeValue($item);

        foreach ($this->items as $index => $value) {
            if ($this->normalizeValue($value) === $normalizedItem) {
                return $index;
            }
        }

        return null;
    }

    /**
     * Retire la première occurrence d'un élément (insensible à la casse pour les strings).
     *
     * @param  mixed  $item  L'élément à retirer
     * @return static Nouvelle instance sans l'élément
     */
    public function removeElement(mixed $item): static
    {
        $index = $this->indexOf($item);

        if ($index === null) {
            return $this;
        }

        return $this->remove($index);
    }
}
