<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Unit\Collections\Utility;

use AndyDefer\DomainStructures\Collections\Utility\IntTypedCollection;
use AndyDefer\DomainStructures\Collections\Utility\StringTypedCollection;
use AndyDefer\DomainStructures\Tests\TestCase;

/**
 * Unit tests for StringTypedCollection class.
 *
 * This test suite validates the string-specific collection functionality:
 * - Type safety (only string values allowed)
 * - Case conversion (toLowercase, toUppercase)
 * - Substring filtering (containsSubstring, startsWith, endsWith)
 * - Trimming and truncation
 * - Pattern matching with regex
 * - String manipulation (join, pad, replace, wrap)
 * - Substring extraction
 * - Sorting (case-insensitive)
 * - Slugify and whitespace removal
 * - Prefix/suffix removal
 *
 * The tests follow the AAA pattern (Arrange, Act, Assert).
 */
final class StringTypedCollectionTest extends TestCase
{
    // ==================== CONSTRUCTOR TESTS ====================

    /**
     * Test that StringTypedCollection constructor sets string as allowed type.
     */
    public function test_constructor_sets_string_as_allowed_type(): void
    {
        // Arrange & Act
        $collection = new StringTypedCollection;

        // Assert
        $this->assertSame(['string'], $collection->getAllowedTypes());
    }

    /**
     * Test that StringTypedCollection accepts only string values.
     */
    public function test_collection_accepts_only_string_values(): void
    {
        // Arrange
        $collection = new StringTypedCollection;

        // Act
        $collection->add('hello', 'world', 'test');

        // Assert
        $this->assertCount(3, $collection);
        $this->assertSame('hello', $collection[0]);
        $this->assertSame('world', $collection[1]);
        $this->assertSame('test', $collection[2]);
    }

    /**
     * Test that collection rejects non-string values.
     */
    public function test_collection_rejects_non_string_values(): void
    {
        // Arrange
        $collection = new StringTypedCollection;

        // Act & Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected type(s) string');

        $collection->add(42);
    }

    // ==================== TO_LOWERCASE / TO_UPPERCASE TESTS ====================

    /**
     * Test that toLowercase converts all strings to lowercase.
     */
    public function test_to_lowercase_converts_all_strings_to_lowercase(): void
    {
        // Arrange
        $collection = new StringTypedCollection;
        $collection->add('HELLO', 'World', 'TeSt', 'UPPER');

        // Act
        $lowercase = $collection->toLowercase();

        // Assert
        $this->assertSame(['hello', 'world', 'test', 'upper'], $lowercase->toArray());
    }

    /**
     * Test that toUppercase converts all strings to uppercase.
     */
    public function test_to_uppercase_converts_all_strings_to_uppercase(): void
    {
        // Arrange
        $collection = new StringTypedCollection;
        $collection->add('hello', 'World', 'TeSt', 'lower');

        // Act
        $uppercase = $collection->toUppercase();

        // Assert
        $this->assertSame(['HELLO', 'WORLD', 'TEST', 'LOWER'], $uppercase->toArray());
    }

    /**
     * Test that case conversions return new collection instance.
     */
    public function test_case_conversions_return_new_collection_instance(): void
    {
        // Arrange
        $collection = new StringTypedCollection;
        $collection->add('Test');

        // Act
        $lowercase = $collection->toLowercase();
        $uppercase = $collection->toUppercase();

        // Assert
        $this->assertNotSame($collection, $lowercase);
        $this->assertNotSame($collection, $uppercase);
    }

    // ==================== CONTAINS_SUBSTRING TESTS ====================

    /**
     * Test that containsSubstring filters strings containing the substring.
     */
    public function test_contains_substring_filters_strings_containing_substring(): void
    {
        // Arrange
        $collection = new StringTypedCollection;
        $collection->add('apple', 'banana', 'grape', 'pineapple', 'orange');

        // Act
        $result = $collection->containsSubstring('apple');

        // Assert
        $this->assertSame(['apple', 'pineapple'], $result->toArray());
        $this->assertCount(2, $result);
    }

