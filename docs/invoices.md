# Invoices

`/v1/invoices` — sales invoices: create, list, find, update, delete, annul, and the DIAN/PDF/
XML/email endpoints.

```php
use Jonathan8312\Siigo\Siigo;

$siigo = app(Siigo::class);
$invoices = $siigo->invoices();
```

## Create

```php
use Jonathan8312\Siigo\DataTransferObjects\Invoices\CustomerRef;
use Jonathan8312\Siigo\DataTransferObjects\Invoices\DocumentRef;
use Jonathan8312\Siigo\DataTransferObjects\Invoices\InvoiceData;
use Jonathan8312\Siigo\DataTransferObjects\Invoices\InvoiceItem;
use Jonathan8312\Siigo\DataTransferObjects\Invoices\InvoicePayment;
use Jonathan8312\Siigo\DataTransferObjects\Invoices\ItemTaxRef;

$invoice = $invoices->create(new InvoiceData(
    document: new DocumentRef(22), // id from $siigo->catalogs()->documentTypes('FV')
    date: '2021-10-15',
    customer: new CustomerRef(identification: '13832081'), // must already exist — see docs/customers.md
    seller: 629, // id from $siigo->catalogs()->users()
    items: [new InvoiceItem(
        code: 'Item-1', // must already exist — see docs/products.md
        quantity: 2,
        price: 50,
        discount: 13, // percentage or absolute value, depending on the document type's discountType
        taxes: [new ItemTaxRef(id: 13156)], // max 2 per item, no two of the same type
    )],
    payments: [new InvoicePayment(id: 5636, value: 87)], // id from $siigo->catalogs()->paymentTypes('FV')
), idempotencyKey: 'order20260823001'); // see "Creating many invoices safely" below

$invoice->id; // GUID assigned by Siigo
$invoice->name; // e.g. "FV-2-22"
```

The document type's `automaticNumber` (`$siigo->catalogs()->documentTypes('FV')`) determines
whether `number` is optional (Siigo assigns the next consecutive) or required — check it
before deciding whether to pass `number` explicitly.

To submit the invoice to the DIAN or email it at creation time:

```php
use Jonathan8312\Siigo\DataTransferObjects\Invoices\MailCommand;
use Jonathan8312\Siigo\DataTransferObjects\Invoices\StampCommand;

$invoice = $invoices->create(new InvoiceData(
    // ...
    stamp: new StampCommand(send: true),
    mail: new MailCommand(send: true),
));
```

There is no confirmed standalone endpoint to stamp an invoice created without `stamp.send:
true` afterward — see [known-issues.md](known-issues.md).

## Creating many invoices: batch vs. one at a time

Siigo has a real batch endpoint, `POST /v1/invoices/batch`, confirmed via Siigo's Apiary
documentation and cross-checked against a real production integration. It is **not** listed
on `developers.siigo.com` as of this writing — Siigo appears to maintain two documentation
sites that are not fully in sync; see [known-issues.md](known-issues.md).

### `createBatch()` — asynchronous, many invoices per request

```php
use Jonathan8312\Siigo\DataTransferObjects\Invoices\BatchInvoiceItem;

$receipt = $invoices->createBatch(
    notificationUrl: 'https://your-app.test/webhooks/siigo-invoices-batch',
    invoices: [
        new BatchInvoiceItem($order1->toInvoiceData(), idempotencyKey: 'order1001'),
        new BatchInvoiceItem($order2->toInvoiceData(), idempotencyKey: 'order1002'),
    ],
);

$receipt->id;     // batch id, to correlate with the webhook notification
$receipt->status; // e.g. "Received" — an acknowledgement, not completion
```

Siigo processes the batch **asynchronously** and later sends a single `POST` to your
`notificationUrl` with every invoice's outcome. The SDK does not register that route for you
— webhook infrastructure (routing, queues, signature handling) is explicitly out of scope for
this SDK (see the project roadmap); it only gives you a typed way to parse what Siigo sends:

```php
use Illuminate\Http\Request;
use Jonathan8312\Siigo\DataTransferObjects\Invoices\InvoiceBatchNotification;

Route::post('/webhooks/siigo-invoices-batch', function (Request $request) {
    $notification = InvoiceBatchNotification::fromArray($request->json()->all());

    foreach ($notification->results as $result) {
        // $result->idempotencyKey matches what you passed to BatchInvoiceItem
        if ($result->successful()) {
            // $result->invoice is a full Invoice, plus $result->publicUrl
        } else {
            // $result->errors is a list<SiigoError> — same shape as a synchronous rejection
        }
    }

    return response()->noContent();
});
```

