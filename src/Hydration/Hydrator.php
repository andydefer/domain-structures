<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Hydration;

use AndyDefer\DomainStructures\Abstracts\AbstractData;
use AndyDefer\DomainStructures\Interfaces\Transformable;
use AndyDefer\DomainStructures\Normalizers\NormalizerChain;
use AndyDefer\DomainStructures\Utils\DataObject;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionUnionType;
use RuntimeException;
use UnitEnum;

/**
 * Centralized hydrator for all Transformable objects.
 * 
 * Handles automatic hydration from arrays, objects, DataObject, or JSON strings.
 * Uses reflection to analyze constructors and convert values to proper types.
 * Validation is done inside the constructor of the target class.
 */
final class Hydrator
{
    /**
     * Hydrate an object from a source.
     *
     * @param class-string $className
     * @param mixed $source
     * @return object
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public static function hydrate(string $className, mixed $source): object
    {
        // Si c'est déjà une instance de la classe ET que ce n'est PAS une Data
        // Les Data doivent toujours créer une nouvelle instance
        if (is_object($source) && $source instanceof $className && !is_subclass_of($className, AbstractData::class)) {
            return $source;
        }

        // CAS SPÉCIAL : Les enums
        if (enum_exists($className)) {
            return self::hydrateEnum($className, $source);
        }

        $reflection = new ReflectionClass($className);
        $constructor = $reflection->getConstructor();

        if (!$constructor) {
            throw new RuntimeException(sprintf('%s must have a constructor', $className));
        }

        // CAS SPÉCIAL : constructeur à un seul paramètre
        if ($constructor->getNumberOfParameters() === 1) {
            $param = $constructor->getParameters()[0];
            $paramType = $param->getType();

            if ($paramType instanceof ReflectionNamedType) {
                $typeName = $paramType->getName();

                // Cas 1: La source est un objet qu'on peut normaliser en scalaire
                if (is_object($source)) {
                    $normalized = NormalizerChain::get()->normalize($source);

                    // Le paramètre attend un scalaire
                    if (self::isScalarType($typeName)) {
                        $converted = self::castToScalar($normalized, $typeName, $param->getName());
                        return new $className($converted);
                    }

                    // Le paramètre attend un Transformable
                    if (is_subclass_of($typeName, Transformable::class)) {
                        $transformed = $typeName::from($normalized);
                        return new $className($transformed);
                    }
                }

                // Cas 2: La source est un scalaire
                if (is_scalar($source)) {
                    // Paramètre attend un scalaire
                    if (self::isScalarType($typeName)) {
                        $converted = self::castToScalar($source, $typeName, $param->getName());
                        return new $className($converted);
                    }

                    // Paramètre attend un Transformable (ex: EmailAddress attend string)
                    if (is_subclass_of($typeName, Transformable::class)) {
                        $transformed = $typeName::from($source);
                        return new $className($transformed);
                    }
                }
            }
        }

        // Normalisation de la source en DataObject (aplatit les objets)
        $data = self::normalizeToDataObject($source, $reflection);

        $parameters = [];

        // Récupérer toutes les valeurs du DataObject
        foreach ($constructor->getParameters() as $parameter) {
            $paramName = $parameter->getName();
            $paramType = $parameter->getType();

            // Vérifier si la clé existe DANS LE DATASET (même avec valeur null)
            $hasKey = $data->has($paramName);

            // La clé est ABSENTE (n'existe pas du tout dans les données)
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

            // La clé existe (peut être null ou une valeur)
            $rawValue = $data->get($paramName);

            // Si la valeur est null
            if ($rawValue === null) {
                // null explicite - on passe null (même si une valeur par défaut existe)
                if ($parameter->allowsNull()) {
                    $parameters[] = null;
                    continue;
                }

                // La valeur est null mais le paramètre ne l'accepte pas
                throw new InvalidArgumentException(
                    sprintf('Parameter "$%s" for %s cannot be null', $paramName, $className)
                );
            }

            // Valeur non null - conversion normale
            if ($paramType !== null) {
                $convertedValue = self::convertValueToType($rawValue, $paramType, $paramName);
            } else {
                $convertedValue = $rawValue;
            }

            $parameters[] = $convertedValue;
        }

        // Créer l'instance (la validation se fait dans le constructeur)
        return new $className(...$parameters);
    }

    /**
     * Hydrate an enum from a source.
     *
     * @param class-string $enumClass
     * @param mixed $source
     * @return \BackedEnum|\UnitEnum
     * @throws InvalidArgumentException
     */
    private static function hydrateEnum(string $enumClass, mixed $source): \BackedEnum|\UnitEnum
    {
        // Si c'est déjà une instance de l'enum
        if (is_object($source) && $source instanceof $enumClass) {
            return $source;
        }

        // Si la source est une string ou un int
        if (is_scalar($source)) {
            // BackedEnum (avec value)
            if (is_subclass_of($enumClass, \BackedEnum::class)) {
                $enum = $enumClass::tryFrom($source);
                if ($enum !== null) {
                    return $enum;
                }
                throw new InvalidArgumentException(
                    sprintf('Invalid value "%s" for enum %s', $source, $enumClass)
                );
            }

            // UnitEnum (sans value) - on cherche une constante avec ce nom
            if (defined("{$enumClass}::{$source}")) {
                return constant("{$enumClass}::{$source}");
            }
            throw new InvalidArgumentException(
                sprintf('Invalid value "%s" for enum %s', $source, $enumClass)
            );
        }

        // Si la source est un tableau avec une clé 'value' ou 'name'
        if (is_array($source)) {
            if (isset($source['value'])) {
                return self::hydrateEnum($enumClass, $source['value']);
            }
            if (isset($source['name'])) {
                return self::hydrateEnum($enumClass, $source['name']);
            }
        }

        // Si la source est un objet
        if (is_object($source)) {
            if (property_exists($source, 'value')) {
                return self::hydrateEnum($enumClass, $source->value);
            }
            if (property_exists($source, 'name')) {
                return self::hydrateEnum($enumClass, $source->name);
            }
        }

        throw new InvalidArgumentException(
            sprintf('Cannot hydrate enum %s from source type: %s', $enumClass, gettype($source))
        );
    }

