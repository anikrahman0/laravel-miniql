<?php

namespace MiniQL\Contracts;

interface HookInterface
{
    /**
     * @param  mixed  &$context  Query node (before) or result (after).
     */
    public function handle(mixed &$context): void;
}
