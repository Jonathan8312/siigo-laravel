<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\Auth\Contracts;

use Jonathan8312\Siigo\Auth\AccessToken;

/**
 * Persists and retrieves a Siigo {@see AccessToken} by an opaque cache
 * key. Deliberately generic — it knows nothing about Siigo credentials
 * or the /auth endpoint, only how to store and fetch a token.
 */
interface TokenRepository
{
    public function get(string $key): ?AccessToken;

    public function put(string $key, AccessToken $token): void;

    public function forget(string $key): void;
}
