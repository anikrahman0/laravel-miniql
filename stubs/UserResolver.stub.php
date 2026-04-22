<?php

namespace App\MiniQL\Resolvers;

use MiniQL\Resolvers\BaseResolver;
use Illuminate\Database\Eloquent\Builder;

/**
 * Example: UserResolver
 *
 * Register in config/miniql.php:
 *   'users' => [
 *       'resolver' => App\MiniQL\Resolvers\UserResolver::class,
 *       ...
 *   ]
 */
class UserResolver extends BaseResolver
{
    protected function model(): string
    {
        return \App\Models\User::class;
    }

    public function query(array $node): Builder
    {
        $q = parent::query($node);

        // Example: always scope to verified users
        $q->whereNotNull('email_verified_at');

        // Example: tenant scoping
        // $q->where('tenant_id', auth()->user()->tenant_id);

        return $q;
    }
}
