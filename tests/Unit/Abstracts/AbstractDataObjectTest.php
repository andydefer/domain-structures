<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Unit\Abstracts;

use AndyDefer\DomainStructures\Tests\TestCase;
use AndyDefer\DomainStructures\Utils\DataObject;
use InvalidArgumentException;
use RuntimeException;

/**
 * Unit tests for DataObject class.
 *
 * This test suite validates the immutable DataObject functionality:
 * - Construction from arrays and objects
 * - Immutability (with, merge, without create new instances)
 * - Property access via magic methods and ArrayAccess
 * - Support for camelCase and snake_case keys
 * - Nested array handling (recursive DataObject conversion)
 * - JSON serialization and fromJson factory
 * - Transformable interface implementation
 *
 * The tests follow the AAA pattern (Arrange, Act, Assert).
 */
final class AbstractDataObjectTest extends TestCase
{
    // ==================== CONSTRUCTOR TESTS ====================

    public function test_constructor_creates_data_object_from_array(): void
    {
        $data = [
            'id' => 1,
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ];

        $dataObject = new DataObject($data);

        $this->assertInstanceOf(DataObject::class, $dataObject);
        $this->assertSame(1, $dataObject->id);
        $this->assertSame('John Doe', $dataObject->name);
        $this->assertSame('john@example.com', $dataObject->email);
    }

    public function test_constructor_creates_empty_data_object(): void
    {
        $dataObject = new DataObject;

        $this->assertInstanceOf(DataObject::class, $dataObject);
        $this->assertFalse($dataObject->has('any_key'));
    }

    public function test_constructor_normalizes_snake_case_keys_to_camel_case(): void
    {
        $data = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email_verified_at' => '2024-01-01 12:00:00',
        ];

        $dataObject = new DataObject($data);

