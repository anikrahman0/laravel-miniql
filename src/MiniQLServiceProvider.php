<?php

namespace MiniQL;

use Illuminate\Support\ServiceProvider;
use MiniQL\Engine\QueryEngine;
use MiniQL\Engine\MutationEngine;
use MiniQL\Schema\SchemaRegistry;
use MiniQL\Schema\SchemaValidator;
use MiniQL\Cache\QueryCache;
use MiniQL\Console\MakeResolverCommand;
use MiniQL\Console\MakeMutationCommand;
use MiniQL\Console\SchemaDumpCommand;
use MiniQL\Console\SchemaValidateCommand;

class MiniQLServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/miniql.php', 'miniql');

        $this->app->singleton(SchemaRegistry::class, function ($app) {
            return new SchemaRegistry(config('miniql.models', []));
        });

        $this->app->singleton(SchemaValidator::class, function ($app) {
            return new SchemaValidator($app->make(SchemaRegistry::class));
        });

        $this->app->singleton(QueryCache::class, function ($app) {
            return new QueryCache(
                $app['cache.store'],
                config('miniql.cache.ttl', 60),
                config('miniql.cache.enabled', false)
            );
        });

        $this->app->singleton(QueryEngine::class, function ($app) {
            return new QueryEngine(
                $app->make(SchemaRegistry::class),
                $app->make(QueryCache::class)
            );
        });

        $this->app->singleton(MutationEngine::class, function ($app) {
            return new MutationEngine(
                $app->make(SchemaRegistry::class),
                $app->make(SchemaValidator::class)
            );
        });

        // Facade binding
        $this->app->singleton('miniql', function ($app) {
            return new MiniQL(
                $app->make(QueryEngine::class),
                $app->make(MutationEngine::class),
                $app->make(SchemaValidator::class)
            );
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/miniql.php' => config_path('miniql.php'),
            ], 'miniql-config');

            $this->publishes([
                __DIR__ . '/../stubs' => base_path('stubs/miniql'),
            ], 'miniql-stubs');

            $this->commands([
                MakeResolverCommand::class,
                MakeMutationCommand::class,
                SchemaDumpCommand::class,
                SchemaValidateCommand::class,
            ]);
        }

        $this->loadRoutesFrom(__DIR__ . '/../routes/miniql.php');
    }
}
