<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Normalizers;

use AndyDefer\DomainStructures\Normalizers\Core\NormalizerInterface;

final class NormalizerChain
{
    private static ?NormalizerInterface $instance = null;

    private function __construct() {}

    public static function get(): NormalizerInterface
    {
        if (self::$instance === null) {
            self::$instance = new RootNormalizer();
        }

        return self::$instance;
    }
}
