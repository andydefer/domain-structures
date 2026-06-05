<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Unit\Hydration\Strategy;

use AndyDefer\DomainStructures\Collections\Utility\StringTypedCollection;
use AndyDefer\DomainStructures\Hydration\Strategy\ZeroParameterStrategy;
use AndyDefer\DomainStructures\Tests\Fixtures\Records\TestUserRecord;
use AndyDefer\DomainStructures\Tests\Fixtures\ValueObjects\TestEmailAddress;
use AndyDefer\DomainStructures\Tests\TestCase;
use AndyDefer\DomainStructures\Tests\Unit\Fixtures\Hydration\ConcreteClassWithConstructor;
use AndyDefer\DomainStructures\Tests\Unit\Fixtures\Hydration\NoConstructorClass;
use AndyDefer\DomainStructures\Utils\DataObject;

final class ZeroParameterStrategyTest extends TestCase
{
    private ZeroParameterStrategy $strategy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->strategy = new ZeroParameterStrategy;
    }

    // ==================== SUPPORTS METHOD TESTS ====================

    public function test_supports_returns_true_for_class_without_constructor(): void
    {
        $result = $this->strategy->supports(NoConstructorClass::class, null);
        $this->assertTrue($result);
    }

    public function test_supports_returns_true_for_class_with_zero_parameter_constructor(): void
    {
        // StringTypedCollection a un constructeur public function __construct()
        $result = $this->strategy->supports(StringTypedCollection::class, []);
        $this->assertTrue($result);
    }

    public function test_supports_returns_false_for_class_with_single_parameter_constructor(): void
    {
        $result = $this->strategy->supports(TestEmailAddress::class, 'test@example.com');
        $this->assertFalse($result);
    }

    public function test_supports_returns_false_for_class_with_constructor_parameter_with_default_value(): void
    {
        // DataObject a un constructeur avec 1 paramètre (array $data = [])
        $result = $this->strategy->supports(DataObject::class, []);
        $this->assertFalse($result);
    }

    public function test_supports_returns_false_for_class_with_multi_parameter_constructor(): void
    {
        $result = $this->strategy->supports(TestUserRecord::class, ['name' => 'John']);
        $this->assertFalse($result);
    }

    // ==================== HYDRATE METHOD TESTS ====================

    public function test_hydrate_creates_new_instance_for_class_without_constructor(): void
    {
        $result = $this->strategy->hydrate(NoConstructorClass::class, null);

        $this->assertInstanceOf(NoConstructorClass::class, $result);
    }

    public function test_hydrate_creates_multiple_instances_independently(): void
    {
        $first = $this->strategy->hydrate(NoConstructorClass::class, null);
        $second = $this->strategy->hydrate(NoConstructorClass::class, null);

        $this->assertInstanceOf(NoConstructorClass::class, $first);
        $this->assertInstanceOf(NoConstructorClass::class, $second);
        $this->assertNotSame($first, $second);
    }

    public function test_hydrate_works_with_any_source_type(): void
    {
        $sources = [null, [], 'string', 42, 3.14, true];

        foreach ($sources as $source) {
            $result = $this->strategy->hydrate(NoConstructorClass::class, $source);
            $this->assertInstanceOf(NoConstructorClass::class, $result);
        }
    }

    // ==================== EDGE CASES TESTS ====================

    public function test_hydrate_creates_instance_for_concrete_class_without_constructor(): void
    {
        $result = $this->strategy->hydrate(ConcreteClassWithConstructor::class, null);

        $this->assertInstanceOf(ConcreteClassWithConstructor::class, $result);
    }

    public function test_hydrate_is_idempotent_returns_different_instances(): void
    {
        $first = $this->strategy->hydrate(NoConstructorClass::class, null);
        $second = $this->strategy->hydrate(NoConstructorClass::class, null);

        $this->assertNotSame($first, $second);
        $this->assertEquals($first, $second);
    }
}
