<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Utils;

/**
 * Associative - Normalise les clés en camelCase.
 *
 * Une fois construit, on ne peut pas le modifier.
 * Pour "modifier", on crée une nouvelle instance avec with(), merge() ou without().
 * Supporte l'accès par propriété (->) et par tableau ([]).
 * Supporte camelCase et snake_case (convertis en camelCase).
 *
 * @template T
 */
class Associative extends DataObject {}
