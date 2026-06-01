<?php

declare(strict_types=1);

namespace AndyDefer\DomainStructures\Tests\Unit\Utils;

use AndyDefer\DomainStructures\Utils\StrictDataObject;
use PHPUnit\Framework\TestCase;

final class StrictDataObjectTest extends TestCase
{
    public function test_preserves_original_key_case(): void
    {
        $data = [
            'user_id' => 1,
            'userName' => 'John Doe',
            'USER_EMAIL' => 'john@example.com',
            'camelCaseKey' => 'value',
            'snake_case_key' => 'another',
            'UPPER_CASE' => 'should stay upper',
        ];

        $object = new StrictDataObject($data);

        $this->assertSame(1, $object->user_id);
        $this->assertSame('John Doe', $object->userName);
        $this->assertSame('john@example.com', $object->USER_EMAIL);
        $this->assertSame('value', $object->camelCaseKey);
        $this->assertSame('another', $object->snake_case_key);
        $this->assertSame('should stay upper', $object->UPPER_CASE);
    }

    public function test_toArray_preserves_original_keys(): void
    {
        $data = [
            'user_id' => 1,
            'userName' => 'John Doe',
            'USER_EMAIL' => 'john@example.com',
        ];

        $object = new StrictDataObject($data);
        $array = $object->toArray();

        $this->assertArrayHasKey('user_id', $array);
        $this->assertArrayHasKey('userName', $array);
        $this->assertArrayHasKey('USER_EMAIL', $array);
        $this->assertSame(1, $array['user_id']);
        $this->assertSame('John Doe', $array['userName']);
        $this->assertSame('john@example.com', $array['USER_EMAIL']);
    }

    public function test_has_method_preserves_key_case(): void
    {
        $object = new StrictDataObject(['user_id' => 1]);

        $this->assertTrue($object->has('user_id'));
        $this->assertFalse($object->has('userId'));
        $this->assertFalse($object->has('USER_ID'));
    }

    public function test_get_method_preserves_key_case(): void
    {
        $object = new StrictDataObject([
            'user_id' => 1,
            'userName' => 'John',
        ]);

        $this->assertSame(1, $object->get('user_id'));
        $this->assertSame('John', $object->get('userName'));
        $this->assertNull($object->get('userId'));
        $this->assertSame('default', $object->get('not_exists', 'default'));
    }

    public function test_with_preserves_key_case(): void
    {
        $object = new StrictDataObject(['user_id' => 1]);
        $newObject = $object->with('userName', 'John Doe');

        $this->assertSame(1, $newObject->user_id);
        $this->assertSame('John Doe', $newObject->userName);
        $this->assertArrayHasKey('user_id', $newObject->toArray());
        $this->assertArrayHasKey('userName', $newObject->toArray());
    }

    public function test_with_does_not_normalize_key(): void
    {
        $object = new StrictDataObject([]);

        // Ajouter avec snake_case
        $newObject = $object->with('snake_case_key', 'value');
        $this->assertSame('value', $newObject->snake_case_key);

        // Ajouter avec camelCase
        $newObject2 = $newObject->with('camelCaseKey', 'another');
        $this->assertSame('another', $newObject2->camelCaseKey);

        // Ajouter avec UPPER_CASE
        $newObject3 = $newObject2->with('UPPER_CASE', 'upper');
        $this->assertSame('upper', $newObject3->UPPER_CASE);
    }

    public function test_merge_preserves_key_case(): void
    {
        $object = new StrictDataObject(['user_id' => 1]);
        $merged = $object->merge([
            'userName' => 'John',
            'USER_EMAIL' => 'john@example.com',
        ]);

        $array = $merged->toArray();
        $this->assertArrayHasKey('user_id', $array);
        $this->assertArrayHasKey('userName', $array);
        $this->assertArrayHasKey('USER_EMAIL', $array);
        $this->assertSame(1, $array['user_id']);
        $this->assertSame('John', $array['userName']);
        $this->assertSame('john@example.com', $array['USER_EMAIL']);
    }

    public function test_without_preserves_key_case(): void
    {
        $object = new StrictDataObject([
            'user_id' => 1,
            'userName' => 'John',
            'USER_EMAIL' => 'john@example.com',
        ]);

        $without = $object->without('user_id', 'USER_EMAIL');

        $array = $without->toArray();
        $this->assertArrayNotHasKey('user_id', $array);
        $this->assertArrayHasKey('userName', $array);
        $this->assertArrayNotHasKey('USER_EMAIL', $array);
        $this->assertSame('John', $array['userName']);
    }

    public function test_array_access_preserves_key_case(): void
    {
        $object = new StrictDataObject(['user_id' => 1]);

        $this->assertTrue(isset($object['user_id']));
        $this->assertFalse(isset($object['userId']));
        $this->assertSame(1, $object['user_id']);
    }

    public function test_isset_with_different_cases(): void
    {
        $object = new StrictDataObject([
            'camelCase' => 1,
            'snake_case' => 2,
            'UPPER_CASE' => 3,
        ]);

        $this->assertTrue(isset($object->camelCase));
        $this->assertTrue(isset($object->snake_case));
        $this->assertTrue(isset($object->UPPER_CASE));

        $this->assertFalse(isset($object->camelcase));
        $this->assertFalse(isset($object->CamelCase));
        $this->assertFalse(isset($object->snakeCase));
    }

    public function test_nested_arrays_are_converted_to_StrictDataObject(): void
    {
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

        $object = new StrictDataObject($data);

        $this->assertInstanceOf(StrictDataObject::class, $object->user);
        $this->assertInstanceOf(StrictDataObject::class, $object->metadata);

        $this->assertSame(1, $object->user->user_id);
        $this->assertSame('John', $object->user->user_name);
        $this->assertSame('2024-01-01', $object->metadata->CREATED_AT);
        $this->assertSame('2024-01-02', $object->metadata->updatedAt);
    }

    public function test_from_static_method_preserves_case(): void
    {
        $data = [
            'user_id' => 1,
            'userName' => 'John',
            'USER_EMAIL' => 'john@example.com',
        ];

        $object = StrictDataObject::from($data);

        $this->assertSame(1, $object->user_id);
        $this->assertSame('John', $object->userName);
        $this->assertSame('john@example.com', $object->USER_EMAIL);
    }

    public function test_fromJson_preserves_case(): void
    {
        $json = '{"user_id":1,"userName":"John","USER_EMAIL":"john@example.com"}';
        $object = StrictDataObject::fromJson($json);

        $this->assertSame(1, $object->user_id);
        $this->assertSame('John', $object->userName);
        $this->assertSame('john@example.com', $object->USER_EMAIL);
    }

    public function test_mixed_snake_and_camel_case_can_coexist(): void
    {
        $object = new StrictDataObject([
            'user_id' => 1,
            'userId' => 2,
        ]);

        // Les deux clés coexistent car la casse est différente
        $this->assertSame(1, $object->user_id);
        $this->assertSame(2, $object->userId);

        $array = $object->toArray();
        $this->assertArrayHasKey('user_id', $array);
        $this->assertArrayHasKey('userId', $array);
    }
}
