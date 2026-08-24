<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\Auth;

use Illuminate\Http\Client\ConnectionException as IlluminateConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Jonathan8312\Siigo\Auth\Contracts\TokenRepository;
use Jonathan8312\Siigo\Exceptions\AuthenticationException;
use Jonathan8312\Siigo\Exceptions\ConnectionException;
use Jonathan8312\Siigo\Exceptions\SiigoError;
use Jonathan8312\Siigo\Http\Client;
use Jonathan8312\Siigo\Http\ClientConfiguration;
use Jonathan8312\Siigo\Siigo;
use Jonathan8312\Siigo\Support\CatalogCache;

/**
 * Obtains, caches, and renews the Siigo JWT.
 *
 * Siigo issues one token per company, valid for 24h with no refresh
 * endpoint — re-authenticating is the only way to get a new one. This
 * class avoids doing that on every request: it reuses a cached,
 * still-valid token via {@see TokenRepository}, and only calls
 * POST /auth when no valid cached token exists for the current
 * credentials.
 *
 * Talks to Illuminate's {@see HttpFactory} directly rather than going
 * through {@see Client}, since Client itself
 * depends on this class for the Authorization header — going the other
 * way would be circular.
 *
 * Immutable: {@see self::withCredentials()} returns a new, detached
 * instance and never mutates the one it was called on, mirroring
 * {@see Client::withAuthenticationManager()}.
 */
final class AuthenticationManager
{
    private const CACHE_KEY_PREFIX = 'siigo:auth-token:';

    public function __construct(
        private readonly HttpFactory $http,
        private readonly AuthCredentials $credentials,
        private readonly ClientConfiguration $config,
        private readonly TokenRepository $tokens,
    ) {}

    public function withCredentials(AuthCredentials $credentials): self
    {
        return new self($this->http, $credentials, $this->config, $this->tokens);
    }

    /**
     * The current, valid Bearer token value — from cache when possible,
     * otherwise freshly obtained from Siigo.
     */
    public function token(): string
    {
        $cacheKey = $this->cacheKey();
        $cached = $this->tokens->get($cacheKey);

        if ($cached !== null && $cached->isValid(new \DateTimeImmutable, $this->config->tokenSafetyMarginSeconds)) {
            return $cached->value();
        }

        return $this->authenticate($cacheKey)->value();
    }

    /**
     * Force a fresh login, bypassing any cached token. Used by
     * {@see Client} exactly once, when Siigo
     * itself rejects an apparently-valid cached token as unauthorized
     * mid-request (e.g. revoked before its cached expiry).
     */
    public function refresh(): string
    {
        return $this->authenticate($this->cacheKey())->value();
    }

    private function authenticate(string $cacheKey): AccessToken
    {
        $issuedAt = new \DateTimeImmutable;

        try {
            $response = $this->http
                ->baseUrl(rtrim($this->config->baseUrl, '/'))
                ->acceptJson()
                ->asJson()
                ->connectTimeout((int) $this->config->connectTimeout)
                ->timeout((int) $this->config->timeout)
                ->withoutRedirecting()
                ->post('auth', [
                    'username' => $this->credentials->username(),
                    'access_key' => $this->credentials->accessKey(),
                ]);
        } catch (IlluminateConnectionException $exception) {
            throw new ConnectionException(
                'Unable to reach Siigo while authenticating.',
                endpoint: 'POST /auth',
                previous: $exception,
            );
        }

        if (! $response->successful()) {
            $json = $response->json();

            throw new AuthenticationException(
                "Siigo rejected the authentication request (HTTP {$response->status()}).",
                statusCode: $response->status(),
                endpoint: 'POST /auth',
                errors: SiigoError::manyFromResponseBody(is_array($json) ? $json : null),
            );
        }

        $json = $response->json();
        $token = AccessToken::fromAuthResponse(is_array($json) ? $json : [], $issuedAt);

        $this->tokens->put($cacheKey, $token);

        return $token;
    }

    /**
     * A stable, non-reversible identifier for the current credentials —
     * used by {@see self::cacheKey()} for the token cache, and reused by
     * {@see CatalogCache} so cached catalog
     * data (taxes, sellers, cost centers, ...) never leaks across
     * companies in a multi-tenant application that switches credentials
     * via {@see Siigo::withCredentials()}.
     *
     * @throws AuthenticationException when no credentials are configured.
     */
    public function credentialsFingerprint(): string
    {
        if (! $this->credentials->hasCredentials()) {
            throw new AuthenticationException(
                'No Siigo credentials configured. Set SIIGO_USERNAME and SIIGO_ACCESS_KEY, or call Siigo::withCredentials().',
            );
        }

        return $this->credentials->fingerprint();
    }

    private function cacheKey(): string
    {
        return self::CACHE_KEY_PREFIX.$this->credentialsFingerprint();
    }
}
