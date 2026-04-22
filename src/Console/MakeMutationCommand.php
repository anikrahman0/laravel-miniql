<?php

namespace MiniQL\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class MakeMutationCommand extends Command
{
    protected $signature   = 'miniql:make-mutation {name : e.g. CreateUser or CreateUserMutation}';
    protected $description = 'Generate a new MiniQL mutation handler class';

    public function handle(): void
    {
        $name      = $this->argument('name');
        $className = Str::studly(Str::endsWith($name, 'Mutation') ? $name : $name . 'Mutation');
        $path      = app_path("MiniQL/Mutations/{$className}.php");

        if (file_exists($path)) {
            $this->error("Mutation [{$className}] already exists.");
            return;
        }

        $this->ensureDir(dirname($path));

        $stub = $this->buildStub($className);
        file_put_contents($path, $stub);

        $this->info("Mutation created: app/MiniQL/Mutations/{$className}.php");
        $this->line("  → Register it in config/miniql.php under your model's 'mutations' key.");
    }

    protected function buildStub(string $className): string
    {
        return <<<PHP
<?php

namespace App\MiniQL\Mutations;

use MiniQL\Mutations\BaseMutation;

class {$className} extends BaseMutation
{
    protected function rules(): array
    {
        return [
            // 'data.name'  => 'required|string|max:255',
            // 'data.email' => 'required|email|unique:users,email',
        ];
    }

    public function handle(array \$node): mixed
    {
        \$data = \$this->validate(\$node);

        // TODO: implement mutation logic
        // e.g. return \\App\\Models\\YourModel::create(\$data);

        return \$data;
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
