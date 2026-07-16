<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Normalizers;

use AndyDefer\DomainStructures\Normalizers\Core\NormalizerInterface;

final class NormalizerChain
{
    private static ?NormalizerInterface $instance = null;

    private static bool $currentPreserveCase = false;

    private function __construct() {}

    public static function get(bool $preserveRecordCase = false): NormalizerInterface
    {
        // Si on change le paramètre, on recrée l'instance
        if (self::$instance === null || self::$currentPreserveCase !== $preserveRecordCase) {
            self::$instance = new RootNormalizer($preserveRecordCase);
            self::$currentPreserveCase = $preserveRecordCase;
        }

        return self::$instance;
    }
}
