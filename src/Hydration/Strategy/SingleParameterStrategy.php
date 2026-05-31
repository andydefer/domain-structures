<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Hydration\Strategy;

use AndyDefer\DomainStructures\Abstracts\AbstractTypedCollection;
use AndyDefer\DomainStructures\Enums\PhpType;
use AndyDefer\DomainStructures\Hydration\Converter\TypeConverterInterface;
use AndyDefer\DomainStructures\Interfaces\Transformable;
use AndyDefer\DomainStructures\Normalizers\NormalizerChain;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionUnionType;

final class SingleParameterStrategy implements HydrationStrategyInterface
{
    /** @param array<TypeConverterInterface> $converters */
    public function __construct(
        private array $converters
    ) {}

    public function supports(string $className, mixed $source): bool
    {
        $reflection = new ReflectionClass($className);
        $constructor = $reflection->getConstructor();

        return $constructor && $constructor->getNumberOfParameters() === 1;
    }

    public function hydrate(string $className, mixed $source): object
    {
        $reflection = new ReflectionClass($className);
        $param = $reflection->getConstructor()->getParameters()[0];
        $paramType = $param->getType();

        if ($this->isSingleValueArray($source)) {
            $source = reset($source);
        }

        if ($paramType instanceof ReflectionUnionType) {
            return $this->handleUnionType($className, $source, $paramType, $param);
        }

        return $this->handleNamedType($className, $source, $paramType, $param);
    }

    private function isSingleValueArray(mixed $source): bool
    {
        if (!is_array($source)) {
            return false;
        }

        if (count($source) !== 1) {
            return false;
        }

        $keys = array_keys($source);
        return !is_int($keys[0]);
    }

    private function handleUnionType(string $className, mixed $source, ReflectionUnionType $unionType, $param): object
    {
        foreach ($unionType->getTypes() as $type) {
            if ($type instanceof ReflectionNamedType) {
                try {
                    $converted = $this->convertValue($source, $type, $param->getName());
                    return new $className($converted);
                } catch (InvalidArgumentException) {
                    continue;
                }
            }
        }

        throw new InvalidArgumentException(
            sprintf('Cannot convert value to any union type for parameter $%s', $param->getName())
        );
    }

    private function handleNamedType(string $className, mixed $source, ReflectionNamedType $type, $param): object
    {
        $converted = $this->convertValue($source, $type, $param->getName());
        return new $className($converted);
    }

    private function convertValue(mixed $source, ReflectionNamedType $type, string $paramName): mixed
    {
        $typeName = $type->getName();

        // Cas : la source est un tableau
        if (is_array($source)) {
            foreach ($this->converters as $converter) {
                if ($converter->supports($typeName)) {
                    return $converter->convert($source, $typeName, $paramName);
                }
            }

            if (empty($source) && is_subclass_of($typeName, AbstractTypedCollection::class)) {
                return new $typeName();
            }

            return $source;
        }

        if (is_object($source)) {
            $normalized = NormalizerChain::get()->normalize($source);
            $phpType = PhpType::fromValue($normalized);

            if ($phpType->isScalar() && $typeName === $phpType->getNormalizedName()) {
                return $normalized;
            }

            foreach ($this->converters as $converter) {
                if ($converter->supports($typeName)) {
                    return $converter->convert($normalized, $typeName, $paramName);
                }
            }
        }

        if (is_scalar($source)) {
            $phpType = PhpType::fromValue($source);

            if ($phpType->isScalar() && $typeName === $phpType->getNormalizedName()) {
                return $source;
            }

            foreach ($this->converters as $converter) {
                if ($converter->supports($typeName)) {
                    return $converter->convert($source, $typeName, $paramName);
                }
            }
        }

        throw new InvalidArgumentException(
            sprintf('Cannot convert value to %s for parameter $%s', $typeName, $paramName)
        );
    }
}
