<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\Tests\Unit\Support;

use Illuminate\Cache\CacheManager;
use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Foundation\Application;
use Jonathan8312\Siigo\Support\CatalogCache;
use PHPUnit\Framework\TestCase;

final class CatalogCacheTest extends TestCase
{
    public function test_remember_only_resolves_once_per_key(): void
    {
        $cache = $this->catalogCache(ttlSeconds: 3600);
        $calls = 0;
        $resolve = function () use (&$calls): string {
            $calls++;

            return 'value';
        };

        $this->assertSame('value', $cache->remember('key-1', $resolve));
        $this->assertSame('value', $cache->remember('key-1', $resolve));

        $this->assertSame(1, $calls);
    }

    public function test_remember_resolves_separately_per_key(): void
    {
        $cache = $this->catalogCache(ttlSeconds: 3600);

        $this->assertSame('a', $cache->remember('key-a', fn (): string => 'a'));
        $this->assertSame('b', $cache->remember('key-b', fn (): string => 'b'));
    }

    public function test_a_ttl_of_zero_disables_caching(): void
    {
        $cache = $this->catalogCache(ttlSeconds: 0);
        $calls = 0;
        $resolve = function () use (&$calls): string {
            $calls++;

            return 'value';
        };

        $cache->remember('key-1', $resolve);
        $cache->remember('key-1', $resolve);

        $this->assertSame(2, $calls);
    }

    private function catalogCache(int $ttlSeconds): CatalogCache
    {
        $app = new Application;
        $app['config'] = new ConfigRepository(['cache' => ['default' => 'array', 'stores' => ['array' => ['driver' => 'array']]]]);

        return new CatalogCache(new CacheManager($app), null, $ttlSeconds);
    }
}
