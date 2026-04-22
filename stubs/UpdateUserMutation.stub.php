<?php

namespace App\MiniQL\Mutations;

use MiniQL\Mutations\BaseMutation;
use App\Models\User;
use MiniQL\Exceptions\MutationException;

/**
 * Example: UpdateUserMutation
 *
 * Request payload:
 *   {
 *     "mutation": {
 *       "updateUser": {
 *         "id": 5,
 *         "data": { "name": "New Name" }
 *       }
 *     }
 *   }
 */
class UpdateUserMutation extends BaseMutation
{
    protected function rules(): array
    {
        return [
            'data.name'  => 'sometimes|string|max:255',
            'data.email' => 'sometimes|email',
        ];
    }

    public function handle(array $node): mixed
    {
        $id   = $node['id'] ?? null;
        $user = User::find($id);

        if (!$user) {
            throw new MutationException("User [{$id}] not found.");
        }

        $data = $this->validate($node);
        $user->update($data);

        return $user->fresh();
    }
}
