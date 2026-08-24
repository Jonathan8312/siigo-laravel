# Customers

`/v1/customers` — create, list, find, update, and delete a company's customers/third parties.

```php
use Jonathan8312\Siigo\Siigo;

$siigo = app(Siigo::class);
$customers = $siigo->customers();
```

## Create

```php
use Jonathan8312\Siigo\DataTransferObjects\Customers\Address;
use Jonathan8312\Siigo\DataTransferObjects\Customers\City;
use Jonathan8312\Siigo\DataTransferObjects\Customers\Contact;
use Jonathan8312\Siigo\DataTransferObjects\Customers\CustomerData;
use Jonathan8312\Siigo\DataTransferObjects\Customers\FiscalResponsibility;
use Jonathan8312\Siigo\DataTransferObjects\Customers\Phone;
use Jonathan8312\Siigo\Enums\PersonType;

$customer = $customers->create(new CustomerData(
    personType: PersonType::Person,
    idType: '13', // cédula de ciudadanía — no catalog endpoint confirmed, see known-issues.md
    identification: '13832081',
    name: ['Marcos', 'Castillo'],
    fiscalResponsibilities: [new FiscalResponsibility(code: 'R-99-PN')],
    address: new Address(
        address: 'Cra. 18 #79A - 42',
        city: new City(countryCode: 'Co', stateCode: '19', cityCode: '19001'),
        postalCode: '110911',
    ),
    phones: [new Phone(indicative: '57', number: '3006003345')],
    contacts: [new Contact(firstName: 'Marcos', lastName: 'Castillo', email: 'marcos@example.com')],
));

$customer->id; // GUID assigned by Siigo
```

`type` defaults to `CustomerType::Customer`, `active` to `true`, `branchOffice` to `0`. Every
other optional field defaults to `null`/empty and is omitted from the request entirely.

## List

```php
$page = $customers->all(active: true, page: 1, pageSize: 25);

foreach ($page->items as $customer) {
    // Customer objects, same shape as create()'s return value
}

$page->totalResults;
```

`all()` accepts every documented filter as a named parameter: `identification`,
`branchOffice`, `active`, `type` (`CustomerType`), `personType` (`PersonType`), and the three
date ranges (`createdStart`/`createdEnd`, `dateStart`/`dateEnd`, `updatedStart`/`updatedEnd`,
each a `DateTimeInterface`). A requested `pageSize` is not always honored by Siigo in
practice — see [known-issues.md](known-issues.md).

## Find

```php
$customer = $customers->find('63f918c2-ca65-4edc-a7db-66bcdd5159fb');
```

Throws `NotFoundException` if the id does not exist.

## Update

```php
$customers->update($customer->id, $customerData);
```

**`PUT` is a full replace, not a partial patch** — Siigo documents that omitted fields are
left empty rather than preserved from the previous state, confirmed against the real API.
Always build a complete `CustomerData` for `update()`, not just the fields that changed.

## Delete

```php
$customers->delete($customer->id);
```

⚠️ Confirmed against the real sandbox that Siigo can reject this with `403
disabled_functionality` ("This functionality is temporarily disabled.") — not confirmed
whether this is account-specific or a general Siigo policy. See
[known-issues.md](known-issues.md) before relying on this endpoint in production.

## Identification types (`idType`)

`idType` is a plain string (e.g. `"13"` for cédula de ciudadanía, `"31"` for NIT) — Siigo does
not expose a catalog endpoint for the full list, so the SDK cannot validate it client-side.
Siigo echoes it back in responses enriched as `{code, name}` (see `IdType`).
