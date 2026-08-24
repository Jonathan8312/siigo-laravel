<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------
    | Siigo API Credentials
    |--------------------------------------------------------------------
    |
    | Siigo issues a JWT access token per company from these two values
    | (username + access_key), generated in Siigo Nube under Alianzas ->
    | "Mi Credencial API". Both are optional here: a multi-tenant
    | application that always selects credentials per request via
    | Siigo::withCredentials() does not need to set these at all. A
    | single-company application typically sets both.
    |
    */

    'username' => env('SIIGO_USERNAME'),

    'access_key' => env('SIIGO_ACCESS_KEY'),

    /*
    |--------------------------------------------------------------------
    | Partner-Id
    |--------------------------------------------------------------------
    |
    | Required by Siigo on every request except the login itself. Must be
    | strictly alphanumeric (3-100 characters, no spaces, hyphens, or
    | other special characters — confirmed empirically, see
    | docs/known-issues.md) and should represent the real integration
    | using the SDK, not the SDK itself. Defaults to TREBOLDEV (Trebol,
    | the SDK's maintainer) so the package works out of the box, but you
    | should set this to your own company/application name in
    | production so Siigo's monitoring reflects your real integration.
    |
    */

    'partner_id' => env('SIIGO_PARTNER_ID', 'TREBOLDEV'),

    /*
    |--------------------------------------------------------------------
    | Siigo Base URL
    |--------------------------------------------------------------------
    |
    | There is no documented separate sandbox base URL: Siigo uses the
    | same https://api.siigo.com for both production and trial accounts,
    | only the rate limit differs (see docs/research/siigo-api-co).
    |
    */

    'base_url' => env('SIIGO_BASE_URL', 'https://api.siigo.com'),

    /*
    |--------------------------------------------------------------------
    | Timeouts (seconds)
    |--------------------------------------------------------------------
    */

    'connect_timeout' => env('SIIGO_CONNECT_TIMEOUT', 5),

    'timeout' => env('SIIGO_TIMEOUT', 15),

    /*
    |--------------------------------------------------------------------
    | Retry Policy
    |--------------------------------------------------------------------
    |
    | Off by default. When enabled, only safe GET requests may be
    | retried automatically, and only for transient connection failures
    | or HTTP 502/503/504 responses. POST/PUT/DELETE requests are never
    | retried automatically regardless of this setting, and HTTP 429 is
    | never retried automatically — Siigo's rate limit (100 req/min in
    | production, 10 req/min on trial accounts) is exposed to the caller
    | as a RateLimitException instead, since blindly retrying a
    | rate-limited request risks contributing to Siigo's documented
    | account-blocking policy for a high error rate.
    |
    */

    'retry' => [
        'enabled' => env('SIIGO_RETRY_ENABLED', false),
        'max_attempts' => env('SIIGO_RETRY_MAX_ATTEMPTS', 2),
        'backoff_ms' => env('SIIGO_RETRY_BACKOFF_MS', 200),
    ],

    /*
    |--------------------------------------------------------------------
    | Maximum Response Size (bytes)
    |--------------------------------------------------------------------
    |
    | A defense-in-depth guard against unbounded memory consumption from
    | an unexpectedly large or malformed Siigo response. Any response
    | body larger than this raises a RequestException instead of being
    | decoded and returned.
    |
    */

    'max_response_bytes' => env('SIIGO_MAX_RESPONSE_BYTES', 20_000_000),

    /*
    |--------------------------------------------------------------------
    | Access Token Cache
    |--------------------------------------------------------------------
    |
    | Siigo's JWT is valid for 24h and there is no refresh endpoint, so
    | the SDK caches it via Laravel's Cache and re-authenticates only
    | once it is close to expiring. "store" defaults to the application's
    | default cache store; set it explicitly if the token should live in
    | a specific store. "token_safety_margin_seconds" renews the token
    | this many seconds before its real expiry, to avoid a request
    | racing against the token expiring mid-flight.
    |
    */

    'cache' => [
        'store' => env('SIIGO_CACHE_STORE'),
        'token_safety_margin_seconds' => env('SIIGO_TOKEN_SAFETY_MARGIN_SECONDS', 60),
    ],

];
