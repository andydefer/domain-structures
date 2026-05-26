<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\TypeDetectors;

final class TypeDetectorChain
{
    private static ?TypeDetectorInterface $instance = null;

    /** @var array<TypeDetectorInterface> */
    private array $detectors = [];

    private function __construct()
    {
        $this->detectors = [
            new ScalarTypeDetector,
            new EnumTypeDetector,
            new RecordTypeDetector,
            new ValueObjectTypeDetector,
            new DataTypeDetector,
            new TypedCollectionTypeDetector,
            new StdClassTypeDetector,
            new DefaultTypeDetector,
        ];
    }

    public static function get(): self
    {
        if (self::$instance === null) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    public function detect(mixed $value): TypeDetectorInterface
    {
        foreach ($this->detectors as $detector) {
            if ($detector->supports($value)) {
                return $detector;
            }
        }

        return new DefaultTypeDetector;
    }

    public function getTypeName(mixed $value): string
    {
        return $this->detect($value)->getTypeName($value);
    }

    public function getClassString(mixed $value): string
    {
        return $this->detect($value)->getClassString($value);
    }
}
