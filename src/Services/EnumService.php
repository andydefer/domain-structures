<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Services;

use BackedEnum;
use InvalidArgumentException;
use UnitEnum;

final class EnumService
{
    /**
     * Returns all scalar values from the enum.
     *
     * For backed enums (string|int), returns the backing values.
     * For pure enums (without values), returns the case names.
     *
     * @param class-string<UnitEnum> $enumClass
     * @return array<int, string|int>
     */
    public function values(string $enumClass): array
    {
        $this->validateEnumClass($enumClass);

        if (is_subclass_of($enumClass, BackedEnum::class)) {
            return array_column($enumClass::cases(), 'value');
        }

        return array_column($enumClass::cases(), 'name');
    }

    /**
     * Returns all case names from the enum.
     *
     * @param class-string<UnitEnum> $enumClass
     * @return array<int, string>
     */
    public function names(string $enumClass): array
    {
        $this->validateEnumClass($enumClass);

        return array_column($enumClass::cases(), 'name');
    }

    /**
     * Returns all enum cases in their defined order.
     *
     * @param class-string<UnitEnum> $enumClass
     * @return array<int, UnitEnum>
     */
    public function cases(string $enumClass): array
    {
        $this->validateEnumClass($enumClass);

        return $enumClass::cases();
    }

    /**
     * Checks if a given value exists in the enum.
     *
     * @param class-string<UnitEnum> $enumClass
     * @param string|int $value
     * @return bool
     */
    public function isValid(string $enumClass, string|int $value): bool
    {
        $this->validateEnumClass($enumClass);

        if (is_subclass_of($enumClass, BackedEnum::class)) {
            return in_array($value, $this->values($enumClass), true);
        }

        return in_array($value, $this->names($enumClass), true);
    }

    /**
     * Retrieves the enum case corresponding to a value.
     *
     * @param class-string<UnitEnum> $enumClass
     * @param string|int $value
     * @return UnitEnum|null
     */
    public function fromValue(string $enumClass, string|int $value): ?UnitEnum
    {
        $this->validateEnumClass($enumClass);

        if (is_subclass_of($enumClass, BackedEnum::class)) {
            if (is_string($value) && $value === '') {
                return null;
            }

            if (is_string($value) && is_numeric($value)) {
                $value = (int) $value;
            }

            /** @var BackedEnum $enumClass */
            return $enumClass::tryFrom($value);
        }

        $value = (string) $value;
        foreach ($enumClass::cases() as $case) {
            if ($case->name === $value) {
                return $case;
            }
        }

        return null;
    }

    /**
     * Creates an enum instance from any source for hydration.
     *
     * @param class-string<UnitEnum> $enumClass
     * @param mixed $source
     * @return UnitEnum
     *
     * @throws InvalidArgumentException
     */
    public function from(string $enumClass, mixed $source): UnitEnum
    {
        $this->validateEnumClass($enumClass);

        // Si c'est déjà une instance du même enum, la retourner directement
        if ($source instanceof UnitEnum && $source instanceof $enumClass) {
            return $source;
        }

        // Pour les backed enums (avec valeur scalaire)
        if (is_subclass_of($enumClass, BackedEnum::class)) {
            if (is_string($source) && $source === '') {
                throw new InvalidArgumentException(sprintf(
                    'Empty string is not a valid backing value for enum %s',
                    $enumClass
                ));
            }

            if (is_string($source) || is_int($source)) {
                if (is_string($source) && is_numeric($source)) {
                    $source = (int) $source;
                }

                /** @var BackedEnum $enumClass */
                $enum = $enumClass::tryFrom($source);
                if ($enum !== null) {
                    return $enum;
                }
            }

            if (is_object($source) && property_exists($source, 'value')) {
                return $this->from($enumClass, $source->value);
            }

            if (is_array($source) && isset($source['value'])) {
                return $this->from($enumClass, $source['value']);
            }

            throw new InvalidArgumentException(sprintf(
                'Cannot convert value to enum %s: expected string|int, got %s',
                $enumClass,
                is_object($source) ? $source::class : gettype($source)
            ));
        }

        // Pour les pure enums (sans valeur scalaire)
        if (is_string($source)) {
            foreach ($enumClass::cases() as $case) {
                if ($case->name === $source) {
                    return $case;
                }
            }
        }

        if (is_object($source) && property_exists($source, 'name')) {
            return $this->from($enumClass, $source->name);
        }

        throw new InvalidArgumentException(sprintf(
            'Cannot convert value to pure enum %s: expected string, got %s',
            $enumClass,
            is_object($source) ? $source::class : gettype($source)
        ));
    }

    /**
     * @param class-string<UnitEnum> $enumClass
     * @throws InvalidArgumentException
     */
    private function validateEnumClass(string $enumClass): void
    {
        if (!is_subclass_of($enumClass, UnitEnum::class)) {
            throw new InvalidArgumentException(sprintf(
                'Class %s is not a valid Enum',
                $enumClass
            ));
        }
    }
}
