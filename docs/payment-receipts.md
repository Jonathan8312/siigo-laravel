# Payment Receipts

`/v1/payment-receipts` — recibos de pago/egreso a proveedores: create, list, find, update, and
delete. Unlike [credit-notes.md](credit-notes.md), both `PUT` and `DELETE` are confirmed against
real sandbox behaviour, despite gaps in Siigo's own `.apib` spec — see
[known-issues.md](known-issues.md).

```php
use Jonathan8312\Siigo\Siigo;

$siigo = app(Siigo::class);
$paymentReceipts = $siigo->paymentReceipts();
```

## Create — abono a una factura de compra (`DebtPayment`)

```php
use Jonathan8312\Siigo\DataTransferObjects\PaymentReceipts\Due;
use Jonathan8312\Siigo\DataTransferObjects\PaymentReceipts\DocumentRef;
use Jonathan8312\Siigo\DataTransferObjects\PaymentReceipts\Payment;
use Jonathan8312\Siigo\DataTransferObjects\PaymentReceipts\PaymentReceiptData;
use Jonathan8312\Siigo\DataTransferObjects\PaymentReceipts\PaymentReceiptItem;
use Jonathan8312\Siigo\DataTransferObjects\PaymentReceipts\SupplierRef;
use Jonathan8312\Siigo\Enums\PaymentReceiptType;

$paymentReceipt = $paymentReceipts->create(new PaymentReceiptData(
    document: new DocumentRef(2376), // id from $siigo->catalogs()->documentTypes('RP')
    date: '2025-01-12',
    type: PaymentReceiptType::DebtPayment,
    supplier: new SupplierRef(identification: '109048401'), // must exist as a Supplier — see docs/customers.md
    payment: new Payment(
        id: 11109, // id from $siigo->catalogs()->paymentTypes('FC') — NOT 'RP', see below
        value: 50000,
    ),
    items: [new PaymentReceiptItem(
        due: new Due(prefix: 'FC-1', consecutive: 684, quote: 1, date: '2020-02-15'),
        value: 50000,
    )],
), idempotencyKey: 'payment-receipt-order20260824001');

$paymentReceipt->id;   // GUID assigned by Siigo
$paymentReceipt->name; // e.g. "RP-1-1052"
```

`items` is required for `DebtPayment` — one entry per invoice due being paid.

### Payment types: use `'FC'`, not `'RP'`

`GET /v1/payment-types?document_type=RP` is rejected by Siigo with `404 not_found` — confirmed
against sandbox. Look up payment types with `$siigo->catalogs()->paymentTypes('FC')` (compra)
instead; its ids are the ones real payment receipts actually use. See
[known-issues.md](known-issues.md).

## Create — anticipo a proveedor (`AdvancePayment`)

```php
$paymentReceipt = $paymentReceipts->create(new PaymentReceiptData(
    document: new DocumentRef(28355),
    date: '2025-01-12',
    type: PaymentReceiptType::AdvancePayment,
    supplier: new SupplierRef('109048401'),
    payment: new Payment(id: 11109, value: 10000),
    // no `items` — there is no invoice due to reference
));
```

## Update and delete

```php
$updated = $paymentReceipts->update($paymentReceipt->id, new PaymentReceiptData(
    document: new DocumentRef(28355),
    date: '2025-01-12',
    type: PaymentReceiptType::AdvancePayment,
    supplier: new SupplierRef('109048401'),
    payment: new Payment(id: 11109, value: 10000),
    observations: 'updated',
));

$paymentReceipts->delete($paymentReceipt->id); // bool — rejected with `delete_not_allowed` if related documents exist
```

## List, find

```php
$page = $paymentReceipts->all(page: 1, pageSize: 25);

foreach ($page->items as $paymentReceipt) {
    // PaymentReceipt objects, same shape as create()'s return value
}

$paymentReceipt = $paymentReceipts->find($id); // throws NotFoundException if missing
```

`all()` accepts two date ranges (`createdStart`/`createdEnd`, `updatedStart`/`updatedEnd`) as
named parameters. `page_size` is not always honored by Siigo — see
[known-issues.md](known-issues.md).

`$paymentReceipt->payment` can be `null` even though `payment` is required on create — some real
sandbox records return without it. Always null-check before reading it.

## Advanced accounting entries (`type: Detailed`)

For postings that touch multiple accounts directly (banks, dues, taxes) instead of the simple
`due`/`payment` model, use `DetailedPaymentReceiptData`:

```php
use Jonathan8312\Siigo\DataTransferObjects\PaymentReceipts\AccountRef;
use Jonathan8312\Siigo\DataTransferObjects\PaymentReceipts\DetailedItem;
use Jonathan8312\Siigo\DataTransferObjects\PaymentReceipts\DetailedPaymentReceiptData;
use Jonathan8312\Siigo\DataTransferObjects\PaymentReceipts\TaxRef;
use Jonathan8312\Siigo\Enums\AccountMovement;

$paymentReceipt = $paymentReceipts->create(new DetailedPaymentReceiptData(
    document: new DocumentRef(24445),
    date: '2025-01-15',
    supplier: new SupplierRef('8694251'),
    items: [
        new DetailedItem(account: new AccountRef('11100501', AccountMovement::Credit), description: 'FC-2 Base', value: 50),
        new DetailedItem(account: new AccountRef('13050501', AccountMovement::Debit), description: 'FC-2 Base', value: 50, due: new Due('FC-1', 684, 1, '2020-02-15')),
        new DetailedItem(account: new AccountRef('24081001', AccountMovement::Debit), description: 'FC-2 Base', value: 19, tax: new TaxRef(13156)),
    ],
));
```

**Not verified against sandbox** — this variant requires real chart-of-accounts codes this SDK
has no way to discover via the API (there is no accounts catalog among Siigo's confirmed
catalogs). It is modeled strictly from Siigo's documented spec. Confirm it works against your own
account's chart of accounts before relying on it in production. See
[known-issues.md](known-issues.md).

## Request/response asymmetries

| Field | On `PaymentReceiptData` (request) | On `PaymentReceipt` (response) |
|---|---|---|
| `supplier` | `SupplierRef {identification, branchOffice}` | `SupplierSummary {id, identification, branchOffice}` |
| `payment` | `Payment {id, value}`, required | `PaymentSummary {id, name, value}`, **nullable** — can be absent even though required on create |

`document`, `currency`, and `items[]` (`due`/`value`) are the same shape both ways.
