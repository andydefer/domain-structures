<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Hydration;

use AndyDefer\DomainStructures\Hydration\Converter\ClassConverter;
use AndyDefer\DomainStructures\Hydration\Converter\EnumConverter;
use AndyDefer\DomainStructures\Hydration\Converter\ScalarConverter;
use AndyDefer\DomainStructures\Hydration\Converter\TransformableConverter;
use AndyDefer\DomainStructures\Hydration\Converter\TypeConverterInterface;
use AndyDefer\DomainStructures\Hydration\Strategy\EnumStrategy;
use AndyDefer\DomainStructures\Hydration\Strategy\HydrationStrategyInterface;
use AndyDefer\DomainStructures\Hydration\Strategy\InstanceStrategy;
use AndyDefer\DomainStructures\Hydration\Strategy\MultiParameterStrategy;
use AndyDefer\DomainStructures\Hydration\Strategy\SingleParameterStrategy;
use RuntimeException;

final class Hydrator
{
    /** @var array<HydrationStrategyInterface> */
    private static array $strategies = [];

    /** @var array<TypeConverterInterface> */
    private static array $converters = [];

    private static bool $initialized = false;

    private static function initialize(): void
    {
        if (self::$initialized) {
            return;
        }

        self::$converters = [
            new ScalarConverter,
            new EnumConverter,
            new TransformableConverter,
            new ClassConverter,
        ];

        self::$strategies = [
            new InstanceStrategy,
            new EnumStrategy,
            new SingleParameterStrategy(self::$converters),
            new MultiParameterStrategy(self::$converters),
        ];

        self::$initialized = true;
    }

    public static function hydrate(string $className, mixed $source): object
    {
        self::initialize();

        foreach (self::$strategies as $strategy) {
            if ($strategy->supports($className, $source)) {
                return $strategy->hydrate($className, $source);
            }
        }

        throw new RuntimeException(sprintf('No hydration strategy found for %s', $className));
    }
}