    /**
     * Test that containsSubstring returns empty when no matches.
     */
    public function test_contains_substring_returns_empty_when_no_matches(): void
    {
        // Arrange
        $collection = new StringTypedCollection;
        $collection->add('cat', 'dog', 'bird');

        // Act
        $result = $collection->containsSubstring('fish');

        // Assert
        $this->assertCount(0, $result);
        $this->assertTrue($result->isEmpty());
    }

    /**
     * Test that containsSubstring is case-sensitive.
     */
    public function test_contains_substring_is_case_sensitive(): void
    {
        // Arrange
        $collection = new StringTypedCollection;
        $collection->add('Apple', 'apple', 'APPLE');

        // Act
        $result = $collection->containsSubstring('apple');

        // Assert
        $this->assertSame(['apple'], $result->toArray());
    }

    // ==================== STARTS_WITH TESTS ====================

    /**
     * Test that startsWith filters strings starting with prefix.
     */
    public function test_starts_with_filters_strings_starting_with_prefix(): void
    {
        // Arrange
        $collection = new StringTypedCollection;
        $collection->add('apple', 'apricot', 'banana', 'avocado', 'grape');

        // Act
        $result = $collection->startsWith('ap');

        // Assert
        $this->assertSame(['apple', 'apricot'], $result->toArray());
        $this->assertCount(2, $result);
    }

    /**
     * Test that startsWith returns empty when no matches.
     */
    public function test_starts_with_returns_empty_when_no_matches(): void
    {
        // Arrange
        $collection = new StringTypedCollection;
        $collection->add('cat', 'dog', 'bird');

        // Act
        $result = $collection->startsWith('x');

        // Assert
        $this->assertCount(0, $result);
    }

    // ==================== ENDS_WITH TESTS ====================

    /**
     * Test that endsWith filters strings ending with suffix.
     */
    public function test_ends_with_filters_strings_ending_with_suffix(): void
    {
        // Arrange
        $collection = new StringTypedCollection;
        $collection->add('apple', 'pineapple', 'grape', 'maple', 'table');

        // Act
        $result = $collection->endsWith('ple');

        // Assert
        $this->assertSame(['apple', 'pineapple', 'maple'], $result->toArray());
        $this->assertCount(3, $result);
    }

    /**
     * Test that endsWith returns empty when no matches.
     */
    public function test_ends_with_returns_empty_when_no_matches(): void
    {
        // Arrange
        $collection = new StringTypedCollection;
        $collection->add('cat', 'dog', 'bird');

        // Act
        $result = $collection->endsWith('xyz');

        // Assert
        $this->assertCount(0, $result);
    }

    // ==================== FILTER_EMPTY TESTS ====================

    /**
     * Test that filterEmpty removes empty strings.
     */
    public function test_filter_empty_removes_empty_strings(): void
    {
        // Arrange
        $collection = new StringTypedCollection;
        $collection->add('hello', '', 'world', '', 'test', '');

        // Act
        $filtered = $collection->filterEmpty();

        // Assert
        $this->assertSame(['hello', 'world', 'test'], $filtered->toArray());
        $this->assertCount(3, $filtered);
    }

    /**
     * Test that filterEmpty keeps strings with spaces (not considered empty).
     */
    public function test_filter_empty_keeps_strings_with_spaces(): void
    {
        // Arrange
        $collection = new StringTypedCollection;
        $collection->add('hello', ' ', 'world', '  ', 'test');

        // Act
        $filtered = $collection->filterEmpty();

        // Assert
        $this->assertSame(['hello', ' ', 'world', '  ', 'test'], $filtered->toArray());
        $this->assertCount(5, $filtered);
    }

    // ==================== TRIM TESTS ====================

    /**
     * Test that trim removes whitespace by default.
     */
    public function test_trim_removes_whitespace_by_default(): void
    {
        // Arrange
        $collection = new StringTypedCollection;
        $collection->add('  hello  ', "\tworld\n", '  test  ');

        // Act
        $trimmed = $collection->trim();

        // Assert
        $this->assertSame(['hello', 'world', 'test'], $trimmed->toArray());
    }

