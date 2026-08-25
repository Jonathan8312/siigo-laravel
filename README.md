# Siigo Laravel SDK

A community-maintained Laravel SDK for the public [Siigo API](https://developers.siigo.com/docs/siigoapi)
— Colombian electronic invoicing (facturación electrónica) and related business documents.

[![CI](https://github.com/Jonathan8312/siigo-laravel/actions/workflows/ci.yml/badge.svg)](https://github.com/Jonathan8312/siigo-laravel/actions/workflows/ci.yml)
[![Packagist Version](https://img.shields.io/packagist/v/jonathan8312/siigo-laravel)](https://packagist.org/packages/jonathan8312/siigo-laravel)
[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](#license)

> **Not an official Siigo package.** This is an independent, open-source integration built by
> reading Siigo's public developer documentation, cross-referenced against Siigo's official
> JavaScript SDK (`SiigoSAS/siigo_sdk_javascript`) and its public API Blueprint spec to fill
> documentation gaps, and verified against real behavior on Siigo's sandbox wherever possible
> — see [docs/known-issues.md](docs/known-issues.md) for every discrepancy found this way. It
> is not affiliated with, endorsed by, or supported by Siigo.

## What this package does

`jonathan8312/siigo-laravel` gives your Laravel application a clean, typed, testable way to
talk to Siigo: issuing sales invoices and credit notes, registering payment receipts to
suppliers, managing customers and products, and looking up Siigo's reference catalogs (taxes,
sellers, cost centers, document types, payment types, and more).

It is deliberately **just an SDK** — it does not calculate totals or taxes, does not model your
invoices/customers/products in your own database, and does not make fiscal decisions on your
behalf. Your application owns its domain logic; this package owns talking to Siigo correctly
and safely.

It is also **free, open source, and has no phone-home behavior** — no license checks, no
telemetry, no tracking, no remote activation. It only ever talks to the Siigo base URL you
configure.

## Requirements

| | |
|---|---|
| PHP | 8.2 or newer |
| Laravel | 12.x or 13.x |

This is the exact matrix verified on every change by [CI](https://github.com/Jonathan8312/siigo-laravel/actions/workflows/ci.yml)
(PHP 8.2/8.4 against Laravel 12, PHP 8.3/8.5 against Laravel 13). No other combination is
tested or supported.

## Installation

```bash
composer require jonathan8312/siigo-laravel
```

The package registers itself automatically via Laravel package discovery — no manual service
provider registration needed.

Optionally publish the config file:

```bash
php artisan vendor:publish --tag=siigo-config
```

This creates `config/siigo.php`. Publishing it is optional — the package ships with sensible
defaults for everything except your Siigo credentials, which you must set yourself. See
[docs/installation.md](docs/installation.md) for the full walkthrough.

## Configuration

Set these in your application's `.env`:

```env
SIIGO_USERNAME=
SIIGO_ACCESS_KEY=
SIIGO_PARTNER_ID=YourCompanyName
```

| Variable | Required | Default | Description |
|---|---|---|---|
| `SIIGO_USERNAME` / `SIIGO_ACCESS_KEY` | For the default company | *(none)* | From Siigo Nube, under **Alianzas → Mi Credencial API**. Optional for multi-tenant apps that always select credentials per request via `withCredentials()`. |
| `SIIGO_PARTNER_ID` | No | `TREBOLDEV` | Must represent *your* integration, not this SDK's — see [Configuration](docs/configuration.md) before shipping to production. |
| `SIIGO_BASE_URL` | No | `https://api.siigo.com` | Siigo has no separate documented sandbox URL. |
| `SIIGO_CONNECT_TIMEOUT` / `SIIGO_TIMEOUT` | No | `5` / `15` | Seconds. |
| `SIIGO_RETRY_ENABLED` | No | `false` | Whether idempotent `GET` requests may be retried automatically. |
| `SIIGO_CACHE_STORE` | No | application default | Cache store for the auth token and catalog cache. |
| `SIIGO_CATALOG_CACHE_TTL_SECONDS` | No | `3600` | How long `$siigo->catalogs()` results are cached per company; `0` disables it. |

See [Configuration](docs/configuration.md) for every available option.

## Quick start

```php
use Jonathan8312\Siigo\Siigo;
use Jonathan8312\Siigo\DataTransferObjects\Invoices\CustomerRef;
use Jonathan8312\Siigo\DataTransferObjects\Invoices\DocumentRef;
use Jonathan8312\Siigo\DataTransferObjects\Invoices\InvoiceData;
use Jonathan8312\Siigo\DataTransferObjects\Invoices\InvoiceItem;
use Jonathan8312\Siigo\DataTransferObjects\Invoices\InvoicePayment;
use Jonathan8312\Siigo\DataTransferObjects\Invoices\ItemTaxRef;

$siigo = app(Siigo::class);

$invoice = $siigo->invoices()->create(new InvoiceData(
    document: new DocumentRef(22), // id from $siigo->catalogs()->documentTypes('FV')
    date: '2026-01-15',
    customer: new CustomerRef(identification: '13832081'), // must already exist — see docs/customers.md
    seller: 629, // id from $siigo->catalogs()->users()
    items: [new InvoiceItem(
        code: 'Item-1', // must already exist — see docs/products.md
        quantity: 2,
        price: 50,
        taxes: [new ItemTaxRef(id: 13156)], // id from $siigo->catalogs()->taxes()
    )],
    payments: [new InvoicePayment(id: 5636, value: 87)], // id from $siigo->catalogs()->paymentTypes('FV')
), idempotencyKey: 'order20260115001');

$invoice->id;   // GUID assigned by Siigo
$invoice->name; // e.g. "FV-2-22"

// Later, look it up again:
$siigo->invoices()->find($invoice->id);
```

Every resource is reached through `$siigo->{resource}()`, never through a hand-built Siigo URL
— see each resource's own doc page below for its full request shape, since Siigo's payloads are
too specific to summarize generically here.

## Available resources

| Resource | Accessor | Docs |
|---|---|---|
| Catalogs | `$siigo->catalogs()` | [docs/catalogs.md](docs/catalogs.md) |
| Customers | `$siigo->customers()` | [docs/customers.md](docs/customers.md) |
| Products | `$siigo->products()` | [docs/products.md](docs/products.md) |
| Invoices | `$siigo->invoices()` | [docs/invoices.md](docs/invoices.md) |
| Credit Notes | `$siigo->creditNotes()` | [docs/credit-notes.md](docs/credit-notes.md) |
| Payment Receipts | `$siigo->paymentReceipts()` | [docs/payment-receipts.md](docs/payment-receipts.md) |

Remaining business resources — Purchases, Vouchers, Quotations, Journals, Reports, ... — are
implemented phase by phase. See [Known limitations](#known-limitations) and
[docs/research/siigo-api-co](docs/research/siigo-api-co) for what has already been investigated
against the real Siigo API ahead of each future module.

### Methods, resource by resource

**Catalogs** — `$siigo->catalogs()` — read-only reference data, cached per company (see
[Caching](#caching) below):

| Method | What it does |
|---|---|
| `accountGroups()`, `taxes()`, `priceLists()`, `warehouses()`, `costCenters()` | List each catalog in full. |
| `users(page, pageSize)` | List sellers/vendedores — the only catalog confirmed paginated. |
| `documentTypes(string $type)` | List document types for a type code (`FV`, `FC`, `NC`, `RC`, `RP`, ...). |
| `paymentTypes(string $documentType)` | List payment methods available for a document type. |

**Customers** — `$siigo->customers()`:

| Method | What it does |
|---|---|
| `create(CustomerData $customer)` | Register a new customer/third party (or supplier — `type: Supplier`). |
| `all(...)` | List customers, with filters (identification, active, type, date ranges, ...). |
| `find(string $id)` | Look up a customer by Siigo's internal GUID. |
| `update(string $id, CustomerData $customer)` | Full replace of an existing customer. |
| `delete(string $id)` | Delete a customer. |

**Products** — `$siigo->products()`:

| Method | What it does |
|---|---|
| `create(ProductData $product)` | Register a new product or service. |
| `all(...)` | List products, with filters (code, account group, type, stock control, active, ids, date ranges, ...). |
| `find(string $id)` | Look up a product by Siigo's internal GUID. |
| `update(string $id, ProductData $product)` | Full replace of an existing product. |
| `delete(string $id)` | Delete a product. |

**Invoices** — `$siigo->invoices()`:

| Method | What it does |
|---|---|
| `create(InvoiceData $invoice, ?string $idempotencyKey = null)` | Issue a new sales invoice. |
| `createBatch(string $notificationUrl, array $invoices)` | Issue many invoices in one asynchronous request — see [docs/invoices.md](docs/invoices.md). |
| `all(...)` | List invoices, with filters (document id, dates, customer, ...). |
| `find(string $id)` | Look up an invoice by GUID. |
| `update(string $id, InvoiceData $invoice)` | Full replace of an existing invoice. |
| `delete(string $id)` | Delete an invoice. |
| `annul(string $id)` | Mark an invoice annulled without deleting it. |
| `stampErrors(string $id)` | DIAN rejection messages for an invoice's electronic submission. |
| `pdf(string $id)` / `xml(string $id)` | Base64-encoded PDF / XML (AttachedDocument). |
| `mail(string $id, string $mailTo, ?string $copyTo = null)` | Resend the invoice by email. |

**Credit Notes** — `$siigo->creditNotes()`:

| Method | What it does |
|---|---|
| `create(CreditNoteData $creditNote, ?string $idempotencyKey = null)` | Issue a new credit note, against an existing invoice or a standalone one. |
| `all(...)` | List credit notes, with filters (name, date ranges, ...). |
| `find(string $id)` | Look up a credit note by GUID. |
| `pdf(string $id)` | Base64-encoded PDF. |

No `PUT`, `DELETE`, or annul endpoint exists for this resource — confirmed against Siigo's own
official SDK and documentation, see [docs/credit-notes.md](docs/credit-notes.md).

**Payment Receipts** — `$siigo->paymentReceipts()` — recibos de pago/egreso to suppliers:

| Method | What it does |
|---|---|
| `create(PaymentReceiptData\|DetailedPaymentReceiptData $paymentReceipt, ?string $idempotencyKey = null)` | Register a debt payment, an advance, or a `Detailed` accounting-entries receipt. |
| `all(...)` | List payment receipts, with date-range filters. |
| `find(string $id)` | Look up a payment receipt by GUID. |
| `update(string $id, ...)` | Update an existing payment receipt — confirmed to exist against sandbox despite being undocumented in Siigo's own spec. |
| `delete(string $id)` | Delete a payment receipt. |

Every table above is a summary — request shapes, required/optional fields, and real behavior
confirmed against sandbox are documented in full on each resource's own doc page linked above.
This README intentionally does not duplicate that detail.

## Multi-company / SaaS usage

Siigo issues one JWT per company. For applications serving more than one company, resolve a
scoped instance per request instead of relying on the configured default:

```php
use Jonathan8312\Siigo\Siigo;

$siigo = app(Siigo::class);

$otherCompany = $siigo->withCredentials('other-username', 'other-access-key');
$otherCompany->invoices()->all();
```

`withCredentials()` never mutates the container singleton — it returns a new, detached `Siigo`
instance with its own token and catalog cache entries, safe under Octane and other long-running
workers. See [docs/authentication.md](docs/authentication.md).

## Why not a Facade?

This package doesn't ship a static `Illuminate\Support\Facades\Siigo` facade — you resolve
`Siigo::class` from the container (or its `'siigo'` alias) directly, as shown throughout this
README. Two reasons:

1. **Immutable multi-company scoping.** `withCredentials()` returns a *new* instance rather than
   mutating the shared singleton (see [Multi-company / SaaS usage](#multi-company--saas-usage)
   above). A static facade makes it easy to call `Siigo::withCredentials(...)` and assume it
   changed the app-wide instance, when really the return value is what you needed to keep.
2. **Testability without facade mocking.** Everything goes through `Illuminate\Http\Client`, so
   `Http::fake()` already covers testing (see
   [Testing your own application](#testing-your-own-application)) — there's no need for
   `Siigo::shouldReceive(...)`-style facade mocks, which would encourage mocking the SDK's own
   methods instead of the actual HTTP boundary.

Full rationale in [`docs/authentication.md`](docs/authentication.md#why-not-a-facade).

## Caching

Reference catalogs (taxes, sellers, cost centers, document/payment types, ...) are per-company
data, not a fixed list — and invoicing needs to resolve these ids on nearly every request. When
resolved through the container, `$siigo->catalogs()` is cached via Laravel's `Cache`, scoped per
company, for `SIIGO_CATALOG_CACHE_TTL_SECONDS` (default one hour) — so your application does not
burn through Siigo's rate limit (as low as 10 req/min on trial accounts) re-fetching
effectively-static data. This SDK does not persist that data anywhere beyond the cache, and does
not ship migrations or Eloquent models to match it against your own local master data — see
[docs/catalogs.md](docs/catalogs.md#caching) for the reasoning and a suggested pattern.

## Error handling

Every failure is surfaced as one of a small set of typed exceptions, all extending
`Jonathan8312\Siigo\Exceptions\SiigoException`:

| Exception | When |
|---|---|
| `AuthenticationException` | HTTP 401, a rejected `POST /auth`, or no credentials configured at all. |
| `ValidationException` | HTTP 400 — Siigo reuses it for field validation, business rules, and header errors alike; inspect `errorCode()` to branch. |
| `NotFoundException` | HTTP 404. |
| `RateLimitException` | HTTP 429 — exposes `retryAfterSeconds()` when Siigo provides it. |
| `ServerException` | HTTP 408/500/503/504. |
| `ConnectionException` | No HTTP response received at all (DNS/TLS failure, timeout). |
| `RequestException` | Catch-all for any other status, or a response body exceeding `max_response_bytes`. |

```php
use Jonathan8312\Siigo\Exceptions\SiigoException;

try {
    $siigo->invoices()->create($invoice);
} catch (SiigoException $exception) {
    $exception->statusCode(); // ?int
    $exception->endpoint();   // ?string, e.g. "POST v1/invoices"
    $exception->errors();     // list<SiigoError> — code, message, params, detail
}
```

`POST`/`PUT`/`DELETE` requests are **never** retried automatically, under any configuration —
an automatic retry of a non-idempotent call risks creating a duplicate document. See
[docs/errors.md](docs/errors.md) for the full model.

## Testing your own application

This SDK talks to Siigo through Laravel's own HTTP client, so `Http::fake()` works exactly as
it does for any other Laravel HTTP call:

```php
use Illuminate\Support\Facades\Http;

Http::fake([
    'https://api.siigo.com/auth' => Http::response(['access_token' => 'fake-jwt', 'expires_in' => 86400], 200),
    'https://api.siigo.com/v1/customers*' => Http::response(['pagination' => [...], 'results' => []], 200),
]);

$siigo = app(Siigo::class);
// every call this SDK makes, including via withCredentials(), is now faked
```

See [docs/testing.md](docs/testing.md) for asserting on requests and for running this package's
own opt-in sandbox verification suite (`composer test:staging`).

## Security

- No licensing checks, telemetry, tracking, or remote activation — this package only ever talks
  to the `SIIGO_BASE_URL` you configure.
- Credentials and the resulting JWT are never exposed as plain string properties, never appear
  in exception messages or logs, and never persisted anywhere beyond Laravel's `Cache`, keyed by
  a non-reversible fingerprint rather than the raw username/access key.
- `withCredentials()` returns new, immutable instances rather than mutating shared state — safe
  under Octane and other long-running workers.
- Explicit connection/request timeouts by default; non-idempotent requests are never retried
  automatically.

See [docs/errors.md](docs/errors.md) and [docs/authentication.md](docs/authentication.md) for
the full detail. Please report suspected security issues privately to
[jt@jonathant.dev](mailto:jt@jonathant.dev) rather than opening a public issue.

## Known limitations

Siigo's own documentation has confirmed gaps and inconsistencies — documented as such rather
than guessed around — in [docs/known-issues.md](docs/known-issues.md): PascalCase error
response keys inconsistent with the rest of the API, a `page_size` query parameter not always
honored, a `Partner-Id` format requirement confirmed only empirically, and more. Worth a read
before filing an issue against unexpected sandbox behavior.

Remaining business resources not yet implemented: Purchases, Vouchers, Quotations, Journals,
Payment Receipts' `Detailed` variant not verified against sandbox, and Reports. See
[docs/research/siigo-api-co](docs/research/siigo-api-co) for the investigation already done
ahead of each.

## Scope

This package is intentionally **just an SDK**. It does not:

- calculate or correct invoice totals, taxes, or any monetary value;
- model your application's customers, orders, or products in a database;
- persist Siigo's reference data anywhere beyond an optional cache (see [Caching](#caching));
- retry non-idempotent operations automatically.

Your application remains responsible for its own domain logic; this package is responsible for
communicating correctly and safely with Siigo.

## Contributing

This package is built module by module, strictly against Siigo's documented behavior and
verified against their sandbox wherever possible — never invented endpoints, fields, or
business rules. If you'd like to contribute, please open an issue first to discuss scope.

## Author

Maintained by Jonathan Torres — [trebolcolombia.com](https://trebolcolombia.com).

## License

MIT — see [LICENSE](LICENSE).
