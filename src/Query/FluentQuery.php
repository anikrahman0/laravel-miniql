<?php

namespace MiniQL\Query;

use MiniQL\Engine\QueryEngine;

/**
 * Fluent query builder:
 *
 *   MiniQL::query('users')
 *       ->fields(['id', 'name'])
 *       ->where('active', true)
 *       ->whereIn('role', ['admin', 'editor'])
 *       ->with('posts', ['fields' => ['id', 'title']])
 *       ->orderBy('created_at', 'desc')
 *       ->limit(20)
 *       ->paginate(15, 1)
 *       ->get();
 */
class FluentQuery
{
    protected array $node = [];

    public function __construct(
        protected string      $type,
        protected QueryEngine $engine
    ) {}

    public function fields(array $fields): static
    {
        $this->node['fields'] = $fields;
        return $this;
    }

    public function where(string $column, mixed $value, string $operator = '='): static
    {
        if ($operator !== '=') {
            $this->node['where'][$column] = ['op' => $operator, 'value' => $value];
        } else {
            $this->node['where'][$column] = $value;
        }
        return $this;
    }

    public function whereIn(string $column, array $values): static
    {
        $this->node['whereIn'][$column] = $values;
        return $this;
    }

    public function whereNull(string $column): static
    {
        $this->node['whereNull'][] = $column;
        return $this;
    }

    public function whereNotNull(string $column): static
    {
        $this->node['whereNotNull'][] = $column;
        return $this;
    }

    public function search(string $term, array $fields = []): static
    {
        $this->node['search'] = ['term' => $term, 'fields' => $fields];
        return $this;
    }

    public function limit(int $limit): static
    {
        $this->node['limit'] = $limit;
        return $this;
    }

    public function orderBy(string $column, string $direction = 'asc'): static
    {
        $this->node['orderBy'] = ['column' => $column, 'direction' => $direction];
        return $this;
    }

    public function with(string $relation, array $relNode = []): static
    {
        $this->node['relations'][$relation] = $relNode;
        return $this;
    }

    public function paginate(int $perPage = 15, int $page = 1): static
    {
        $this->node['page']    = $page;
        $this->node['perPage'] = $perPage;
        return $this;
    }

    /**
     * Execute and return raw array result.
     */
    public function get(): mixed
    {
        $config = app(\MiniQL\Schema\SchemaRegistry::class)->get($this->type);
        return $this->engine->resolveType($this->type, $this->node);
    }

    /**
     * Return the raw node array (for debugging / chaining).
     */
    public function toArray(): array
    {
        return $this->node;
    }
}