    /**
     * Test that trim with custom characters works.
     */
    public function test_trim_with_custom_characters_works(): void
    {
        // Arrange
        $collection = new StringTypedCollection;
        $collection->add('--hello--', '??world??', '!!test!!');

        // Act
        $trimmed = $collection->trim('-? !');

        // Assert
        $this->assertSame(['hello', 'world', 'test'], $trimmed->toArray());
    }

    // ==================== TRUNCATE TESTS ====================

    /**
     * Test that truncate cuts strings longer than length.
     */
    public function test_truncate_cuts_strings_longer_than_length(): void
    {
        // Arrange
        $collection = new StringTypedCollection;
        $collection->add('Hello World', 'Short', 'This is a very long string');

        // Act
        $truncated = $collection->truncate(5);

        // Assert
        $this->assertSame(['Hello', 'Short', 'This '], $truncated->toArray());
    }

    /**
     * Test that truncate adds suffix when truncated.
     */
    public function test_truncate_adds_suffix_when_truncated(): void
    {
        // Arrange
        $collection = new StringTypedCollection;
        $collection->add('Hello World', 'Short', 'Long text here');

        // Act
        $truncated = $collection->truncate(5, '...');

        // Assert
        $this->assertSame(['Hello...', 'Short', 'Long ...'], $truncated->toArray());
    }

    /**
     * Test that truncate leaves short strings unchanged.
     */
    public function test_truncate_leaves_short_strings_unchanged(): void
    {
        // Arrange
        $collection = new StringTypedCollection;
        $collection->add('Hi', 'Hello', 'Greetings');

        // Act
        $truncated = $collection->truncate(10, '...');

        // Assert
        $this->assertSame(['Hi', 'Hello', 'Greetings'], $truncated->toArray());
    }

    // ==================== MATCHING_REGEX TESTS ====================

    /**
     * Test that matchingRegex filters strings matching pattern.
     */
    public function test_matching_regex_filters_strings_matching_pattern(): void
    {
        // Arrange
        $collection = new StringTypedCollection;
        $collection->add('user@example.com', 'not-an-email', 'test@test.com', 'invalid');

        // Act
        $result = $collection->matchingRegex('/^[a-z]+@[a-z]+\.[a-z]+$/');

        // Assert
        $this->assertSame(['user@example.com', 'test@test.com'], $result->toArray());
    }

    /**
     * Test that matchingRegex throws exception for invalid pattern.
     */
    public function test_matching_regex_throws_exception_for_invalid_pattern(): void
    {
        // Arrange
        $collection = new StringTypedCollection;
        $collection->add('test');

        // Act & Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid regular expression pattern');

        $collection->matchingRegex('/invalid(regex/');
    }

    // ==================== JOIN TESTS ====================

    /**
     * Test that join concatenates all strings with separator.
     */
    public function test_join_concatenates_all_strings_with_separator(): void
    {
        // Arrange
        $collection = new StringTypedCollection;
        $collection->add('Hello', 'World', 'PHP');

        // Act
        $result = $collection->join(' ');

        // Assert
        $this->assertSame('Hello World PHP', $result);
    }

    /**
     * Test that join without separator concatenates directly.
     */
    public function test_join_without_separator_concatenates_directly(): void
    {
        // Arrange
        $collection = new StringTypedCollection;
        $collection->add('a', 'b', 'c');

        // Act
        $result = $collection->join();

        // Assert
        $this->assertSame('abc', $result);
    }

    /**
     * Test that join on empty collection returns empty string.
     */
    public function test_join_on_empty_collection_returns_empty_string(): void
    {
        // Arrange
        $emptyCollection = new StringTypedCollection;

        // Act
        $result = $emptyCollection->join(',');

        // Assert
        $this->assertSame('', $result);
    }

    // ==================== LENGTHS TESTS ====================

    /**
     * Test that lengths returns IntTypedCollection of string lengths.
     */
    public function test_lengths_returns_int_collection_of_string_lengths(): void
    {
        // Arrange
        $collection = new StringTypedCollection;
        $collection->add('hello', 'world', 'php', 'test');

        // Act
        $lengths = $collection->lengths();

        // Assert
        $this->assertInstanceOf(IntTypedCollection::class, $lengths);
        $this->assertSame([5, 5, 3, 4], $lengths->toArray());
    }

