<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Normalizers;

use AndyDefer\DomainStructures\Normalizers\Core\NormalizerInterface;

/**
 * Normalizer for stdClass objects.
 *
 * Converts stdClass objects to associative arrays.
 *
 * @example
 * $obj = new \stdClass();
 * $obj->name = 'John';
 * $obj->age = 30;
 * $normalizer = new StdClassNormalizer();
 * $result = $normalizer->normalize($obj); // ['name' => 'John', 'age' => 30]
 */
final class StdClassNormalizer implements NormalizerInterface
{
    private ?NormalizerInterface $next = null;

    private ?NormalizerInterface $recursiveNormalizer = null;

    public function supports(mixed $value): bool
    {
        return is_object($value) && get_class($value) === 'stdClass';
    }

    public function normalize(mixed $value): array
    {
        if (! is_object($value) || get_class($value) !== 'stdClass') {
            throw new \InvalidArgumentException('Expected stdClass instance');
        }

        $array = (array) $value;
        $result = [];

        foreach ($array as $key => $val) {
            if ($this->recursiveNormalizer !== null && $this->recursiveNormalizer->supports($val)) {
                $result[$key] = $this->recursiveNormalizer->normalize($val);
            } else {
                $result[$key] = $val;
            }
        }

        return $result;
    }

    public function setNext(?NormalizerInterface $next): void
    {
        $this->next = $next;
    }

    /**
     * Sets the recursive normalizer for nested values.
     */
    public function setRecursiveNormalizer(NormalizerInterface $normalizer): void
    {
        $this->recursiveNormalizer = $normalizer;
    }
}