    /**
     * Normalize any source to DataObject.
     * Objects are flattened using NormalizerChain before being passed to DataObject.
     *
     * @param mixed $source
     * @return DataObject
     */
    private static function normalizeToDataObject(mixed $source, ReflectionClass $reflection): DataObject
    {
        // Déjà un DataObject
        if ($source instanceof DataObject) {
            return $source;
        }

        // JSON string
        if (is_string($source) && (str_starts_with(trim($source), '{') || str_starts_with(trim($source), '['))) {
            $decoded = json_decode($source, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $source = $decoded;
            }
        }

        // Objet
        if (is_object($source)) {
            $flattened = NormalizerChain::get()->normalize($source);

            // Si le résultat n'est pas un tableau (ex: string pour DateTime)
            // on le transforme en tableau avec une clé générique
            if (!is_array($flattened)) {
                // Pour un objet simple comme DateTime, on utilise la valeur normalisée
                // Le CAS SPÉCIAL dans hydrate() a déjà traité les cas à un paramètre
                return DataObject::from(['value' => $flattened]);
            }

            return DataObject::from($flattened);
        }

        // Tableau ou autre
        return DataObject::from($source);
    }

    /**
     * Convert a raw value to the expected parameter type.
     *
     * @throws InvalidArgumentException
     */
    private static function convertValueToType(mixed $rawValue, \ReflectionType $paramType, string $paramName): mixed
    {
        // Union types
        if ($paramType instanceof ReflectionUnionType) {
            foreach ($paramType->getTypes() as $type) {
                if ($type instanceof ReflectionNamedType) {
                    try {
                        return self::convertToNamedType($rawValue, $type, $paramName);
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

        // Named types
        if ($paramType instanceof ReflectionNamedType) {
            return self::convertToNamedType($rawValue, $paramType, $paramName);
        }

        return $rawValue;
    }

    /**
     * Convert to a named type.
     *
     * @throws InvalidArgumentException
     */
    private static function convertToNamedType(mixed $rawValue, ReflectionNamedType $type, string $paramName): mixed
    {
        $typeName = $type->getName();

        // Nullable
        if ($rawValue === null && $type->allowsNull()) {
            return null;
        }

        // Déjà du bon type
        if ($rawValue instanceof $typeName) {
            return $rawValue;
        }

        // Types scalaires
        if (self::isScalarType($typeName)) {
            return self::castToScalar($rawValue, $typeName, $paramName);
        }

        // Enums
        if (enum_exists($typeName)) {
            return self::hydrateEnum($typeName, $rawValue);
        }

        // Transformable (Value Objects, Records, etc.)
        if (is_subclass_of($typeName, Transformable::class)) {
            // On aplatit l'objet avant de le passer à ::from
            if (is_object($rawValue)) {
                $flattened = NormalizerChain::get()->normalize($rawValue);
                return $typeName::from($flattened);
            }
            return $typeName::from($rawValue);
        }

        // Classe standard avec constructeur
        if (class_exists($typeName)) {
            return new $typeName($rawValue);
        }

        throw new InvalidArgumentException(sprintf(
            'Cannot convert value for parameter $%s: unknown type %s',
            $paramName,
            $typeName
        ));
    }

    /**
     * Check if a type name is a scalar type.
     */
    private static function isScalarType(string $typeName): bool
    {
        return in_array($typeName, ['int', 'float', 'string', 'bool', 'boolean'], true);
    }

    /**
     * Cast value to scalar type.
     *
     * @throws InvalidArgumentException
     */
    private static function castToScalar(mixed $rawValue, string $typeName, string $paramName): int|float|string|bool
    {
        return match ($typeName) {
            'int', 'integer' => self::toInt($rawValue, $paramName),
            'float', 'double' => self::toFloat($rawValue, $paramName),
            'string' => self::toString($rawValue, $paramName),
            'bool', 'boolean' => self::toBool($rawValue, $paramName),
            default => throw new InvalidArgumentException(sprintf(
                'Cannot cast to scalar type %s for parameter $%s',
                $typeName,
                $paramName
            )),
        };
    }

    /**
     * Convert value to integer.
     *
     * @throws InvalidArgumentException
     */
    private static function toInt(mixed $rawValue, string $paramName): int
    {
        if (is_numeric($rawValue)) {
            return (int)$rawValue;
        }
        throw new InvalidArgumentException(
            sprintf('Cannot convert value to int for parameter $%s', $paramName)
        );
    }

    /**
     * Convert value to float.
     *
     * @throws InvalidArgumentException
     */
    private static function toFloat(mixed $rawValue, string $paramName): float
    {
        if (is_numeric($rawValue)) {
            return (float)$rawValue;
        }
        throw new InvalidArgumentException(
            sprintf('Cannot convert value to float for parameter $%s', $paramName)
        );
    }

    /**
     * Convert value to string.
     *
     * @throws InvalidArgumentException
     */
    private static function toString(mixed $rawValue, string $paramName): string
    {
        if ($rawValue === null) {
            throw new InvalidArgumentException(
                sprintf('Cannot convert null to string for parameter $%s', $paramName)
            );
        }

        if (is_scalar($rawValue) || method_exists($rawValue, '__toString')) {
            return (string)$rawValue;
        }
        throw new InvalidArgumentException(
            sprintf('Cannot convert value to string for parameter $%s', $paramName)
        );
    }

    /**
     * Convert value to boolean.
     *
     * @throws InvalidArgumentException
     */
    private static function toBool(mixed $rawValue, string $paramName): bool
    {
        if (is_bool($rawValue)) {
            return $rawValue;
        }
        if (is_numeric($rawValue)) {
            return (bool)$rawValue;
        }
        if (is_string($rawValue)) {
            return filter_var($rawValue, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
        }
        throw new InvalidArgumentException(
            sprintf('Cannot convert value to bool for parameter $%s', $paramName)
        );
    }
}