    /**
     * Test that lengths on empty collection returns empty IntTypedCollection.
     */
    public function test_lengths_on_empty_collection_returns_empty_int_collection(): void
    {
        // Arrange
        $emptyCollection = new StringTypedCollection;

        // Act
        $lengths = $emptyCollection->lengths();

        // Assert
        $this->assertCount(0, $lengths->toArray());
        $this->assertInstanceOf(IntTypedCollection::class, $lengths);
    }

    // ==================== PAD TESTS ====================

    /**
     * Test that pad pads strings to specified length.
     */
    public function test_pad_pads_strings_to_specified_length(): void
    {
        // Arrange
        $collection = new StringTypedCollection;
        $collection->add('hi', 'hello', 'a');

        // Act
        $padded = $collection->pad(5, ' ', STR_PAD_RIGHT);

        // Assert
        $this->assertSame(['hi   ', 'hello', 'a    '], $padded->toArray());
    }

    /**
     * Test that pad with left padding works.
     */
    public function test_pad_with_left_padding_works(): void
    {
        // Arrange
        $collection = new StringTypedCollection;
        $collection->add('hi', 'hello', 'a');

        // Act
        $padded = $collection->pad(5, '*', STR_PAD_LEFT);

        // Assert
        $this->assertSame(['***hi', 'hello', '****a'], $padded->toArray());
    }

    /**
     * Test that pad with both padding works.
     */
    public function test_pad_with_both_padding_works(): void
    {
        // Arrange
        $collection = new StringTypedCollection;
        $collection->add('hi', 'hello', 'a');

        // Act
        $padded = $collection->pad(5, '-', STR_PAD_BOTH);

        // Assert
        $this->assertSame(['-hi--', 'hello', '--a--'], $padded->toArray());
    }

    // ==================== REPLACE TESTS ====================

    /**
     * Test that replace replaces all occurrences.
     */
    public function test_replace_replaces_all_occurrences(): void
    {
        // Arrange
        $collection = new StringTypedCollection;
        $collection->add('Hello World', 'World is great', 'Hello World again');

        // Act
        $replaced = $collection->replace('World', 'PHP');

        // Assert
        $this->assertSame(['Hello PHP', 'PHP is great', 'Hello PHP again'], $replaced->toArray());
    }

    /**
     * Test that replace with array works.
     */
    public function test_replace_with_array_works(): void
    {
        // Arrange
        $collection = new StringTypedCollection;
        $collection->add('apple banana cherry', 'banana apple');

        // Act
        $replaced = $collection->replace(['apple', 'banana'], ['orange', 'grape']);

        // Assert
        $this->assertSame(['orange grape cherry', 'grape orange'], $replaced->toArray());
    }

    // ==================== FIRST_CHARACTER / LAST_CHARACTER TESTS ====================

    /**
     * Test that firstCharacter returns first character of each string.
     */
    public function test_first_character_returns_first_character(): void
    {
        // Arrange
        $collection = new StringTypedCollection;
        $collection->add('hello', 'world', 'php');

        // Act
        $firstChars = $collection->firstCharacter();

        // Assert
        $this->assertSame(['h', 'w', 'p'], $firstChars->toArray());
    }

    /**
     * Test that lastCharacter returns last character of each string.
     */
    public function test_last_character_returns_last_character(): void
    {
        // Arrange
        $collection = new StringTypedCollection;
        $collection->add('hello', 'world', 'php');

        // Act
        $lastChars = $collection->lastCharacter();

        // Assert
        $this->assertSame(['o', 'd', 'p'], $lastChars->toArray());
    }

    // ==================== SUBSTRING TESTS ====================

    /**
     * Test that substring extracts from offset.
     */
    public function test_substring_extracts_from_offset(): void
    {
        // Arrange
        $collection = new StringTypedCollection;
        $collection->add('hello', 'world', 'php');

        // Act
        $substrings = $collection->substring(1);

        // Assert
        $this->assertSame(['ello', 'orld', 'hp'], $substrings->toArray());
    }

