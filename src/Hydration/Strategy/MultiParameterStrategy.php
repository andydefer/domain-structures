<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Hydration\Strategy;

use AndyDefer\DomainStructures\Abstracts\AbstractDataObject;
use AndyDefer\DomainStructures\Hydration\Converter\TypeConverterInterface;
use AndyDefer\DomainStructures\Normalizers\NormalizerChain;
use AndyDefer\DomainStructures\Utils\DataObject;
use AndyDefer\DomainStructures\Utils\StrictDataObject;
use InvalidArgumentException;
use ReflectionClass;

final class MultiParameterStrategy implements HydrationStrategyInterface
{
    /** @param array<TypeConverterInterface> $converters */
    public function __construct(
        private array $converters
    ) {}

    public function supports(string $className, mixed $source): bool
    {
        $reflection = new ReflectionClass($className);
        $constructor = $reflection->getConstructor();

        return $constructor && $constructor->getNumberOfParameters() !== 1;
    }

    public function hydrate(string $className, mixed $source): object
    {
        $reflection = new ReflectionClass($className);
        $constructor = $reflection->getConstructor();

        if (!$constructor) {
            throw new InvalidArgumentException(sprintf('%s must have a constructor', $className));
        }

        $data = $this->normalizeToDataObject($source);
        $parameters = [];

        foreach ($constructor->getParameters() as $parameter) {
            $paramName = $parameter->getName();
            $paramType = $parameter->getType();
            $hasKey = $data->has($paramName);

            if (!$hasKey) {
                if ($parameter->isDefaultValueAvailable()) {
                    $parameters[] = $parameter->getDefaultValue();
                    continue;
                }

                if ($parameter->allowsNull()) {
                    $parameters[] = null;
                    continue;
                }

                throw new InvalidArgumentException(
                    sprintf('Missing required parameter "$%s" for %s', $paramName, $className)
                );
            }

            $rawValue = $data->get($paramName);

            if ($rawValue === null) {
                if ($parameter->allowsNull()) {
                    $parameters[] = null;
                    continue;
                }

                throw new InvalidArgumentException(
                    sprintf('Parameter "$%s" for %s cannot be null', $paramName, $className)
                );
            }

            if ($paramType !== null) {
                $parameters[] = $this->convertValueToType($rawValue, $paramType, $paramName);
            } else {
                $parameters[] = $rawValue;
            }
        }

        return new $className(...$parameters);
    }

    private function normalizeToDataObject(mixed $source): AbstractDataObject
    {
        if ($source instanceof AbstractDataObject) {
            return $source;
        }

        if (is_string($source) && (str_starts_with(trim($source), '{') || str_starts_with(trim($source), '['))) {
            $decoded = json_decode($source, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $source = $decoded;
            }
        }

        if (is_object($source)) {
            $flattened = NormalizerChain::get()->normalize($source);

            if (!is_array($flattened)) {
                return DataObject::from(['value' => $flattened]);
            }

            return DataObject::from($flattened);
        }

        return DataObject::from($source);
    }

    private function convertValueToType(mixed $rawValue, \ReflectionType $paramType, string $paramName): mixed
    {
        if ($paramType instanceof \ReflectionUnionType) {
            foreach ($paramType->getTypes() as $type) {
                if ($type instanceof \ReflectionNamedType) {
                    try {
                        return $this->convertToNamedType($rawValue, $type, $paramName);
                    } catch (InvalidArgumentException) {
                        continue;
                    }
                }
            }
            throw new InvalidArgumentException(sprintf(
                'Unable to convert value for parameter $%s: no matching union type',
                $paramName
            ));
        }

        if ($paramType instanceof \ReflectionNamedType) {
            return $this->convertToNamedType($rawValue, $paramType, $paramName);
        }

        return $rawValue;
    }

    private function convertToNamedType(mixed $rawValue, \ReflectionNamedType $type, string $paramName): mixed
    {
        $typeName = $type->getName();

        if ($rawValue === null && $type->allowsNull()) {
            return null;
        }

        if ($rawValue instanceof $typeName) {
            return $rawValue;
        }

        foreach ($this->converters as $converter) {
            if ($converter->supports($typeName)) {
                return $converter->convert($rawValue, $typeName, $paramName);
            }
        }

        throw new InvalidArgumentException(sprintf(
            'Cannot convert value for parameter $%s: unknown type %s',
            $paramName,
            $typeName
        ));
    }
}
