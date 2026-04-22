<?php

namespace MiniQL\Cache;

use Illuminate\Contracts\Cache\Repository;

class QueryCache
{
    protected string $prefix = 'miniql:';

    public function __construct(
        protected Repository $store,
        protected int        $ttl,
        protected bool       $enabled
    ) {}

    public function makeKey(string $type, array $node): string
    {
        return $this->prefix . $type . ':' . md5(serialize($node));
    }

    public function has(string $key): bool
    {
        return $this->enabled && $this->store->has($key);
    }

    public function get(string $key): mixed
    {
        return $this->enabled ? $this->store->get($key) : null;
    }

    public function put(string $key, mixed $value): void
    {
        if ($this->enabled) {
            $this->store->put($key, $value, $this->ttl);
        }
    }

    /**
     * Invalidate all cached queries for a type (call after mutations).
     */
    public function invalidateType(string $type): void
    {
        if ($this->enabled) {
            // Works with Redis (tags not needed; prefix-flush pattern)
            $this->store->forget($this->prefix . $type . ':*');
        }
    }

    public function flush(): void
    {
        // Prefix-based flush — works best with Redis
        if ($this->enabled) {
            $this->store->flush();
        }
    }
}