    /**
     * Test that substring with length works.
     */
    public function test_substring_with_length_works(): void
    {
        // Arrange
        $collection = new StringTypedCollection;
        $collection->add('hello', 'world', 'php');

        // Act
        $substrings = $collection->substring(1, 2);

        // Assert
        $this->assertSame(['el', 'or', 'hp'], $substrings->toArray());
    }

    /**
     * Test that substring with negative offset works.
     */
    public function test_substring_with_negative_offset_works(): void
    {
        // Arrange
        $collection = new StringTypedCollection;
        $collection->add('hello', 'world', 'php');

        // Act
        $substrings = $collection->substring(-2);

        // Assert
        $this->assertSame(['lo', 'ld', 'hp'], $substrings->toArray());
    }

    // ==================== COUNT_MATCHING_REGEX / HAS_MATCHING_REGEX TESTS ====================

    /**
     * Test that countMatchingRegex returns correct count.
     */
    public function test_count_matching_regex_returns_correct_count(): void
    {
        // Arrange
        $collection = new StringTypedCollection;
        $collection->add('test@test.com', 'invalid', 'user@example.com', 'not-email');

        // Act
        $count = $collection->countMatchingRegex('/^[^@]+@[^@]+\.[^@]+$/');

        // Assert
        $this->assertSame(2, $count);
    }

    /**
     * Test that hasMatchingRegex returns true when matches exist.
     */
    public function test_has_matching_regex_returns_true_when_matches_exist(): void
    {
        // Arrange
        $collection = new StringTypedCollection;
        $collection->add('email@test.com', 'invalid');

        // Act
        $has = $collection->hasMatchingRegex('/@/');

        // Assert
        $this->assertTrue($has);
    }

    /**
     * Test that hasMatchingRegex returns false when no matches.
     */
    public function test_has_matching_regex_returns_false_when_no_matches(): void
    {
        // Arrange
        $collection = new StringTypedCollection;
        $collection->add('no', 'at', 'sign', 'here');

        // Act
        $has = $collection->hasMatchingRegex('/@/');

        // Assert
        $this->assertFalse($has);
    }

    // ==================== UNIQUE_CASE_INSENSITIVE TESTS ====================

    /**
     * Test that uniqueCaseInsensitive removes duplicates case-insensitively.
     */
    public function test_unique_case_insensitive_removes_duplicates(): void
    {
        // Arrange
        $collection = new StringTypedCollection;
        $collection->add('Hello', 'HELLO', 'hello', 'World', 'WORLD');

        // Act
        $unique = $collection->uniqueCaseInsensitive();

        // Assert - preserves first occurrence's case
        $this->assertSame(['Hello', 'World'], $unique->toArray());
        $this->assertCount(2, $unique);
    }

    /**
     * Test that uniqueCaseInsensitive on unique strings returns all.
     */
    public function test_unique_case_insensitive_on_unique_returns_all(): void
    {
        // Arrange
        $collection = new StringTypedCollection;
        $collection->add('apple', 'banana', 'cherry');

        // Act
        $unique = $collection->uniqueCaseInsensitive();

        // Assert
        $this->assertSame(['apple', 'banana', 'cherry'], $unique->toArray());
    }

    // ==================== SORT_CASE_INSENSITIVE TESTS ====================

    /**
     * Test that sortCaseInsensitive sorts alphabetically ignoring case.
     */
    public function test_sort_case_insensitive_sorts_alphabetically_ignoring_case(): void
    {
        // Arrange
        $collection = new StringTypedCollection;
        $collection->add('banana', 'Apple', 'cherry', 'apple', 'Banana');

        // Act
        $sorted = $collection->sortCaseInsensitive();

        // Assert
        $this->assertSame(['Apple', 'apple', 'banana', 'Banana', 'cherry'], $sorted->toArray());
    }

    /**
     * Test that sortCaseInsensitive descending works.
     */
    public function test_sort_case_insensitive_descending_works(): void
    {
        // Arrange
        $collection = new StringTypedCollection;
        $collection->add('banana', 'Apple', 'cherry', 'apple');

        // Act
        $sorted = $collection->sortCaseInsensitive(true);

        // Assert
        $this->assertSame(['cherry', 'banana', 'Apple', 'apple'], $sorted->toArray());
    }

