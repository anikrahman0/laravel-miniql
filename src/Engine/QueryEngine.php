<?php

namespace MiniQL\Engine;

use Illuminate\Support\Collection;
use MiniQL\Cache\QueryCache;
use MiniQL\Contracts\ResolverInterface;
use MiniQL\Contracts\HookInterface;
use MiniQL\Schema\SchemaRegistry;
use MiniQL\Exceptions\QueryException;

class QueryEngine
{
    public function __construct(
        protected SchemaRegistry $registry,
        protected QueryCache     $cache
    ) {}

    /**
     * Execute a full query payload (multiple top-level types).
     */
    public function execute(array $queryPayload): array
    {
        $result = [];

        foreach ($queryPayload as $type => $node) {
            $result[$type] = $this->resolveType($type, $node);
        }

        return $result;
    }

    /**
     * Resolve a single top-level type query.
     */
    public function resolveType(string $type, array $node): mixed
    {
        $config     = $this->registry->get($type);
        $cacheKey   = $this->cache->makeKey($type, $node);

        if ($this->cache->has($cacheKey)) {
            return $this->cache->get($cacheKey);
        }

        // Run before_query hook
        $this->runHook($config, 'before_query', $node);

        // Use custom resolver OR default engine
        $resolverClass = $this->registry->getResolver($type);

        $query = $resolverClass
            ? app($resolverClass)->query($node)
            : $this->buildQuery($type, $node, $config);

        // Apply global execution middleware
        foreach (config('miniql.execution_middleware', []) as $mw) {
            $query = app($mw)->handle($type, $node, $query);
        }

        // Pagination or plain get
        if (!empty($node['page'])) {
            $perPage = $node['perPage'] ?? config('miniql.pagination.default_per_page', 15);
            $raw     = $query->paginate($perPage);
            $items   = collect($raw->items());
            $result  = [
                'data'  => $this->mapCollection($items, $node, $config),
                'meta'  => [
                    'current_page' => $raw->currentPage(),
                    'per_page'     => $raw->perPage(),
                    'total'        => $raw->total(),
                    'last_page'    => $raw->lastPage(),
                    'from'         => $raw->firstItem(),
                    'to'           => $raw->lastItem(),
                ],
            ];
        } else {
            $items  = $query->get();
            $result = $this->mapCollection($items, $node, $config);
        }

        // Run after_query hook
        $this->runHook($config, 'after_query', $result);

        $this->cache->put($cacheKey, $result);

        return $result;
    }

    /**
     * Build an Eloquent query from a node definition.
     */
    protected function buildQuery(string $type, array $node, array $config): \Illuminate\Database\Eloquent\Builder
    {
        $modelClass = $config['model'];
        $query      = $modelClass::query();

        // Apply global scopes from config
        foreach ($this->registry->getScopes($type) as $scope) {
            $query->{$scope}();
        }

        // WHERE (simple + advanced)
        if (!empty($node['where'])) {
            foreach ($node['where'] as $col => $val) {
                if (is_array($val)) {
                    // Advanced: { "status": {"op": "!=", "value": "draft"} }
                    $op    = $val['op']    ?? '=';
                    $value = $val['value'] ?? null;
                    $query->where($col, $op, $value);
                } else {
                    $query->where($col, $val);
                }
            }
        }

        // WHERE IN
        if (!empty($node['whereIn'])) {
            foreach ($node['whereIn'] as $col => $values) {
                $query->whereIn($col, (array) $values);
            }
        }

        // WHERE NULL / NOT NULL
        if (!empty($node['whereNull'])) {
            foreach ((array) $node['whereNull'] as $col) {
                $query->whereNull($col);
            }
        }

        if (!empty($node['whereNotNull'])) {
            foreach ((array) $node['whereNotNull'] as $col) {
                $query->whereNotNull($col);
            }
        }

        // SEARCH (basic LIKE across fields)
        if (!empty($node['search'])) {
            $term   = $node['search']['term']   ?? '';
            $fields = $node['search']['fields'] ?? ($config['fields'] ?? []);
            $query->where(function ($q) use ($fields, $term) {
                foreach ($fields as $field) {
                    $q->orWhere($field, 'LIKE', "%{$term}%");
                }
            });
        }

        // LIMIT
        if (!empty($node['limit'])) {
            $maxResults = config('miniql.security.max_results', 1000);
            $query->limit(min($node['limit'], $maxResults));
        }

        // ORDER BY
        if (!empty($node['orderBy'])) {
            $orderBy   = $node['orderBy'];
            $col       = is_array($orderBy) ? ($orderBy['column'] ?? 'id') : $orderBy;
            $direction = is_array($orderBy) ? ($orderBy['direction'] ?? 'asc') : 'asc';
            $query->orderBy($col, $direction);
        }

        // EAGER LOAD (N+1 prevention)
        if (!empty($node['relations'])) {
            $this->applyEagerLoads($query, $node['relations']);
        }

        return $query;
    }

