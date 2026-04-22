<?php

namespace MiniQL\Contracts;

interface MutationHandlerInterface
{
    /**
     * Execute the mutation.
     *
     * @param  array  $node  The mutation input payload.
     * @return mixed         Any serialisable result.
     */
    public function handle(array $node): mixed;
}
