<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Unit\Utils;

use AndyDefer\DomainStructures\Utils\StrictDataObject;
use AndyDefer\DomainStructures\Tests\TestCase;

final class StrictDataObjectTest extends TestCase
{
    // ==================== TESTS POUR LA PRÉSERVATION DE LA CASSE ====================

    public function test_preserves_original_key_case_for_magic_getter(): void
    {
        // Arrange
        $data = [
            'user_id' => 1,
            'userName' => 'John Doe',
            'USER_EMAIL' => 'john@example.com',
        ];
        $object = new StrictDataObject($data);

        // Act & Assert
        $this->assertSame(1, $object->user_id);
        $this->assertSame('John Doe', $object->userName);
        $this->assertSame('john@example.com', $object->USER_EMAIL);
    }

    public function test_preserves_original_key_case_for_get_method(): void
    {
        // Arrange
        $data = [
            'user_id' => 1,
            'userName' => 'John Doe',
            'USER_EMAIL' => 'john@example.com',
        ];
        $object = new StrictDataObject($data);

        // Act & Assert
        $this->assertSame(1, $object->get('user_id'));
        $this->assertSame('John Doe', $object->get('userName'));
        $this->assertSame('john@example.com', $object->get('USER_EMAIL'));
    }

    public function test_preserves_original_key_case_for_has_method(): void
    {
        // Arrange
        $object = new StrictDataObject(['user_id' => 1]);

        // Act & Assert
        $this->assertTrue($object->has('user_id'));
        $this->assertFalse($object->has('userId'));
        $this->assertFalse($object->has('USER_ID'));
    }

    public function test_preserves_original_key_case_for_array_access(): void
    {
        // Arrange
        $object = new StrictDataObject(['user_id' => 1]);

        // Act & Assert
        $this->assertTrue(isset($object['user_id']));
        $this->assertFalse(isset($object['userId']));
        $this->assertSame(1, $object['user_id']);
    }

    // ==================== TESTS POUR WITH() ====================

    public function test_with_preserves_key_case_for_new_key(): void
    {
        // Arrange
        $object = new StrictDataObject(['user_id' => 1]);

        // Act
        $newObject = $object->with('userName', 'John Doe');

        // Assert
        $this->assertSame(1, $newObject->user_id);
        $this->assertSame('John Doe', $newObject->userName);
        $this->assertArrayHasKey('user_id', $newObject->toArray());
        $this->assertArrayHasKey('userName', $newObject->toArray());
    }

    public function test_with_preserves_key_case_for_snake_case(): void
    {
        // Arrange
        $object = new StrictDataObject([]);

        // Act
        $newObject = $object->with('snake_case_key', 'value');

        // Assert
        $this->assertSame('value', $newObject->snake_case_key);
        $this->assertArrayHasKey('snake_case_key', $newObject->toArray());
    }

    public function test_with_preserves_key_case_for_camel_case(): void
    {
        // Arrange
        $object = new StrictDataObject([]);

        // Act
        $newObject = $object->with('camelCaseKey', 'value');

        // Assert
        $this->assertSame('value', $newObject->camelCaseKey);
        $this->assertArrayHasKey('camelCaseKey', $newObject->toArray());
    }

    public function test_with_preserves_key_case_for_upper_case(): void
    {
        // Arrange
        $object = new StrictDataObject([]);

        // Act
        $newObject = $object->with('UPPER_CASE_KEY', 'value');

        // Assert
        $this->assertSame('value', $newObject->UPPER_CASE_KEY);
        $this->assertArrayHasKey('UPPER_CASE_KEY', $newObject->toArray());
    }

    // ==================== TESTS POUR MERGE() ====================

    public function test_merge_preserves_key_case_for_all_keys(): void
    {
        // Arrange
        $object = new StrictDataObject(['user_id' => 1]);
        $mergeData = [
            'userName' => 'John',
            'USER_EMAIL' => 'john@example.com',
            'contact.phone' => '123456789',
        ];

        // Act
        $merged = $object->merge($mergeData);

        // Assert
        $array = $merged->toArray();
        $this->assertArrayHasKey('user_id', $array);
        $this->assertArrayHasKey('userName', $array);
        $this->assertArrayHasKey('USER_EMAIL', $array);
        $this->assertArrayHasKey('contact.phone', $array);
        $this->assertSame(1, $array['user_id']);
        $this->assertSame('John', $array['userName']);
        $this->assertSame('john@example.com', $array['USER_EMAIL']);
    }

    // ==================== TESTS POUR WITHOUT() ====================

