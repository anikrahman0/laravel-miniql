<?php

namespace MiniQL\Resolvers;

use MiniQL\Contracts\ResolverInterface;
use Illuminate\Database\Eloquent\Builder;

abstract class BaseResolver implements ResolverInterface
{
    /**
     * Override to provide the Eloquent model class.
     */
    abstract protected function model(): string;

    public function query(array $node): Builder
    {
        $q = ($this->model())::query();

        if (!empty($node['where'])) {
            foreach ($node['where'] as $col => $val) {
                $q->where($col, $val);
            }
        }

        return $q;
    }
}
