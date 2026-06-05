<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Hydration\Strategy;

use AndyDefer\DomainStructures\Abstracts\AbstractTypedCollection;
use AndyDefer\DomainStructures\Enums\PhpType;
use AndyDefer\DomainStructures\Hydration\Converter\TypeConverterInterface;
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
        $constructor = $reflection->getConstructor();

        if (!$constructor || $constructor->getNumberOfParameters() !== 1) {
            throw new InvalidArgumentException(sprintf(
                '%s does not have a constructor with exactly 1 parameter',
                $className
            ));
        }

        $param = $constructor->getParameters()[0];
        $paramType = $param->getType();

        // Gérer null si le paramètre l'accepte
        if ($source === null && $param->allowsNull()) {
            return new $className(null);
        }

        // Cas spécial : tableau associatif à une seule clé -> extraire la valeur
        if ($this->isSingleKeyAssociativeArray($source)) {
            $source = reset($source);
        }

        // Normalisation des floats pour les paramètres de type string
        $source = $this->normalizeFloatValue($source, $paramType);

        if ($paramType instanceof ReflectionUnionType) {
            return $this->handleUnionType($className, $source, $paramType, $param);
        }

        return $this->handleNamedType($className, $source, $paramType, $param);
    }

    /**
     * Vérifie si c'est un tableau associatif avec une seule clé (non numérique).
     * Exemple: ['value' => 'something'] ou ['email' => 'test@example.com']
     */
    private function isSingleKeyAssociativeArray(mixed $source): bool
    {
        if (!is_array($source)) {
            return false;
        }

        if (count($source) !== 1) {
            return false;
        }

        $keys = array_keys($source);

        // La clé doit être une string (associatif) et non un int (indexé)
        return is_string($keys[0]);
    }

    /**
     * Normalise les valeurs flottantes pour les paramètres de type string.
     */
    private function normalizeFloatValue(mixed $source, ReflectionNamedType|ReflectionUnionType|null $paramType): mixed
    {
        if (!is_float($source)) {
            return $source;
        }

        if ($paramType instanceof ReflectionNamedType && $paramType->getName() === 'string') {
            $rounded = round($source, 2);
            return number_format($rounded, 2, '.', '');
        }

        if ($paramType instanceof ReflectionUnionType) {
            foreach ($paramType->getTypes() as $type) {
                if ($type instanceof ReflectionNamedType && $type->getName() === 'string') {
                    $rounded = round($source, 2);
                    return number_format($rounded, 2, '.', '');
                }
            }
        }

        return $source;
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
        $paramType = PhpType::fromTypeString($typeName);

        // ==================== TABLEAU ====================
        if (is_array($source)) {
            // Si le paramètre attend un objet (Record, ValueObject, Data, Collection, Enum)
            if ($paramType->isObject()) {
                // Déléguer aux converters
                foreach ($this->converters as $converter) {
                    if ($converter->supports($typeName)) {
                        return $converter->convert($source, $typeName, $paramName);
                    }
                }
            }

            throw new InvalidArgumentException(
                sprintf('Cannot convert array to %s for parameter $%s', $typeName, $paramName)
            );
        }

        // ==================== OBJET ====================
        if (is_object($source)) {
            // Si c'est déjà une instance du type cible, la retourner directement
            if ($source instanceof $typeName) {
                return $source;
            }

            // Normaliser l'objet
            $normalized = NormalizerChain::get()->normalize($source);

            // Déléguer aux converters
            foreach ($this->converters as $converter) {
                if ($converter->supports($typeName)) {
                    return $converter->convert($normalized, $typeName, $paramName);
                }
            }
        }

        // ==================== SCALAIRE ====================
        if (is_scalar($source)) {
            // Si le paramètre attend un scalaire du même type
            $phpType = PhpType::fromValue($source);
            if ($phpType->isScalar() && $typeName === $phpType->getNormalizedName()) {
                return $source;
            }

            // Sinon, déléguer aux converters
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
