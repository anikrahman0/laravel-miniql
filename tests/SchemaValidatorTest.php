<?php

namespace MiniQL\Tests;

use MiniQL\Schema\SchemaRegistry;
use MiniQL\Schema\SchemaValidator;
use MiniQL\Exceptions\ValidationException;
use Orchestra\Testbench\TestCase;
use MiniQL\MiniQLServiceProvider;

class SchemaValidatorTest extends TestCase
{
    protected SchemaRegistry  $registry;
    protected SchemaValidator $validator;

    protected function getPackageProviders($app): array
    {
        return [MiniQLServiceProvider::class];
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->registry = new SchemaRegistry([
            'users' => [
                'model'     => \stdClass::class,
                'fields'    => ['id', 'name', 'email'],
                'relations' => ['posts'],
                'mutations' => [],
                'hooks'     => [],
                'scopes'    => [],
                'meta'      => [],
            ],
            'posts' => [
                'model'     => \stdClass::class,
                'fields'    => ['id', 'title', 'user_id'],
                'relations' => ['user'],
                'mutations' => [],
                'hooks'     => [],
                'scopes'    => [],
                'meta'      => [],
            ],
        ]);

        $this->validator = new SchemaValidator($this->registry);
    }

    public function test_valid_query_passes(): void
    {
        $this->validator->validateQueryPayload([
            'users' => [
                'fields'    => ['id', 'name'],
                'where'     => ['id' => 1],
                'relations' => ['posts' => ['fields' => ['id', 'title']]],
            ],
        ]);

        $this->assertTrue(true); // no exception = pass
    }

    public function test_invalid_type_throws(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessageMatches('/Unknown type/');

        $this->validator->validateQueryPayload([
            'nonexistent' => ['fields' => ['id']],
        ]);
    }

    public function test_invalid_field_throws(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessageMatches('/Field \[password\] is not allowed/');

        $this->validator->validateQueryPayload([
            'users' => ['fields' => ['id', 'password']],
        ]);
    }

    public function test_invalid_relation_throws(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessageMatches('/Relation \[comments\] is not allowed/');

        $this->validator->validateQueryPayload([
            'users' => ['relations' => ['comments' => []]],
        ]);
    }

    public function test_invalid_where_column_throws(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessageMatches('/Cannot filter on \[password\]/');

        $this->validator->validateQueryPayload([
            'users' => ['where' => ['password' => 'secret']],
        ]);
    }

    public function test_too_many_query_nodes_throws(): void
    {
        config(['miniql.security.max_query_nodes' => 2]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessageMatches('/Too many query nodes/');

        $this->validator->validateQueryPayload([
            'users' => [],
            'posts' => [],
            'extra' => [],
        ]);
    }
}
