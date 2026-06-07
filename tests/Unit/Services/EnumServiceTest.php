<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Unit\Services;

use AndyDefer\DomainStructures\Services\EnumService;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestBackedEnum;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestBackedIntEnum;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestBackedStringEnum;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestCurrency;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestLifeStage;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestPureEnum;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestSingleCaseEnum;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestUserGrade;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestUserRole;
use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestUserStatus;
use AndyDefer\DomainStructures\Tests\TestCase;
use AndyDefer\DomainStructures\Tests\UnitTestCase;
use InvalidArgumentException;

final class EnumServiceTest extends TestCase
{
    private EnumService $enumService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->enumService = new EnumService();
    }

    public function test_values_returns_backing_values_for_backed_string_enum(): void
    {
        $values = $this->enumService->values(TestUserStatus::class);

        $this->assertSame(['active', 'inactive', 'pending', 'suspended'], $values);
    }

    public function test_values_returns_backing_values_for_backed_int_enum(): void
    {
        $values = $this->enumService->values(TestBackedIntEnum::class);

        $this->assertSame([1, 2, 3], $values);
    }

    public function test_values_returns_names_for_pure_enum(): void
    {
        $values = $this->enumService->values(TestPureEnum::class);

        $this->assertSame(['VALUE_ONE', 'VALUE_TWO', 'VALUE_THREE'], $values);
    }

    public function test_names_returns_case_names(): void
    {
        $names = $this->enumService->names(TestUserStatus::class);

        $this->assertSame(['ACTIVE', 'INACTIVE', 'PENDING', 'SUSPENDED'], $names);
    }

    public function test_cases_returns_all_cases(): void
    {
        $cases = $this->enumService->cases(TestUserRole::class);

        $this->assertCount(3, $cases);
        $this->assertSame(TestUserRole::ADMIN, $cases[0]);
        $this->assertSame(TestUserRole::USER, $cases[1]);
        $this->assertSame(TestUserRole::GUEST, $cases[2]);
    }

    public function test_is_valid_returns_true_for_valid_backed_string_value(): void
    {
        $result = $this->enumService->isValid(TestUserStatus::class, 'active');

        $this->assertTrue($result);
    }

    public function test_is_valid_returns_false_for_invalid_backed_string_value(): void
    {
        $result = $this->enumService->isValid(TestUserStatus::class, 'invalid');

        $this->assertFalse($result);
    }

    public function test_is_valid_returns_true_for_valid_backed_int_value(): void
    {
        $result = $this->enumService->isValid(TestBackedIntEnum::class, 1);

        $this->assertTrue($result);
    }

    public function test_is_valid_returns_false_for_invalid_backed_int_value(): void
    {
        $result = $this->enumService->isValid(TestBackedIntEnum::class, 99);

        $this->assertFalse($result);
    }

    public function test_is_valid_returns_true_for_valid_pure_enum_name(): void
    {
        $result = $this->enumService->isValid(TestPureEnum::class, 'VALUE_ONE');

        $this->assertTrue($result);
    }

    public function test_is_valid_returns_false_for_invalid_pure_enum_name(): void
    {
        $result = $this->enumService->isValid(TestPureEnum::class, 'INVALID');

        $this->assertFalse($result);
    }

    public function test_from_value_returns_enum_for_valid_backed_string(): void
    {
        $enum = $this->enumService->fromValue(TestUserStatus::class, 'active');

        $this->assertSame(TestUserStatus::ACTIVE, $enum);
    }

    public function test_from_value_returns_null_for_invalid_backed_string(): void
    {
        $enum = $this->enumService->fromValue(TestUserStatus::class, 'invalid');

        $this->assertNull($enum);
    }

    public function test_from_value_returns_enum_for_valid_backed_int(): void
    {
        $enum = $this->enumService->fromValue(TestBackedIntEnum::class, 1);

        $this->assertSame(TestBackedIntEnum::VALUE_ONE, $enum);
    }

    public function test_from_value_returns_null_for_empty_string(): void
    {
        $enum = $this->enumService->fromValue(TestUserStatus::class, '');

        $this->assertNull($enum);
    }

    public function test_from_value_returns_null_for_invalid_backed_int(): void
    {
        $enum = $this->enumService->fromValue(TestBackedIntEnum::class, 99);

        $this->assertNull($enum);
    }

    public function test_from_value_returns_enum_for_valid_pure_enum_name(): void
    {
        $enum = $this->enumService->fromValue(TestPureEnum::class, 'VALUE_ONE');

        $this->assertSame(TestPureEnum::VALUE_ONE, $enum);
    }

    public function test_from_value_returns_null_for_invalid_pure_enum_name(): void
    {
        $enum = $this->enumService->fromValue(TestPureEnum::class, 'INVALID');

        $this->assertNull($enum);
    }

    public function test_from_returns_same_instance_when_source_is_already_enum(): void
    {
        $source = TestUserStatus::ACTIVE;
        $enum = $this->enumService->from(TestUserStatus::class, $source);

        $this->assertSame($source, $enum);
    }

    public function test_from_converts_string_to_backed_enum(): void
    {
        $enum = $this->enumService->from(TestUserStatus::class, 'active');

        $this->assertSame(TestUserStatus::ACTIVE, $enum);
    }

    public function test_from_converts_numeric_string_to_backed_int_enum(): void
    {
        $enum = $this->enumService->from(TestBackedIntEnum::class, '1');

        $this->assertSame(TestBackedIntEnum::VALUE_ONE, $enum);
    }

    public function test_from_converts_int_to_backed_int_enum(): void
    {
        $enum = $this->enumService->from(TestBackedIntEnum::class, 1);

        $this->assertSame(TestBackedIntEnum::VALUE_ONE, $enum);
    }

    public function test_from_converts_string_to_pure_enum(): void
    {
        $enum = $this->enumService->from(TestPureEnum::class, 'VALUE_ONE');

        $this->assertSame(TestPureEnum::VALUE_ONE, $enum);
    }

    public function test_from_converts_object_with_value_property_to_backed_enum(): void
    {
        $source = new class {
            public string $value = 'active';
        };

        $enum = $this->enumService->from(TestUserStatus::class, $source);

        $this->assertSame(TestUserStatus::ACTIVE, $enum);
    }

    public function test_from_converts_array_with_value_key_to_backed_enum(): void
    {
        $source = ['value' => 'active'];

        $enum = $this->enumService->from(TestUserStatus::class, $source);

        $this->assertSame(TestUserStatus::ACTIVE, $enum);
    }

    public function test_from_converts_object_with_name_property_to_pure_enum(): void
    {
        $source = new class {
            public string $name = 'VALUE_ONE';
        };

        $enum = $this->enumService->from(TestPureEnum::class, $source);

        $this->assertSame(TestPureEnum::VALUE_ONE, $enum);
    }

    public function test_from_throws_exception_for_empty_string_on_backed_enum(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Empty string is not a valid backing value for enum');

        $this->enumService->from(TestUserStatus::class, '');
    }

    public function test_from_throws_exception_for_invalid_string_on_backed_enum(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->enumService->from(TestUserStatus::class, 'invalid');
    }

    public function test_from_throws_exception_for_invalid_string_on_pure_enum(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->enumService->from(TestPureEnum::class, 'INVALID');
    }

    public function test_from_throws_exception_for_invalid_type(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->enumService->from(TestUserStatus::class, ['not', 'valid']);
    }

    public function test_validate_enum_class_throws_exception_for_non_enum(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('is not a valid Enum');

        $this->enumService->values('NotAnEnum');
    }

    public function test_values_with_single_case_enum(): void
    {
        $values = $this->enumService->values(TestSingleCaseEnum::class);

        $this->assertSame(['single'], $values);
    }

    public function test_from_value_with_numeric_string_for_backed_int(): void
    {
        $enum = $this->enumService->fromValue(TestBackedIntEnum::class, '2');

        $this->assertSame(TestBackedIntEnum::VALUE_TWO, $enum);
    }

    public function test_currency_enum_returns_correct_values(): void
    {
        $values = $this->enumService->values(TestCurrency::class);

        $this->assertSame(['EUR', 'USD', 'GBP'], $values);
    }

    public function test_life_stage_enum_has_twelve_cases(): void
    {
        $cases = $this->enumService->cases(TestLifeStage::class);

        $this->assertCount(12, $cases);
    }

    public function test_user_grade_enum_has_four_cases(): void
    {
        $values = $this->enumService->values(TestUserGrade::class);

        $this->assertSame([1, 2, 3, 4], $values);
    }
}
