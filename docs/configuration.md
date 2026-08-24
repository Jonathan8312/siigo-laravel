# Configuration

`config/siigo.php` (publish it with `php artisan vendor:publish --tag=siigo-config`, or just
set the environment variables below — the package merges its own defaults regardless).

## Credentials

| Env variable | Config key | Default | Notes |
|---|---|---|---|
| `SIIGO_USERNAME` | `username` | `null` | Optional here if every request goes through `Siigo::withCredentials()`. |
| `SIIGO_ACCESS_KEY` | `access_key` | `null` | Same as above. |

Both are generated in Siigo Nube under **Alianzas → Mi Credencial API**. Never commit them —
keep them in `.env`. See [authentication.md](authentication.md).

## Partner-Id

| Env variable | Config key | Default |
|---|---|---|
| `SIIGO_PARTNER_ID` | `partner_id` | `TREBOLDEV` |

Required by Siigo on every request except the login itself, and **must be strictly
alphanumeric** — 3 to 100 characters, no spaces, hyphens, or other special characters
(confirmed empirically, see [known-issues.md](known-issues.md)). It should identify *your*
integration, not the SDK. The default (`TREBOLDEV`, Trebol, the SDK's maintainer) makes the
package work out of the box, but you should set your own value in production: Siigo monitors
this header and can block accounts that report non-real integration data.

## Base URL

| Env variable | Config key | Default |
|---|---|---|
| `SIIGO_BASE_URL` | `base_url` | `https://api.siigo.com` |

There is no documented separate sandbox URL — production and trial accounts share this same
base URL; only the rate limit differs (see below).

## Timeouts (seconds)

| Env variable | Config key | Default |
|---|---|---|
| `SIIGO_CONNECT_TIMEOUT` | `connect_timeout` | `5` |
| `SIIGO_TIMEOUT` | `timeout` | `15` |

## Retry policy

| Env variable | Config key | Default |
|---|---|---|
| `SIIGO_RETRY_ENABLED` | `retry.enabled` | `false` |
| `SIIGO_RETRY_MAX_ATTEMPTS` | `retry.max_attempts` | `2` |
| `SIIGO_RETRY_BACKOFF_MS` | `retry.backoff_ms` | `200` |

Off by default. When enabled, only `GET` requests are ever retried automatically, and only
for a connection failure or a `502`/`503`/`504` response. `POST`/`PUT`/`DELETE` requests are
never retried automatically, and `HTTP 429` (rate limit) is never retried automatically
either — see [errors.md](errors.md). Siigo's documented limit is 100 requests/minute in
production, 10 requests/minute on trial accounts.

## Maximum response size

| Env variable | Config key | Default |
|---|---|---|
| `SIIGO_MAX_RESPONSE_BYTES` | `max_response_bytes` | `20000000` (20 MB) |

A defense-in-depth guard: any response body larger than this raises a `RequestException`
instead of being decoded, protecting against unbounded memory use from an unexpectedly large
or malformed response.

## Cache

| Env variable | Config key | Default |
|---|---|---|
| `SIIGO_CACHE_STORE` | `cache.store` | `null` (application's default cache store) |
| `SIIGO_TOKEN_SAFETY_MARGIN_SECONDS` | `cache.token_safety_margin_seconds` | `60` |
| `SIIGO_CATALOG_CACHE_TTL_SECONDS` | `cache.catalog_ttl_seconds` | `3600` (1 hour) |

Siigo's JWT is valid for 24h with no refresh endpoint, so the SDK caches it via Laravel's
`Cache` and only re-authenticates once the cached token is within
`token_safety_margin_seconds` of expiring — this avoids a request racing against the token
expiring mid-flight. See [authentication.md](authentication.md).

`catalog_ttl_seconds` caches `$siigo->catalogs()` results (taxes, sellers, cost centers, ...) in
the same `store`, keyed per company — this is reference data that changes rarely, and
re-fetching it on every invoice/payment receipt burns through Siigo's rate limit fast. Set to
`0` to disable catalog caching and always hit the real API. See [catalogs.md](catalogs.md#caching).
