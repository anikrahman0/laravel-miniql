<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Route Configuration
    |--------------------------------------------------------------------------
    */
    'route' => [
        'prefix'     => 'api/miniql',
        'middleware' => ['api'],
        'name'       => 'miniql',
    ],

    /*
    |--------------------------------------------------------------------------
    | Security
    |--------------------------------------------------------------------------
    */
    'security' => [
        // Maximum allowed query nesting depth (prevents abuse)
        'max_depth'          => 5,

        // Maximum number of top-level queries per request
        'max_query_nodes'    => 10,

        // Maximum results per query (hard cap, overrides per-query limit)
        'max_results'        => 1000,

        // Require authentication for all endpoints
        'require_auth'       => false,

        // Rate limiting (requests per minute per IP, 0 = disabled)
        'rate_limit'         => 60,

        // Allowed origins for CORS (* = all)
        'allowed_origins'    => ['*'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    */
    'cache' => [
        'enabled'  => env('MINIQL_CACHE', false),
        'ttl'      => env('MINIQL_CACHE_TTL', 60),   // seconds
        'store'    => env('MINIQL_CACHE_STORE', 'redis'),
        // Fields that bust cache automatically when mutated
        'auto_invalidate' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Pagination defaults
    |--------------------------------------------------------------------------
    */
    'pagination' => [
        'default_per_page' => 15,
        'max_per_page'     => 100,
    ],

    /*
    |--------------------------------------------------------------------------
    | Schema / Models
    |--------------------------------------------------------------------------
    | Each key is the "type name" used in queries.
    |
    | 'fields'    => whitelist of selectable columns
    | 'relations' => whitelist of eager-loadable relations
    | 'resolver'  => optional custom resolver class
    | 'mutations' => map of mutation name => handler class
    | 'hooks'     => before/after hooks for queries and mutations
    | 'scopes'    => global query scopes applied to every query
    | 'meta'      => optional free-form metadata
    */
    'models' => [

        'users' => [
            'model'      => \App\Models\User::class,
            'fields'     => ['id', 'name', 'email', 'created_at'],
            'relations'  => ['posts'],
            'resolver'   => null,    // e.g. App\MiniQL\Resolvers\UserResolver::class
            'mutations'  => [
                'createUser' => \App\MiniQL\Mutations\CreateUserMutation::class,
                'updateUser' => \App\MiniQL\Mutations\UpdateUserMutation::class,
                'deleteUser' => \App\MiniQL\Mutations\DeleteUserMutation::class,
            ],
            'hooks'      => [
                // 'before_query'    => App\MiniQL\Hooks\UserBeforeQueryHook::class,
                // 'after_query'     => App\MiniQL\Hooks\UserAfterQueryHook::class,
                // 'before_mutation' => App\MiniQL\Hooks\UserBeforeMutationHook::class,
                // 'after_mutation'  => App\MiniQL\Hooks\UserAfterMutationHook::class,
            ],
            'scopes'     => [],   // e.g. ['active'] applies ->active() scope
            'meta'       => [],
        ],

        'posts' => [
            'model'      => \App\Models\Post::class,
            'fields'     => ['id', 'title', 'body', 'user_id', 'created_at'],
            'relations'  => ['user', 'comments'],
            'resolver'   => null,
            'mutations'  => [
                'createPost' => \App\MiniQL\Mutations\CreatePostMutation::class,
            ],
            'hooks'      => [],
            'scopes'     => [],
            'meta'       => [],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Global Middleware for query execution (not HTTP middleware)
    |--------------------------------------------------------------------------
    */
    'execution_middleware' => [
        // \App\MiniQL\Middleware\LogQueryMiddleware::class,
        // \App\MiniQL\Middleware\TenantScopeMiddleware::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Introspection
    |--------------------------------------------------------------------------
    | When enabled, GET /api/miniql/schema returns the full schema as JSON.
    */
    'introspection' => [
        'enabled'    => env('MINIQL_INTROSPECTION', true),
        'middleware' => [], // e.g. ['auth:sanctum'] to protect it
    ],

    /*
    |--------------------------------------------------------------------------
    | Error Handling
    |--------------------------------------------------------------------------
    */
    'errors' => [
        // Return SQL errors in debug mode only
        'expose_sql'         => env('APP_DEBUG', false),
        // Include stack trace in error responses (debug only)
        'expose_trace'       => env('APP_DEBUG', false),
        // Log every query error
        'log_errors'         => true,
    ],

];
