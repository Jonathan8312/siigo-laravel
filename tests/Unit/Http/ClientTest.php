<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\Tests\Unit\Http;

use Illuminate\Cache\CacheManager;
use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Foundation\Application;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\Request;
use Jonathan8312\Siigo\Auth\AuthCredentials;
use Jonathan8312\Siigo\Auth\AuthenticationManager;
use Jonathan8312\Siigo\Auth\CacheTokenRepository;
use Jonathan8312\Siigo\Exceptions\AuthenticationException;
use Jonathan8312\Siigo\Exceptions\ConnectionException;
use Jonathan8312\Siigo\Exceptions\NotFoundException;
use Jonathan8312\Siigo\Exceptions\RateLimitException;
use Jonathan8312\Siigo\Exceptions\RequestException;
use Jonathan8312\Siigo\Exceptions\ServerException;
use Jonathan8312\Siigo\Exceptions\ValidationException;
use Jonathan8312\Siigo\Http\Client;
use Jonathan8312\Siigo\Http\ClientConfiguration;
use PHPUnit\Framework\TestCase;

final class ClientTest extends TestCase
{
    public function test_get_sends_authorization_and_partner_id_headers(): void
    {
        $http = $this->fakeHttp(['https://api.siigo.test/auth' => ['access_token' => 'jwt-value', 'expires_in' => 86400]]);
        $http->fake(['https://api.siigo.test/v1/customers*' => $http->response(['results' => []], 200)]);

        $this->client($http)->get('v1/customers');

        $http->assertSent(fn (Request $request): bool => $request->hasHeader('Authorization', 'Bearer jwt-value')
            && $request->hasHeader('Partner-Id', 'TestingPartner'));
    }

    public function test_post_sends_the_idempotency_key_when_given(): void
    {
        $http = $this->fakeHttp(['https://api.siigo.test/auth' => ['access_token' => 'jwt-value', 'expires_in' => 86400]]);
        $http->fake(['https://api.siigo.test/v1/invoices' => $http->response(['id' => '1'], 201)]);

        $this->client($http)->post('v1/invoices', ['name' => 'FV-1'], idempotencyKey: 'invoice1');

        $http->assertSent(fn (Request $request): bool => $request->hasHeader('Idempotency-Key', 'invoice1'));
    }

    public function test_post_rejects_an_idempotency_key_with_a_hyphen(): void
    {
        $http = $this->fakeHttp(['https://api.siigo.test/auth' => ['access_token' => 'jwt-value', 'expires_in' => 86400]]);

        $this->expectException(\InvalidArgumentException::class);

        $this->client($http)->post('v1/invoices', [], idempotencyKey: 'invoice-1');
    }

    public function test_get_omits_the_idempotency_key_header(): void
    {
        $http = $this->fakeHttp(['https://api.siigo.test/auth' => ['access_token' => 'jwt-value', 'expires_in' => 86400]]);
        $http->fake(['https://api.siigo.test/v1/customers*' => $http->response(['results' => []], 200)]);

        $this->client($http)->get('v1/customers');

        $http->assertSent(fn (Request $request): bool => ! $request->hasHeader('Idempotency-Key'));
    }

    public function test_maps_400_to_validation_exception_with_parsed_errors(): void
    {
        $http = $this->fakeHttp(['https://api.siigo.test/auth' => ['access_token' => 'jwt-value', 'expires_in' => 86400]]);
        $http->fake(['https://api.siigo.test/v1/customers' => $http->response([
            'Status' => 400,
            'Errors' => [[
                'Code' => 'parameter_required',
                'Message' => 'The field identification is required',
                'Params' => ['identification'],
                'Detail' => 'Check the API documentation',
            ]],
        ], 400)]);

        try {
            $this->client($http)->post('v1/customers');
            $this->fail('Expected a ValidationException.');
        } catch (ValidationException $exception) {
            $this->assertSame(400, $exception->statusCode());
            $this->assertSame('parameter_required', $exception->errorCode());
            $this->assertSame(['identification'], $exception->errors()[0]->params);
        }
    }

    public function test_maps_404_to_not_found_exception(): void
    {
        $http = $this->fakeHttp(['https://api.siigo.test/auth' => ['access_token' => 'jwt-value', 'expires_in' => 86400]]);
        $http->fake(['https://api.siigo.test/v1/customers/missing' => $http->response([], 404)]);

        $this->expectException(NotFoundException::class);

        $this->client($http)->get('v1/customers/missing');
    }

    public function test_maps_429_to_rate_limit_exception_with_retry_after(): void
    {
        $http = $this->fakeHttp(['https://api.siigo.test/auth' => ['access_token' => 'jwt-value', 'expires_in' => 86400]]);
        $http->fake(['https://api.siigo.test/v1/customers*' => $http->response([], 429, ['Retry-After' => '30'])]);

        try {
            $this->client($http)->get('v1/customers');
            $this->fail('Expected a RateLimitException.');
        } catch (RateLimitException $exception) {
            $this->assertSame(30, $exception->retryAfterSeconds());
        }
    }

    public function test_maps_500_to_server_exception(): void
    {
        $http = $this->fakeHttp(['https://api.siigo.test/auth' => ['access_token' => 'jwt-value', 'expires_in' => 86400]]);
        $http->fake(['https://api.siigo.test/v1/customers*' => $http->response([], 500)]);

        $this->expectException(ServerException::class);

        $this->client($http)->get('v1/customers');
    }

