<?php

// src/Services/CollectionHydrationService.php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Services;

use AndyDefer\DomainStructures\Abstracts\AbstractTypedCollection;
use AndyDefer\DomainStructures\Configs\CollectionFamilyConfig;
use AndyDefer\DomainStructures\Enums\PhpType;
use InvalidArgumentException;
use RuntimeException;

final class CollectionHydrationService
{
    private ItemHydrationService $itemHydrationService;

    private CollectionFamilyConfig $familyConfig;

    public function __construct()
    {
        $this->itemHydrationService = new ItemHydrationService;
        $this->familyConfig = new CollectionFamilyConfig;
    }

    public function collect(
        iterable $sources,
        string $collectionClass = AbstractTypedCollection::class
    ): AbstractTypedCollection {
        if (! is_subclass_of($collectionClass, AbstractTypedCollection::class)) {
            throw new InvalidArgumentException(sprintf(
                'Collection class "%s" must extend %s',
                $collectionClass,
                AbstractTypedCollection::class
            ));
        }

        if ($sources instanceof $collectionClass) {
            return $sources;
        }

        try {
            $tempCollection = new $collectionClass;
        } catch (\ArgumentCountError $e) {
            throw new InvalidArgumentException(sprintf(
                'Collection class "%s" cannot be instantiated. It may require constructor arguments.',
                $collectionClass
            ));
        }

        $allowedTypes = $tempCollection->getAllowedTypes();

        if (empty($allowedTypes)) {
            throw new InvalidArgumentException(sprintf(
                'Collection class "%s" has no allowed types defined',
                $collectionClass
            ));
        }

        // Vérifier la cohérence des familles (incluant les scalaires)
        $this->validateFamilyConsistency($allowedTypes);

        $collection = new $collectionClass;

        foreach ($sources as $item) {
            // INTERDIRE LES COLLECTIONS IMBRIQUÉES
            if ($item instanceof AbstractTypedCollection) {
                throw new InvalidArgumentException(
                    'Nested collections are not allowed. Use flat collections instead.'
                );
            }

            $convertedItem = $this->convertItem($item, $allowedTypes);
            $collection->add($convertedItem);
        }

        return $collection;
    }

    public function collectFromJson(
        string $json,
        string $collectionClass = AbstractTypedCollection::class
    ): AbstractTypedCollection {
        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException(sprintf('Invalid JSON: %s', json_last_error_msg()));
        }

        if (! is_array($data) && ! is_object($data)) {
            throw new InvalidArgumentException('JSON must decode to an array or object for collection hydration');
        }

        if (is_object($data)) {
            $data = (array) $data;
        }

        return $this->collect($data, $collectionClass);
    }

    /**
     * Valide que tous les types autorisés appartiennent à la même famille
     * Pour les scalaires, ils doivent être du même type exact
     *
     * @param  array<string>  $allowedTypes
     *
     * @throws InvalidArgumentException
     */
    private function validateFamilyConsistency(array $allowedTypes): void
    {
        if (count($allowedTypes) <= 1) {
            return;
        }

        $families = [];

        foreach ($allowedTypes as $type) {
            $family = $this->getFamily($type);

            // Pour les scalaires, on utilise le type exact comme identifiant de famille
            if ($family === $this->familyConfig->scalar()) {
                $family = $type; // 'int', 'string', 'float', 'bool', 'null'
            }

            $families[$type] = $family;
        }

        $uniqueFamilies = array_unique($families);

        if (count($uniqueFamilies) > 1) {
            $familyGroups = [];
            foreach ($families as $type => $family) {
                $familyName = $this->familyConfig->getDisplayName($family);
                $familyGroups[$familyName][] = $type;
            }

            $groups = [];
            foreach ($familyGroups as $family => $types) {
                $groups[] = sprintf('%s: [%s]', $family, implode(', ', $types));
            }

            throw new InvalidArgumentException(sprintf(
                'Inconsistent families in collection. All allowed types must belong to the same family. Found: %s',
                implode(' | ', $groups)
            ));
        }
    }

    /**
     * Détermine la famille d'un type
     */
    private function getFamily(string $type): string
    {
        // Vérifier les types scalaires
        if (in_array($type, PhpType::getScalarTypeNames(), true)) {
            return $this->familyConfig->scalar();
        }

        // Vérifier les énumérations
        if (enum_exists($type)) {
            return $this->familyConfig->enum();
        }

        // Vérifier les abstractions de domaine
        if (is_subclass_of($type, $this->familyConfig->dataObject())) {
            return $this->familyConfig->dataObject();
        }

        if (is_subclass_of($type, $this->familyConfig->record())) {
            return $this->familyConfig->record();
        }

        if (is_subclass_of($type, $this->familyConfig->data())) {
            return $this->familyConfig->data();
        }

        if (is_subclass_of($type, $this->familyConfig->valueObject())) {
            return $this->familyConfig->valueObject();
        }

        throw new InvalidArgumentException(sprintf(
            'Type "%s" does not belong to any valid family (must be scalar, UnitEnum, AbstractValueObject, AbstractData, AbstractRecord, or AbstractDataObject)',
            $type
        ));
    }

    private function convertItem(mixed $item, array $allowedTypes): mixed
    {
        // Cas 1: L'item a un _type explicite
        if (is_array($item) && isset($item['_type'])) {
            $explicitType = $item['_type'];

            if (! in_array($explicitType, $allowedTypes, true)) {
                throw new InvalidArgumentException(sprintf(
                    'Type "%s" specified in "_type" is not allowed. Allowed: %s',
                    $explicitType,
                    implode('|', $allowedTypes)
                ));
            }

            // Extraire la valeur à hydrater
            $valueToHydrate = $item;

            // Si le tableau a exactement 2 clés ('_type' et 'value'), on prend uniquement la valeur
            if (count($item) === 2 && array_key_exists('value', $item)) {
                $valueToHydrate = $item['value'];
            } elseif (count($item) > 2) {
                // Si c'est un tableau complexe, on retire _type pour l'hydratation
                unset($valueToHydrate['_type']);
            }

            // Utiliser ItemHydrationService pour l'hydratation
            return $this->itemHydrationService->hydrate($explicitType, $valueToHydrate);
        }

        // Cas 2: Format simplifié {value: ...}
        if (is_array($item) && count($item) === 1 && isset($item['value'])) {
            $item = $item['value'];
        }

        // Cas 3: Un seul type autorisé
        if (count($allowedTypes) === 1) {
            return $this->itemHydrationService->hydrate($allowedTypes[0], $item);
        }

        // Cas 4: Multiples types autorisés - essayer chacun
        $lastException = null;
        foreach ($allowedTypes as $allowedType) {
            try {
                return $this->itemHydrationService->hydrate($allowedType, $item);
            } catch (\Exception $e) {
                $lastException = $e;

                continue;
            }
        }

        throw new InvalidArgumentException(sprintf(
            'Cannot convert item to any allowed type [%s]. Last error: %s',
            implode('|', $allowedTypes),
            $lastException?->getMessage()
        ), 0, $lastException);
    }
}
