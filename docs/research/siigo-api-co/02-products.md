# Siigo API Colombia — Products (`/v1/products`)

Referencia interna de ingeniería (síntesis propia, no copia literal) construida a partir de
`developers.siigo.com/docs/siigoapi/productos/*`. Confirmados navegando el sidebar real
(hidratado vía JS) de esa sección: existen exactamente **5 endpoints** bajo `productos`, sin
más allá de estos:

| Página | Slug | Endpoint |
|---|---|---|
| Crear Producto | `crear-producto` | `POST /v1/products` |
| Actualizar Producto | `actualizar-producto` | `PUT /v1/products/{id}` |
| Consultar Producto | `consultar-producto` | `GET /v1/products/{id}` |
| Listar Productos | `listar-productos` | `GET /v1/products` |
| Borrar Producto | `eliminar-producto` | `DELETE /v1/products/{id}` |

Headers en todos: `Authorization: <token>`, `Partner-Id: <token>`. `Idempotency-Key` **no**
está documentado para `POST /v1/products` (a diferencia de facturas/notas/journals/vouchers —
ver `00-core-auth-http.md`; no asumir que aplica aquí).

---

## Crear Producto — `POST /v1/products`

### Body (`application/json`)

Campos obligatorios: `code`, `name`, `account_group`. (`unit` no es obligatorio pese a
aparecer en el enunciado inicial de la tarea — tiene default `94`.)

| Campo | Tipo | Obligatorio | Reglas / default |
|---|---|---|---|
| `code` | string | **Sí** | Único, alfanumérico, **sin espacios**, máx. 30 caracteres |
| `name` | string | **Sí** | Máx. 100 caracteres, permite espacios y caracteres especiales |
| `account_group` | number | **Sí** | Id de `/v1/account-groups`, debe existir y estar activo |
| `type` | string | No | `Product` (default), `Service`, `ConsumerGood` — **o `Combo`** (ver ambigüedad abajo). `Combo` solo permitido en Siigo Nube Premium |
| `stock_control` | boolean | No | Default `false` |
| `active` | boolean | No | Default `true` |
| `tax_classification` | string | No | `Taxed`, `Exempt`, `Excluded` — default `Taxed` (doc dice literal `"taxed"` en minúscula en un punto y `Taxed` en otro) |
| `tax_included` | boolean | No | Default `false` |
| `tax_consumption_value` | number | No | Máx. 2 decimales, positivo |
| `taxes[].id` | number | No | Debe existir previamente en Siigo Nube |
| `taxes[].milliliters` | number | Condicional | **Obligatorio** si el impuesto es de bebidas azucaradas |
| `taxes[].rate` | number | Condicional | **Obligatorio** si el impuesto es de bebidas azucaradas; valores permitidos: `18, 35, 28, 55, 38, 65` |
| `prices[].currency_code` | string | No | Debe existir en Siigo Nube (ej. `COP`) |
| `prices[].price_list[].position` | number | No | Entero 1–12 (viene de `/v1/price-lists`) |
| `prices[].price_list[].value` | number | No | Máx. 2 decimales, positivo |
| `unit` | string | No | Código de unidad de medida (default `"94"` = Unidad). En el **request** es un código string; en la **respuesta** viene expandido como objeto `{code, name}` |
| `unit_label` | string | No | Texto libre para impresión de factura |
| `reference` | string | No | Alfanumérico, permite espacios, máx. 80 caracteres |
| `description` | string | No | Máx. 2500 caracteres |
| `additional_fields.barcode` | string | No | Alfanumérico, máx. 50 caracteres |
| `additional_fields.brand` | string | No | Alfanumérico, máx. 50 caracteres |
| `additional_fields.tariff` | string | No | Numérico, máx. 10 caracteres (código arancelario) |
| `additional_fields.model` | string | No | Alfanumérico, máx. 50 caracteres |
| `components[].code` | string | Solo `Combo` | Código de un producto existente y activo que compone el combo |
| `components[].quantity` | number | Solo `Combo` | Cantidad del componente |
| `product_id` | string (uuid) | No | Presente en el schema de request pero es inusual enviarlo en creación (normalmente el id lo asigna Siigo) |

