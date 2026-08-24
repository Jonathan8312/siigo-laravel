<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\Tests\Unit\Auth;

use Illuminate\Cache\CacheManager;
use Illuminate\Config\Repository;
use Illuminate\Foundation\Application;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\Request;
use Jonathan8312\Siigo\Auth\AuthCredentials;
use Jonathan8312\Siigo\Auth\AuthenticationManager;
use Jonathan8312\Siigo\Auth\CacheTokenRepository;
use Jonathan8312\Siigo\Exceptions\AuthenticationException;
use Jonathan8312\Siigo\Http\ClientConfiguration;
use PHPUnit\Framework\TestCase;

final class AuthenticationManagerTest extends TestCase
{
    public function test_token_authenticates_and_caches_on_first_call(): void
    {
        $http = new HttpFactory;
        $http->fake([
            'https://siigo.test/auth' => $http->response([
                'access_token' => 'jwt-value',
                'expires_in' => 86400,
                'token_type' => 'Bearer',
            ], 200),
        ]);

        $manager = $this->makeManager($http);

        $this->assertSame('jwt-value', $manager->token());
        $http->assertSentCount(1);
    }

    public function test_token_reuses_a_cached_valid_token_without_a_second_request(): void
    {
        $http = new HttpFactory;
        $http->fake([
            'https://siigo.test/auth' => $http->response([
                'access_token' => 'jwt-value',
                'expires_in' => 86400,
            ], 200),
        ]);

        $manager = $this->makeManager($http);

        $manager->token();
        $manager->token();

        $http->assertSentCount(1);
    }

    public function test_token_re_authenticates_once_the_cached_token_is_within_the_safety_margin(): void
    {
        $http = new HttpFactory;
        $http->fake([
            'https://siigo.test/auth' => $http->sequence()
                // Expires in 30s, which is inside a 60s safety margin: the
                // very next token() call must treat it as already invalid.
                ->push(['access_token' => 'first-jwt', 'expires_in' => 30], 200)
                ->push(['access_token' => 'second-jwt', 'expires_in' => 86400], 200),
        ]);

        $manager = $this->makeManager($http, tokenSafetyMarginSeconds: 60);

        $this->assertSame('first-jwt', $manager->token());
        $this->assertSame('second-jwt', $manager->token());
        $http->assertSentCount(2);
    }

    public function test_refresh_always_re_authenticates_even_with_a_valid_cached_token(): void
    {
        $http = new HttpFactory;
        $http->fake([
            'https://siigo.test/auth' => $http->sequence()
                ->push(['access_token' => 'first-jwt', 'expires_in' => 86400], 200)
                ->push(['access_token' => 'second-jwt', 'expires_in' => 86400], 200),
        ]);

        $manager = $this->makeManager($http);

        $this->assertSame('first-jwt', $manager->token());
        $this->assertSame('second-jwt', $manager->refresh());
        $http->assertSentCount(2);
    }

    public function test_token_throws_authentication_exception_without_credentials(): void
    {
        $http = new HttpFactory;
        $manager = new AuthenticationManager(
            $http,
            new AuthCredentials,
            $this->configuration(),
            new CacheTokenRepository($this->cacheFactory()),
        );

        $this->expectException(AuthenticationException::class);

        $manager->token();
    }

    public function test_token_throws_authentication_exception_when_siigo_rejects_the_login(): void
    {
        $http = new HttpFactory;
        $http->fake([
            'https://siigo.test/auth' => $http->response([
                'Status' => 400,
                'Errors' => [[
                    'Code' => 'invalid_credentials',
                    'Message' => 'Invalid username or access_key',
                    'Params' => [],
                    'Detail' => 'Check your credentials',
                ]],
            ], 400),
        ]);

        $manager = $this->makeManager($http);

        try {
            $manager->token();
            $this->fail('Expected an AuthenticationException.');
        } catch (AuthenticationException $exception) {
            $this->assertSame(400, $exception->statusCode());
            $this->assertSame('invalid_credentials', $exception->errorCode());
        }
    }

    public function test_authenticate_sends_no_partner_id_header(): void
    {
        $http = new HttpFactory;
        $http->fake([
            'https://siigo.test/auth' => $http->response([
                'access_token' => 'jwt-value',
                'expires_in' => 86400,
            ], 200),
        ]);

        $this->makeManager($http)->token();

        $http->assertSent(fn (Request $request): bool => ! $request->hasHeader('Partner-Id'));
    }

    private function makeManager(HttpFactory $http, int $tokenSafetyMarginSeconds = 60): AuthenticationManager
    {
        return new AuthenticationManager(
            $http,
            new AuthCredentials('user@example.com', 'secret-key'),
            $this->configuration($tokenSafetyMarginSeconds),
            new CacheTokenRepository($this->cacheFactory()),
        );
    }

    private function configuration(int $tokenSafetyMarginSeconds = 60): ClientConfiguration
    {
        return new ClientConfiguration(
            baseUrl: 'https://siigo.test',
            partnerId: 'TestingPartner',
            connectTimeout: 5.0,
            timeout: 15.0,
            tokenSafetyMarginSeconds: $tokenSafetyMarginSeconds,
        );
    }

    private function cacheFactory(): CacheManager
    {
        $app = new Application;
        $app['config'] = new Repository([
            'cache' => ['default' => 'array', 'stores' => ['array' => ['driver' => 'array']]],
        ]);

        return new CacheManager($app);
    }
}
