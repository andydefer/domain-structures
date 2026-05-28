<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Traits;

/**
 * Provides common utility methods for PHP 8.1+ Enums.
 *
 * This trait adds convenient methods to enums for value validation, listing,
 * and case retrieval. It works with both backed enums (with scalar values)
 * and pure enums (without values).
 */
trait Enumable
{
    /**
     * Returns all scalar values from the enum.
     *
     * For backed enums (string|int), returns the backing values.
     * For pure enums (without values), returns the case names.
     *
     * @return array<int, string|int> Array of enum values or case names
     */
    public static function values(): array
    {
        if (self::isBackedEnum()) {
            return array_column(self::cases(), 'value');
        }

        return array_column(self::cases(), 'name');
    }

    /**
     * Returns all case names from the enum.
     *
     * @return array<int, string> Array of enum case names (UPPER_CASE format)
     */
    public static function names(): array
    {
        return array_column(self::cases(), 'name');
    }

    /**
     * Returns all enum cases in their defined order.
     *
     * This is an alias for the native cases() method that provides a more
     * semantic name when the intent is to respect the definition order.
     *
     * @return array<int, self> Array of all enum cases
     */
    public static function typesInOrder(): array
    {
        return self::cases();
    }

    /**
     * Checks if a given value exists in the enum.
     *
     * For backed enums, checks against backing values.
     * For pure enums, checks against case names.
     *
     * @param  string|int  $value  The value to validate
     * @return bool True if the value exists in the enum, false otherwise
     */
    public static function isValid(string|int $value): bool
    {
        if (self::isBackedEnum()) {
            return in_array($value, self::values(), true);
        }

        return in_array($value, self::names(), true);
    }

    /**
     * Retrieves the enum case corresponding to a value.
     *
     * For backed enums, returns the case with the matching backing value.
     * For pure enums, attempts to find a case by name (case-sensitive).
     *
     * @param  string|int  $value  The value to search for
     * @return self|null The matching enum case, or null if not found
     */
    public static function fromValue(string|int $value): ?self
    {
        if (self::isBackedEnum()) {
            // Vérifier si la valeur est vide (uniquement pour les string enums)
            if (is_string($value) && $value === '') {
                return null;
            }

            // Pour les backed enums int, convertir la string en int si possible
            if (is_string($value) && is_numeric($value)) {
                $value = (int) $value;
            }

            return self::tryFrom($value);
        }

        $value = (string) $value;
        foreach (self::cases() as $case) {
            if ($case->name === $value) {
                return $case;
            }
        }

        return null;
    }

    /**
     * Creates an enum instance from any source for hydration.
     *
     * This method is used by the Hydratable trait to convert any source value
     * into the correct enum case. It handles:
     * - If source is already an enum instance, returns it directly
     * - For backed enums, converts from string/int via tryFrom()
     * - For pure enums, matches by case name
     *
     * @param  mixed  $source  The source value (string, int, or existing enum)
     * @return self The matching enum case
     *
     * @throws \InvalidArgumentException If the source cannot be converted
     */
    public static function from(mixed $source): self
    {
        // Si c'est déjà une instance du même enum, la retourner directement
        if ($source instanceof self) {
            return $source;
        }

        // Pour les backed enums (avec valeur scalaire)
        if (self::isBackedEnum()) {
            // CORRECTION: Vérifier les valeurs vides AVANT tout traitement
            if (is_string($source) && $source === '') {
                throw new \InvalidArgumentException(sprintf(
                    'Empty string is not a valid backing value for enum %s',
                    self::class
                ));
            }

            // Si c'est une string ou un entier
            if (is_string($source) || is_int($source)) {
                // Pour les int enums, convertir la string en int si nécessaire
                if (is_string($source) && is_numeric($source)) {
                    $source = (int) $source;
                }
                $enum = self::tryFrom($source);
                if ($enum !== null) {
                    return $enum;
                }
            }

            // Si c'est un objet avec une propriété value
            if (is_object($source) && property_exists($source, 'value')) {
                return self::from($source->value);
            }

            // Si c'est un tableau avec une clé value
            if (is_array($source) && isset($source['value'])) {
                return self::from($source['value']);
            }

            throw new \InvalidArgumentException(sprintf(
                'Cannot convert value to enum %s: expected string|int, got %s',
                self::class,
                is_object($source) ? $source::class : gettype($source)
            ));
        }

        // Pour les pure enums (sans valeur scalaire)
        if (is_string($source)) {
            foreach (self::cases() as $case) {
                if ($case->name === $source) {
                    return $case;
                }
            }
        }

        // Si c'est un objet, essayer de lire la propriété name
        if (is_object($source) && property_exists($source, 'name')) {
            return self::from($source->name);
        }

        throw new \InvalidArgumentException(sprintf(
            'Cannot convert value to pure enum %s: expected string, got %s',
            self::class,
            is_object($source) ? $source::class : gettype($source)
        ));
    }

    /**
     * Checks if the enum is a backed enum (has scalar values).
     *
     * @return bool True if the enum is backed, false if it's a pure enum
     */
    private static function isBackedEnum(): bool
    {
        return is_subclass_of(self::class, \BackedEnum::class);
    }
}
