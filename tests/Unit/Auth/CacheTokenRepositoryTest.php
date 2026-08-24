<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\Tests\Unit\Auth;

use Illuminate\Cache\CacheManager;
use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Foundation\Application;
use Jonathan8312\Siigo\Auth\AccessToken;
use Jonathan8312\Siigo\Auth\CacheTokenRepository;
use PHPUnit\Framework\TestCase;

final class CacheTokenRepositoryTest extends TestCase
{
    public function test_put_then_get_round_trips_the_token(): void
    {
        $repository = new CacheTokenRepository($this->cacheFactory());
        $token = new AccessToken('jwt-value', new \DateTimeImmutable('+1 hour'));

        $repository->put('key', $token);
        $retrieved = $repository->get('key');

        $this->assertNotNull($retrieved);
        $this->assertSame('jwt-value', $retrieved->value());
        $this->assertSame(
            $token->expiresAt()->format(\DATE_ATOM),
            $retrieved->expiresAt()->format(\DATE_ATOM),
        );
    }

    public function test_get_returns_null_for_a_missing_key(): void
    {
        $repository = new CacheTokenRepository($this->cacheFactory());

        $this->assertNull($repository->get('missing'));
    }

    public function test_get_returns_null_for_a_malformed_payload(): void
    {
        $cache = $this->cacheFactory();
        $repository = new CacheTokenRepository($cache);
        $cache->store()->put('key', ['unexpected' => 'shape'], 60);

        $this->assertNull($repository->get('key'));
    }

    public function test_put_does_not_store_an_already_expired_token(): void
    {
        $cache = $this->cacheFactory();
        $repository = new CacheTokenRepository($cache);
        $token = new AccessToken('jwt-value', new \DateTimeImmutable('-1 second'));

        $repository->put('key', $token);

        $this->assertNull($repository->get('key'));
    }

    public function test_forget_removes_the_cached_token(): void
    {
        $repository = new CacheTokenRepository($this->cacheFactory());
        $repository->put('key', new AccessToken('jwt-value', new \DateTimeImmutable('+1 hour')));

        $repository->forget('key');

        $this->assertNull($repository->get('key'));
    }

    private function cacheFactory(): CacheManager
    {
        $app = new Application;
        $app['config'] = new ConfigRepository([
            'cache' => ['default' => 'array', 'stores' => ['array' => ['driver' => 'array']]],
        ]);

        return new CacheManager($app);
    }
}
