# Products

`/v1/products` — create, list, find, update, and delete a company's products/services.

```php
use Jonathan8312\Siigo\Siigo;

$siigo = app(Siigo::class);
$products = $siigo->products();
```

## Create

```php
use Jonathan8312\Siigo\DataTransferObjects\Products\AdditionalFields;
use Jonathan8312\Siigo\DataTransferObjects\Products\PriceListEntry;
use Jonathan8312\Siigo\DataTransferObjects\Products\ProductData;
use Jonathan8312\Siigo\DataTransferObjects\Products\ProductPrice;
use Jonathan8312\Siigo\DataTransferObjects\Products\ProductTax;

$product = $products->create(new ProductData(
    code: 'Item-1',
    name: 'Cotton shirt',
    accountGroup: 1253, // id from $siigo->catalogs()->accountGroups()
    taxes: [new ProductTax(id: 13156)], // id from $siigo->catalogs()->taxes()
    prices: [new ProductPrice('COP', [new PriceListEntry(position: 1, value: 1069.77)])],
    unit: '94', // code from Siigo's unit-of-measure list; "94" = Unidad
    reference: 'REF1',
    description: 'This is a description',
    additionalFields: new AdditionalFields(barcode: 'B0123', brand: 'Gef'),
));

$product->id; // GUID assigned by Siigo
```

`type` defaults to `ProductType::Product`, `taxClassification` to `TaxClassification::Taxed`,
`active` to `true`, `stockControl`/`taxIncluded` to `false`.

### Combo products

```php
use Jonathan8312\Siigo\DataTransferObjects\Products\ComboComponent;
use Jonathan8312\Siigo\Enums\ProductType;

$combo = $products->create(new ProductData(
    code: '1234',
    name: 'Combo de prueba',
    accountGroup: 121,
    type: ProductType::Combo, // requires Siigo Nube Premium
    components: [
        new ComboComponent(code: 'product-1', quantity: 100),
        new ComboComponent(code: 'product-2', quantity: 20),
    ],
));
```

## List

```php
$page = $products->all(active: true, page: 1, pageSize: 25);

foreach ($page->items as $product) {
    // Product objects, same shape as create()'s return value
}
```

`all()` accepts `code`, `accountGroup`, `type`, `stockControl`, `active`, `ids` (up to 20
GUIDs), and the three date ranges (`createdStart`/`createdEnd`, `dateStart`/`dateEnd`,
`updatedStart`/`updatedEnd`) as named parameters.

## Find

```php
$product = $products->find('497f6eca-6276-4993-bfeb-53cbbbba6f08');
```

Throws `NotFoundException` if the id does not exist. The response includes read-only fields
not present on creation: `availableQuantity`, `warehouses` (per-warehouse stock), and
`metadata`.

## Update

```php
$products->update($product->id, $productData);
```

Siigo rejects changing `accountGroup`, or a `Combo`'s `components`, once the product has
movements on a document — not enforced client-side, since the SDK has no way to know a
product's movement history.

## Delete

```php
$products->delete($product->id); // bool — confirmed against the real API, see known-issues.md
```

## Request/response asymmetries

Several fields are shaped differently depending on direction — confirmed real API behavior,
not a documentation error:

| Field | On `ProductData` (request) | On `Product` (response) |
|---|---|---|
| `unit` | plain code string, e.g. `"94"` | `Unit {code, name}` |
| `accountGroup` / `account_group` | plain id | `AccountGroupRef {id, name}` |
| `taxes[]` | `ProductTax {id, milliliters?, rate?}` | `ProductTaxDetails {id, name, type, percentage}` |
| `components[]` | `ComboComponent {code, quantity}` | `ComboComponentDetails {id, code, name}` |

`ProductPrice`/`PriceListEntry` are the same class in both directions — Siigo only adds a
`name` on the response, which the class carries as an optional field never sent back.
