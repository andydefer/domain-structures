<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Normalizers;

use AndyDefer\DomainStructures\Enums\NormalizeMode;
use InvalidArgumentException;

abstract class AbstractNormalizer implements NormalizerInterface
{
    protected ?NormalizerInterface $next = null;

    public function setNext(?NormalizerInterface $next): void
    {
        $this->next = $next;
    }

    protected function next(mixed $value, NormalizeMode $mode = NormalizeMode::ARRAY, bool $includeNulls = true): mixed
    {
        if ($this->next === null) {
            throw new InvalidArgumentException(sprintf('No normalizer found for type %s', gettype($value)));
        }

        return $this->next->normalize($value, $mode, $includeNulls);
    }
}
