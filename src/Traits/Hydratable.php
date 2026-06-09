<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Traits;

use AndyDefer\DomainStructures\Abstracts\AbstractTypedCollection;
use AndyDefer\DomainStructures\Collections\Core\TypedCollection;
use AndyDefer\DomainStructures\Hydration\Hydrator;
use InvalidArgumentException;
use RuntimeException;

/**
 * @deprecated Ce trait est déprécié depuis la version 2.0.0.
 *             Il sera supprimé dans la version 3.0.0.
 * 
 * RAISONS DE LA DÉPRÉCIATION :
 * 
 * 1. MÉTHODES STATIQUES DANS UN TRAIT - Anti-pattern majeur
 *    Les méthodes statiques dans un trait créent un couplage implicite.
 *    On ne peut pas les mocker, les remplacer ou les tester isolément.
 * 
 * 2. COUPLAGE FORT À HYDRAFTER - Dépendance cachée
 *    Le trait dépend du singleton Hydrator sans injection.
 *    Impossible de changer d'hydrateur ou de le mocker.
 * 
 * 3. SINGLETON HYDRAFTER - Anti-pattern global
 *    Hydrator::hydrate() est un singleton global.
 *    Crée un état global, difficile à tester.
 * 
 * 4. RESPONSABILITÉS MULTIPLES - Violation du SRP
 *    Un Value Object ne devrait pas savoir comment s'hydrater lui-même.
 *    L'hydratation est une responsabilité transverse qui appartient à un Service.
 * 
 * 5. COMPORTEMENT IMPLICITE - Magie cachée
 *    Les méthodes from(), fromJson(), collect() apparaissent partout.
 *    On ne sait pas qu'elles viennent d'un trait.
 * 
 * 6. DIFFICULTÉ DE TEST - Pas de mock possible
 *    Les méthodes statiques ne peuvent pas être mockées.
 *    Test impossible sans l'hydrateur réel.
 * 
 * 7. AMBIGUÏTÉ DES COLLECTIONS - Typage dynamique dangereux
 *    collect() utilise le typage dynamique, source d'erreurs.
 *    La collectionClass peut être n'importe quoi.
 * 
 * 8. DUPLICATION INDIRECTE - Chaque classe Value Object a les mêmes méthodes
 *    Mais on ne peut pas factoriser la logique métier de ces méthodes.
 * 
 * ✅ NOUVELLE APPROCHE : Utilisez des Services d'hydratation dédiés.
 * 
 * @example
 * // ❌ À ÉVITER (déprécié)
 * final class EmailAddress extends AbstractValueObject
 * {
 *     use Hydratable;
 * 
 *     public function __construct(public readonly string $value) {}
 * }
 * 
 * $email = EmailAddress::from('user@example.com');      // Statique, magique
 * $email = EmailAddress::fromJson('"user@example.com"'); // Statique, magique
 * $collection = EmailAddress::collect(['a@b.com']);      // Statique, magique
 * 
 * // ✅ RECOMMANDÉ (nouvelle approche)
 * 
 * // 1. Record simple (PHP 8.0+)
 * readonly class EmailAddress
 * {
 *     public function __construct(public readonly string $value) {}
 * }
 * 
 * // 2. Factory Service dédié
 * final class EmailAddressFactory
 * {
 *     public function create(string $value): EmailAddress
 *     {
 *         if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
 *             throw new InvalidArgumentException("Invalid email: {$value}");
 *         }
 *         return new EmailAddress($value);
 *     }
 * 
 *     public function createFromJson(string $json): EmailAddress
 *     {
 *         $data = json_decode($json, true);
 *         if (json_last_error() !== JSON_ERROR_NONE) {
 *             throw new RuntimeException('Invalid JSON');
 *         }
 *         return $this->create($data);
 *     }
 * }
 * 
 * // 3. Collection Service dédié
 * final class EmailAddressCollectionService
 * {
 *     public function __construct(
 *         private readonly EmailAddressFactory $factory
 *     ) {}
 * 
 *     public function collect(iterable $sources): EmailAddressCollection
 *     {
 *         $collection = new EmailAddressCollection();
 *         foreach ($sources as $source) {
 *             $collection->add($this->factory->create($source));
 *         }
 *         return $collection;
 *     }
 * }
 * 
 * // 4. Hydrator Service dédié (DIP)
 * interface HydratorInterface
 * {
 *     public function hydrate(string $class, mixed $source): object;
 * }
 * 
 * final class GenericHydrator implements HydratorInterface
 * {
 *     public function __construct(
 *         private readonly ContainerInterface $container
 *     ) {}
 * 
 *     public function hydrate(string $class, mixed $source): object
 *     {
 *         // Logique d'hydratation injectable et testable
 *     }
 * }
 * 
 * // Usage explicite, testable, découplé
 * $factory = new EmailAddressFactory();
 * $email = $factory->create('user@example.com');
 * $email = $factory->createFromJson('"user@example.com"');
 * $collection = (new EmailAddressCollectionService($factory))->collect(['a@b.com']);
 * 
 * // Ou via injection de dépendances
 * final class UserService
 * {
 *     public function __construct(
 *         private readonly EmailAddressFactory $emailFactory,
 *         private readonly EmailAddressCollectionService $collectionService,
 *     ) {}
 * }
 * 
 * @author Andy Defer
 * @deprecated since 2.0.0, will be removed in 3.0.0
 */
