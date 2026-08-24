<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\Auth;

use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Jonathan8312\Siigo\Auth\Contracts\TokenRepository;

/**
 * Stores an {@see AccessToken} through Laravel's Cache, resolved lazily
 * on every call so a runtime store change (or an Octane-reset config
 * value) is always respected rather than captured once at construction.
 *
 * The token itself is never handed to the cache store as an AccessToken
 * object (which refuses serialization by design, see
 * {@see AccessToken::__serialize()}) — only its raw value and expiry are
 * stored, in a plain array this class controls entirely.
 */
final class CacheTokenRepository implements TokenRepository
{
    public function __construct(
        private readonly CacheFactory $cache,
        private readonly ?string $store = null,
    ) {}

    public function get(string $key): ?AccessToken
    {
        $payload = $this->store()->get($key);

        if (! is_array($payload)) {
            return null;
        }

        $token = $payload['token'] ?? null;
        $expiresAt = $payload['expires_at'] ?? null;

        if (! is_string($token) || $token === '' || ! is_string($expiresAt)) {
            return null;
        }

        try {
            return new AccessToken($token, new \DateTimeImmutable($expiresAt));
        } catch (\Exception) {
            // Malformed/foreign cache payload — treat as a cache miss
            // rather than letting a corrupt entry break authentication.
            return null;
        }
    }

    public function put(string $key, AccessToken $token): void
    {
        $ttlSeconds = $token->expiresAt()->getTimestamp() - (new \DateTimeImmutable)->getTimestamp();

        if ($ttlSeconds <= 0) {
            return;
        }

        $this->store()->put($key, [
            'token' => $token->value(),
            'expires_at' => $token->expiresAt()->format(\DATE_ATOM),
        ], $ttlSeconds);
    }

    public function forget(string $key): void
    {
        $this->store()->forget($key);
    }

    private function store(): CacheRepository
    {
        return $this->store !== null ? $this->cache->store($this->store) : $this->cache->store();
    }
}
