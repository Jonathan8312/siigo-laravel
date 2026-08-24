# Testing

The package's own test suite lives in three suites:

```text
tests/
├── Unit        # isolated, no container — construct SDK classes directly
├── Feature     # boots a Testbench app, resolves Siigo::class from the container, uses Http::fake()
└── Staging     # hits the real Siigo sandbox, opt-in, never run in CI
```

```bash
composer test          # Unit + Feature
composer test:staging  # Staging — requires .env.staging.local
composer analyse        # PHPStan (level max)
composer format:test    # Pint, check only
```

## Testing your own application's code

Fake the HTTP layer with Laravel's `Http::fake()` — the SDK's `Http\Client` goes through
Laravel's `Illuminate\Http\Client\Factory`, so it is faked the same way any other outgoing
HTTP call in your application would be:

```php
use Illuminate\Support\Facades\Http;
use Jonathan8312\Siigo\Siigo;

Http::fake([
    'https://api.siigo.com/auth' => Http::response([
        'access_token' => 'fake-jwt',
        'expires_in' => 86400,
    ], 200),
    'https://api.siigo.com/v1/customers*' => Http::response([
        'pagination' => ['page' => 1, 'page_size' => 25, 'total_results' => 0],
        'results' => [],
    ], 200),
]);

$siigo = app(Siigo::class);
```

You do not need to fake anything credential-related beyond the `/auth` response above — the
SDK's `AuthenticationManager` handles caching and reuse transparently.

## Staging tests against the real sandbox

`tests/Staging` talks to `https://api.siigo.com` using credentials from
`.env.staging.local` (gitignored — never commit it; see `.env.staging.local.example` for the
expected format). Every staging test extends `Jonathan8312\Siigo\Tests\Staging\StagingTestCase`,
which skips (never fails) automatically when that file is missing or incomplete, so cloning
the repository without credentials still leaves `composer test` fully green.

Staging tests default to read-only (`GET`) requests. A test that performs a write must document
and accept that side effect explicitly in its own docblock.
