<?php

namespace MiniQL\Contracts;

interface ResolverInterface
{
    /**
     * Return an Eloquent query builder for the given node definition.
     *
     * @param array $node  The parsed query node (where, fields, limit …)
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(array $node): \Illuminate\Database\Eloquent\Builder;
}
