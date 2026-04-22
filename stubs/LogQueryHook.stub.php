<?php

namespace App\MiniQL\Hooks;

use MiniQL\Contracts\HookInterface;

/**
 * Example: LogQueryHook (after_query)
 *
 * Register in config/miniql.php:
 *   'users' => [
 *       'hooks' => [
 *           'after_query' => App\MiniQL\Hooks\LogQueryHook::class,
 *       ],
 *   ]
 */
class LogQueryHook implements HookInterface
{
    public function handle(mixed &$context): void
    {
        // $context is the result array after a query
        $count = is_array($context) ? count($context) : '?';
        logger()->info("[MiniQL] Query returned {$count} result(s).");
    }
}