Ejemplo — creación de producto tipo `Combo`:

```json
{
  "type": "Combo",
  "code": "1234",
  "name": "Combo de prueba",
  "account_group": 121,
  "components": [
    { "code": "product-1", "quantity": 100 },
    { "code": "product-2", "quantity": 20 }
  ]
}
```

Ejemplo — creación de producto estándar:

```json
{
  "code": "Item-1",
  "name": "Cotton shirt",
  "account_group": 1253,
  "type": "Product",
  "stock_control": false,
  "active": true,
  "tax_classification": "Taxed",
  "tax_included": false,
  "tax_consumption_value": 0,
  "taxes": [{ "id": 13156 }],
  "prices": [
    { "currency_code": "COP", "price_list": [{ "position": 1, "value": 1069.77 }] }
  ],
  "unit": "94",
  "unit_label": "Unit",
  "reference": "REF1",
  "description": "This is a description",
  "additional_fields": { "barcode": "B0123", "brand": "Gef", "tariff": "1234567890", "model": "Loiry" }
}
```

### Response — `201`

Igual estructura que "Consultar Producto" (ver abajo), con campos extra de solo lectura
(`available_quantity`, `warehouses`, `metadata`).

---

## Actualizar Producto — `PUT /v1/products/{id}`

- Path param: `id` (uuid, formato `00000000-0000-0000-0000-000000000000`).
- Mismo body/campos que crear, con una restricción adicional documentada:
  - `account_group`: **no puede modificarse** si el producto ya tiene movimientos en algún
    documento.
  - `components` de un `Combo`: **no pueden modificarse** si el combo ya tuvo movimientos
    (ya se relacionó en documentos).
- Response `200` con la misma estructura que "Consultar Producto".

---

## Consultar Producto — `GET /v1/products/{id}`

- Path param: `id` (uuid).

### Response — `200`

| Campo | Tipo | Descripción |
|---|---|---|
| `id` | string (uuid) | Id único |
| `code` | string | Código único |
| `name` | string | Nombre |
| `account_group.id` | number | Id de la clasificación de inventario |
| `account_group.name` | string | Nombre de la clasificación |
| `type` | string | `Product`, `Service` o `ConsumerGood` |
| `stock_control` | boolean | Control de inventario (default `false`) |
| `active` | boolean | Estado (default `true`) |
| `tax_classification` | string | `Gravado`/`Taxed`, `Exento`/`Exempt`, `Excluido`/`Excluded` (la doc mezcla términos en español e inglés según la página) |
| `tax_included` | boolean | IVA incluido |
| `tax_consumption_value` | number | Valor impuesto al consumo |
| `taxes[].id` / `.name` / `.type` / `.percentage` | — | Impuestos aplicados |
| `prices[].currency_code` | string | Ej. `COP` |
| `prices[].price_list[].position` | number | Posición de lista de precio |
| `prices[].price_list[].name` | string | Nombre de la lista |
| `prices[].price_list[].value` | string\* | Valor (\*la doc lo tipa como `string` en la respuesta, aunque en el request es `number` — inconsistencia de tipos, ver ambigüedades) |
| `unit.code` | string | Código de unidad, ej. `94` |
| `unit.name` | string | Nombre de unidad, ej. `Unidad` |
| `unit_label` | string | Unidad para impresión de factura |
| `reference` | string | Referencia / código de fábrica |
| `description` | string | Descripción |
| `additional_fields.barcode` / `.brand` / `.tariff` / `.model` | string | Campos adicionales |
| `available_quantity` | number | Cantidad disponible sumando todas las bodegas |
| `warehouses[].id` / `.name` | — | Bodega |
| `warehouses[].quantity` | string\* | Cantidad disponible en esa bodega (\*tipado como `string` en la doc) |
| `metadata.created` | string | Fecha de creación |
| `metadata.last_updated` | string | Fecha de última actualización |
| `metadata.stock_updated` | string | Fecha de última actualización de inventario |
| `components[]` | array\<object\> | **Solo para productos tipo `Combo`**: `{ id, code, name }` de cada componente |

