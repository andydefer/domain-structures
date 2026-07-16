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

    private bool $initialized = false;

    private bool $preserveRecordCase = false;

    public function __construct(bool $preserveRecordCase = false)
    {
        $this->preserveRecordCase = $preserveRecordCase;
    }

    private function initialize(): void
    {
        if ($this->initialized) {
            return;
        }

        // Créer tous les normaliseurs
        $null = new NullNormalizer;
        $scalar = new ScalarNormalizer;
        $enum = new EnumNormalizer;
        $dateTime = new DateTimeNormalizer;
        $record = new RecordNormalizer($this->preserveRecordCase);  // ← on passe le paramètre
        $vo = new ValueObjectNormalizer;
        $data = new DataNormalizer;
        $collection = new TypedCollectionNormalizer;
        $dataObject = new DataObjectNormalizer;
        $sequential = new SequentialNormalizer;
        $array = new ArrayNormalizer;
        $stdClass = new StdClassNormalizer;

        $normalizers = [
            $null,
            $scalar,
            $enum,
            $dateTime,
            $record,
            $vo,
            $data,
            $collection,
            $dataObject,
            $sequential,
            $array,
            $stdClass,
        ];

        // Configurer le normaliseur récursif pour chacun
        foreach ($normalizers as $normalizer) {
            if (method_exists($normalizer, 'setRecursiveNormalizer')) {
                $normalizer->setRecursiveNormalizer($this);
            }
        }

        $this->normalizers = $normalizers;
        $this->initialized = true;
    }

    public function supports(mixed $value): bool
    {
        return true;
    }

    public function normalize(mixed $value): mixed
    {
        $this->initialize();

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
