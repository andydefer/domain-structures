<?php

declare(strict_types=1);

use AndyDefer\DomainStructures\Normalizers\NormalizerChain;
use AndyDefer\DomainStructures\Normalizers\RootNormalizer;

if (! function_exists('normalizer_chain')) {
    /**
     * Get the default DomainStructures normalizer instance.
     *
     * @param  bool  $preserveRecordCase  Whether to preserve record case sensitivity
     *
     * @example
     * $array = normalizer_chain()->normalize($userRecord);
     * $array = normalizer_chain(true)->normalize($valueObject);
     */
    function normalizer_chain(bool $preserveRecordCase = false): RootNormalizer
    {
        return NormalizerChain::get($preserveRecordCase);
    }
}