```json
{
  "id": "497f6eca-6276-4993-bfeb-53cbbbba6f08",
  "code": "string",
  "name": "string",
  "account_group": { "id": 0, "name": "string" },
  "type": "string",
  "stock_control": true,
  "active": true,
  "tax_classification": "string",
  "tax_included": true,
  "tax_consumption_value": 0,
  "taxes": [{ "id": 0, "name": "string", "type": "string", "percentage": 0 }],
  "prices": [{ "currency_code": "string", "price_list": [{ "position": 0, "name": "string", "value": 0 }] }],
  "unit": { "code": "string", "name": "string" },
  "unit_label": "string",
  "reference": "string",
  "description": "string",
  "additional_fields": { "barcode": "string", "brand": "string", "tariff": "string", "model": "string" },
  "available_quantity": 0,
  "warehouses": [{ "id": 0, "name": "string", "quantity": 0 }],
  "metadata": { "created": "string", "last_updated": "string", "stock_updated": "string" }
}
```

---

## Listar Productos — `GET /v1/products`

Ordenado por fecha de creación, más recientes primero. Equivale al menú Siigo Nube:
`Transacciones > Inventarios > Productos / Servicios`.

### Query params

| Param | Tipo | Descripción |
|---|---|---|
| `code` | string | Filtra por código exacto |
| `account_group` | string | Filtra por id de clasificación de inventario |
| `type` | string | `Product`, `Service` o `Consumer Good` (default `Product`) |
| `stock_control` | string | Default `false` |
| `active` | string | Default `true` |
| `ids` | string | Hasta **20 ids** (uuid) separados por coma: `?ids={GUID},{GUID}` |
| `created_start` / `created_end` | string (`date-time`, RFC3339) | Rango por fecha de creación |
| `date_start` / `date_end` | string (`date-time`) | Rango genérico de fecha (documentado sin más detalle de a qué campo aplica) |
| `updated_start` / `updated_end` | string (`date-time`) | Rango por `last_updated` (incluye cambios de saldo de inventario) |
| `page` | integer (`int32`) | Página |
| `page_size` | integer (`int32`) | Resultados por página |

Nota: la sección de "Parámetros" en prosa (arriba de la tabla técnica) solo menciona
`code`, `created_start/end`, `updated_start/end` e `id` (singular) — la tabla técnica completa
de query params (la que realmente aplica al endpoint) es la de arriba, con `ids` (plural) y
los parámetros adicionales `type`, `stock_control`, `active`, `date_start`, `date_end`,
`page`, `page_size`. Usar la tabla técnica como fuente de verdad.

### Response — `200` (paginada, mismo sobre que `00-core-auth-http.md`)

```json
{
  "pagination": { "page": 1, "page_size": 25, "total_results": 250 },
  "results": [
    {
      "id": "string", "code": "string", "name": "string",
      "account_group": { "id": 0, "name": "string" },
      "type": "string", "stock_control": true, "active": true,
      "tax_classification": "string", "tax_included": true, "tax_consumption_value": 0,
      "taxes": [{ "id": 0, "name": "string", "type": "string", "percentage": 0 }],
      "prices": [{ "currency_code": "string", "price_list": [{ "position": 0, "name": "string", "value": 0 }] }],
      "unit": { "code": "string", "name": "string" },
      "unit_label": "string", "reference": "string", "description": "string",
      "additional_fields": { "barcode": "string", "brand": "string", "tariff": "string", "model": "string" },
      "available_quantity": 0,
      "warehouses": [{ "id": 0, "name": "string", "quantity": 0 }],
      "metadata": { "created": "string", "last_updated": "string", "stock_updated": "string" }
    }
  ],
  "__links": { "previous": { "href": "string" }, "self": { "href": "string" }, "next": { "href": "string" } }
}
```

