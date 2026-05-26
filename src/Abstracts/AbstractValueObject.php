<?php

namespace AndyDefer\DomainStructures\Abstracts;

use AndyDefer\DomainStructures\Enums\NormalizeMode;
use AndyDefer\DomainStructures\Normalizers\NormalizerChain;
use stdClass;
use UnitEnum;

abstract class AbstractValueObject
{
    protected function __construct() {}

    abstract protected static function from(...$values): static;

    abstract public function getValue(): int|string|float|bool|null|UnitEnum|AbstractRecord|AbstractValueObject|AbstractData|AbstractTypedCollection|stdClass;

    public function normalize(NormalizeMode $mode = NormalizeMode::ARRAY): array|string
    {
        $value = $this->getValue();
        $normalized = NormalizerChain::get()->normalize($value, $mode, true);

        return $mode === NormalizeMode::JSON ? json_encode($normalized, JSON_THROW_ON_ERROR) : $normalized;
    }

    public function equals(self $other): bool
    {
        return get_class($this) === get_class($other) && $this->getValue() === $other->getValue();
    }

    public function __toString(): string
    {
        return $this->normalize(NormalizeMode::JSON);
    }
}
