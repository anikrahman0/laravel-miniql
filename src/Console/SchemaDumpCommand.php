<?php

namespace MiniQL\Console;

use Illuminate\Console\Command;
use MiniQL\Schema\SchemaRegistry;

class SchemaDumpCommand extends Command
{
    protected $signature   = 'miniql:schema-dump {--json : Output raw JSON}';
    protected $description = 'Dump the registered MiniQL schema to the console';

    public function handle(SchemaRegistry $registry): void
    {
        $schema = $registry->introspect();

        if ($this->option('json')) {
            $this->line(json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            return;
        }

        $this->info('═══════════════════════════════════════');
        $this->info('  MiniQL Registered Schema');
        $this->info('═══════════════════════════════════════');

        foreach ($schema as $type => $def) {
            $this->line('');
            $this->comment("  ▸ {$type}");
            $this->line('    Fields    : ' . implode(', ', $def['fields']));
            $this->line('    Relations : ' . (count($def['relations']) ? implode(', ', $def['relations']) : '—'));
            $this->line('    Mutations : ' . (count($def['mutations']) ? implode(', ', $def['mutations']) : '—'));
        }

        $this->line('');
        $this->info('Endpoint: POST /' . config('miniql.route.prefix', 'api/miniql'));
    }
}
