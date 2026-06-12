<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Collections\Utility;

use AndyDefer\DomainStructures\Abstracts\AbstractTypedCollection;

/**
 * Type-safe collection for string values.
 *
 * Provides specialized string manipulation methods including case conversion,
 * substring filtering, trimming, truncation, pattern matching, and string
 * transformations.
 *
 * @extends AbstractTypedCollection<string>
 */
final class StringTypedCollection extends AbstractTypedCollection
{
    public function __construct()
    {
        parent::__construct('string');
    }

    /**
     * Convert all strings to lowercase.
     *
     * @return self New collection with all strings in lowercase
     */
    public function toLowercase(): self
    {
        $result = new self;
        foreach ($this->items as $item) {
            $result->add(mb_strtolower($item));
        }

        return $result;
    }

    /**
     * Convert all strings to uppercase.
     *
     * @return self New collection with all strings in uppercase
     */
    public function toUppercase(): self
    {
        $result = new self;
        foreach ($this->items as $item) {
            $result->add(mb_strtoupper($item));
        }

        return $result;
    }

    /**
     * Filter strings that contain a specific substring.
     *
     * @param  string  $search  Substring to search for
     * @return self New collection containing only strings that contain the substring
     */
    public function containsSubstring(string $search): self
    {
        $result = new self;
        foreach ($this->items as $item) {
            if (str_contains($item, $search)) {
                $result->add($item);
            }
        }

        return $result;
    }

    /**
     * Filter strings that start with a specific prefix.
     *
     * @param  string  $prefix  Prefix to check at the beginning of each string
     * @return self New collection containing only strings that start with the prefix
     */
    public function startsWith(string $prefix): self
    {
        $result = new self;
        foreach ($this->items as $item) {
            if (str_starts_with($item, $prefix)) {
                $result->add($item);
            }
        }

        return $result;
    }

    /**
     * Filter strings that end with a specific suffix.
     *
     * @param  string  $suffix  Suffix to check at the end of each string
     * @return self New collection containing only strings that end with the suffix
     */
    public function endsWith(string $suffix): self
    {
        $result = new self;
        foreach ($this->items as $item) {
            if (str_ends_with($item, $suffix)) {
                $result->add($item);
            }
        }

        return $result;
    }

    /**
     * Remove empty strings from the collection.
     *
     * Filters out empty strings (''). Note that strings containing only whitespace
     * are NOT considered empty by this method.
     *
     * @return self New collection with empty strings removed
     */
    public function filterEmpty(): self
    {
        $result = new self;
        foreach ($this->items as $item) {
            if ($item !== '') {
                $result->add($item);
            }
        }

        return $result;
    }

    /**
     * Trim whitespace (or other characters) from the beginning and end of each string.
     *
     * @param  string  $characters  Optional characters to trim (default: whitespace)
     * @return self New collection with trimmed strings
     */
    public function trim(string $characters = " \n\r\t\v\0"): self
    {
        $result = new self;
        foreach ($this->items as $item) {
            $result->add(trim($item, $characters));
        }

        return $result;
    }

    /**
     * Truncate strings to a specified maximum length.
     *
     * If a string exceeds the maximum length, it is cut and the suffix is appended.
     * Strings shorter than the limit remain unchanged.
     *
     * @param  int  $length  Maximum length of the resulting string
     * @param  string  $suffix  Suffix to append to truncated strings (default: empty string)
     * @return self New collection with truncated strings
     */
    public function truncate(int $length, string $suffix = ''): self
    {
        $result = new self;
        foreach ($this->items as $item) {
            if (mb_strlen($item) <= $length) {
                $result->add($item);
            } else {
                $truncated = mb_substr($item, 0, $length);
                if ($suffix !== '') {
                    $truncated .= $suffix;
                }
                $result->add($truncated);
            }
        }

        return $result;
    }

    /**
     * Filter strings matching a regular expression pattern.
     *
     * @param  string  $pattern  Regular expression pattern to match (PCRE format)
     * @return self New collection containing only strings matching the pattern
     *
     * @throws \InvalidArgumentException If the pattern is invalid
     */
    public function matchingRegex(string $pattern): self
    {
        if (@preg_match($pattern, '') === false) {
            throw new \InvalidArgumentException(sprintf('Invalid regular expression pattern: "%s"', $pattern));
        }

        $result = new self;
        foreach ($this->items as $item) {
            if (preg_match($pattern, $item) === 1) {
                $result->add($item);
            }
        }

        return $result;
    }

    /**
     * Join all strings with a separator.
     *
     * @param  string  $separator  Separator between strings (default: empty string)
     * @return string Combined string with all items joined
     */
    public function join(string $separator = ''): string
    {
        return implode($separator, $this->items);
    }

    /**
     * Get the length of each string.
     *
     * @return IntTypedCollection Collection of string lengths
     */
    public function lengths(): IntTypedCollection
    {
        $result = new IntTypedCollection;
        foreach ($this->items as $item) {
            $result->add(mb_strlen($item));
        }

        return $result;
    }

    /**
     * Pad each string to a certain length.
     *
     * @param  int  $length  Length to pad to
     * @param  string  $padString  String to use for padding (default: space)
     * @param  int  $padType  Type of padding (STR_PAD_RIGHT, STR_PAD_LEFT, STR_PAD_BOTH)
     * @return self New collection with padded strings
     */
    public function pad(int $length, string $padString = ' ', int $padType = STR_PAD_RIGHT): self
    {
        $result = new self;
        foreach ($this->items as $item) {
            $result->add(str_pad($item, $length, $padString, $padType));
        }

        return $result;
    }

