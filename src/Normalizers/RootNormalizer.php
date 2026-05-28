<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Normalizers;

use AndyDefer\DomainStructures\Normalizers\Core\NormalizerInterface;
use RuntimeException;

final class RootNormalizer implements NormalizerInterface
{
    /** @var array<NormalizerInterface> */
    private array $normalizers = [];

    private ?NormalizerInterface $next = null;

    public function __construct()
    {
        $this->initializeNormalizers();
    }

    private function initializeNormalizers(): void
    {
        // Créer tous les normaliseurs
        $null = new NullNormalizer;
        $scalar = new ScalarNormalizer;
        $enum = new EnumNormalizer;
        $record = new RecordNormalizer;
        $vo = new ValueObjectNormalizer;
        $data = new DataNormalizer;
        $collection = new TypedCollectionNormalizer;
        $dataObject = new DataObjectNormalizer;
        $array = new ArrayNormalizer;

        // Configurer le normaliseur récursif pour chacun
        $normalizers = [$null, $scalar, $enum, $record, $vo, $data, $collection, $dataObject, $array];

        foreach ($normalizers as $normalizer) {
            if (method_exists($normalizer, 'setRecursiveNormalizer')) {
                $normalizer->setRecursiveNormalizer($this);
            }
        }

        $this->normalizers = $normalizers;
    }

    public function supports(mixed $value): bool
    {
        return true;
    }

    public function normalize(mixed $value): mixed
    {
        foreach ($this->normalizers as $normalizer) {
            if ($normalizer->supports($value)) {
                return $normalizer->normalize($value);
            }
        }

        throw new RuntimeException(sprintf(
            'No normalizer found for type %s',
            is_object($value) ? $value::class : gettype($value)
        ));
    }

    public function setNext(?NormalizerInterface $next): void
    {
        $this->next = $next;
    }
}