        $this->assertTrue($dataObject->has('firstName'));
        $this->assertTrue($dataObject->has('lastName'));
        $this->assertTrue($dataObject->has('emailVerifiedAt'));
        $this->assertSame('John', $dataObject->firstName);
        $this->assertSame('Doe', $dataObject->lastName);
        $this->assertSame('2024-01-01 12:00:00', $dataObject->emailVerifiedAt);
    }

    public function test_constructor_preserves_camel_case_keys(): void
    {
        $data = [
            'firstName' => 'John',
            'lastName' => 'Doe',
        ];

        $dataObject = new DataObject($data);

        $this->assertTrue($dataObject->has('firstName'));
        $this->assertTrue($dataObject->has('lastName'));
        $this->assertSame('John', $dataObject->firstName);
        $this->assertSame('Doe', $dataObject->lastName);
    }

    public function test_constructor_handles_nested_arrays(): void
    {
        $data = [
            'user' => [
                'name' => 'John',
                'email' => 'john@example.com',
            ],
            'tags' => ['premium', 'vip'],
        ];

        $dataObject = new DataObject($data);

        $this->assertInstanceOf(DataObject::class, $dataObject->user);
        $this->assertSame('John', $dataObject->user->name);
        $this->assertSame('john@example.com', $dataObject->user->email);
        $this->assertIsArray($dataObject->tags);
        $this->assertSame(['premium', 'vip'], $dataObject->tags);
    }

    // ==================== IMMUTABILITY TESTS ====================

    public function test_with_creates_new_instance_with_modified_property(): void
    {
        $original = new DataObject(['name' => 'John']);
        $modified = $original->with('name', 'Jane');

        $this->assertNotSame($original, $modified);
        $this->assertSame('John', $original->name);
        $this->assertSame('Jane', $modified->name);
    }

    public function test_with_adds_new_property(): void
    {
        $original = new DataObject(['name' => 'John']);
        $modified = $original->with('email', 'john@example.com');

        $this->assertFalse($original->has('email'));
        $this->assertTrue($modified->has('email'));
        $this->assertSame('john@example.com', $modified->email);
    }

    public function test_with_normalizes_snake_case_key(): void
    {
        $original = new DataObject(['name' => 'John']);
        $modified = $original->with('email_verified', true);

        $this->assertTrue($modified->has('emailVerified'));
        $this->assertTrue($modified->emailVerified);
    }

    public function test_merge_creates_new_instance_with_merged_data(): void
    {
        $original = new DataObject(['name' => 'John', 'age' => 30]);
        $merged = $original->merge(['email' => 'john@example.com', 'age' => 31]);

        $this->assertNotSame($original, $merged);
        $this->assertSame('John', $original->name);
        $this->assertSame(30, $original->age);
        $this->assertFalse($original->has('email'));
        $this->assertSame('John', $merged->name);
        $this->assertSame(31, $merged->age);
        $this->assertSame('john@example.com', $merged->email);
    }

    public function test_merge_normalizes_snake_case_keys(): void
    {
        $original = new DataObject(['firstName' => 'John']);
        $merged = $original->merge(['last_name' => 'Doe']);

        $this->assertTrue($merged->has('lastName'));
        $this->assertSame('Doe', $merged->lastName);
    }

    public function test_without_creates_new_instance_without_specified_keys(): void
    {
        $original = new DataObject(['name' => 'John', 'email' => 'john@example.com', 'age' => 30]);
        $modified = $original->without('email', 'age');

        $this->assertNotSame($original, $modified);
        $this->assertTrue($original->has('email'));
        $this->assertTrue($original->has('age'));
        $this->assertFalse($modified->has('email'));
        $this->assertFalse($modified->has('age'));
        $this->assertTrue($modified->has('name'));
        $this->assertSame('John', $modified->name);
    }

    public function test_without_normalizes_snake_case_keys(): void
    {
        $original = new DataObject(['firstName' => 'John', 'lastName' => 'Doe']);
        $modified = $original->without('last_name');

        $this->assertFalse($modified->has('lastName'));
        $this->assertTrue($modified->has('firstName'));
    }

    public function test_without_does_nothing_for_non_existent_keys(): void
    {
        $original = new DataObject(['name' => 'John']);
        $modified = $original->without('email', 'phone');

        $this->assertNotSame($original, $modified);
        $this->assertTrue($modified->has('name'));
        $this->assertSame('John', $modified->name);
    }

    // ==================== MAGIC GETTER TESTS ====================

    public function test_magic_getter_returns_value_by_camel_case(): void
    {
        $dataObject = new DataObject(['firstName' => 'John', 'last_name' => 'Doe']);

        $this->assertSame('John', $dataObject->firstName);
        $this->assertSame('Doe', $dataObject->lastName);
    }

    public function test_magic_getter_returns_null_for_non_existent_key(): void
    {
        $dataObject = new DataObject(['name' => 'John']);

        $this->assertNull($dataObject->email);
    }

    public function test_magic_isset_returns_true_for_existent_key_even_when_null(): void
    {
        $dataObject = new DataObject(['name' => null, 'email' => 'john@example.com']);

        $this->assertTrue(isset($dataObject->name));
        $this->assertTrue(isset($dataObject->email));
        $this->assertFalse(isset($dataObject->phone));
    }

    public function test_magic_setter_throws_exception(): void
    {
        $dataObject = new DataObject(['name' => 'John']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('DataObject is immutable');

        $dataObject->name = 'Jane';
    }

    // ==================== ARRAY ACCESS TESTS ====================

    public function test_offset_exists_works_with_string_offset(): void
    {
        $dataObject = new DataObject(['name' => 'John', 'email' => null]);

        $this->assertTrue(isset($dataObject['name']));
        $this->assertTrue(isset($dataObject['email']));
        $this->assertFalse(isset($dataObject['phone']));
    }

    public function test_offset_exists_works_with_snake_case_string(): void
    {
        $dataObject = new DataObject(['firstName' => 'John']);

        $this->assertTrue(isset($dataObject['first_name']));
    }

    public function test_offset_exists_works_with_integer_offset(): void
    {
        $dataObject = new DataObject([0 => 'first', 1 => 'second']);

        $this->assertTrue(isset($dataObject[0]));
        $this->assertTrue(isset($dataObject[1]));
        $this->assertFalse(isset($dataObject[2]));
    }

    public function test_offset_get_works_with_string_offset(): void
    {
        $dataObject = new DataObject(['name' => 'John', 'email' => null]);

        $this->assertSame('John', $dataObject['name']);
        $this->assertNull($dataObject['email']);
        $this->assertNull($dataObject['phone']);
    }

    public function test_offset_get_works_with_snake_case_string(): void
    {
        $dataObject = new DataObject(['firstName' => 'John', 'emailVerifiedAt' => '2024-01-01']);

        $this->assertSame('John', $dataObject['first_name']);
        $this->assertSame('2024-01-01', $dataObject['email_verified_at']);
    }

    public function test_offset_get_works_with_integer_offset(): void
    {
        $dataObject = new DataObject([0 => 'first', 1 => 'second']);

        $this->assertSame('first', $dataObject[0]);
        $this->assertSame('second', $dataObject[1]);
    }

    public function test_offset_set_throws_exception(): void
    {
        $dataObject = new DataObject(['name' => 'John']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('DataObject is immutable');

        $dataObject['name'] = 'Jane';
    }

    public function test_offset_unset_throws_exception(): void
    {
        $dataObject = new DataObject(['name' => 'John']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('DataObject is immutable');

        unset($dataObject['name']);
    }

    // ==================== TO_ARRAY METHOD TESTS ====================

    public function test_to_array_returns_original_array(): void
    {
        $data = [
            'id' => 1,
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ];

        $dataObject = new DataObject($data);
        $result = $dataObject->toArray();

        $this->assertIsArray($result);
        $this->assertSame($data, $result);
    }

    public function test_to_array_converts_nested_data_objects_recursively(): void
    {
        $data = [
            'user' => [
                'name' => 'John',
                'email' => 'john@example.com',
            ],
            'address' => [
                'city' => 'Paris',
                'zip' => '75001',
            ],
        ];

        $dataObject = new DataObject($data);
        $result = $dataObject->toArray();

        $this->assertIsArray($result);
        $this->assertIsArray($result['user']);
        $this->assertIsArray($result['address']);
        $this->assertSame('John', $result['user']['name']);
        $this->assertSame('Paris', $result['address']['city']);
    }

    public function test_to_array_handles_nested_arrays_with_mixed_content(): void
    {
        $innerDataObject = new DataObject(['nested' => 'value']);
        $data = [
            'object' => $innerDataObject,
            'array' => [$innerDataObject, 'simple' => $innerDataObject],
        ];

        $dataObject = new DataObject($data);
        $result = $dataObject->toArray();

        $this->assertIsArray($result);
        $this->assertIsArray($result['object']);
        $this->assertSame('value', $result['object']['nested']);
        $this->assertIsArray($result['array'][0]);
        $this->assertIsArray($result['array']['simple']);
    }

    // ==================== GET AND HAS METHODS TESTS ====================

    public function test_get_returns_value_for_existing_key(): void
    {
        $dataObject = new DataObject(['name' => 'John']);

        $this->assertSame('John', $dataObject->get('name'));
    }

    public function test_get_returns_default_for_non_existent_key(): void
    {
        $dataObject = new DataObject(['name' => 'John']);

        $this->assertNull($dataObject->get('email'));
        $this->assertSame('default', $dataObject->get('email', 'default'));
    }

    public function test_get_returns_null_for_existing_null_value(): void
    {
        $dataObject = new DataObject(['name' => null]);

        $this->assertNull($dataObject->get('name'));
        $this->assertNull($dataObject->get('name', 'default'));
    }

    public function test_get_works_with_snake_case_keys(): void
    {
        $dataObject = new DataObject(['firstName' => 'John']);

        $this->assertSame('John', $dataObject->get('first_name'));
    }

    public function test_has_returns_true_for_existing_key(): void
    {
        $dataObject = new DataObject(['name' => 'John', 'email' => null]);

        $this->assertTrue($dataObject->has('name'));
        $this->assertTrue($dataObject->has('email'));
        $this->assertFalse($dataObject->has('phone'));
    }

    public function test_has_works_with_snake_case_keys(): void
    {
        $dataObject = new DataObject(['firstName' => 'John']);

        $this->assertTrue($dataObject->has('first_name'));
    }

    // ==================== TRANSFORMABLE IMPLEMENTATION TESTS ====================

    public function test_from_returns_same_instance_when_source_is_data_object(): void
    {
        $original = new DataObject(['name' => 'John']);
        $result = DataObject::from($original);

        $this->assertSame($original, $result);
    }

    public function test_from_creates_data_object_from_array(): void
    {
        $data = ['name' => 'John', 'email' => 'john@example.com'];
        $result = DataObject::from($data);

        $this->assertInstanceOf(DataObject::class, $result);
        $this->assertSame('John', $result->name);
        $this->assertSame('john@example.com', $result->email);
    }

    public function test_from_creates_data_object_from_object(): void
    {
        $source = new class
        {
            public string $name = 'John';

            public string $email = 'john@example.com';
        };

        $result = DataObject::from($source);

        $this->assertInstanceOf(DataObject::class, $result);
        $this->assertSame('John', $result->name);
        $this->assertSame('john@example.com', $result->email);
    }

    public function test_from_throws_exception_for_invalid_source(): void
    {
        // Arrange
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Cannot create .*DataObject from integer/');

        // Act
        DataObject::from(42);

        // Assert is handled by expectException
    }

    // ==================== FROM_JSON METHOD TESTS ====================

    public function test_from_json_creates_data_object_from_valid_json(): void
    {
        $json = '{"name":"John","email":"john@example.com"}';
        $result = DataObject::fromJson($json);

        $this->assertInstanceOf(DataObject::class, $result);
        $this->assertSame('John', $result->name);
        $this->assertSame('john@example.com', $result->email);
    }

    public function test_from_json_handles_empty_json(): void
    {
        $result = DataObject::fromJson('{}');

        $this->assertInstanceOf(DataObject::class, $result);
        $this->assertEmpty($result->toArray());
    }

    public function test_from_json_handles_invalid_json_gracefully(): void
    {
        $result = DataObject::fromJson('invalid json');

        $this->assertInstanceOf(DataObject::class, $result);
        $this->assertEmpty($result->toArray());
    }

    // ==================== TO_STRING METHOD TESTS ====================

    public function test_to_string_returns_json_representation(): void
    {
        $dataObject = new DataObject(['name' => 'John', 'age' => 30]);
        $string = (string) $dataObject;

        $this->assertIsString($string);
        $this->assertJson($string);

        $decoded = json_decode($string, true);
        $this->assertSame('John', $decoded['name']);
        $this->assertSame(30, $decoded['age']);
    }

    // ==================== NESTED DATA OBJECT TESTS ====================

    public function test_nested_arrays_are_converted_to_data_objects(): void
    {
        $data = [
            'user' => [
                'profile' => [
                    'name' => 'John',
                    'email' => 'john@example.com',
                ],
            ],
        ];

        $dataObject = new DataObject($data);

        $this->assertInstanceOf(DataObject::class, $dataObject->user);
        $this->assertInstanceOf(DataObject::class, $dataObject->user->profile);
        $this->assertSame('John', $dataObject->user->profile->name);
    }

    public function test_with_handles_nested_data_objects(): void
    {
        $original = new DataObject(['user' => ['name' => 'John']]);
        $modified = $original->with('user', ['name' => 'Jane', 'email' => 'jane@example.com']);

        $this->assertInstanceOf(DataObject::class, $modified->user);
        $this->assertSame('Jane', $modified->user->name);
        $this->assertSame('jane@example.com', $modified->user->email);
    }

    // ==================== EDGE CASES TESTS ====================

    public function test_handles_empty_array_gracefully(): void
    {
        $dataObject = new DataObject([]);

        $this->assertEmpty($dataObject->toArray());
        $this->assertNull($dataObject->any_key);
        $this->assertFalse($dataObject->has('any_key'));
    }

    public function test_handles_array_with_numeric_keys(): void
    {
        $data = [0 => 'first', 1 => 'second', 2 => 'third'];
        $dataObject = new DataObject($data);

        $this->assertSame('first', $dataObject[0]);
        $this->assertSame('second', $dataObject[1]);
        $this->assertSame('third', $dataObject[2]);
    }

    public function test_handles_mixed_keys_correctly(): void
    {
        $data = [
            'name' => 'John',
            0 => 'numeric',
            'address' => 'Paris',
        ];
        $dataObject = new DataObject($data);

        $this->assertSame('John', $dataObject->name);
        $this->assertSame('numeric', $dataObject[0]);
        $this->assertSame('Paris', $dataObject->address);
    }

    public function test_chaining_multiple_operations(): void
    {
        $original = new DataObject(['name' => 'John']);
        $result = $original
            ->with('email', 'john@example.com')
            ->with('age', 30)
            ->merge(['city' => 'Paris', 'age' => 31])
            ->without('email');

        $this->assertNotSame($original, $result);
        $this->assertSame('John', $result->name);
        $this->assertFalse($result->has('email'));
        $this->assertSame(31, $result->age);
        $this->assertSame('Paris', $result->city);
    }

    // ==================== KEY NORMALIZATION TESTS ====================

    public function test_snake_case_keys_are_normalized_correctly(): void
    {
        $data = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email_verified_at' => '2024-01-01',
        ];

        $dataObject = new DataObject($data);

        $this->assertTrue($dataObject->has('firstName'));
        $this->assertTrue($dataObject->has('lastName'));
        $this->assertTrue($dataObject->has('emailVerifiedAt'));
    }

    public function test_complex_camel_case_conversion(): void
    {
        $dataObject = new DataObject(['XMLHttpRequest' => 'value']);
        $this->assertTrue($dataObject->has('XMLHttpRequest'));
    }

    // ==================== CONVERT_VALUE METHOD TESTS ====================

    public function test_convert_value_handles_data_object_preservation(): void
    {

        $user = new DataObject(['name' => 'John', 'age' => 30]);

        // Crée une nouvelle instance
        $updated = $user->with('age', 31);
        $withEmail = $user->with('email', 'john@example.com');

        $inner = new DataObject(['nested' => 'value']);
        $data = ['inner' => $inner];
        $dataObject = new DataObject($data);

        $this->assertSame($inner, $dataObject->inner);
    }

    public function test_convert_value_handles_arrays_with_data_objects(): void
    {
        $inner = new DataObject(['nested' => 'value']);
        $data = ['array' => [$inner, 'simple' => $inner]];
        $dataObject = new DataObject($data);

        $this->assertInstanceOf(DataObject::class, $dataObject->array[0]);
        $this->assertInstanceOf(DataObject::class, $dataObject->array['simple']);
    }
}
