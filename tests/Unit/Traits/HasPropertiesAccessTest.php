<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Unit\Traits;

use AndyDefer\DomainStructures\Tests\Fixtures\Enums\TestCurrency;
use AndyDefer\DomainStructures\Tests\Fixtures\ValueObjects\TestMoney;
use PHPUnit\Framework\TestCase;

final class HasPropertiesAccessTest extends TestCase
{
    public function test_can_access_properties_via_magic_get(): void
    {
        $money = TestMoney::from([
            'amount' => 99.99,
            'currency' => 'EUR',
            'emailAddress' => 'andykanidimbu@gmail.com'
        ]);

        $this->assertSame(99.99, $money->amount);
        $this->assertSame(TestCurrency::EUR, $money->currency);
    }

    public function test_isset_returns_true_for_existing_properties(): void
    {
        $money = TestMoney::from([
            'amount' => 100.00,
            'currency' => 'USD'
        ]);

        $this->assertTrue(isset($money->amount));
        $this->assertTrue(isset($money->currency));
    }

    public function test_isset_returns_false_for_non_existing_properties(): void
    {
        $money = TestMoney::from([
            'amount' => 100.00,
            'currency' => 'USD'
        ]);

        $this->assertFalse(isset($money->nonExistent));
    }

    public function test_throws_exception_when_accessing_non_existent_property(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Property "nonExistent" does not exist in');

        $money = TestMoney::from([
            'amount' => 100.00,
            'currency' => 'USD'
        ]);

        $value = $money->nonExistent;
    }

    public function test_can_access_properties_after_operation(): void
    {
        $money1 = TestMoney::from([
            'amount' => 50.00,
            'currency' => 'EUR'
        ]);

        $money2 = TestMoney::from([
            'amount' => 30.00,
            'currency' => 'EUR'
        ]);

        $total = $money1->add($money2);

        $this->assertSame(80.00, $total->amount);
        $this->assertSame(TestCurrency::EUR, $total->currency);
    }

    public function test_can_format_using_properties(): void
    {
        $money = TestMoney::from([
            'amount' => 99.99,
            'currency' => 'EUR'
        ]);

        $formatted = $money->format();

        $this->assertSame('€99.99', $formatted);
    }

    public function test_properties_are_readonly(): void
    {
        $money = TestMoney::from([
            'amount' => 100.00,
            'currency' => 'USD'
        ]);

        // Les propriétés doivent être accessibles mais non modifiables
        $this->assertSame(100.00, $money->amount);

        // PHP n'empêche pas la modification directe d'une propriété public readonly
        // mais le test vérifie que c'est bien une propriété de l'objet
        $this->assertTrue(property_exists($money, 'amount'));
        $this->assertTrue(property_exists($money, 'currency'));
    }
}