Each `BatchInvoiceItem`'s `idempotencyKey` is a body field here (alphanumeric, max 30
characters — same restriction as the header form below), not an HTTP header; `createBatch()`
sends no `Idempotency-Key` header at all. The whole request body is capped at **1MB** by
Siigo — `createBatch()` checks this client-side and throws before sending an oversized batch,
so split large batches yourself if you hit that.

### `create()` in a loop — synchronous, one invoice per request

For a handful of invoices, or when you need the result immediately rather than via webhook,
calling `create()` repeatedly is simpler and still safe:

```php
use Jonathan8312\Siigo\Exceptions\RateLimitException;
use Jonathan8312\Siigo\Exceptions\ValidationException;

$results = [];

foreach ($ordersToInvoice as $order) {
    try {
        $results[$order->id] = $invoices->create(
            $order->toInvoiceData(),
            idempotencyKey: 'order'.$order->id, // must stay alphanumeric
        );
    } catch (RateLimitException $e) {
        sleep($e->retryAfterSeconds() ?? 10);
        // re-attempt this order with the same idempotencyKey
    } catch (ValidationException $e) {
        // record $e->errors() for this order and continue with the next one —
        // never assume the whole loop failed because one invoice was rejected
    }
}
```

`idempotencyKey` (max 30 alphanumeric characters, no hyphens or other special characters —
confirmed empirically, see [known-issues.md](known-issues.md)) makes a retried `create()`
call after a network failure or timeout return the original invoice instead of creating a
duplicate. Pace your requests within Siigo's rate limit (100/min production, 10/min trial
accounts) and catch `RateLimitException` to back off — the SDK never retries automatically on
`429` (see [errors.md](errors.md)), since Siigo also documents blocking accounts with a
sustained high error rate.

## List

```php
$page = $invoices->all(customerIdentification: '13832081', page: 1, pageSize: 25);

foreach ($page->items as $invoice) {
    // Invoice objects, same shape as create()'s return value
}
```

`all()` accepts `documentId`, `customerIdentification`, `customerBranchOffice`, `name`, and
three date ranges (`createdStart`/`createdEnd`, `dateStart`/`dateEnd`,
`updatedStart`/`updatedEnd`) as named parameters.

## Find, update, delete, annul

```php
$invoice = $invoices->find($id);          // throws NotFoundException if missing
$invoice = $invoices->update($id, $data); // full replace — see InvoiceData's docblock
$invoices->delete($id);                   // bool
$invoices->annul($id);                    // bool — marks annulled: true, keeps history
```

An invoice cannot be edited, deleted, or annulled once it is being transmitted to the DIAN or
already has a CUFE (accepted), or while related documents (credit/debit notes, cash receipts,
portfolio adjustments) still exist — those must be removed first.

## Electronic invoicing (DIAN)

```php
$errors = $invoices->stampErrors($id); // list<string> — rejection reasons, if any
$pdf = $invoices->pdf($id);            // InvoiceFile{id, cufe, base64}
$xml = $invoices->xml($id);            // InvoiceFile{id, cufe, base64}
```

`$invoice->stamp->status` is a `StampStatus` (`Draft`, `Accepted`, `Rejected`) once the
invoice has gone through the DIAN flow.

## Send by email

```php
$status = $invoices->mail($id, mailTo: 'customer@example.com', copyTo: 'billing@example.com');
```

`copyTo` accepts up to 5 addresses separated by `;`.

## Request/response asymmetries

Confirmed real API behavior, not documentation errors — several fields are shaped
differently depending on direction:

| Field | On `InvoiceData` (request) | On `Invoice` (response) |
|---|---|---|
| `customer` | `CustomerRef {identification, branchOffice}` | `CustomerSummary {id, identification, branchOffice}` |
| `items[].discount` | plain number | `ItemDiscount {percentage, value}` |
| `items[].warehouse` | plain id | `ItemWarehouseRef {id, name}` |
| `items[].taxes[]` | `ItemTaxRef {id}` | `InvoiceItemTax {id, name, type, percentage, value, baseValue}` |
| `payments[]` | `InvoicePayment {id, value, dueDate}` | `InvoicePaymentDetails {id, name, value, dueDate}` |
| `globalDiscounts[]` | `GlobalCharge {id, percentage}` | `GlobalChargeDetails {id, name, percentage, value}` (also used for `globalCharges`) |
| `retentions` | `list<int>` (ids) | `list<InvoiceRetention>` (enriched) |

`document` and `currency` are the same shape both ways.
