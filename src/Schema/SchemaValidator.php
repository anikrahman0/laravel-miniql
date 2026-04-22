<?php

namespace MiniQL\Schema;

use MiniQL\Exceptions\ValidationException;

class SchemaValidator
{
    public function __construct(protected SchemaRegistry $registry) {}

    // ──────────────────────────────────────────────
    // Query validation
    // ──────────────────────────────────────────────

    public function validateQueryPayload(array $query): void
    {
        $maxNodes = config('miniql.security.max_query_nodes', 10);

        if (count($query) > $maxNodes) {
            throw new ValidationException(
                "Too many query nodes. Max allowed: {$maxNodes}."
            );
        }

        foreach ($query as $type => $node) {
            $this->validateNode($type, $node);
        }
    }

    public function validateNode(string $type, array $node, int $depth = 0): void
    {
        $maxDepth = config('miniql.security.max_depth', 5);

        if ($depth > $maxDepth) {
            throw new ValidationException("Query depth exceeds allowed maximum of {$maxDepth}.");
        }

        if (!$this->registry->has($type)) {
            throw new ValidationException(
                "Unknown type: [{$type}]. It is not registered in the schema."
            );
        }

        // Validate requested fields
        if (!empty($node['fields'])) {
            $allowed = $this->registry->getAllowedFields($type);
            foreach ($node['fields'] as $field) {
                if (!in_array($field, $allowed, true)) {
                    throw new ValidationException(
                        "Field [{$field}] is not allowed on type [{$type}]."
                    );
                }
            }
        }

        // Validate requested relations
        if (!empty($node['relations'])) {
            $allowed = $this->registry->getAllowedRelations($type);
            foreach ($node['relations'] as $relKey => $relNode) {
                if (!in_array($relKey, $allowed, true)) {
                    throw new ValidationException(
                        "Relation [{$relKey}] is not allowed on type [{$type}]."
                    );
                }

                // Recurse into nested relations
                if (!empty($relNode['relations'])) {
                    $this->validateNode($relKey, $relNode, $depth + 1);
                }
            }
        }

        // Validate where clauses (only allowed fields)
        if (!empty($node['where'])) {
            $allowed = $this->registry->getAllowedFields($type);
            foreach ($node['where'] as $col => $val) {
                if (!in_array($col, $allowed, true)) {
                    throw new ValidationException(
                        "Cannot filter on [{$col}] — not in allowed fields for [{$type}]."
                    );
                }
            }
        }

        // Validate orderBy
        if (!empty($node['orderBy'])) {
            $allowed   = $this->registry->getAllowedFields($type);
            $orderBy   = $node['orderBy'];
            $col       = is_array($orderBy) ? ($orderBy['column'] ?? null) : $orderBy;
            $direction = is_array($orderBy) ? strtolower($orderBy['direction'] ?? 'asc') : 'asc';

            if ($col && !in_array($col, $allowed, true)) {
                throw new ValidationException(
                    "Cannot order by [{$col}] — not in allowed fields for [{$type}]."
                );
            }

            if (!in_array($direction, ['asc', 'desc'], true)) {
                throw new ValidationException("Invalid orderBy direction [{$direction}].");
            }
        }

        // Validate pagination
        if (!empty($node['page'])) {
            $maxPerPage = config('miniql.pagination.max_per_page', 100);
            $perPage    = $node['perPage'] ?? config('miniql.pagination.default_per_page', 15);

            if ($perPage > $maxPerPage) {
                throw new ValidationException(
                    "perPage [{$perPage}] exceeds max allowed [{$maxPerPage}]."
                );
            }
        }
    }

    // ──────────────────────────────────────────────
    // Mutation validation
    // ──────────────────────────────────────────────

    public function validateMutationPayload(array $mutations): void
    {
        $globalMutations = $this->registry->getGlobalMutations();

        foreach ($mutations as $name => $node) {
            if (!isset($globalMutations[$name])) {
                throw new ValidationException(
                    "Unknown mutation: [{$name}]. Register it in the schema config."
                );
            }
        }
    }
}