    /**
     * Recursively build eager loads with nested constraints.
     */
    protected function applyEagerLoads(\Illuminate\Database\Eloquent\Builder $query, array $relations): void
    {
        $withs = [];

        foreach ($relations as $relKey => $relNode) {
            if (!empty($relNode['where']) || !empty($relNode['limit'])) {
                $withs[$relKey] = function ($q) use ($relNode) {
                    if (!empty($relNode['where'])) {
                        foreach ($relNode['where'] as $col => $val) {
                            $q->where($col, $val);
                        }
                    }
                    if (!empty($relNode['limit'])) {
                        $q->limit($relNode['limit']);
                    }
                    if (!empty($relNode['orderBy'])) {
                        $orderBy   = $relNode['orderBy'];
                        $col       = is_array($orderBy) ? ($orderBy['column'] ?? 'id') : $orderBy;
                        $direction = is_array($orderBy) ? ($orderBy['direction'] ?? 'asc') : 'asc';
                        $q->orderBy($col, $direction);
                    }
                };
            } else {
                $withs[] = $relKey;
            }
        }

        $query->with($withs);
    }

    /**
     * Map a collection of Eloquent models to clean arrays.
     */
    protected function mapCollection(Collection $items, array $node, array $config): array
    {
        return $items->map(fn ($item) => $this->mapItem($item, $node, $config))->values()->toArray();
    }

    /**
     * Map a single Eloquent model to a clean array (fields + relations).
     */
    public function mapItem($item, array $node, array $config): array
    {
        $result = [];
        $fields = $node['fields'] ?? $config['fields'];

        foreach ($fields as $field) {
            $result[$field] = $item->{$field};
        }

        if (!empty($node['relations'])) {
            foreach ($node['relations'] as $relKey => $relNode) {
                $related = $item->{$relKey};

                if ($related instanceof Collection) {
                    $result[$relKey] = $related->map(
                        fn ($child) => $this->mapRelated($child, $relNode)
                    )->values()->toArray();
                } else {
                    $result[$relKey] = $related
                        ? $this->mapRelated($related, $relNode)
                        : null;
                }
            }
        }

        return $result;
    }

    /**
     * Map a related (eager-loaded) model.
     */
    protected function mapRelated($item, array $node): array
    {
        $result = [];
        $fields = $node['fields'] ?? [];

        // If no fields specified, return all model visible attributes
        if (empty($fields)) {
            return $item->toArray();
        }

        foreach ($fields as $field) {
            $result[$field] = $item->{$field};
        }

        // Recurse into nested relations
        if (!empty($node['relations'])) {
            foreach ($node['relations'] as $relKey => $relNode) {
                $related = $item->{$relKey};

                if ($related instanceof Collection) {
                    $result[$relKey] = $related->map(
                        fn ($child) => $this->mapRelated($child, $relNode)
                    )->values()->toArray();
                } else {
                    $result[$relKey] = $related
                        ? $this->mapRelated($related, $relNode)
                        : null;
                }
            }
        }

        return $result;
    }

    protected function runHook(array $config, string $hookName, mixed &$context): void
    {
        $hookClass = $config['hooks'][$hookName] ?? null;

        if ($hookClass && class_exists($hookClass)) {
            app($hookClass)->handle($context);
        }
    }
}
