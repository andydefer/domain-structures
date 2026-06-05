<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Unit\Utils;

use AndyDefer\DomainStructures\Tests\TestCase;
use AndyDefer\DomainStructures\Utils\DataObject;

final class DataObjectTest extends TestCase
{
    // ==================== TESTS POUR LA NORMALISATION CAMELCASE ====================

    public function test_normalizes_snake_case_keys_to_camel_case(): void
    {
        // Arrange
        $data = [
            'user_id' => 1,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email_address' => 'john@example.com',
        ];

        // Act
        $object = new DataObject($data);

        // Assert
        $this->assertSame(1, $object->userId);
        $this->assertSame('John', $object->firstName);
        $this->assertSame('Doe', $object->lastName);
        $this->assertSame('john@example.com', $object->emailAddress);
    }

    public function test_normalizes_keys_with_multiple_underscores(): void
    {
        // Arrange
        $data = [
            'user_id_number' => 12345,
            'email_verified_at' => '2024-01-01',
            'is_active_flag' => true,
        ];

        // Act
        $object = new DataObject($data);

        // Assert
        $this->assertSame(12345, $object->userIdNumber);
        $this->assertSame('2024-01-01', $object->emailVerifiedAt);
        $this->assertTrue($object->isActiveFlag);
    }

    public function test_preserves_camel_case_keys_as_is(): void
    {
        // Arrange
        $data = [
            'userId' => 1,
            'firstName' => 'John',
            'emailAddress' => 'john@example.com',
        ];

        // Act
        $object = new DataObject($data);

        // Assert
        $this->assertSame(1, $object->userId);
        $this->assertSame('John', $object->firstName);
        $this->assertSame('john@example.com', $object->emailAddress);
    }

    // ==================== TESTS POUR GET() AVEC NORMALISATION ====================

    public function test_get_accepts_snake_case_and_returns_camel_case_value(): void
    {
        // Arrange
        $object = new DataObject(['user_name' => 'John Doe']);

        // Act
        $valueViaSnakeCase = $object->get('user_name');
        $valueViaCamelCase = $object->get('userName');

        // Assert
        $this->assertSame('John Doe', $valueViaSnakeCase);
        $this->assertSame('John Doe', $valueViaCamelCase);
    }

    public function test_has_accepts_both_snake_and_camel_case(): void
    {
        // Arrange
        $object = new DataObject(['user_name' => 'John Doe']);

        // Act & Assert
        $this->assertTrue($object->has('user_name'));
        $this->assertTrue($object->has('userName'));
        $this->assertFalse($object->has('userNameX'));
    }

    // ==================== TESTS POUR WITH() ====================

    public function test_with_normalizes_key_to_camel_case(): void
    {
        // Arrange
        $object = new DataObject(['user_id' => 1]);

        // Act
        $newObject = $object->with('first_name', 'John');

        // Assert
        $this->assertSame(1, $newObject->userId);
        $this->assertSame('John', $newObject->firstName);
        $this->assertArrayHasKey('userId', $newObject->toArray());
        $this->assertArrayHasKey('firstName', $newObject->toArray());
    }

    // ==================== TESTS POUR WITHOUT() ====================