    public function test_maps_403_to_generic_request_exception(): void
    {
        $http = $this->fakeHttp(['https://api.siigo.test/auth' => ['access_token' => 'jwt-value', 'expires_in' => 86400]]);
        $http->fake(['https://api.siigo.test/v1/customers*' => $http->response([], 403)]);

        $this->expectException(RequestException::class);

        $this->client($http)->get('v1/customers');
    }

    public function test_retries_get_on_a_retryable_status_when_enabled(): void
    {
        $http = $this->fakeHttp(['https://api.siigo.test/auth' => ['access_token' => 'jwt-value', 'expires_in' => 86400]]);
        $http->fake(['https://api.siigo.test/v1/customers*' => $http->sequence()
            ->push([], 503)
            ->push(['results' => []], 200)]);

        $response = $this->client($http, retryEnabled: true, retryMaxAttempts: 2)->get('v1/customers');

        $this->assertTrue($response->successful());
    }

    public function test_never_retries_a_post_even_when_retry_is_enabled(): void
    {
        $http = $this->fakeHttp(['https://api.siigo.test/auth' => ['access_token' => 'jwt-value', 'expires_in' => 86400]]);
        $http->fake(['https://api.siigo.test/v1/invoices' => $http->response([], 503)]);

        try {
            $this->client($http, retryEnabled: true, retryMaxAttempts: 3)->post('v1/invoices');
            $this->fail('Expected a ServerException.');
        } catch (ServerException) {
            // 1 auth call + exactly 1 invoice attempt: never retried despite retryEnabled.
            $http->assertSentCount(2);
        }
    }

    public function test_never_retries_a_429_even_when_retry_is_enabled(): void
    {
        $http = $this->fakeHttp(['https://api.siigo.test/auth' => ['access_token' => 'jwt-value', 'expires_in' => 86400]]);
        $http->fake(['https://api.siigo.test/v1/customers*' => $http->response([], 429)]);

        $this->expectException(RateLimitException::class);

        $this->client($http, retryEnabled: true, retryMaxAttempts: 3)->get('v1/customers');
    }

    public function test_forces_one_re_login_and_retries_once_on_a_mid_request_401(): void
    {
        $http = $this->fakeHttp(['https://api.siigo.test/auth' => ['access_token' => 'jwt-value', 'expires_in' => 86400]]);
        $http->fake(['https://api.siigo.test/v1/customers*' => $http->sequence()
            ->push([], 401)
            ->push(['results' => []], 200)]);

        $response = $this->client($http)->get('v1/customers');

        $this->assertTrue($response->successful());
    }

    public function test_does_not_loop_forever_on_a_persistent_401(): void
    {
        $http = $this->fakeHttp(['https://api.siigo.test/auth' => ['access_token' => 'jwt-value', 'expires_in' => 86400]]);
        $http->fake(['https://api.siigo.test/v1/customers*' => $http->response([], 401)]);

        $this->expectException(AuthenticationException::class);

        $this->client($http)->get('v1/customers');
    }

    public function test_maps_a_transport_failure_to_connection_exception(): void
    {
        $http = $this->fakeHttp(['https://api.siigo.test/auth' => ['access_token' => 'jwt-value', 'expires_in' => 86400]]);
        $http->fake(['https://api.siigo.test/v1/customers*' => fn () => throw new \Illuminate\Http\Client\ConnectionException('timed out')]);

        $this->expectException(ConnectionException::class);

        $this->client($http)->get('v1/customers');
    }

    public function test_rejects_an_oversized_response_body(): void
    {
        $http = $this->fakeHttp(['https://api.siigo.test/auth' => ['access_token' => 'jwt-value', 'expires_in' => 86400]]);
        $http->fake(['https://api.siigo.test/v1/customers*' => $http->response(str_repeat('a', 100), 200)]);

        $this->expectException(RequestException::class);

        $this->client($http, maxResponseBytes: 10)->get('v1/customers');
    }

    /**
     * @param  array<string, array<string, mixed>>  $authResponseBodyByUrl
     */
    private function fakeHttp(array $authResponseBodyByUrl): HttpFactory
    {
        $http = new HttpFactory;

        foreach ($authResponseBodyByUrl as $url => $body) {
            $http->fake([$url => $http->response($body, 200)]);
        }

        return $http;
    }

    private function client(
        HttpFactory $http,
        bool $retryEnabled = false,
        int $retryMaxAttempts = 1,
        int $maxResponseBytes = 20_000_000,
    ): Client {
        $config = new ClientConfiguration(
            baseUrl: 'https://api.siigo.test',
            partnerId: 'TestingPartner',
            connectTimeout: 5.0,
            timeout: 15.0,
            retryEnabled: $retryEnabled,
            retryMaxAttempts: $retryMaxAttempts,
            retryBackoffMilliseconds: 0,
            maxResponseBytes: $maxResponseBytes,
        );

        $tokens = new CacheTokenRepository($this->cacheFactory());
        $auth = new AuthenticationManager($http, new AuthCredentials('user@example.com', 'secret-key'), $config, $tokens);

        return new Client($http, $auth, $config);
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