trait Hydratable
{
    /**
     * Creates an instance from a source.
     * 
     * @deprecated Utilisez une Factory Service injectée à la place.
     *             Les méthodes statiques ne sont pas testables ni mockables.
     * 
     * @param  mixed  $source  The source data (string, array, object, DataObject, or JSON)
     * @throws RuntimeException|InvalidArgumentException
     */
    public static function from(mixed $source): static
    {
        // Déclencher une erreur de dépréciation
        @trigger_error(
            sprintf(
                'L\'utilisation de %s::from() est dépréciée depuis la version 2.0.0. ' .
                    'Cette méthode sera supprimée dans la version 3.0.0. ' .
                    'Utilisez une Factory Service injectée à la place.',
                static::class
            ),
            E_USER_DEPRECATED
        );

        return Hydrator::hydrate(static::class, $source);
    }

    /**
     * Creates an instance from a JSON string.
     * 
     * @deprecated Utilisez une Factory Service avec une méthode createFromJson().
     * 
     * @param  string  $json  JSON string
     * @throws RuntimeException If JSON is invalid
     */
    public static function fromJson(string $json): static
    {
        @trigger_error(
            sprintf(
                'L\'utilisation de %s::fromJson() est dépréciée depuis la version 2.0.0. ' .
                    'Cette méthode sera supprimée dans la version 3.0.0. ' .
                    'Utilisez une Factory Service avec une méthode createFromJson() à la place.',
                static::class
            ),
            E_USER_DEPRECATED
        );

        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException(sprintf(
                'Invalid JSON: %s',
                json_last_error_msg()
            ));
        }

        return static::from($data);
    }

    /**
     * Hydrates a collection of sources into a typed collection.
     * 
     * @deprecated Utilisez un Collection Service dédié à la place.
     *             Le typage dynamique est dangereux et difficile à maintenir.
     * 
     * @template TCollection of AbstractTypedCollection
     * @param  iterable<mixed>  $sources
     * @param  class-string<TCollection>  $collectionClass
     * @return TCollection
     * @throws InvalidArgumentException
     */
    public static function collect(iterable $sources, string $collectionClass = TypedCollection::class): AbstractTypedCollection
    {
        @trigger_error(
            sprintf(
                'L\'utilisation de %s::collect() est dépréciée depuis la version 2.0.0. ' .
                    'Cette méthode sera supprimée dans la version 3.0.0. ' .
                    'Utilisez un Collection Service dédié à la place.',
                static::class
            ),
            E_USER_DEPRECATED
        );

        if (! is_subclass_of($collectionClass, AbstractTypedCollection::class)) {
            throw new InvalidArgumentException(sprintf(
                'Collection class "%s" must extend %s',
                $collectionClass,
                AbstractTypedCollection::class
            ));
        }

        $allowedTypes = method_exists(static::class, 'getAllowedTypes')
            ? static::getAllowedTypes()
            : [static::class];

        $collection = new $collectionClass(...$allowedTypes);

        foreach ($sources as $source) {
            $collection->add(static::from($source));
        }

        return $collection;
    }
}
