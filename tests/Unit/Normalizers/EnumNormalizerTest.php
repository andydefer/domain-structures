<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Unit\Normalizers;

use AndyDefer\DomainStructures\Normalizers\EnumNormalizer;
use AndyDefer\DomainStructures\Normalizers\RootNormalizer;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestBackedIntEnum;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestBackedStringEnum;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestPureEnum;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestUserRole;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestUserStatus;
use AndyDefer\DomainStructures\Tests\TestCase;

final class EnumNormalizerTest extends TestCase
{
    private EnumNormalizer $normalizer;
    private RootNormalizer $rootNormalizer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rootNormalizer = new RootNormalizer();

        $this->normalizer = new EnumNormalizer();
        $this->normalizer->setRecursiveNormalizer($this->rootNormalizer);
    }

    // ==================== SUPPORTS METHOD TESTS ====================

    public function test_supports_returns_true_for_backed_string_enum(): void
    {
        $value = TestBackedStringEnum::VALUE_ONE;
        $result = $this->normalizer->supports($value);
        $this->assertTrue($result);
    }

    public function test_supports_returns_true_for_backed_int_enum(): void
    {
        $value = TestBackedIntEnum::VALUE_ONE;
        $result = $this->normalizer->supports($value);
        $this->assertTrue($result);
    }

    public function test_supports_returns_true_for_pure_enum(): void
    {
        $value = TestPureEnum::VALUE_ONE;
        $result = $this->normalizer->supports($value);
        $this->assertTrue($result);
    }

    public function test_supports_returns_true_for_any_unitenum(): void
    {
        $values = [
            TestUserStatus::ACTIVE,
            TestUserRole::ADMIN,
            TestBackedStringEnum::VALUE_TWO,
            TestBackedIntEnum::VALUE_TWO,
            TestPureEnum::VALUE_TWO,
        ];

        foreach ($values as $value) {
            $result = $this->normalizer->supports($value);
            $this->assertTrue($result);
        }
    }

    public function test_supports_returns_false_for_non_enum_values(): void
    {
        $values = [
            42,
            'string',
            3.14,
            true,
            null,
            new \stdClass,
            [],
        ];

        foreach ($values as $value) {
            $result = $this->normalizer->supports($value);
            $this->assertFalse($result, 'Failed for value type: ' . gettype($value));
        }
    }

    // ==================== NORMALIZE METHOD TESTS - BACKED STRING ENUM ====================

    public function test_normalize_returns_string_value_for_backed_string_enum(): void
    {
        $value = TestBackedStringEnum::VALUE_ONE;
        $normalized = $this->normalizer->normalize($value);
        $this->assertSame('one', $normalized);
        $this->assertIsString($normalized);
    }

    public function test_normalize_returns_correct_value_for_each_backed_string_enum_case(): void
    {
        $enums = [
            TestBackedStringEnum::VALUE_ONE,
            TestBackedStringEnum::VALUE_TWO,
            TestBackedStringEnum::VALUE_THREE,
        ];

        $expected = ['one', 'two', 'three'];

        $result = array_map(
            fn($enum) => $this->normalizer->normalize($enum),
            $enums
        );

        $this->assertSame($expected, $result);
    }

    // ==================== NORMALIZE METHOD TESTS - BACKED INT ENUM ====================

    public function test_normalize_returns_int_value_for_backed_int_enum(): void
    {
        $value = TestBackedIntEnum::VALUE_ONE;
        $normalized = $this->normalizer->normalize($value);
        $this->assertSame(1, $normalized);
        $this->assertIsInt($normalized);
    }

    public function test_normalize_returns_correct_value_for_each_backed_int_enum_case(): void
    {
        $enums = [
            TestBackedIntEnum::VALUE_ONE,
            TestBackedIntEnum::VALUE_TWO,
            TestBackedIntEnum::VALUE_THREE,
        ];

        $expected = [1, 2, 3];

        $result = array_map(
            fn($enum) => $this->normalizer->normalize($enum),
            $enums
        );

        $this->assertSame($expected, $result);
    }

    // ==================== NORMALIZE METHOD TESTS - PURE ENUM ====================

    public function test_normalize_returns_name_for_pure_enum(): void
    {
        $value = TestPureEnum::VALUE_ONE;
        $normalized = $this->normalizer->normalize($value);
        $this->assertSame('VALUE_ONE', $normalized);
        $this->assertIsString($normalized);
    }

    public function test_normalize_returns_correct_name_for_each_pure_enum_case(): void
    {
        $enums = [
            TestPureEnum::VALUE_ONE,
            TestPureEnum::VALUE_TWO,
            TestPureEnum::VALUE_THREE,
        ];

        $expected = ['VALUE_ONE', 'VALUE_TWO', 'VALUE_THREE'];

        $result = array_map(
            fn($enum) => $this->normalizer->normalize($enum),
            $enums
        );

        $this->assertSame($expected, $result);
    }

    // ==================== FORWARDING TESTS ====================

    public function test_normalize_forwards_to_next_normalizer_when_value_not_enum(): void
    {
        $value = 'not an enum';
        $normalized = $this->normalizer->normalize($value);
        $this->assertSame('not an enum', $normalized);
    }

    public function test_normalize_forwards_null_to_next_normalizer(): void
    {
        $value = null;
        $normalized = $this->normalizer->normalize($value);
        $this->assertNull($normalized);
    }

    public function test_normalize_forwards_integer_to_next_normalizer(): void
    {
        $value = 42;
        $normalized = $this->normalizer->normalize($value);
        $this->assertSame(42, $normalized);
    }

    // ==================== EDGE CASES TESTS ====================

    public function test_normalize_handles_all_enum_cases_consistently(): void
    {
        $backedStringCases = TestBackedStringEnum::cases();
        $backedIntCases = TestBackedIntEnum::cases();
        $pureCases = TestPureEnum::cases();

        foreach ($backedStringCases as $enum) {
            $normalized = $this->normalizer->normalize($enum);
            $this->assertIsString($normalized);
            $this->assertNotEmpty($normalized);
        }

        foreach ($backedIntCases as $enum) {
            $normalized = $this->normalizer->normalize($enum);
            $this->assertIsInt($normalized);
        }

        foreach ($pureCases as $enum) {
            $normalized = $this->normalizer->normalize($enum);
            $this->assertIsString($normalized);
            $this->assertNotEmpty($normalized);
        }
    }

    public function test_normalize_is_idempotent(): void
    {
        $enum = TestBackedStringEnum::VALUE_ONE;
        $first = $this->normalizer->normalize($enum);
        $second = $this->normalizer->normalize($enum);
        $third = $this->normalizer->normalize($enum);

        $this->assertSame($first, $second);
        $this->assertSame($second, $third);
    }

    public function test_normalize_preserves_value_type_consistency(): void
    {
        $stringEnum = TestBackedStringEnum::VALUE_ONE;
        $intEnum = TestBackedIntEnum::VALUE_ONE;
        $pureEnum = TestPureEnum::VALUE_ONE;

        $stringResult = $this->normalizer->normalize($stringEnum);
        $intResult = $this->normalizer->normalize($intEnum);
        $pureResult = $this->normalizer->normalize($pureEnum);

        $this->assertIsString($stringResult);
        $this->assertIsInt($intResult);
        $this->assertIsString($pureResult);
    }
}
