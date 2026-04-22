<?php

namespace MiniQL\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class MiniQLRateLimit
{
    public function handle(Request $request, Closure $next): mixed
    {
        $limit = config('miniql.security.rate_limit', 0);

        if ($limit <= 0) {
            return $next($request);
        }

        $key      = 'miniql:' . $request->ip();
        $executed = RateLimiter::attempt($key, $limit, fn () => true);

        if (!$executed) {
            return response()->json([
                'data'   => null,
                'errors' => [['message' => 'Too many requests. Please slow down.']],
            ], 429);
        }

        return $next($request);
    }
}
