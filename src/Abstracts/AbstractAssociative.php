<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Abstracts;

/**
 * AbstractDataObject - Classe de base immutable pour les objets de données.
 *
 * Une fois construit, on ne peut pas le modifier.
 * Pour "modifier", on crée une nouvelle instance avec with(), merge() ou without().
 * Supporte l'accès par propriété (->) et par tableau ([]).
 *
 * @template T
 */
abstract class AbstractAssociative extends AbstractDataObject {}
