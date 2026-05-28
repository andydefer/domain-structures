<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Normalizers\Core;

use InvalidArgumentException;

abstract class AbstractNormalizer implements NormalizerInterface
{
    protected ?NormalizerInterface $next = null;

    protected ?NormalizerInterface $recursiveNormalizer = null;

    public function setNext(?NormalizerInterface $next): void
    {
        $this->next = $next;
    }

    public function setRecursiveNormalizer(?NormalizerInterface $normalizer): void
    {
        $this->recursiveNormalizer = $normalizer;
    }

    /**
     * Passes the value to the next normalizer in the chain.
     *
     * @param  mixed  $value  The value to normalize
     * @return mixed The normalized value
     *
     * @throws InvalidArgumentException If no normalizer is available
     */
    protected function next(mixed $value): mixed
    {
        // Si on a un normaliseur récursif, l'utiliser pour les valeurs imbriquées
        if ($this->recursiveNormalizer !== null) {
            return $this->recursiveNormalizer->normalize($value);
        }

        // Fallback sur la chaîne classique
        if ($this->next === null) {
            throw new InvalidArgumentException(sprintf(
                'No normalizer found for type %s',
                is_object($value) ? $value::class : gettype($value)
            ));
        }

        return $this->next->normalize($value);
    }
}
