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

    public function test_constructor_sets_string_as_allowed_type(): void
    {
        $collection = new StringTypedCollection;

        $this->assertSame(['string'], $collection->getAllowedTypes());
    }

    public function test_collection_accepts_only_string_values(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('hello', 'world', 'test');

        $this->assertCount(3, $collection);
        $this->assertSame('hello', $collection[0]);
        $this->assertSame('world', $collection[1]);
        $this->assertSame('test', $collection[2]);
    }

    public function test_collection_rejects_non_string_values(): void
    {
        $collection = new StringTypedCollection;

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected type(s) string');

        $collection->add(42);
    }

    // ==================== TO_LOWERCASE / TO_UPPERCASE TESTS ====================

    public function test_to_lowercase_converts_all_strings_to_lowercase(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('HELLO', 'World', 'TeSt', 'UPPER');

        $lowercase = $collection->toLowercase();

        $this->assertSame(['hello', 'world', 'test', 'upper'], $lowercase->toArray());
    }

    public function test_to_uppercase_converts_all_strings_to_uppercase(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('hello', 'World', 'TeSt', 'lower');

        $uppercase = $collection->toUppercase();

        $this->assertSame(['HELLO', 'WORLD', 'TEST', 'LOWER'], $uppercase->toArray());
    }

    public function test_case_conversions_return_new_collection_instance(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('Test');

        $lowercase = $collection->toLowercase();
        $uppercase = $collection->toUppercase();

        $this->assertNotSame($collection, $lowercase);
        $this->assertNotSame($collection, $uppercase);
    }

    // ==================== CONTAINS_SUBSTRING TESTS ====================

    public function test_contains_substring_filters_strings_containing_substring(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('apple', 'banana', 'grape', 'pineapple', 'orange');

        $result = $collection->containsSubstring('apple');

        $this->assertSame(['apple', 'pineapple'], $result->toArray());
        $this->assertCount(2, $result);
    }

    public function test_contains_substring_returns_empty_when_no_matches(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('cat', 'dog', 'bird');

        $result = $collection->containsSubstring('fish');

        $this->assertCount(0, $result);
        $this->assertTrue($result->isEmpty());
    }

    public function test_contains_substring_is_case_sensitive(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('Apple', 'apple', 'APPLE');

        $result = $collection->containsSubstring('apple');

        $this->assertSame(['apple'], $result->toArray());
    }

    // ==================== STARTS_WITH TESTS ====================

    public function test_starts_with_filters_strings_starting_with_prefix(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('apple', 'apricot', 'banana', 'avocado', 'grape');

        $result = $collection->startsWith('ap');

        $this->assertSame(['apple', 'apricot'], $result->toArray());
        $this->assertCount(2, $result);
    }

    public function test_starts_with_returns_empty_when_no_matches(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('cat', 'dog', 'bird');

        $result = $collection->startsWith('x');

        $this->assertCount(0, $result);
    }

    // ==================== ENDS_WITH TESTS ====================

    public function test_ends_with_filters_strings_ending_with_suffix(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('apple', 'pineapple', 'grape', 'maple', 'table');

        $result = $collection->endsWith('ple');

        $this->assertSame(['apple', 'pineapple', 'maple'], $result->toArray());
        $this->assertCount(3, $result);
    }

    public function test_ends_with_returns_empty_when_no_matches(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('cat', 'dog', 'bird');

        $result = $collection->endsWith('xyz');

        $this->assertCount(0, $result);
    }

    // ==================== FILTER_EMPTY TESTS ====================

    public function test_filter_empty_removes_empty_strings(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('hello', '', 'world', '', 'test', '');

        $filtered = $collection->filterEmpty();

        $this->assertSame(['hello', 'world', 'test'], $filtered->toArray());
        $this->assertCount(3, $filtered);
    }

    public function test_filter_empty_keeps_strings_with_spaces(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('hello', ' ', 'world', '  ', 'test');

        $filtered = $collection->filterEmpty();

        $this->assertSame(['hello', ' ', 'world', '  ', 'test'], $filtered->toArray());
        $this->assertCount(5, $filtered);
    }

    // ==================== TRIM TESTS ====================

    public function test_trim_removes_whitespace_by_default(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('  hello  ', "\tworld\n", '  test  ');

        $trimmed = $collection->trim();

        $this->assertSame(['hello', 'world', 'test'], $trimmed->toArray());
    }

    public function test_trim_with_custom_characters_works(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('--hello--', '??world??', '!!test!!');

        $trimmed = $collection->trim('-? !');

        $this->assertSame(['hello', 'world', 'test'], $trimmed->toArray());
    }

    // ==================== TRUNCATE TESTS ====================

    public function test_truncate_cuts_strings_longer_than_length(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('Hello World', 'Short', 'This is a very long string');

        $truncated = $collection->truncate(5, '');

        $this->assertSame(['Hello', 'Short', 'This '], $truncated->toArray());
    }

    public function test_truncate_adds_suffix_when_truncated(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('Hello World', 'Short', 'Long text here');

        $truncated = $collection->truncate(5, '...');

        $this->assertSame(['Hello...', 'Short', 'Long ...'], $truncated->toArray());
    }

    public function test_truncate_leaves_short_strings_unchanged(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('Hi', 'Hello', 'Greetings');

        $truncated = $collection->truncate(10, '...');

        $this->assertSame(['Hi', 'Hello', 'Greetings'], $truncated->toArray());
    }

    // ==================== MATCHING_REGEX TESTS ====================

    public function test_matching_regex_filters_strings_matching_pattern(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('user@example.com', 'not-an-email', 'test@test.com', 'invalid');

        $result = $collection->matchingRegex('/^[a-z]+@[a-z]+\.[a-z]+$/');

        $this->assertSame(['user@example.com', 'test@test.com'], $result->toArray());
    }

    public function test_matching_regex_throws_exception_for_invalid_pattern(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('test');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid regular expression pattern');

        $collection->matchingRegex('/invalid(regex/');
    }

    // ==================== JOIN TESTS ====================

    public function test_join_concatenates_all_strings_with_separator(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('Hello', 'World', 'PHP');

        $result = $collection->join(' ');

        $this->assertSame('Hello World PHP', $result);
    }

    public function test_join_without_separator_concatenates_directly(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('a', 'b', 'c');

        $result = $collection->join();

        $this->assertSame('abc', $result);
    }

    public function test_join_on_empty_collection_returns_empty_string(): void
    {
        $emptyCollection = new StringTypedCollection;

        $result = $emptyCollection->join(',');

        $this->assertSame('', $result);
    }

    // ==================== LENGTHS TESTS ====================

    public function test_lengths_returns_int_collection_of_string_lengths(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('hello', 'world', 'php', 'test');

        $lengths = $collection->lengths();

        $this->assertInstanceOf(IntTypedCollection::class, $lengths);
        $this->assertSame([5, 5, 3, 4], $lengths->toArray());
    }

    public function test_lengths_on_empty_collection_returns_empty_int_collection(): void
    {
        $emptyCollection = new StringTypedCollection;

        $lengths = $emptyCollection->lengths();

        $this->assertCount(0, $lengths->toArray());
        $this->assertInstanceOf(IntTypedCollection::class, $lengths);
    }

    // ==================== PAD TESTS ====================

    public function test_pad_pads_strings_to_specified_length(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('hi', 'hello', 'a');

        $padded = $collection->pad(5, ' ', STR_PAD_RIGHT);

        $this->assertSame(['hi   ', 'hello', 'a    '], $padded->toArray());
    }

    public function test_pad_with_left_padding_works(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('hi', 'hello', 'a');

        $padded = $collection->pad(5, '*', STR_PAD_LEFT);

        $this->assertSame(['***hi', 'hello', '****a'], $padded->toArray());
    }

    public function test_pad_with_both_padding_works(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('hi', 'hello', 'a');

        $padded = $collection->pad(5, '-', STR_PAD_BOTH);

        $this->assertSame(['-hi--', 'hello', '--a--'], $padded->toArray());
    }

    // ==================== REPLACE TESTS ====================

    public function test_replace_replaces_all_occurrences(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('Hello World', 'World is great', 'Hello World again');

        $replaced = $collection->replace('World', 'PHP');

        $this->assertSame(['Hello PHP', 'PHP is great', 'Hello PHP again'], $replaced->toArray());
    }

    public function test_replace_with_array_works(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('apple banana cherry', 'banana apple');

        $replaced = $collection->replace(['apple', 'banana'], ['orange', 'grape']);

        $this->assertSame(['orange grape cherry', 'grape orange'], $replaced->toArray());
    }

    // ==================== FIRST_CHARACTER / LAST_CHARACTER TESTS ====================

    public function test_first_character_returns_first_character(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('hello', 'world', 'php');

        $firstChars = $collection->firstCharacter();

        $this->assertSame(['h', 'w', 'p'], $firstChars->toArray());
    }

    public function test_last_character_returns_last_character(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('hello', 'world', 'php');

        $lastChars = $collection->lastCharacter();

        $this->assertSame(['o', 'd', 'p'], $lastChars->toArray());
    }

    // ==================== SUBSTRING TESTS ====================

    public function test_substring_extracts_from_offset(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('hello', 'world', 'php');

        $substrings = $collection->substring(1);

        $this->assertSame(['ello', 'orld', 'hp'], $substrings->toArray());
    }

    public function test_substring_with_length_works(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('hello', 'world', 'php');

        $substrings = $collection->substring(1, 2);

        $this->assertSame(['el', 'or', 'hp'], $substrings->toArray());
    }

    public function test_substring_with_negative_offset_works(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('hello', 'world', 'php');

        $substrings = $collection->substring(-2);

        $this->assertSame(['lo', 'ld', 'hp'], $substrings->toArray());
    }

    // ==================== COUNT_MATCHING_REGEX / HAS_MATCHING_REGEX TESTS ====================

    public function test_count_matching_regex_returns_correct_count(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('test@test.com', 'invalid', 'user@example.com', 'not-email');

        $count = $collection->countMatchingRegex('/^[^@]+@[^@]+\.[^@]+$/');

        $this->assertSame(2, $count);
    }

    public function test_has_matching_regex_returns_true_when_matches_exist(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('email@test.com', 'invalid');

        $has = $collection->hasMatchingRegex('/@/');

        $this->assertTrue($has);
    }

    public function test_has_matching_regex_returns_false_when_no_matches(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('no', 'at', 'sign', 'here');

        $has = $collection->hasMatchingRegex('/@/');

        $this->assertFalse($has);
    }

    // ==================== UNIQUE_CASE_INSENSITIVE TESTS ====================

    public function test_unique_case_insensitive_removes_duplicates(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('Hello', 'HELLO', 'hello', 'World', 'WORLD');

        $unique = $collection->uniqueCaseInsensitive();

        $this->assertSame(['Hello', 'World'], $unique->toArray());
        $this->assertCount(2, $unique);
    }

    public function test_unique_case_insensitive_on_unique_returns_all(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('apple', 'banana', 'cherry');

        $unique = $collection->uniqueCaseInsensitive();

        $this->assertSame(['apple', 'banana', 'cherry'], $unique->toArray());
    }

    // ==================== SORT_CASE_INSENSITIVE TESTS ====================

    public function test_sort_case_insensitive_sorts_alphabetically_ignoring_case(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('banana', 'Apple', 'cherry', 'apple', 'Banana');

        $sorted = $collection->sortCaseInsensitive();

        $this->assertSame(['Apple', 'apple', 'Banana', 'banana', 'cherry'], $sorted->toArray());
    }

    public function test_sort_case_insensitive_descending_works(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('banana', 'Apple', 'cherry', 'apple');

        $sorted = $collection->sortCaseInsensitive(true);

        $this->assertSame(['cherry', 'banana', 'apple', 'Apple'], $sorted->toArray());
    }

    // ==================== REMOVE_WHITESPACE TESTS ====================

    public function test_remove_whitespace_removes_all_whitespace(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('Hello World', '  spaces  ', "tab\tseparated", "new\nline");

        $cleaned = $collection->removeWhitespace();

        $this->assertSame(['HelloWorld', 'spaces', 'tabseparated', 'newline'], $cleaned->toArray());
    }

    // ==================== SLUGIFY TESTS ====================

    public function test_slugify_converts_to_url_friendly_format(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('Hello World!', 'PHP 8.0 is great', 'Special @#$ Characters');

        $slugs = $collection->slugify();

        $this->assertSame(['hello-world', 'php-8-0-is-great', 'special-characters'], $slugs->toArray());
    }

    public function test_slugify_handles_multiple_spaces_and_special_chars(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('Multiple   spaces   here', 'UPPERCASE and lowercase');

        $slugs = $collection->slugify();

        $this->assertSame(['multiple-spaces-here', 'uppercase-and-lowercase'], $slugs->toArray());
    }

    // ==================== WRAP TESTS ====================

    public function test_wrap_adds_prefix_and_suffix(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('hello', 'world');

        $wrapped = $collection->wrap('{{', '}}');

        $this->assertSame(['{{hello}}', '{{world}}'], $wrapped->toArray());
    }

    public function test_wrap_with_single_parameter_uses_same_for_prefix_and_suffix(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('hello', 'world');

        $wrapped = $collection->wrap('"');

        $this->assertSame(['"hello"', '"world"'], $wrapped->toArray());
    }

    // ==================== REMOVE_PREFIX / REMOVE_SUFFIX TESTS ====================

    public function test_remove_prefix_removes_prefix_when_present(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('pre_hello', 'pre_world', 'no_prefix', 'pre_test');

        $removed = $collection->removePrefix('pre_');

        $this->assertSame(['hello', 'world', 'no_prefix', 'test'], $removed->toArray());
    }

    public function test_remove_suffix_removes_suffix_when_present(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('hello_suf', 'world_suf', 'no_suffix', 'test_suf');

        $removed = $collection->removeSuffix('_suf');

        $this->assertSame(['hello', 'world', 'no_suffix', 'test'], $removed->toArray());
    }

    public function test_remove_prefix_leaves_unchanged_when_prefix_not_present(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('hello', 'world');

        $removed = $collection->removePrefix('pre_');

        $this->assertSame(['hello', 'world'], $removed->toArray());
    }

    // ==================== CHAINING TESTS ====================

    public function test_complex_chaining_of_multiple_operations(): void
    {
        $collection = new StringTypedCollection;
        $collection->add(
            '  Hello World  ',
            'PHP is awesome',
            '  UPPERCASE   ',
            'lowercase',
            'Mixed CASE string'
        );

        $result = $collection
            ->trim()
            ->toLowercase()
            ->slugify()
            ->join('-');

        $this->assertSame('hello-world-php-is-awesome-uppercase-lowercase-mixed-case-string', $result);
    }

    public function test_filtering_then_transformation_chain(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('apple@test.com', 'invalid', 'banana@test.com', 'not-email', 'cherry@test.com');

        $domains = $collection
            ->matchingRegex('/^[^@]+@[^@]+\.[^@]+$/')
            ->map(fn($email) => substr($email, strpos($email, '@') + 1));

        $this->assertSame(['test.com', 'test.com', 'test.com'], $domains->toArray());
    }

    // ==================== EDGE CASES TESTS ====================

    public function test_collection_handles_empty_strings_correctly(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('', '', 'test', '');

        $count = $collection->count();
        $filtered = $collection->filterEmpty();

        $this->assertCount(4, $collection);
        $this->assertCount(1, $filtered);
        $this->assertSame('test', $filtered[0]);
    }

    public function test_collection_handles_unicode_characters(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('café', 'привет', '世界', 'ñandú');

        $lengths = $collection->lengths();
        $firstChars = $collection->firstCharacter();

        $this->assertSame([4, 6, 2, 5], $lengths->toArray());
        $this->assertSame(['c', 'п', '世', 'ñ'], $firstChars->toArray());
    }

    public function test_collection_handles_very_long_strings(): void
    {
        $collection = new StringTypedCollection;
        $longString = str_repeat('a', 10000);
        $collection->add($longString, 'short');

        $truncated = $collection->truncate(10, '...');

        $this->assertSame([substr($longString, 0, 10) . '...', 'short'], $truncated->toArray());
    }

    public function test_collection_preserves_order_after_operations(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('third', 'first', 'second', 'fourth');

        $filtered = $collection->containsSubstring('i');

        $this->assertSame(['third', 'first'], $filtered->toArray());
    }

    public function test_normalize_returns_array_of_strings(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('hello', 'world', 'test');

        $normalized = $collection->normalize();

        $this->assertSame(['hello', 'world', 'test'], $normalized);
    }

    public function test_json_serialization_works(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('a', 'b', 'c');

        $json = json_encode($collection);

        $this->assertSame('["a","b","c"]', $json);
    }
}