    /**
     * Replace all occurrences of a search string with a replacement.
     *
     * @param  string|array<string>  $search  Value(s) to search for
     * @param  string|array<string>  $replace  Value(s) to replace with
     * @return self New collection with replaced strings
     */
    public function replace(string|array $search, string|array $replace): self
    {
        $result = new self;
        foreach ($this->items as $item) {
            $result->add(str_replace($search, $replace, $item));
        }

        return $result;
    }

    /**
     * Get the first character of each string.
     *
     * @return self New collection with first characters
     */
    public function firstCharacter(): self
    {
        $result = new self;
        foreach ($this->items as $item) {
            $result->add(mb_substr($item, 0, 1));
        }

        return $result;
    }

    /**
     * Get the last character of each string.
     *
     * @return self New collection with last characters
     */
    public function lastCharacter(): self
    {
        $result = new self;
        foreach ($this->items as $item) {
            $result->add(mb_substr($item, -1, 1));
        }

        return $result;
    }

    /**
     * Extract a substring from each string.
     *
     * @param  int  $offset  Starting position (negative for counting from end)
     * @param  int|null  $length  Maximum length of the substring (null for rest of string)
     * @return self New collection with extracted substrings
     */
    public function substring(int $offset, ?int $length = null): self
    {
        $result = new self;
        foreach ($this->items as $item) {
            if ($length === null) {
                $result->add(mb_substr($item, $offset));
            } else {
                $result->add(mb_substr($item, $offset, $length));
            }
        }

        return $result;
    }

    /**
     * Count how many strings match a regular expression pattern.
     *
     * @param  string  $pattern  Regular expression pattern to match
     * @return int Number of strings matching the pattern
     */
    public function countMatchingRegex(string $pattern): int
    {
        return $this->matchingRegex($pattern)->count();
    }

    /**
     * Check if any string matches a regular expression pattern.
     *
     * @param  string  $pattern  Regular expression pattern to match
     * @return bool True if at least one string matches the pattern
     */
    public function hasMatchingRegex(string $pattern): bool
    {
        return $this->matchingRegex($pattern)->isNotEmpty();
    }

    /**
     * Get all unique strings (case-insensitive).
     *
     * @return self New collection with case-insensitive unique values
     */
    public function uniqueCaseInsensitive(): self
    {
        $seen = [];
        $result = new self;

        foreach ($this->items as $item) {
            $lowercase = mb_strtolower($item);
            if (! in_array($lowercase, $seen, true)) {
                $seen[] = $lowercase;
                $result->add($item);
            }
        }

        return $result;
    }

    /**
     * Sort strings case-insensitively with deterministic order.
     *
     * @param  bool  $descending  Whether to sort in descending order
     * @return self New collection with case-insensitive sort
     */
    public function sortCaseInsensitive(bool $descending = false): self
    {
        $items = $this->items;

        usort($items, function ($a, $b) {
            // D'abord, comparer insensible à la casse
            $cmp = strcasecmp($a, $b);

            if ($cmp !== 0) {
                return $cmp;
            }

            // Si égaux insensible à la casse, trier par ordre ASCII
            // pour avoir un ordre déterministe: majuscules avant minuscules
            // (ex: 'Banana' avant 'banana' car 'B' (66) < 'b' (98))
            return strcmp($a, $b);
        });

        if ($descending) {
            $items = array_reverse($items);
        }

        $result = new self;
        foreach ($items as $item) {
            $result->add($item);
        }

        return $result;
    }

    /**
     * Remove all whitespace from each string.
     *
     * @return self New collection with whitespace removed
     */
    public function removeWhitespace(): self
    {
        $result = new self;
        foreach ($this->items as $item) {
            $cleaned = preg_replace('/\s+/', '', $item);
            $result->add($cleaned);
        }

        return $result;
    }

    /**
     * Slugify each string (convert to URL-friendly format).
     *
     * Converts strings to lowercase, removes special characters, and replaces
     * spaces with hyphens.
     *
     * @return self New collection with slugified strings
     */
    public function slugify(): self
    {
        $result = new self;
        foreach ($this->items as $item) {
            $slug = mb_strtolower($item);
            $slug = preg_replace('/[^a-z0-9]+/u', '-', $slug);
            $result->add(trim($slug, '-'));
        }

        return $result;
    }

    /**
     * Wrap each string with a prefix and suffix.
     *
     * @param  string  $prefix  String to add at the beginning
     * @param  string  $suffix  String to add at the end (defaults to prefix if not provided)
     * @return self New collection with wrapped strings
     */
    public function wrap(string $prefix, ?string $suffix = null): self
    {
        $suffix = $suffix ?? $prefix;
        $result = new self;
        foreach ($this->items as $item) {
            $result->add($prefix . $item . $suffix);
        }

        return $result;
    }

    /**
     * Remove a prefix from each string if it exists.
     *
     * @param  string  $prefix  Prefix to remove
     * @return self New collection with prefix removed
     */
    public function removePrefix(string $prefix): self
    {
        $result = new self;
        foreach ($this->items as $item) {
            if (str_starts_with($item, $prefix)) {
                $result->add(mb_substr($item, mb_strlen($prefix)));
            } else {
                $result->add($item);
            }
        }

        return $result;
    }

    /**
     * Remove a suffix from each string if it exists.
     *
     * @param  string  $suffix  Suffix to remove
     * @return self New collection with suffix removed
     */
    public function removeSuffix(string $suffix): self
    {
        $result = new self;
        foreach ($this->items as $item) {
            if (str_ends_with($item, $suffix)) {
                $result->add(mb_substr($item, 0, -mb_strlen($suffix)));
            } else {
                $result->add($item);
            }
        }

        return $result;
    }
}
