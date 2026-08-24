# Credit Notes

`/v1/credit-notes` — credit notes (notas crédito): create, list, find, and PDF. There is no
confirmed `PUT`, `DELETE`, or annul endpoint — see [known-issues.md](known-issues.md).

```php
use Jonathan8312\Siigo\Siigo;

$siigo = app(Siigo::class);
$creditNotes = $siigo->creditNotes();
```

## Create, against an existing invoice

```php
use Jonathan8312\Siigo\DataTransferObjects\CreditNotes\CreditNoteData;
use Jonathan8312\Siigo\DataTransferObjects\CreditNotes\CreditNoteItem;
use Jonathan8312\Siigo\DataTransferObjects\CreditNotes\CreditNotePayment;
use Jonathan8312\Siigo\DataTransferObjects\CreditNotes\DocumentRef;
use Jonathan8312\Siigo\DataTransferObjects\CreditNotes\ItemTaxRef;
use Jonathan8312\Siigo\Enums\CreditNoteReason;

$creditNote = $creditNotes->create(new CreditNoteData(
    document: new DocumentRef(2), // id from $siigo->catalogs()->documentTypes('NC')
    date: '2021-10-15',
    reason: CreditNoteReason::PartialReturnOrRejection, // required for electronic credit notes
    invoice: $invoice->id, // GUID of an invoice created via $siigo->invoices()
    items: [new CreditNoteItem(
        code: 'Item-1',
        quantity: 2,
        price: 50,
        discount: 13,
        taxes: [new ItemTaxRef(id: 13156)],
    )],
    payments: [new CreditNotePayment(id: 5636, value: 87)], // id from $siigo->catalogs()->paymentTypes('NC')
), idempotencyKey: 'credit-note-order20260823001');

$creditNote->id;   // GUID assigned by Siigo
$creditNote->name; // e.g. "NC-2-22"
```

`reason` is Siigo's DIAN rejection-reason code. {@see CreditNoteReason} only names the codes
whose meaning is unambiguous in Siigo's own documentation (`1`, `2`, `3`, `4`, `6`) — pass a
raw `int` for any other code your account may need. See
[known-issues.md](known-issues.md) for why `5` and `7` are excluded.

## Create, against an invoice that doesn't exist in Siigo

```php
use Jonathan8312\Siigo\DataTransferObjects\CreditNotes\CreditNoteInvoiceData;
use Jonathan8312\Siigo\DataTransferObjects\CreditNotes\CustomerRef;

$creditNote = $creditNotes->create(new CreditNoteData(
    document: new DocumentRef(2379),
    date: '2024-05-24',
    reason: CreditNoteReason::InvoiceAnnulment,
    invoiceData: new CreditNoteInvoiceData(
        date: '2024-03-20',
        prefix: 'FV',
        number: '458', // required when reason is InvoiceAnnulment
        cufe: '302580df-838b-4531-b8bf-dd3c9hasdfu8e5', // required when reason is InvoiceAnnulment
    ),
    customer: new CustomerRef(identification: '28211179'), // must already exist — see docs/customers.md
    seller: 62, // id from $siigo->catalogs()->users()
    items: [new CreditNoteItem(code: 'Code-1', quantity: 1, price: 2000)],
    payments: [new CreditNotePayment(id: 542, value: 2000)],
));
```

`invoice` and `invoiceData` are mutually exclusive: pass `invoice` (a GUID) when the sales
invoice exists in Siigo, or `invoiceData` + `customer` + `seller` when it doesn't. The SDK does
not enforce this itself (Siigo's own documentation of the two fields is inconsistent — see
[known-issues.md](known-issues.md)) — Siigo validates it server-side.

## Gift items (obsequio)

A line item with `price: 0` requires stating its real value and who bears the VAT:

```php
use Jonathan8312\Siigo\Enums\CreditNoteTaxpayer;

new CreditNoteItem(
    code: 'Item-1',
    quantity: 2,
    price: 0,
    taxBase: 1000,       // required when price is 0
    taxpayer: CreditNoteTaxpayer::Company, // or ::Customer — required when price is 0
    taxes: [new ItemTaxRef(id: 31779)],
);
```

## List, find, PDF

```php
$page = $creditNotes->all(name: 'NC-1-1516', page: 1, pageSize: 25);

foreach ($page->items as $creditNote) {
    // CreditNote objects, same shape as create()'s return value
}

$creditNote = $creditNotes->find($id); // throws NotFoundException if missing
$pdf = $creditNotes->pdf($id);         // CreditNoteFile{id, cude, base64}
```

`all()` accepts `name` and three date ranges (`createdStart`/`createdEnd`,
`dateStart`/`dateEnd`, `updatedStart`/`updatedEnd`) as named parameters.

## Electronic submission and email at creation time

```php
use Jonathan8312\Siigo\DataTransferObjects\CreditNotes\MailCommand;
use Jonathan8312\Siigo\DataTransferObjects\CreditNotes\StampCommand;

$creditNote = $creditNotes->create(new CreditNoteData(
    // ...
    stamp: new StampCommand(send: true),
    mail: new MailCommand(send: true),
));
```

Unlike invoices, there is no standalone "send by mail" endpoint documented for credit notes —
`mail.send: true` at creation is the only confirmed way to email one.

## Request/response asymmetries

Same pattern as invoices — see [invoices.md](invoices.md#requestresponse-asymmetries):

| Field | On `CreditNoteData` (request) | On `CreditNote` (response) |
|---|---|---|
| `invoice` | `string` (GUID) | `CreditNoteInvoiceRef {id, name}` |
| `customer` | `CustomerRef {identification, branchOffice}` (only with `invoiceData`) | `CustomerSummary {id, identification, branchOffice}` (always present, inherited from the invoice) |
| `items[].discount` | plain number | `ItemDiscount {percentage, value}` |
| `items[].warehouse` | plain id | `ItemWarehouseRef {id, name}` |
| `items[].taxes[]` | `ItemTaxRef {id}` | `CreditNoteItemTax {id, name, type, percentage, value, baseValue}` |
| `payments[]` | `CreditNotePayment {id, value, dueDate}` | `CreditNotePaymentDetails {id, name, value, dueDate}` |
| `retentions` | `list<int>` (ids) | `list<CreditNoteRetention>` (enriched) |

`document` and `currency` are the same shape both ways.
