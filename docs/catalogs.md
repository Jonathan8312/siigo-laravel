# Catalogs

Siigo's read-only master/reference data — the values `Customers`, `Products`, `Invoices`,
`CreditNotes`, and `PaymentReceipts` reference by id (taxes, sellers, cost centers, document
types, payment types, ...). All catalogs are `GET` and require no request body.

```php
use Jonathan8312\Siigo\Siigo;

$siigo = app(Siigo::class);
$catalogs = $siigo->catalogs();
```

## Available catalogs

| Method | Endpoint | Returns |
|---|---|---|
| `accountGroups()` | `GET /v1/account-groups` | `list<AccountGroup>` |
| `taxes()` | `GET /v1/taxes` | `list<Tax>` |
| `priceLists()` | `GET /v1/price-lists` | `list<PriceList>` |
| `warehouses()` | `GET /v1/warehouses` | `list<Warehouse>` |
| `users(page, pageSize)` | `GET /v1/users` | `PaginatedResponse<User>` |
| `documentTypes(string $type)` | `GET /v1/document-types` | `list<DocumentType>` |
| `paymentTypes(string $documentType)` | `GET /v1/payment-types` | `list<PaymentType>` |
| `costCenters()` | `GET /v1/cost-centers` | `list<CostCenter>` |

`documentTypes()` and `paymentTypes()` both require their string argument — Siigo rejects the
request otherwise. `documentTypes()`'s parameter looked optional in Siigo's own documentation
but is not in practice; see [known-issues.md](known-issues.md).

```php
foreach ($catalogs->taxes() as $tax) {
    // $tax->id, $tax->name, $tax->type, $tax->percentage, $tax->active
}

$salesDocumentTypes = $catalogs->documentTypes(type: 'FV');
$salesPaymentTypes = $catalogs->paymentTypes(documentType: 'FV');
```

## Pagination

`users()` is the only catalog confirmed to return Siigo's paginated envelope. It returns a
`PaginatedResponse` with `items`, `page`, `pageSize`, and `totalResults`:

```php
$sellers = $catalogs->users(page: 1, pageSize: 25);

foreach ($sellers->items as $user) {
    // $user->id, $user->username, $user->firstName, ...
}

$sellers->page;
$sellers->totalResults;
```

Note: a requested `pageSize` is not always honored by Siigo in practice (confirmed for
`customers`, not yet confirmed one way or the other for `users`) — always read `pageSize` back
from the response rather than assuming your request value was applied. See
[known-issues.md](known-issues.md).

## Not implemented here

`fixed-assets`, `expenses`, and `misc-incomes` are documented catalogs but are out of scope
for this phase — they are only relevant to journals and cash vouchers, which are not yet
implemented. See `docs/research/siigo-api-co/01-catalogs.md` for what was investigated about
them.

## Caching

Catalogs are per-company data (each Siigo account configures its own taxes, sellers, cost
centers, document types, and payment types), not a fixed global list — they are scoped to
whichever credentials are currently authenticated. When resolved through the container
(`app(Siigo::class)->catalogs()`), every method here is cached via Laravel's Cache, keyed by
company, for `SIIGO_CATALOG_CACHE_TTL_SECONDS` (default `3600`, one hour). This matters because
these are exactly the ids your application needs on every invoice/payment receipt (tax id,
seller id, cost center id) — without caching, resolving them per request would burn through
Siigo's rate limit fast (as low as 10 req/min on trial accounts).

```env
# .env
SIIGO_CATALOG_CACHE_TTL_SECONDS=3600   # 0 disables catalog caching entirely
SIIGO_CACHE_STORE=redis                 # optional — same store the auth token cache uses
```

Set `SIIGO_CATALOG_CACHE_TTL_SECONDS=0` to always hit the real API — useful in tests, or if your
application already caches these ids itself. A `Catalogs` instance built manually (`new
Catalogs($client)`, as the test suite does) is never cached — caching only applies to the
container-resolved instance.

This SDK does not persist catalog data anywhere beyond that cache: it does not ship migrations,
Eloquent models, or a sync command (see README "What it does not do"). There is no dedicated
`sucursales`/branch-office catalog either — `branch_office` on `Customers`/`Invoices` is a plain
integer (0-999) your application assigns per customer, not something Siigo exposes a lookup
table for.

## Matching Siigo ids to your own application's master data

If your application has its own tax/seller/cost-center tables and needs to keep them in sync
with Siigo's ids (e.g. so a user picks "IVA 19%" from your own UI and the SDK call uses the
right Siigo id), that mapping is your application's responsibility, built on top of the cached
catalog calls above — for example, an Eloquent model with a `siigo_id` column, refreshed on a
schedule from `$catalogs->taxes()`/`$catalogs->users()`/`$catalogs->costCenters()`. This SDK
deliberately does not ship that table or a sync command itself, to stay a pure Siigo API client
— but nothing stops your application from building one against these cached, typed DTOs.
