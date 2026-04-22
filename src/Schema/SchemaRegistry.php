<?php

namespace MiniQL\Schema;

use MiniQL\Exceptions\SchemaException;

class SchemaRegistry
{
    public function __construct(protected array $schema) {}

    public function all(): array
    {
        return $this->schema;
    }

    public function has(string $type): bool
    {
        return isset($this->schema[$type]);
    }

    public function get(string $type): array
    {
        if (!$this->has($type)) {
            throw new SchemaException("Unknown type: [{$type}]. Check your miniql.php config.");
        }

        return $this->schema[$type];
    }

    public function getModel(string $type): string
    {
        return $this->get($type)['model'];
    }

    public function getAllowedFields(string $type): array
    {
        return $this->get($type)['fields'] ?? [];
    }

    public function getAllowedRelations(string $type): array
    {
        return $this->get($type)['relations'] ?? [];
    }

    public function getResolver(string $type): ?string
    {
        return $this->get($type)['resolver'] ?? null;
    }

    public function getMutation(string $type, string $mutationName): ?string
    {
        return $this->get($type)['mutations'][$mutationName] ?? null;
    }

    public function getGlobalMutations(): array
    {
        $all = [];
        foreach ($this->schema as $type => $def) {
            foreach ($def['mutations'] ?? [] as $name => $class) {
                $all[$name] = ['type' => $type, 'class' => $class];
            }
        }
        return $all;
    }

    public function getHooks(string $type): array
    {
        return $this->get($type)['hooks'] ?? [];
    }

    public function getScopes(string $type): array
    {
        return $this->get($type)['scopes'] ?? [];
    }

    /**
     * Return full introspection payload.
     */
    public function introspect(): array
    {
        $result = [];

        foreach ($this->schema as $type => $def) {
            $result[$type] = [
                'fields'    => $def['fields'] ?? [],
                'relations' => $def['relations'] ?? [],
                'mutations' => array_keys($def['mutations'] ?? []),
                'meta'      => $def['meta'] ?? [],
            ];
        }

        return $result;
    }
}