    // ==================== REMOVE_WHITESPACE TESTS ====================

    /**
     * Test that removeWhitespace removes all whitespace.
     */
    public function test_remove_whitespace_removes_all_whitespace(): void
    {
        // Arrange
        $collection = new StringTypedCollection;
        $collection->add('Hello World', '  spaces  ', 'tab\tseparated', 'new\nline');

        // Act
        $cleaned = $collection->removeWhitespace();

        // Assert
        $this->assertSame(['HelloWorld', 'spaces', 'tabseparated', 'newline'], $cleaned->toArray());
    }

    // ==================== SLUGIFY TESTS ====================

    /**
     * Test that slugify converts strings to URL-friendly format.
     */
    public function test_slugify_converts_to_url_friendly_format(): void
    {
        // Arrange
        $collection = new StringTypedCollection;
        $collection->add('Hello World!', 'PHP 8.0 is great', 'Special @#$ Characters');

        // Act
        $slugs = $collection->slugify();

        // Assert
        $this->assertSame(['hello-world', 'php-8-0-is-great', 'special-characters'], $slugs->toArray());
    }

    /**
     * Test that slugify handles multiple spaces and special chars.
     */
    public function test_slugify_handles_multiple_spaces_and_special_chars(): void
    {
        // Arrange
        $collection = new StringTypedCollection;
        $collection->add('Multiple   spaces   here', 'UPPERCASE and lowercase');

        // Act
        $slugs = $collection->slugify();

        // Assert
        $this->assertSame(['multiple-spaces-here', 'uppercase-and-lowercase'], $slugs->toArray());
    }

    // ==================== WRAP TESTS ====================

    /**
     * Test that wrap adds prefix and suffix.
     */
    public function test_wrap_adds_prefix_and_suffix(): void
    {
        // Arrange
        $collection = new StringTypedCollection;
        $collection->add('hello', 'world');

        // Act
        $wrapped = $collection->wrap('{{', '}}');

        // Assert
        $this->assertSame(['{{hello}}', '{{world}}'], $wrapped->toArray());
    }

    /**
     * Test that wrap with single parameter uses same for prefix/suffix.
     */
    public function test_wrap_with_single_parameter_uses_same_for_prefix_and_suffix(): void
    {
        // Arrange
        $collection = new StringTypedCollection;
        $collection->add('hello', 'world');

        // Act
        $wrapped = $collection->wrap('"');

        // Assert
        $this->assertSame(['"hello"', '"world"'], $wrapped->toArray());
    }

    // ==================== REMOVE_PREFIX / REMOVE_SUFFIX TESTS ====================

    /**
     * Test that removePrefix removes prefix when present.
     */
    public function test_remove_prefix_removes_prefix_when_present(): void
    {
        // Arrange
        $collection = new StringTypedCollection;
        $collection->add('pre_hello', 'pre_world', 'no_prefix', 'pre_test');

        // Act
        $removed = $collection->removePrefix('pre_');

        // Assert
        $this->assertSame(['hello', 'world', 'no_prefix', 'test'], $removed->toArray());
    }

    /**
     * Test that removeSuffix removes suffix when present.
     */
    public function test_remove_suffix_removes_suffix_when_present(): void
    {
        // Arrange
        $collection = new StringTypedCollection;
        $collection->add('hello_suf', 'world_suf', 'no_suffix', 'test_suf');

        // Act
        $removed = $collection->removeSuffix('_suf');

        // Assert
        $this->assertSame(['hello', 'world', 'no_suffix', 'test'], $removed->toArray());
    }

    /**
     * Test that removePrefix leaves string unchanged when prefix not present.
     */
    public function test_remove_prefix_leaves_unchanged_when_prefix_not_present(): void
    {
        // Arrange
        $collection = new StringTypedCollection;
        $collection->add('hello', 'world');

        // Act
        $removed = $collection->removePrefix('pre_');

        // Assert
        $this->assertSame(['hello', 'world'], $removed->toArray());
    }

