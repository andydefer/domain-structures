<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Fixtures\Collections;

use AndyDefer\DomainStructures\Abstracts\AbstractTypedCollection;
use AndyDefer\DomainStructures\Tests\Fixtures\ValueObjects\TestEmailAddress;

/**
 * Type-safe collection for Email Value Objects.
 *
 * @extends AbstractTypedCollection<TestEmailAddress>
 */
final class TestEmailCollection extends AbstractTypedCollection
{
    public function __construct()
    {
        parent::__construct(TestEmailAddress::class);
    }

    /**
     * Returns a new collection with unique email values (case-insensitive).
     */
    public function unique(): self
    {
        $seen = [];
        $result = new self;

        foreach ($this->items as $email) {
            $value = strtolower($email->getValue());
            if (! in_array($value, $seen, true)) {
                $seen[] = $value;
                $result->add($email);
            }
        }

        return $result;
    }

    /**
     * Returns a new collection with emails from a specific domain.
     */
    public function fromDomain(string $domain): self
    {
        return $this->filter(fn (TestEmailAddress $email) => str_ends_with($email->getValue(), '@'.$domain));
    }

    /**
     * Returns an array of domains extracted from emails.
     *
     * @return array<int, string>
     */
    public function getDomains(): array
    {
        return $this->map(fn (TestEmailAddress $email) => explode('@', $email->getValue())[1])->toArray();
    }

    /**
     * Returns a new collection with unique domains.
     *
     * @return array<int, string>
     */
    public function getUniqueDomains(): array
    {
        return array_values(array_unique($this->getDomains()));
    }

    /**
     * Returns a new collection with emails that have a specific domain.
     */
    public function withDomain(string $domain): self
    {
        return $this->filter(fn (TestEmailAddress $email) => str_ends_with($email->getValue(), '@'.$domain));
    }
}
