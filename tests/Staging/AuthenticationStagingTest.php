<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\Tests\Staging;

use Illuminate\Cache\CacheManager;
use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Foundation\Application;
use Illuminate\Http\Client\Factory as HttpFactory;
use Jonathan8312\Siigo\Auth\AuthCredentials;
use Jonathan8312\Siigo\Auth\AuthenticationManager;
use Jonathan8312\Siigo\Auth\CacheTokenRepository;
use Jonathan8312\Siigo\Http\Client;
use Jonathan8312\Siigo\Http\ClientConfiguration;

/**
 * Fase 1 completion criterion, verified against the real Siigo sandbox:
 * the SDK can authenticate and perform a generic request successfully.
 * Opt-in — see StagingTestCase.
 */
final class AuthenticationStagingTest extends StagingTestCase
{
    public function test_authenticates_against_the_real_siigo_sandbox(): void
    {
        $token = $this->authenticationManager()->token();

        $this->assertNotSame('', $token);
    }

    public function test_performs_a_generic_authenticated_request(): void
    {
        $client = new Client(new HttpFactory, $this->authenticationManager(), $this->configuration());

        $response = $client->get('v1/customers', ['page' => 1, 'page_size' => 1]);

        $this->assertTrue($response->successful());
        $this->assertIsArray($response->json());
        $this->assertArrayHasKey('pagination', $response->json());
        $this->assertArrayHasKey('results', $response->json());
    }

    private function authenticationManager(): AuthenticationManager
    {
        $credentials = new AuthCredentials(
            self::env('SIIGO_USERNAME'),
            self::env('SIIGO_ACCESS_KEY'),
        );

        $tokens = new CacheTokenRepository($this->isolatedCacheFactory());

        return new AuthenticationManager(new HttpFactory, $credentials, $this->configuration(), $tokens);
    }

    private function configuration(): ClientConfiguration
    {
        return new ClientConfiguration(
            baseUrl: self::envOrDefault('SIIGO_BASE_URL', 'https://api.siigo.com'),
            partnerId: self::envOrDefault('SIIGO_PARTNER_ID', 'TREBOLDEV'),
            connectTimeout: 5.0,
            timeout: 30.0,
        );
    }

    /**
     * A fresh, isolated in-memory cache per test run, so staging tests
     * never depend on (or pollute) whatever cache store the rest of the
     * test suite or a developer's machine happens to be using.
     */
    private function isolatedCacheFactory(): CacheManager
    {
        $app = new Application;
        $app['config'] = new ConfigRepository([
            'cache' => ['default' => 'array', 'stores' => ['array' => ['driver' => 'array']]],
        ]);

        return new CacheManager($app);
    }
}
