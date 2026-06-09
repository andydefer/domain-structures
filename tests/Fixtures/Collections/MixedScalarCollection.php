<?php
// tests/Fixtures/Collections/MixedScalarCollection.php
declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Fixtures\Collections;

use AndyDefer\DomainStructures\Abstracts\AbstractTypedCollection;

/**
 * Collection qui mélange différents types scalaires (TEST D'ERREUR)
 */
final class MixedScalarCollection extends AbstractTypedCollection
{
    public function __construct()
    {
        parent::__construct('int', 'string', 'float', 'bool');
    }
}
