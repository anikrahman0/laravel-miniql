<?php

namespace MiniQL\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class MakeResolverCommand extends Command
{
    protected $signature   = 'miniql:make-resolver {name : The resolver class name (e.g. UserResolver)}';
    protected $description = 'Generate a new MiniQL resolver class';

    public function handle(): void
    {
        $name      = $this->argument('name');
        $className = Str::studly(Str::endsWith($name, 'Resolver') ? $name : $name . 'Resolver');
        $path      = app_path("MiniQL/Resolvers/{$className}.php");

        if (file_exists($path)) {
            $this->error("Resolver [{$className}] already exists.");
            return;
        }

        $this->ensureDir(dirname($path));

        $stub = $this->buildStub($className);
        file_put_contents($path, $stub);

        $this->info("Resolver created: app/MiniQL/Resolvers/{$className}.php");
        $this->line("  → Register it in config/miniql.php under your model's 'resolver' key.");
    }

    protected function buildStub(string $className): string
    {
        return <<<PHP
<?php

namespace App\MiniQL\Resolvers;

use MiniQL\Resolvers\BaseResolver;
use Illuminate\Database\Eloquent\Builder;

class {$className} extends BaseResolver
{
    protected function model(): string
    {
        return \\App\\Models\\YourModel::class; // TODO: replace with actual model
    }

    public function query(array \$node): Builder
    {
        \$q = parent::query(\$node);

        // Add your custom query logic here.
        // e.g. \$q->where('active', true);

        return \$q;
    }
}
PHP;
    }

    protected function ensureDir(string $dir): void
    {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }
}
