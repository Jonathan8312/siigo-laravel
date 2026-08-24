# Errors

Every exception the SDK throws extends `Jonathan8312\Siigo\Exceptions\SiigoException`
(abstract). None of them ever carry credentials, the Authorization header, or unredacted
request payloads.

```php
abstract class SiigoException extends \RuntimeException
{
    public function statusCode(): ?int;   // HTTP status, when a response was actually received
    public function endpoint(): ?string;  // e.g. "GET v1/customers" — never query params, headers, or the base URL
    public function errors(): array;      // list<SiigoError>, parsed from Siigo's `Errors` array
    public function errorCode(): ?string; // convenience: errors()[0]->code, or null
    public function response(): ?SiigoResponse; // the raw response, when one exists
}
```

## Exception hierarchy

| Exception | When |
|---|---|
| `AuthenticationException` | HTTP 401, a rejected `POST /auth`, an unexpected `/auth` response shape, or no credentials configured at all (`statusCode()` is `null` for the last two). |
| `ValidationException` | HTTP 400. Siigo reuses 400 for field validation *and* configuration errors *and* business rule violations (`already_exists`, `duplicated_document`, `blocked_transactions`...) *and* missing/invalid headers (`header_required`, `invalid_partner_id`). Inspect `errorCode()` to branch on the specific case. |
| `NotFoundException` | HTTP 404. |
| `RateLimitException` | HTTP 429. Exposes `retryAfterSeconds()`, from Siigo's `Retry-After` header when present. |
| `ServerException` | HTTP 408, 500, 503, or 504 — Siigo failed to respond in time or failed on its side. A response was received, unlike `ConnectionException`. |
| `ConnectionException` | No HTTP response was ever received: DNS failure, refused connection, TLS failure, or a timeout at the transport level. `response()` is always `null`. |
| `RequestException` | Catch-all: HTTP 403, 409, 415, any other undocumented status, or a response body exceeding `max_response_bytes`. |

## Siigo's error response shape

```json
{
  "Status": 400,
  "Errors": [
    {
      "Code": "parameter_required",
      "Message": "The field code is required",
      "Params": ["code"],
      "Detail": "Check the API documentation"
    }
  ]
}
```

Note the PascalCase keys — an exception to the snake_case used elsewhere in the Siigo API,
confirmed against the real API (see [known-issues.md](known-issues.md)). `Errors` is always an
array: a single request can fail for more than one field/reason at once. Each entry becomes a
`Jonathan8312\Siigo\Exceptions\SiigoError` (`code`, `message`, `params`, `detail`), accessible via
`$exception->errors()`.

## Handling errors

```php
use Jonathan8312\Siigo\Exceptions\ValidationException;
use Jonathan8312\Siigo\Exceptions\RateLimitException;

try {
    $siigo->client()->post('v1/customers', $payload);
} catch (ValidationException $e) {
    foreach ($e->errors() as $error) {
        // $error->code, $error->message, $error->params
    }
} catch (RateLimitException $e) {
    // back off yourself; $e->retryAfterSeconds() when Siigo provides it
}
```

## Retries

The SDK never retries automatically for `POST`/`PUT`/`DELETE`, and never retries a `429`
automatically at all — Siigo documents a policy of blocking accounts with a high error rate
over time, so blindly retrying a rate-limited or failed write risks making that worse. When
`SIIGO_RETRY_ENABLED=true`, only `GET` requests are retried, and only for a connection failure
or a `502`/`503`/`504` response. See [configuration.md](configuration.md).
