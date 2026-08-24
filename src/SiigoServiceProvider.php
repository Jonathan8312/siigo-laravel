<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo;

use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\ServiceProvider;
use Jonathan8312\Siigo\Auth\AuthCredentials;
use Jonathan8312\Siigo\Auth\AuthenticationManager;
use Jonathan8312\Siigo\Auth\CacheTokenRepository;
use Jonathan8312\Siigo\Http\Client;
use Jonathan8312\Siigo\Http\ClientConfiguration;
use Jonathan8312\Siigo\Support\CatalogCache;

final class SiigoServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/siigo.php', 'siigo');

        // Laravel's own FoundationServiceProvider already binds
        // Illuminate\Http\Client\Factory as a singleton in every real
        // application (and in Testbench), which is what makes
        // Http::fake() reach requests made through our injected
        // instance. This binding only fills the gap for minimal
        // container setups that have not loaded it.
        if (! $this->app->bound(HttpFactory::class)) {
            $this->app->singleton(HttpFactory::class);
        }

        $this->app->singleton(Siigo::class, function (Application $app): Siigo {
            $config = $app->make('config')->get('siigo', []);
            $config = is_array($config) ? $config : [];
            $retry = is_array($config['retry'] ?? null) ? $config['retry'] : [];
            $cache = is_array($config['cache'] ?? null) ? $config['cache'] : [];

            $credentials = new AuthCredentials(
                self::toStringOrNull($config['username'] ?? null),
                self::toStringOrNull($config['access_key'] ?? null),
            );

            $clientConfiguration = new ClientConfiguration(
                baseUrl: self::toStringOrNull($config['base_url'] ?? null) ?? '',
                partnerId: self::toStringOrNull($config['partner_id'] ?? null) ?? '',
                connectTimeout: self::toFloat($config['connect_timeout'] ?? null, 5.0),
                timeout: self::toFloat($config['timeout'] ?? null, 15.0),
                retryEnabled: self::toBool($retry['enabled'] ?? null, false),
                retryMaxAttempts: self::toInt($retry['max_attempts'] ?? null, 1),
                retryBackoffMilliseconds: self::toInt($retry['backoff_ms'] ?? null, 0),
                maxResponseBytes: self::toInt($config['max_response_bytes'] ?? null, 20_000_000),
                cacheStore: self::toStringOrNull($cache['store'] ?? null),
                tokenSafetyMarginSeconds: self::toInt($cache['token_safety_margin_seconds'] ?? null, 60),
            );

            $tokens = new CacheTokenRepository($app->make(CacheFactory::class), $clientConfiguration->cacheStore);

            $auth = new AuthenticationManager(
                $app->make(HttpFactory::class),
                $credentials,
                $clientConfiguration,
                $tokens,
            );

            $client = new Client($app->make(HttpFactory::class), $auth, $clientConfiguration);

            $catalogCache = new CatalogCache(
                $app->make(CacheFactory::class),
                $clientConfiguration->cacheStore,
                self::toInt($cache['catalog_ttl_seconds'] ?? null, 3600),
            );

            return new Siigo($client, $auth, $catalogCache);
        });

        $this->app->alias(Siigo::class, 'siigo');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/siigo.php' => $this->app->configPath('siigo.php'),
            ], 'siigo-config');
        }
    }

    /**
     * @return array<int, string>
     */
    public function provides(): array
    {
        return [Siigo::class, 'siigo'];
    }

    private static function toStringOrNull(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }

    private static function toFloat(mixed $value, float $default): float
    {
        return is_numeric($value) ? (float) $value : $default;
    }

    private static function toInt(mixed $value, int $default): int
    {
        return is_numeric($value) ? (int) $value : $default;
    }

    private static function toBool(mixed $value, bool $default): bool
    {
        return is_bool($value) ? $value : $default;
    }
}