    public function test_without_accepts_snake_case_keys(): void
    {
        // Arrange
        $object = new DataObject([
            'user_id' => 1,
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        // Act
        $without = $object->without('user_id', 'last_name');

        // Assert
        $array = $without->toArray();
        $this->assertArrayNotHasKey('userId', $array);
        $this->assertArrayHasKey('firstName', $array);
        $this->assertArrayNotHasKey('lastName', $array);
        $this->assertSame('John', $array['firstName']);
    }

    // ==================== TESTS POUR MAGIC GETTER ====================

    public function test_magic_getter_accepts_both_snake_and_camel_case(): void
    {
        // Arrange
        $object = new DataObject(['user_name' => 'John Doe']);

        // Act & Assert
        $this->assertSame('John Doe', $object->user_name);
        $this->assertSame('John Doe', $object->userName);
    }

    // ==================== TESTS POUR ARRAY ACCESS ====================

    public function test_array_access_accepts_both_snake_and_camel_case(): void
    {
        // Arrange
        $object = new DataObject(['user_name' => 'John Doe']);

        // Act & Assert
        $this->assertSame('John Doe', $object['user_name']);
        $this->assertSame('John Doe', $object['userName']);
        $this->assertTrue(isset($object['user_name']));
        $this->assertTrue(isset($object['userName']));
    }

    // ==================== TESTS POUR TOARRAY() ====================

    public function test_to_array_returns_camel_case_keys(): void
    {
        // Arrange
        $data = [
            'user_id' => 1,
            'first_name' => 'John',
            'last_name' => 'Doe',
        ];
        $object = new DataObject($data);

        // Act
        $array = $object->toArray();

        // Assert
        $this->assertArrayHasKey('userId', $array);
        $this->assertArrayHasKey('firstName', $array);
        $this->assertArrayHasKey('lastName', $array);
        $this->assertArrayNotHasKey('user_id', $array);
        $this->assertArrayNotHasKey('first_name', $array);
        $this->assertArrayNotHasKey('last_name', $array);
        $this->assertSame(1, $array['userId']);
        $this->assertSame('John', $array['firstName']);
        $this->assertSame('Doe', $array['lastName']);
    }

    // ==================== TESTS POUR NESTED ARRAYS ====================

    public function test_nested_arrays_are_normalized_to_camel_case(): void
    {
        // Arrange
        $data = [
            'user_profile' => [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'address_info' => [
                    'street_name' => 'Main St',
                    'zip_code' => '12345',
                ],
            ],
        ];

        // Act
        $object = new DataObject($data);

        // Assert
        $this->assertInstanceOf(DataObject::class, $object->userProfile);
        $this->assertSame('John', $object->userProfile->firstName);
        $this->assertSame('Doe', $object->userProfile->lastName);
        $this->assertSame('Main St', $object->userProfile->addressInfo->streetName);
        $this->assertSame('12345', $object->userProfile->addressInfo->zipCode);
    }

    public function test_nested_indexed_arrays_remain_unchanged(): void
    {
        // Arrange
        $data = [
            'user_list' => [
                ['first_name' => 'John', 'last_name' => 'Doe'],
                ['first_name' => 'Jane', 'last_name' => 'Smith'],
            ],
            'tags' => ['php', 'testing', 'laravel'],
        ];

        // Act
        $object = new DataObject($data);

        // Assert
        $this->assertIsArray($object->userList);
        $this->assertIsArray($object->tags);
        $this->assertSame('John', $object->userList[0]['firstName']);
        $this->assertSame('Jane', $object->userList[1]['firstName']);
    }

    // ==================== TESTS POUR FROM() STATIC ====================

    public function test_from_accepts_snake_case_array_and_normalizes_keys(): void
    {
        // Arrange
        $data = [
            'user_id' => 1,
            'first_name' => 'John',
            'last_name' => 'Doe',
        ];

        // Act
        $object = DataObject::from($data);

        // Assert
        $this->assertSame(1, $object->userId);
        $this->assertSame('John', $object->firstName);
        $this->assertSame('Doe', $object->lastName);
    }

    // ==================== TESTS POUR FROMJSON() ====================

    public function test_from_json_normalizes_snake_case_to_camel_case(): void
    {
        // Arrange
        $json = '{"user_id":1,"first_name":"John","last_name":"Doe"}';

        // Act
        $object = DataObject::fromJson($json);

        // Assert
        $this->assertSame(1, $object->userId);
        $this->assertSame('John', $object->firstName);
        $this->assertSame('Doe', $object->lastName);
    }

    // ==================== TESTS POUR L'IMMUTABILITÉ ====================

    public function test_data_object_is_immutable(): void
    {
        // Arrange
        $object = new DataObject(['name' => 'John']);

        // Act & Assert
        $this->expectException(\RuntimeException::class);
        $object->name = 'Jane';
    }
}
