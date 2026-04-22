<?php

namespace App\MiniQL\Mutations;

use MiniQL\Mutations\BaseMutation;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * Example: CreateUserMutation
 *
 * Register in config/miniql.php:
 *   'users' => [
 *       'mutations' => [
 *           'createUser' => App\MiniQL\Mutations\CreateUserMutation::class,
 *       ],
 *       ...
 *   ]
 *
 * Request payload:
 *   {
 *     "mutation": {
 *       "createUser": {
 *         "data": {
 *           "name":  "Apon",
 *           "email": "apon@example.com",
 *           "password": "secret123"
 *         }
 *       }
 *     }
 *   }
 */
class CreateUserMutation extends BaseMutation
{
    protected function rules(): array
    {
        return [
            'data.name'     => 'required|string|max:255',
            'data.email'    => 'required|email|unique:users,email',
            'data.password' => 'required|string|min:8',
        ];
    }

    public function handle(array $node): mixed
    {
        $data = $this->validate($node);

        return User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
        ]);
    }
}
