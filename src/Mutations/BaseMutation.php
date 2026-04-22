<?php

namespace MiniQL\Mutations;

use MiniQL\Contracts\MutationHandlerInterface;
use Illuminate\Support\Facades\Validator;
use MiniQL\Exceptions\ValidationException;

abstract class BaseMutation implements MutationHandlerInterface
{
    /**
     * Override to provide Laravel validation rules for the mutation input.
     */
    protected function rules(): array
    {
        return [];
    }

    /**
     * Override to provide custom error messages.
     */
    protected function messages(): array
    {
        return [];
    }

    /**
     * Validate the node against rules() before handle() runs.
     */
    protected function validate(array $node): array
    {
        $rules = $this->rules();

        if (empty($rules)) {
            return $node['data'] ?? $node;
        }

        $data      = $node['data'] ?? $node;
        $validator = Validator::make($data, $rules, $this->messages());

        if ($validator->fails()) {
            throw new ValidationException(
                'Mutation validation failed: ' . implode(', ', $validator->errors()->all())
            );
        }

        return $validator->validated();
    }
}
