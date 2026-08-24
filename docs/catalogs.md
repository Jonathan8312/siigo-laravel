# Catalogs

Siigo's read-only master/reference data — the values `Customers`, `Products`, and `Invoices`
reference by id (not yet implemented; see the project roadmap). All catalogs are `GET`,
company-agnostic, and require no request body.

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
