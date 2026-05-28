<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Unit\Collections\Utility;

use AndyDefer\DomainStructures\Collections\Utility\StringTypedCollection;
use AndyDefer\DomainStructures\Normalizers\NormalizerChain;
use AndyDefer\DomainStructures\Tests\TestCase;

/**
 * Unit tests for StringTypedCollection class.
 *
 * This test suite validates the string collection functionality:
 * - Type safety (only string values allowed)
 * - Case conversion (toLowercase, toUppercase)
 * - String filtering (containsSubstring, startsWith, endsWith)
 * - String manipulation (trim, truncate, pad, replace)
 * - Pattern matching (matchingRegex, slugify)
 * - Collection operations
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

    public function test_collection_accepts_only_strings(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('a', 'b', 'c');

        $this->assertCount(3, $collection);
        $this->assertSame('a', $collection[0]);
        $this->assertSame('b', $collection[1]);
        $this->assertSame('c', $collection[2]);
    }

    public function test_collection_rejects_non_strings(): void
    {
        $collection = new StringTypedCollection;

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected type(s) string');

        $collection->add(123);
    }

    // ==================== NORMALIZATION TESTS ====================

    public function test_normalize_returns_array_of_strings(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('hello', 'world', 'test');

        $normalized = NormalizerChain::get()->normalize($collection);

        $this->assertSame(['hello', 'world', 'test'], $normalized);
    }

    public function test_normalize_on_empty_collection_returns_empty_array(): void
    {
        $collection = new StringTypedCollection;

        $normalized = NormalizerChain::get()->normalize($collection);

        $this->assertEmpty($normalized);
    }

    // ==================== TO_LOWERCASE METHOD TESTS ====================

    public function test_to_lowercase_converts_all_strings_to_lowercase(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('HELLO', 'WoRlD', 'TeSt');

        $lowercased = $collection->toLowercase();

        $this->assertSame(['hello', 'world', 'test'], $lowercased->toArray());
    }

    public function test_to_lowercase_returns_new_collection_instance(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('HELLO', 'WORLD');

        $lowercased = $collection->toLowercase();

        $this->assertNotSame($collection, $lowercased);
        $this->assertInstanceOf(StringTypedCollection::class, $lowercased);
    }

    public function test_to_lowercase_on_empty_collection_returns_empty_collection(): void
    {
        $emptyCollection = new StringTypedCollection;

        $lowercased = $emptyCollection->toLowercase();

        $this->assertCount(0, $lowercased);
    }

    public function test_to_lowercase_preserves_already_lowercase_strings(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('hello', 'world');

        $lowercased = $collection->toLowercase();

        $this->assertSame(['hello', 'world'], $lowercased->toArray());
    }

    // ==================== TO_UPPERCASE METHOD TESTS ====================

    public function test_to_uppercase_converts_all_strings_to_uppercase(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('hello', 'world', 'test');

        $uppercased = $collection->toUppercase();

        $this->assertSame(['HELLO', 'WORLD', 'TEST'], $uppercased->toArray());
    }

    public function test_to_uppercase_returns_new_collection_instance(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('hello', 'world');

        $uppercased = $collection->toUppercase();

        $this->assertNotSame($collection, $uppercased);
        $this->assertInstanceOf(StringTypedCollection::class, $uppercased);
    }

    public function test_to_uppercase_on_empty_collection_returns_empty_collection(): void
    {
        $emptyCollection = new StringTypedCollection;

        $uppercased = $emptyCollection->toUppercase();

        $this->assertCount(0, $uppercased);
    }

    // ==================== CONTAINS_SUBSTRING METHOD TESTS ====================

    public function test_contains_substring_filters_strings_containing_substring(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('apple', 'banana', 'grape', 'pineapple');

        $filtered = $collection->containsSubstring('apple');

        $this->assertSame(['apple', 'pineapple'], $filtered->toArray());
    }

    public function test_contains_substring_returns_empty_collection_when_no_match(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('apple', 'banana', 'grape');

        $filtered = $collection->containsSubstring('xyz');

        $this->assertCount(0, $filtered);
        $this->assertTrue($filtered->isEmpty());
    }

    public function test_contains_substring_is_case_sensitive(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('Apple', 'apple', 'APPLE');

        $filtered = $collection->containsSubstring('apple');

        $this->assertSame(['apple'], $filtered->toArray());
    }

    // ==================== STARTS_WITH METHOD TESTS ====================

    public function test_starts_with_filters_strings_with_prefix(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('apple', 'apricot', 'banana', 'grape');

        $filtered = $collection->startsWith('ap');

        $this->assertSame(['apple', 'apricot'], $filtered->toArray());
    }

    public function test_starts_with_returns_empty_collection_when_no_match(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('apple', 'banana', 'grape');

        $filtered = $collection->startsWith('xyz');

        $this->assertCount(0, $filtered);
    }

    public function test_starts_with_is_case_sensitive(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('Apple', 'apple');

        $filtered = $collection->startsWith('A');

        $this->assertSame(['Apple'], $filtered->toArray());
    }

    // ==================== ENDS_WITH METHOD TESTS ====================

    public function test_ends_with_filters_strings_with_suffix(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('apple', 'pineapple', 'banana', 'grape');

        $filtered = $collection->endsWith('apple');

        $this->assertSame(['apple', 'pineapple'], $filtered->toArray());
    }

    public function test_ends_with_returns_empty_collection_when_no_match(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('apple', 'banana', 'grape');

        $filtered = $collection->endsWith('xyz');

        $this->assertCount(0, $filtered);
    }

    public function test_ends_with_is_case_sensitive(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('apple', 'Apple');

        $filtered = $collection->endsWith('Apple');

        $this->assertSame(['Apple'], $filtered->toArray());
    }

    // ==================== FILTER_EMPTY METHOD TESTS ====================

    public function test_filter_empty_removes_empty_strings(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('hello', '', 'world', '', 'test');

        $filtered = $collection->filterEmpty();

        $this->assertSame(['hello', 'world', 'test'], $filtered->toArray());
    }

    public function test_filter_empty_keeps_strings_with_whitespace(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('hello', ' ', 'world', "\t", 'test');

        $filtered = $collection->filterEmpty();

        $this->assertSame(['hello', ' ', 'world', "\t", 'test'], $filtered->toArray());
    }

    public function test_filter_empty_on_empty_collection_returns_empty_collection(): void
    {
        $emptyCollection = new StringTypedCollection;

        $filtered = $emptyCollection->filterEmpty();

        $this->assertCount(0, $filtered);
    }

    // ==================== TRIM METHOD TESTS ====================

    public function test_trim_removes_whitespace_from_beginning_and_end(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('  hello  ', '  world  ', 'test  ');

        $trimmed = $collection->trim();

        $this->assertSame(['hello', 'world', 'test'], $trimmed->toArray());
    }

    public function test_trim_with_custom_characters(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('--hello--', '--world--', 'test--');

        $trimmed = $collection->trim('-');

        $this->assertSame(['hello', 'world', 'test'], $trimmed->toArray());
    }

    public function test_trim_returns_new_collection_instance(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('  hello  ');

        $trimmed = $collection->trim();

        $this->assertNotSame($collection, $trimmed);
        $this->assertInstanceOf(StringTypedCollection::class, $trimmed);
    }

    // ==================== TRUNCATE METHOD TESTS ====================

    public function test_truncate_cuts_strings_to_maximum_length(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('Hello World', 'Short', 'Very Long String Here');

        $truncated = $collection->truncate(5);

        $this->assertSame(['Hello', 'Short', 'Very '], $truncated->toArray());
    }

    public function test_truncate_appends_suffix_when_truncated(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('Hello World', 'Short');

        $truncated = $collection->truncate(5, '...');

        $this->assertSame(['Hello...', 'Short'], $truncated->toArray());
    }

    public function test_truncate_leaves_short_strings_unchanged(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('Hi', 'Bye', 'Test');

        $truncated = $collection->truncate(10, '...');

        $this->assertSame(['Hi', 'Bye', 'Test'], $truncated->toArray());
    }

    // ==================== MATCHING_REGEX METHOD TESTS ====================

    public function test_matching_regex_filters_strings_matching_pattern(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('abc123', 'def456', 'ghi789', 'xyz');

        $filtered = $collection->matchingRegex('/\d+/');

        $this->assertSame(['abc123', 'def456', 'ghi789'], $filtered->toArray());
    }

    public function test_matching_regex_returns_empty_collection_when_no_match(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('abc', 'def', 'ghi');

        $filtered = $collection->matchingRegex('/\d+/');

        $this->assertCount(0, $filtered);
    }

    public function test_matching_regex_throws_exception_for_invalid_pattern(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('test');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid regular expression pattern');

        $collection->matchingRegex('/invalid regex');
    }

    // ==================== JOIN METHOD TESTS ====================

    public function test_join_combines_all_strings_with_separator(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('a', 'b', 'c');

        $result = $collection->join(',');

        $this->assertSame('a,b,c', $result);
    }

    public function test_join_with_default_separator(): void
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

    public function test_join_on_single_item_returns_item_without_separator(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('alone');

        $result = $collection->join(',');

        $this->assertSame('alone', $result);
    }

    // ==================== LENGTHS METHOD TESTS ====================

    public function test_lengths_returns_collection_of_string_lengths(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('hello', 'world', 'test');

        $lengths = $collection->lengths();

        $this->assertSame([5, 5, 4], $lengths->toArray());
    }

    public function test_lengths_handles_empty_strings(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('', 'a', 'ab');

        $lengths = $collection->lengths();

        $this->assertSame([0, 1, 2], $lengths->toArray());
    }

    // ==================== PAD METHOD TESTS ====================

    public function test_pad_right_by_default(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('a', 'ab', 'abc');

        $padded = $collection->pad(5);

        $this->assertSame(['a    ', 'ab   ', 'abc  '], $padded->toArray());
    }

    public function test_pad_left(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('a', 'ab', 'abc');

        $padded = $collection->pad(5, ' ', STR_PAD_LEFT);

        $this->assertSame(['    a', '   ab', '  abc'], $padded->toArray());
    }

    public function test_pad_both(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('a', 'ab', 'abc');

        $padded = $collection->pad(5, ' ', STR_PAD_BOTH);

        $this->assertSame(['  a  ', ' ab  ', ' abc '], $padded->toArray());
    }

    public function test_pad_with_custom_pad_string(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('a', 'ab');

        $padded = $collection->pad(5, '-=');

        $this->assertSame(['a-=-=', 'ab-=-'], $padded->toArray());
    }

    // ==================== REPLACE METHOD TESTS ====================

    public function test_replace_replaces_all_occurrences(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('hello world', 'world hello');

        $replaced = $collection->replace('world', 'planet');

        $this->assertSame(['hello planet', 'planet hello'], $replaced->toArray());
    }

    public function test_replace_with_arrays(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('apple banana cherry');

        $replaced = $collection->replace(['apple', 'banana'], ['orange', 'grape']);

        $this->assertSame(['orange grape cherry'], $replaced->toArray());
    }

    // ==================== FIRST_CHARACTER METHOD TESTS ====================

    public function test_first_character_returns_first_character_of_each_string(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('hello', 'world', 'test');

        $firstChars = $collection->firstCharacter();

        $this->assertSame(['h', 'w', 't'], $firstChars->toArray());
    }

    public function test_first_character_handles_empty_strings(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('', 'a', 'ab');

        $firstChars = $collection->firstCharacter();

        $this->assertSame(['', 'a', 'a'], $firstChars->toArray());
    }

    // ==================== LAST_CHARACTER METHOD TESTS ====================

    public function test_last_character_returns_last_character_of_each_string(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('hello', 'world', 'test');

        $lastChars = $collection->lastCharacter();

        $this->assertSame(['o', 'd', 't'], $lastChars->toArray());
    }

    public function test_last_character_handles_empty_strings(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('', 'a', 'ab');

        $lastChars = $collection->lastCharacter();

        $this->assertSame(['', 'a', 'b'], $lastChars->toArray());
    }

    // ==================== SUBSTRING METHOD TESTS ====================

    public function test_substring_extracts_substrings(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('hello', 'world', 'test');

        $substrings = $collection->substring(1, 3);

        $this->assertSame(['ell', 'orl', 'est'], $substrings->toArray());
    }

    public function test_substring_without_length_returns_to_end(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('hello', 'world');

        $substrings = $collection->substring(2);

        $this->assertSame(['llo', 'rld'], $substrings->toArray());
    }

    public function test_substring_with_negative_offset(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('hello', 'world');

        $substrings = $collection->substring(-2);

        $this->assertSame(['lo', 'ld'], $substrings->toArray());
    }

    // ==================== COUNT_MATCHING_REGEX METHOD TESTS ====================

    public function test_count_matching_regex_returns_count_of_matching_strings(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('abc123', 'def456', 'ghi', 'jkl789');

        $count = $collection->countMatchingRegex('/\d+/');

        $this->assertSame(3, $count);
    }

    // ==================== HAS_MATCHING_REGEX METHOD TESTS ====================

    public function test_has_matching_regex_returns_true_when_match_exists(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('abc', '123', 'def');

        $hasMatch = $collection->hasMatchingRegex('/\d+/');

        $this->assertTrue($hasMatch);
    }

    public function test_has_matching_regex_returns_false_when_no_match(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('abc', 'def', 'ghi');

        $hasMatch = $collection->hasMatchingRegex('/\d+/');

        $this->assertFalse($hasMatch);
    }

    // ==================== UNIQUE_CASE_INSENSITIVE METHOD TESTS ====================

    public function test_unique_case_insensitive_removes_duplicates_case_insensitively(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('apple', 'Apple', 'APPLE', 'banana', 'Banana');

        $unique = $collection->uniqueCaseInsensitive();

        $this->assertSame(['apple', 'banana'], $unique->toArray());
    }

    public function test_unique_case_insensitive_preserves_first_occurrence(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('Apple', 'apple', 'BANANA', 'banana');

        $unique = $collection->uniqueCaseInsensitive();

        $this->assertSame(['Apple', 'BANANA'], $unique->toArray());
    }

    // ==================== SORT_CASE_INSENSITIVE METHOD TESTS ====================

    public function test_sort_case_insensitive_sorts_case_insensitively(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('banana', 'Apple', 'grape', 'Cherry');

        $sorted = $collection->sortCaseInsensitive();

        $this->assertSame(['Apple', 'banana', 'Cherry', 'grape'], $sorted->toArray());
    }

    public function test_sort_case_insensitive_descending(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('banana', 'Apple', 'grape', 'Cherry');

        $sorted = $collection->sortCaseInsensitive(true);

        $this->assertSame(['grape', 'Cherry', 'banana', 'Apple'], $sorted->toArray());
    }

    // ==================== REMOVE_WHITESPACE METHOD TESTS ====================

    public function test_remove_whitespace_removes_all_whitespace(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('hello world', '  spaces  ', 'no spaces');

        $cleaned = $collection->removeWhitespace();

        $this->assertSame(['helloworld', 'spaces', 'nospaces'], $cleaned->toArray());
    }

    // ==================== SLUGIFY METHOD TESTS ====================

    public function test_slugify_converts_to_url_friendly_format(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('Hello World!', 'Test String 123', 'Special @#$%');

        $slugified = $collection->slugify();

        $this->assertSame(['hello-world', 'test-string-123', 'special'], $slugified->toArray());
    }

    // ==================== WRAP METHOD TESTS ====================

    public function test_wrap_adds_prefix_and_suffix(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('hello', 'world');

        $wrapped = $collection->wrap('(', ')');

        $this->assertSame(['(hello)', '(world)'], $wrapped->toArray());
    }

    public function test_wrap_with_same_prefix_and_suffix(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('hello', 'world');

        $wrapped = $collection->wrap('"');

        $this->assertSame(['"hello"', '"world"'], $wrapped->toArray());
    }

    // ==================== REMOVE_PREFIX METHOD TESTS ====================

    public function test_remove_prefix_strips_prefix_if_present(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('pre_hello', 'pre_world', 'test');

        $result = $collection->removePrefix('pre_');

        $this->assertSame(['hello', 'world', 'test'], $result->toArray());
    }

    // ==================== REMOVE_SUFFIX METHOD TESTS ====================

    public function test_remove_suffix_strips_suffix_if_present(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('hello_suf', 'world_suf', 'test');

        $result = $collection->removeSuffix('_suf');

        $this->assertSame(['hello', 'world', 'test'], $result->toArray());
    }

    // ==================== COLLECTION OPERATIONS TESTS ====================

    public function test_map_works_with_string_collection(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('hello', 'world');

        $mapped = $collection->map(fn (string $item) => strtoupper($item));

        $this->assertSame(['HELLO', 'WORLD'], $mapped->toArray());
    }

    public function test_filter_works_with_string_collection(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('hello', 'world', 'hi');

        $filtered = $collection->filter(fn (string $item) => strlen($item) > 3);

        $this->assertSame(['hello', 'world'], $filtered->toArray());
    }

    public function test_merge_works_with_string_collection(): void
    {
        $collection1 = new StringTypedCollection;
        $collection2 = new StringTypedCollection;
        $collection1->add('a', 'b');
        $collection2->add('c', 'd');

        $merged = $collection1->merge($collection2);

        $this->assertSame(['a', 'b', 'c', 'd'], $merged->toArray());
    }

    // ==================== EDGE CASES TESTS ====================

    public function test_collection_handles_unicode_characters(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('café', 'München', '你好');

        $this->assertCount(3, $collection);
        $this->assertSame('café', $collection[0]);
        $this->assertSame('München', $collection[1]);
        $this->assertSame('你好', $collection[2]);
    }

    public function test_to_lowercase_handles_unicode(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('Café', 'MÜNCHEN', 'HELLO');

        $lowercased = $collection->toLowercase();

        $this->assertSame(['café', 'münchen', 'hello'], $lowercased->toArray());
    }

    public function test_to_uppercase_handles_unicode(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('café', 'münchen', 'hello');

        $uppercased = $collection->toUppercase();

        $this->assertSame(['CAFÉ', 'MÜNCHEN', 'HELLO'], $uppercased->toArray());
    }

    public function test_collection_can_be_json_serialized(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('a', 'b', 'c');

        $json = json_encode($collection);

        $this->assertSame('["a","b","c"]', $json);
    }

    public function test_normalize_works_with_string_collection(): void
    {
        $collection = new StringTypedCollection;
        $collection->add('a', 'b', 'c');

        $normalized = NormalizerChain::get()->normalize($collection);

        $this->assertSame(['a', 'b', 'c'], $normalized);
    }
}
