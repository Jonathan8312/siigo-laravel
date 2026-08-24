<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\Support;

use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Jonathan8312\Siigo\Auth\CacheTokenRepository;
use Jonathan8312\Siigo\Resources\Catalogs;

/**
 * Caches {@see Catalogs} results through
 * Laravel's Cache, so an application does not have to re-fetch
 * effectively-static reference data (taxes, sellers, cost centers, ...)
 * on every request — Siigo's rate limit is tight enough (10 req/min on
 * trial accounts, see docs/known-issues.md) that an app resolving these
 * on every invoice would burn through it fast.
 *
 * Resolved lazily on every call, same rationale as
 * {@see CacheTokenRepository}. A `ttlSeconds`
 * of `0` or less disables caching entirely — every call passes straight
 * through to `$resolve`.
 *
 * Deliberately does not persist to a database table: this SDK stays a
 * pure Siigo API client (see README "What it does not do") — matching
 * Siigo's own catalog ids to an application's local master data is that
 * application's responsibility, not this package's.
 */
final class CatalogCache
{
    public function __construct(
        private readonly CacheFactory $cache,
        private readonly ?string $store,
        private readonly int $ttlSeconds,
    ) {}

    /**
     * @template TValue
     *
     * @param  \Closure(): TValue  $resolve
     * @return TValue
     */
    public function remember(string $key, \Closure $resolve): mixed
    {
        if ($this->ttlSeconds <= 0) {
            return $resolve();
        }

        return $this->store()->remember($key, $this->ttlSeconds, $resolve);
    }

    private function store(): CacheRepository
    {
        return $this->store !== null ? $this->cache->store($this->store) : $this->cache->store();
    }
}
