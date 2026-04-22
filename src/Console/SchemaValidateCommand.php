<?php

namespace MiniQL\Console;

use Illuminate\Console\Command;
use MiniQL\Schema\SchemaRegistry;

class SchemaValidateCommand extends Command
{
    protected $signature   = 'miniql:schema-validate';
    protected $description = 'Validate the MiniQL schema config — checks models, resolvers, and mutation handlers exist';

    public function handle(SchemaRegistry $registry): int
    {
        $schema  = config('miniql.models', []);
        $errors  = [];
        $checked = 0;

        $this->info('Validating MiniQL schema...');

        foreach ($schema as $type => $def) {
            $checked++;

            // Model
            if (empty($def['model'])) {
                $errors[] = "[{$type}] Missing 'model' key.";
            } elseif (!class_exists($def['model'])) {
                $errors[] = "[{$type}] Model class [{$def['model']}] not found.";
            }

            // Resolver
            if (!empty($def['resolver']) && !class_exists($def['resolver'])) {
                $errors[] = "[{$type}] Resolver class [{$def['resolver']}] not found.";
            }

            // Mutations
            foreach ($def['mutations'] ?? [] as $name => $class) {
                if (!class_exists($class)) {
                    $errors[] = "[{$type}.mutations.{$name}] Handler class [{$class}] not found.";
                } elseif (!method_exists($class, 'handle')) {
                    $errors[] = "[{$type}.mutations.{$name}] Handler [{$class}] is missing handle() method.";
                }
            }

            // Hooks
            foreach ($def['hooks'] ?? [] as $hookName => $class) {
                if ($class && !class_exists($class)) {
                    $errors[] = "[{$type}.hooks.{$hookName}] Hook class [{$class}] not found.";
                }
            }
        }

        if (empty($errors)) {
            $this->info("✔ Schema is valid. Checked {$checked} type(s). No issues found.");
            return self::SUCCESS;
        }

        $this->error('Schema validation failed with ' . count($errors) . ' error(s):');
        foreach ($errors as $err) {
            $this->line("  ✗ {$err}");
        }

        return self::FAILURE;
    }
}