---

## Borrar Producto — `DELETE /v1/products/{id}`

- Path param: `id` (uuid).
- Sin body.

### Response — `200`

```json
{ "id": "63f918c2-ca65-4edc-a7db-66bcdd5159fb", "deleted": true }
```

No se documentan restricciones explícitas sobre productos con movimientos (a diferencia de
`account_group` en actualizar) — no confirmado si Siigo rechaza el `DELETE` de un producto ya
usado en documentos; probablemente cae en el error genérico `delete_not_allowed` visto en
`00-core-auth-http.md`, pero no está confirmado específicamente para `products`.

---

## Ambigüedades / pendientes de confirmar

- **`type` de producto — inconsistencia entre páginas**: en "Crear Producto" la columna de
  características dice `type: Product, Service, Combo`; en "Actualizar Producto" dice
  `type: Product, Service, ConsumerGood`. Pero la descripción detallada del campo `type` en
  el body de **ambos** endpoints dice textualmente: *"Este producto puede ser de tipo
  'Product', 'Service' o 'Consumer Good'"* — sin mencionar `Combo` ahí, aunque sí se documenta
  `Combo` como valor válido más arriba en la misma página de creación (con la restricción de
  ser exclusivo de Siigo Nube Premium) y se usa en el JSON de ejemplo. Conclusión más
  probable: los valores válidos reales son `Product`, `Service`, `ConsumerGood` y `Combo`
  (este último solo en creación, con Premium), pero la documentación oficial no lo deja 100%
  consistente entre sus propias tablas.
- **Tipo de dato de `value`/`quantity` en la respuesta**: `prices[].price_list[].value` y
  `warehouses[].quantity` están tipados como `number` en el ejemplo JSON de "Consultar
  Producto" pero el schema/tabla de tipos en algunas páginas los describe como `string`. No
  se pudo determinar cuál es el comportamiento real sin una cuenta de pruebas — anotar como
  riesgo para el mapeo de tipos del SDK (aceptar ambos, castear a número de forma defensiva).
- **`tax_classification`**: valores documentados en español (`Gravado`, `Exento`, `Excluido`)
  en la página de consulta, pero en inglés (`Taxed`, `Exempt`, `Excluded`) en las páginas de
  creación/edición — probablemente el API solo acepta los valores en inglés (`Taxed` /
  `Exempt` / `Excluded`) y la página de consulta solo tradujo la descripción libremente; no
  confirmado con una llamada real.
- **`unit` request vs response**: en el request es un string (código, ej. `"94"`); en la
  respuesta es un objeto `{code, name}`. Confirmado explícitamente por las dos páginas —
  no es error de documentación, es una asimetría real de la API que el SDK debe modelar con
  dos tipos distintos (o un solo DTO con serialización distinta para request/response).
  Igual con `taxes`: en el request es `taxes[].id` (solo id), en la respuesta viene expandido
  con `name`, `type`, `percentage`.
- **`date_start` / `date_end` en Listar Productos**: la doc no aclara a qué campo de fecha
  aplican estos dos parámetros (a diferencia de `created_start/end` y `updated_start/end`,
  que sí especifican el campo). Podría ser un parámetro genérico/alias legado — no confirmado.
- **Comportamiento de `DELETE` sobre producto con movimientos**: no documentado explícitamente
  para este recurso (sí lo está para `account_group` en actualización). Verificar contra
  sandbox en fase de implementación.
- **Idempotencia en `POST /v1/products`**: no mencionada en la página de creación de producto
  ni en `00-core-auth-http.md` como uno de los 4 endpoints que soportan `Idempotency-Key`
  (facturas, notas crédito, journals, vouchers). Asumir que **no** aplica a productos salvo
  confirmación posterior.
