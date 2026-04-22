<?php

namespace MiniQL;

use MiniQL\Engine\QueryEngine;
use MiniQL\Engine\MutationEngine;
use MiniQL\Schema\SchemaValidator;
use MiniQL\Exceptions\MiniQLException;

class MiniQL
{
    public function __construct(
        protected QueryEngine    $queryEngine,
        protected MutationEngine $mutationEngine,
        protected SchemaValidator $validator
    ) {}

    /**
     * Execute a raw MiniQL payload (query + mutation).
     * Returns ['data' => [...], 'errors' => [...]]
     */
    public function execute(array $payload): array
    {
        $data   = [];
        $errors = [];

        // Handle queries
        if (!empty($payload['query'])) {
            try {
                $this->validator->validateQueryPayload($payload['query']);
                $data = array_merge($data, $this->queryEngine->execute($payload['query']));
            } catch (MiniQLException $e) {
                $errors[] = ['message' => $e->getMessage(), 'type' => 'query_error'];
            }
        }

        // Handle mutations
        if (!empty($payload['mutation'])) {
            try {
                $this->validator->validateMutationPayload($payload['mutation']);
                $mutationResult = $this->mutationEngine->execute($payload['mutation']);
                $data = array_merge($data, $mutationResult);
            } catch (MiniQLException $e) {
                $errors[] = ['message' => $e->getMessage(), 'type' => 'mutation_error'];
            }
        }

        return [
            'data'   => $data,
            'errors' => $errors,
        ];
    }

    /**
     * Fluent query builder shortcut:
     *   MiniQL::query('users')->where('id', 1)->fields(['id','name'])->get()
     */
    public function query(string $model): \MiniQL\Query\FluentQuery
    {
        return new \MiniQL\Query\FluentQuery($model, $this->queryEngine);
    }
}
