<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Normalizers;

final class NormalizerChain
{
    private static ?NormalizerInterface $instance = null;

    private function __construct() {}

    public static function get(): NormalizerInterface
    {
        if (self::$instance === null) {
            self::$instance = self::buildChain();
        }

        return self::$instance;
    }

    private static function buildChain(): NormalizerInterface
    {
        $null = new NullNormalizer;
        $scalar = new ScalarNormalizer;
        $enum = new EnumNormalizer;
        $record = new RecordNormalizer;
        $vo = new ValueObjectNormalizer;
        $data = new DataNormalizer;
        $collection = new TypedCollectionNormalizer;
        $stdClass = new StdClassNormalizer;
        $array = new ArrayNormalizer;

        $null->setNext($scalar);
        $scalar->setNext($enum);
        $enum->setNext($record);
        $record->setNext($vo);
        $vo->setNext($data);
        $data->setNext($collection);
        $collection->setNext($stdClass);
        $stdClass->setNext($array);

        return $null;
    }
}