    // ==================== CHAINING TESTS ====================

    /**
     * Test complex chaining of multiple operations.
     */
    public function test_complex_chaining_of_multiple_operations(): void
    {
        // Arrange
        $collection = new StringTypedCollection;
        $collection->add(
            '  Hello World  ',
            'PHP is awesome',
            '  UPPERCASE   ',
            'lowercase',
            'Mixed CASE string'
        );

        // Act - Trim, lowercase, slugify, then join
        $result = $collection
            ->trim()
            ->toLowercase()
            ->slugify()
            ->join('-');

        // Assert
        $this->assertSame('hello-world-php-is-awesome-uppercase-lowercase-mixed-case-string', $result);
    }

    /**
     * Test filtering then transformation chain.
     */
    public function test_filtering_then_transformation_chain(): void
    {
        // Arrange
        $collection = new StringTypedCollection;
        $collection->add('apple@test.com', 'invalid', 'banana@test.com', 'not-email', 'cherry@test.com');

        // Act - Get valid emails, then extract domain
        $domains = $collection
            ->matchingRegex('/^[^@]+@[^@]+\.[^@]+$/')
            ->map(fn ($email) => substr($email, strpos($email, '@') + 1));

        // Assert
        $this->assertSame(['test.com', 'test.com', 'test.com'], $domains->toArray());
    }

    // ==================== EDGE CASES TESTS ====================

    /**
     * Test that collection handles empty strings correctly.
     */
    public function test_collection_handles_empty_strings_correctly(): void
    {
        // Arrange
        $collection = new StringTypedCollection;
        $collection->add('', '', 'test', '');

        // Act
        $count = $collection->count();
        $filtered = $collection->filterEmpty();

        // Assert
        $this->assertCount(4, $collection);
        $this->assertCount(1, $filtered);
        $this->assertSame('test', $filtered[0]);
    }

    /**
     * Test that collection handles unicode characters.
     */
    public function test_collection_handles_unicode_characters(): void
    {
        // Arrange
        $collection = new StringTypedCollection;
        $collection->add('café', 'привет', '世界', 'ñandú');

        // Act
        $lengths = $collection->lengths();
        $firstChars = $collection->firstCharacter();

        // Assert
        $this->assertSame([4, 6, 2, 5], $lengths->toArray()); // note: strlen counts bytes
        $this->assertSame(['c', 'п', '世', 'ñ'], $firstChars->toArray());
    }

    /**
     * Test that collection handles very long strings.
     */
    public function test_collection_handles_very_long_strings(): void
    {
        // Arrange
        $collection = new StringTypedCollection;
        $longString = str_repeat('a', 10000);
        $collection->add($longString, 'short');

        // Act
        $truncated = $collection->truncate(10, '...');

        // Assert
        $this->assertSame([substr($longString, 0, 10).'...', 'short'], $truncated->toArray());
    }

    /**
     * Test that collection preserves order after operations.
     */
    public function test_collection_preserves_order_after_operations(): void
    {
        // Arrange
        $collection = new StringTypedCollection;
        $collection->add('third', 'first', 'second', 'fourth');

        // Act
        $filtered = $collection->containsSubstring('i');

        // Assert - order preserved: 'third', 'first', 'second' (contains 'i')
        $this->assertSame(['third', 'first', 'second'], $filtered->toArray());
    }

    /**
     * Test that normalize returns array of strings.
     */
    public function test_normalize_returns_array_of_strings(): void
    {
        // Arrange
        $collection = new StringTypedCollection;
        $collection->add('hello', 'world', 'test');

        // Act
        $normalized = $collection->normalize();

        // Assert
        $this->assertSame(['hello', 'world', 'test'], $normalized);
    }

    /**
     * Test that JSON serialization works.
     */
    public function test_json_serialization_works(): void
    {
        // Arrange
        $collection = new StringTypedCollection;
        $collection->add('a', 'b', 'c');

        // Act
        $json = json_encode($collection);

        // Assert
        $this->assertSame('["a","b","c"]', $json);
    }
}
