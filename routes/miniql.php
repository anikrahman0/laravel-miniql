<?php

use Illuminate\Support\Facades\Route;
use MiniQL\Http\Controllers\MiniQLController;
use MiniQL\Http\Middleware\MiniQLRateLimit;

$prefix     = config('miniql.route.prefix', 'api/miniql');
$middleware = config('miniql.route.middleware', ['api']);
$name       = config('miniql.route.name', 'miniql');

// Apply introspection-specific middleware separately
$introspectionMiddleware = array_merge(
    $middleware,
    config('miniql.introspection.middleware', [])
);

Route::prefix($prefix)
    ->middleware(array_merge($middleware, [MiniQLRateLimit::class]))
    ->name($name . '.')
    ->group(function () {

        // Main query/mutation endpoint
        Route::post('/', MiniQLController::class)->name('endpoint');

        // Schema introspection
        Route::get('/schema', [MiniQLController::class, 'schema'])->name('schema');

    });
