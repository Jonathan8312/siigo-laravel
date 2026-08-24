<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Jonathan8312\Siigo\Siigo;
use Jonathan8312\Siigo\Tests\TestCase;

/**
 * Multi-tenant usage: a single Laravel application talking to more than
 * one Siigo company via Siigo::withCredentials(), without disturbing the
 * container singleton's own default credentials/token.
 */
final class WithCredentialsFeatureTest extends TestCase
{
    public function test_with_credentials_authenticates_independently_and_does_not_mutate_the_singleton(): void
    {
        Http::fake([
            'https://siigo.test/auth' => Http::sequence()
                ->push(['access_token' => 'default-company-jwt', 'expires_in' => 86400], 200)
                ->push(['access_token' => 'other-company-jwt', 'expires_in' => 86400], 200),
            'https://siigo.test/v1/ping*' => Http::response(['ok' => true], 200),
        ]);

        $default = $this->app()->make(Siigo::class);
        $other = $default->withCredentials('other-company@example.com', 'other-access-key');

        $this->assertNotSame($default, $other);

        $default->client()->get('v1/ping');
        $other->client()->get('v1/ping');

        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://siigo.test/auth'
            && $request['username'] === 'default-testing-username');
        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://siigo.test/auth'
            && $request['username'] === 'other-company@example.com');

        // Resolving the singleton again still returns the untouched default instance.
        $this->assertSame($default, $this->app()->make(Siigo::class));
    }

    public function test_two_companies_cache_tokens_under_independent_keys(): void
    {
        Http::fake([
            'https://siigo.test/auth' => Http::sequence()
                ->push(['access_token' => 'company-a-jwt', 'expires_in' => 86400], 200)
                ->push(['access_token' => 'company-b-jwt', 'expires_in' => 86400], 200),
            'https://siigo.test/v1/ping*' => Http::response(['ok' => true], 200),
        ]);

        $companyA = $this->app()->make(Siigo::class)->withCredentials('a@example.com', 'key-a');
        $companyB = $this->app()->make(Siigo::class)->withCredentials('b@example.com', 'key-b');

        $companyA->client()->get('v1/ping');
        $companyB->client()->get('v1/ping');
        $companyA->client()->get('v1/ping'); // reuses company A's cached token
        $companyB->client()->get('v1/ping'); // reuses company B's cached token

        Http::assertSentCount(6); // 2 auth + 4 pings, never re-authenticating either company
    }
}
