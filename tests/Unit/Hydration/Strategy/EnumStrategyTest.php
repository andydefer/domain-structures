<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Unit\Hydration\Strategy;

use AndyDefer\DomainStructures\Hydration\Strategy\EnumStrategy;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestBackedIntEnum;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestBackedStringEnum;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestPureEnum;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestUserRole;
use AndyDefer\DomainStructures\Tests\TestCase;
use InvalidArgumentException;
use stdClass;

final class EnumStrategyTest extends TestCase
{
    private EnumStrategy $strategy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->strategy = new EnumStrategy;
    }

    // ==================== SUPPORTS METHOD TESTS ====================

    public function test_supports_returns_true_for_backed_string_enum(): void
    {
        $result = $this->strategy->supports(TestBackedStringEnum::class, null);
        $this->assertTrue($result);
    }

    public function test_supports_returns_true_for_backed_int_enum(): void
    {
        $result = $this->strategy->supports(TestBackedIntEnum::class, null);
        $this->assertTrue($result);
    }

    public function test_supports_returns_true_for_pure_enum(): void
    {
        $result = $this->strategy->supports(TestPureEnum::class, null);
        $this->assertTrue($result);
    }

    public function test_supports_returns_true_for_any_enum(): void
    {
        $enums = [
            TestUserRole::class,
            TestBackedStringEnum::class,
            TestBackedIntEnum::class,
            TestPureEnum::class,
        ];

        foreach ($enums as $enum) {
            $result = $this->strategy->supports($enum, null);
            $this->assertTrue($result, "Failed for enum: {$enum}");
        }
    }

    public function test_supports_returns_false_for_non_enum(): void
    {
        $nonEnums = [self::class, 'int', 'string', stdClass::class];

        foreach ($nonEnums as $nonEnum) {
            $result = $this->strategy->supports($nonEnum, null);
            $this->assertFalse($result, "Failed for: {$nonEnum}");
        }
    }

    // ==================== HYDRATE FROM SCALAR - BACKED STRING ENUM ====================

    public function test_hydrate_creates_backed_string_enum_from_string(): void
    {
        $result = $this->strategy->hydrate(TestBackedStringEnum::class, 'one');
        $this->assertSame(TestBackedStringEnum::VALUE_ONE, $result);
    }

    /**
     * Un string backed enum peut être créé depuis une string numérique
     * SI cette string numérique correspond à la valeur d'une des cases.
     * 
     * Exemple : TestBackedStringEnum a des valeurs 'one', 'two', 'three'
     * La string '2' ne correspond à aucune case.
     * La string 'two' correspond à TestBackedStringEnum::VALUE_TWO.
     */
    public function test_hydrate_creates_backed_string_enum_from_valid_string_value(): void
    {
        // Arrange: Utiliser une string qui correspond à une valeur existante
        // TestBackedStringEnum a les valeurs 'one', 'two', 'three'
        $validStringValue = 'two';

        // Act: Tenter de créer l'enum depuis cette string
        $result = $this->strategy->hydrate(TestBackedStringEnum::class, $validStringValue);

        // Assert: L'enum est correctement créé
        $this->assertSame(TestBackedStringEnum::VALUE_TWO, $result);
    }

    public function test_hydrate_returns_same_instance_when_source_is_enum(): void
    {
        $original = TestBackedStringEnum::VALUE_THREE;
        $result = $this->strategy->hydrate(TestBackedStringEnum::class, $original);
        $this->assertSame($original, $result);
    }

    public function test_hydrate_throws_exception_for_invalid_backed_string_enum_value(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid value "invalid" for enum');

        $this->strategy->hydrate(TestBackedStringEnum::class, 'invalid');
    }

    public function test_hydrate_throws_exception_for_numeric_string_not_matching_enum_value(): void
    {
        // Arrange: '2' est une string numérique mais ne correspond à aucune valeur
        // car TestBackedStringEnum a des valeurs textuelles ('one', 'two', 'three')
        $numericStringNotMatching = '2';

        // Assert: Une exception est levée car la valeur n'existe pas dans l'enum
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf(
            'Invalid value "%s" for enum',
            $numericStringNotMatching
        ));

        // Act: Tenter de créer l'enum depuis cette string numérique
        $this->strategy->hydrate(TestBackedStringEnum::class, $numericStringNotMatching);
    }

    // ==================== HYDRATE FROM SCALAR - BACKED INT ENUM ====================

    public function test_hydrate_creates_backed_int_enum_from_int(): void
    {
        $result = $this->strategy->hydrate(TestBackedIntEnum::class, 2);
        $this->assertSame(TestBackedIntEnum::VALUE_TWO, $result);
    }

    public function test_hydrate_creates_backed_int_enum_from_numeric_string(): void
    {
        $result = $this->strategy->hydrate(TestBackedIntEnum::class, '3');
        $this->assertSame(TestBackedIntEnum::VALUE_THREE, $result);
    }

    public function test_hydrate_returns_same_instance_for_backed_int_enum(): void
    {
        $original = TestBackedIntEnum::VALUE_ONE;
        $result = $this->strategy->hydrate(TestBackedIntEnum::class, $original);
        $this->assertSame($original, $result);
    }

    public function test_hydrate_throws_exception_for_invalid_backed_int_enum_value(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid value "99" for enum');

        $this->strategy->hydrate(TestBackedIntEnum::class, 99);
    }

    public function test_hydrate_throws_exception_for_string_not_matching_int_enum(): void
    {
        // Arrange: '99' est une string numérique mais ne correspond à aucune valeur
        $stringNotMatching = '99';

        // Assert: Exception car 99 n'existe pas dans TestBackedIntEnum
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf(
            'Invalid value "%s" for enum',
            $stringNotMatching
        ));

        // Act: Tenter de créer l'enum
        $this->strategy->hydrate(TestBackedIntEnum::class, $stringNotMatching);
    }

    // ==================== HYDRATE FROM SCALAR - PURE ENUM ====================

    public function test_hydrate_creates_pure_enum_from_case_name(): void
    {
        $result = $this->strategy->hydrate(TestPureEnum::class, 'VALUE_TWO');
        $this->assertSame(TestPureEnum::VALUE_TWO, $result);
    }

    public function test_hydrate_returns_same_instance_for_pure_enum(): void
    {
        $original = TestPureEnum::VALUE_THREE;
        $result = $this->strategy->hydrate(TestPureEnum::class, $original);
        $this->assertSame($original, $result);
    }

    public function test_hydrate_throws_exception_for_invalid_pure_enum_case(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid value "INVALID_CASE" for enum');

        $this->strategy->hydrate(TestPureEnum::class, 'INVALID_CASE');
    }

    // ==================== HYDRATE FROM ARRAY ====================

    public function test_hydrate_backed_string_enum_from_array_with_value(): void
    {
        $result = $this->strategy->hydrate(TestBackedStringEnum::class, ['value' => 'three']);
        $this->assertSame(TestBackedStringEnum::VALUE_THREE, $result);
    }

    public function test_hydrate_backed_int_enum_from_array_with_value(): void
    {
        $result = $this->strategy->hydrate(TestBackedIntEnum::class, ['value' => 1]);
        $this->assertSame(TestBackedIntEnum::VALUE_ONE, $result);
    }

    public function test_hydrate_pure_enum_from_array_with_name(): void
    {
        $result = $this->strategy->hydrate(TestPureEnum::class, ['name' => 'VALUE_ONE']);
        $this->assertSame(TestPureEnum::VALUE_ONE, $result);
    }

    public function test_hydrate_throws_exception_for_array_without_valid_keys(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf(
            'Cannot hydrate enum %s from array without "value" or "name" key',
            TestBackedStringEnum::class
        ));

        $this->strategy->hydrate(TestBackedStringEnum::class, ['invalid' => 'data']);
    }

    // ==================== HYDRATE FROM OBJECT ====================

    public function test_hydrate_backed_enum_from_object_with_value_property(): void
    {
        $object = new class {
            public string $value = 'two';
        };

        $result = $this->strategy->hydrate(TestBackedStringEnum::class, $object);
        $this->assertSame(TestBackedStringEnum::VALUE_TWO, $result);
    }

    public function test_hydrate_pure_enum_from_object_with_name_property(): void
    {
        $object = new class {
            public string $name = 'VALUE_TWO';
        };

        $result = $this->strategy->hydrate(TestPureEnum::class, $object);
        $this->assertSame(TestPureEnum::VALUE_TWO, $result);
    }

    public function test_hydrate_throws_exception_for_object_without_valid_properties(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf(
            'Cannot hydrate enum %s from object without "value" or "name" property',
            TestBackedStringEnum::class
        ));

        $object = new class {
            public string $invalid = 'data';
        };

        $this->strategy->hydrate(TestBackedStringEnum::class, $object);
    }

    // ==================== EDGE CASES TESTS ====================

    public function test_hydrate_throws_exception_for_unsupported_source_type(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf(
            'Cannot hydrate enum %s from source type: resource',
            TestBackedStringEnum::class
        ));

        $resource = fopen('php://memory', 'r');
        $this->strategy->hydrate(TestBackedStringEnum::class, $resource);
        fclose($resource);
    }

    public function test_hydrate_is_idempotent(): void
    {
        $enum = TestBackedStringEnum::VALUE_ONE;

        $first = $this->strategy->hydrate(TestBackedStringEnum::class, $enum);
        $second = $this->strategy->hydrate(TestBackedStringEnum::class, $first);

        $this->assertSame($enum, $first);
        $this->assertSame($first, $second);
    }
}
