# Authentication

You never construct or manage the Siigo JWT yourself. `Jonathan8312\Siigo\Auth\AuthenticationManager`
does it for you.

## How it works

1. On the first request, the SDK calls `POST https://api.siigo.com/auth` with your
   `SIIGO_USERNAME` and `SIIGO_ACCESS_KEY`.
2. Siigo returns a JWT (`access_token`) valid for 24 hours (`expires_in: 86400`).
3. The SDK stores it via Laravel's `Cache`, keyed by a fingerprint of your credentials — never
   the raw username/access_key.
4. Every subsequent request reuses that cached token, as long as it is not within
   `SIIGO_TOKEN_SAFETY_MARGIN_SECONDS` (default 60s) of expiring.
5. Once it is, the SDK re-authenticates automatically before the next request — you never see
   an expired-token error under normal conditions.

There is no refresh endpoint; re-authenticating is the only way Siigo issues a new token.

## Mid-request 401 recovery

If Siigo rejects an apparently-valid cached token as unauthorized mid-request (for example, an
`access_key` rotated or revoked before the cached token's expiry), the SDK forces exactly one
fresh login and retries the request once — including `POST`/`PUT`/`DELETE` requests. This is
safe because a `401` means Siigo never reached your request's business logic in the first
place, so nothing was executed twice. If the second attempt also fails with `401`, the SDK
gives up and raises `AuthenticationException` rather than looping against a persistently
broken credential.

## Multiple companies (multi-tenant)

Inject `Siigo::class` normally for your application's default company. For any other company,
derive a new, independent instance:

```php
use Jonathan8312\Siigo\Siigo;

$siigo = app(Siigo::class);

$otherCompany = $siigo->withCredentials('other-username', 'other-access-key');
```

`withCredentials()` never mutates the container singleton — it returns a new, detached `Siigo`
instance with its own token cache entry, safe to use concurrently with the default one (and
safe under Octane or other long-running workers, since no shared state is ever written to).

## Security

- The username, access key, and JWT are never exposed as plain string properties anywhere in
  the SDK — they live behind non-serializable value objects (`AuthCredentials`,
  `AccessToken`) that redact themselves under `var_dump()`/`print_r()` and refuse to be
  serialized directly.
- Exceptions thrown by the SDK never carry credentials or the Authorization header — see
  [errors.md](errors.md).
- `Partner-Id` is not a secret, but Siigo does validate its format strictly — see
  [configuration.md](configuration.md) and [known-issues.md](known-issues.md).

## Why not a Facade?

`Jonathan8312\Siigo\Siigo` is registered as a container singleton with a `'siigo'` alias
(`SiigoServiceProvider::register()`), but this package deliberately does not ship a static
`Illuminate\Support\Facades\Siigo` facade on top of it. Two concrete reasons:

1. **A facade would hide the immutability contract above.** Everything in this document works
   because `withCredentials()` returns a *new*, detached `Siigo` instance rather than mutating
   the shared singleton. A static facade call like `Siigo::withCredentials('other-username',
   'other-access-key')` *looks* like a safe, self-contained statement — but the returned
   instance would be silently discarded, and the shared singleton (and therefore every other
   request or job sharing it under Octane) would be completely unaffected. Requiring
   `app(Siigo::class)->withCredentials(...)` first, and forcing you to capture the result, makes
   it obvious you're holding a new object, not mutating a global one.
2. **Facades push testing toward the wrong boundary.** Because every resource is built on
   `Illuminate\Http\Client`, `Http::fake()` already gives you full control over what this SDK
   does in tests (see [`testing.md`](testing.md)) — you fake the actual network boundary, and
   get real assertions about the requests this package sends. A static facade invites
   `Siigo::shouldReceive('invoices')->andReturn(...)`-style mocks instead, which test that your
   code calls the SDK a certain way rather than that it produces the right HTTP request — a
   weaker, more brittle kind of test that also has to be rewritten every time this SDK's
   internals change shape.

None of this stops you from writing your own thin facade in your application if you prefer that
call style for read-only, single-company use — just be aware of point 1 above if you also use
`withCredentials()` anywhere in that same app.