    public function test_without_preserves_original_key_case_for_removal(): void
    {
        // Arrange
        $object = new StrictDataObject([
            'user_id' => 1,
            'userName' => 'John',
            'USER_EMAIL' => 'john@example.com',
        ]);

        // Act
        $without = $object->without('user_id', 'USER_EMAIL');

        // Assert
        $array = $without->toArray();
        $this->assertArrayNotHasKey('user_id', $array);
        $this->assertArrayHasKey('userName', $array);
        $this->assertArrayNotHasKey('USER_EMAIL', $array);
        $this->assertSame('John', $array['userName']);
    }

    // ==================== TESTS POUR TOARRAY() ====================

    public function test_toArray_preserves_original_key_case(): void
    {
        // Arrange
        $data = [
            'user_id' => 1,
            'userName' => 'John Doe',
            'USER_EMAIL' => 'john@example.com',
        ];
        $object = new StrictDataObject($data);

        // Act
        $array = $object->toArray();

        // Assert
        $this->assertArrayHasKey('user_id', $array);
        $this->assertArrayHasKey('userName', $array);
        $this->assertArrayHasKey('USER_EMAIL', $array);
        $this->assertSame(1, $array['user_id']);
        $this->assertSame('John Doe', $array['userName']);
        $this->assertSame('john@example.com', $array['USER_EMAIL']);
    }

    // ==================== TESTS POUR LES CLÉS MIXTES ====================

    public function test_mixed_snake_and_camel_case_can_coexist(): void
    {
        // Arrange
        $object = new StrictDataObject([
            'user_id' => 1,
            'userId' => 2,
        ]);

        // Act & Assert
        $this->assertSame(1, $object->user_id);
        $this->assertSame(2, $object->userId);

        $array = $object->toArray();
        $this->assertArrayHasKey('user_id', $array);
        $this->assertArrayHasKey('userId', $array);
    }

    public function test_identical_keys_with_different_case_are_distinct(): void
    {
        // Arrange
        $object = new StrictDataObject([
            'Value' => 1,
            'VALUE' => 2,
            'value' => 3,
        ]);

        // Act & Assert
        $this->assertSame(1, $object->Value);
        $this->assertSame(2, $object->VALUE);
        $this->assertSame(3, $object->value);
    }

    // ==================== TESTS POUR NESTED ARRAYS ====================

    public function test_nested_associative_arrays_become_nested_StrictDataObject(): void
    {
        // Arrange
        $data = [
            'user' => [
                'user_id' => 1,
                'user_name' => 'John',
            ],
            'metadata' => [
                'CREATED_AT' => '2024-01-01',
                'updatedAt' => '2024-01-02',
            ],
        ];

        // Act
        $object = new StrictDataObject($data);

        // Assert
        $this->assertInstanceOf(StrictDataObject::class, $object->user);
        $this->assertInstanceOf(StrictDataObject::class, $object->metadata);
        $this->assertSame(1, $object->user->user_id);
        $this->assertSame('John', $object->user->user_name);
        $this->assertSame('2024-01-01', $object->metadata->CREATED_AT);
        $this->assertSame('2024-01-02', $object->metadata->updatedAt);
    }

    // ==================== TESTS POUR FROM() STATIC ====================

    public function test_from_static_method_preserves_key_case(): void
    {
        // Arrange
        $data = [
            'user_id' => 1,
            'userName' => 'John',
            'USER_EMAIL' => 'john@example.com',
        ];

        // Act
        $object = StrictDataObject::from($data);

        // Assert
        $this->assertSame(1, $object->user_id);
        $this->assertSame('John', $object->userName);
        $this->assertSame('john@example.com', $object->USER_EMAIL);
    }

    // ==================== TESTS POUR FROMJSON() ====================

    public function test_fromJson_preserves_key_case(): void
    {
        // Arrange
        $json = '{"user_id":1,"userName":"John","USER_EMAIL":"john@example.com"}';

        // Act
        $object = StrictDataObject::fromJson($json);

        // Assert
        $this->assertSame(1, $object->user_id);
        $this->assertSame('John', $object->userName);
        $this->assertSame('john@example.com', $object->USER_EMAIL);
    }

    // ==================== TESTS POUR L'IMMUTABILITÉ ====================

    public function test_strict_data_object_is_immutable(): void
    {
        // Arrange
        $object = new StrictDataObject(['name' => 'John']);

        // Act & Assert
        $this->expectException(\RuntimeException::class);
        $object->name = 'Jane';
    }
}
