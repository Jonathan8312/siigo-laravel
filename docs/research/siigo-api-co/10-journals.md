# Siigo API Colombia — Journals (Comprobantes Contables)

Referencia interna de ingeniería (síntesis propia, no copia literal) construida a partir de
`developers.siigo.com/docs/siigoapi/journal-entry/3-get-journals/` (confirmada vía WebFetch),
del spec API Blueprint oficial (`siigoapi.docs.apiary.io`, obtenido vía
`https://raw.githubusercontent.com/jdlar1/siigo-mcp/master/siigoapi.apib`) y del SDK oficial
`SiigoSAS/siigo_sdk_javascript` (`src/api/JournalEntryApi.js`, generado por OpenAPI Generator).
Recurso base: `/v1/journals`.

## Endpoints

| Método | URL | Descripción |
|---|---|---|
| POST | `/v1/journals` | Crear comprobante contable |
| GET | `/v1/journals/{id}` | Consultar un comprobante por id (confirmado por SDK + página oficial) |
| GET | `/v1/journals` | Listar comprobantes contables |
| GET | `/v1/document-types?type=CC` | Consultar tipos de comprobante contable configurados |

No hay `PUT` ni `DELETE` documentados para journals en ninguna de las tres fuentes — un asiento
contable creado por error aparentemente debe corregirse con un asiento inverso (patrón contable
estándar), no editando/borrando el original.

## POST /v1/journals — Crear comprobante contable

El spec solo documenta en detalle el ejemplo de "cuentas sin detalles" (tipo 1 de la tabla de
abajo); para los otros 4 tipos de comprobante indica contactar `soporteapi@siigo.com`. La
combinación de campos usados por línea (`items[]`) determina el tipo real.

### Tipos de comprobantes contables soportados (mismo endpoint, diferente combinación de campos)

| Tipo | Descripción |
|---|---|
| 1 | Sin detalles en cuentas contables (bancos, tesorería, etc.) |
| 2 | Cuentas contables con manejo de vencimientos (cuentas por cobrar/pagar) — usa `items[].due` |
| 3 | Cuentas contables con manejo de impuestos — usa `items[].tax` |
| 4 | Cuentas contables con manejo de activos fijos — usa `items[].fixed_assets` |
| 5 | Cuentas contables con manejo de inventarios — usa `items[].product` |

### Campos del body

| Campo | Tipo | Obligatorio | Notas |
|---|---|---|---|
| `document.id` | number | Sí | Ver `/document-types?type=CC` |
| `date` | string (`yyyy-MM-dd`) | Sí | Para comprobantes electrónicos, no más de 10 días de diferencia con la fecha actual |
| `number` | number | No | Consecutivo manual; si se envía, no debe existir ya en Siigo Nube |
| `currency.code` / `currency.exchange_rate` | — | No | Solo moneda extranjera |
| `items[].account.code` | string | Sí | Código de cuenta contable, debe existir y estar activo |
| `items[].account.movement` | string | Sí | `"Debit"` o `"Credit"` |
| `items[].customer.identification` | string | Condicional | Obligatorio si la cuenta maneja terceros (cuentas por cobrar/pagar) |
| `items[].customer.branch_office` | number | No | Default `0` |
| `items[].cost_center` | number | No | Debe existir y estar activo |
| `items[].value` | number | Sí | Máx. 2 decimales |
| `items[].description` | string | No | |
| `items[].due.prefix/consecutive/quote/date` | — | Condicional | Obligatorio si la cuenta está relacionada con vencimientos de cartera |
| `items[].tax.id` | number | Condicional | Obligatorio si la cuenta está relacionada con impuestos |
| `items[].tax.name` | string | Condicional | |
| `items[].tax.type` | string | Condicional | Ej. `IVA` |
| `items[].tax.percentage` | number | Condicional | |
| `items[].tax.base_value` | number | Condicional | Base para el cálculo del impuesto |
| `items[].fixed_assets` | number | No | Id del activo fijo |
| `items[].product.code` | string | Condicional | Obligatorio si la línea maneja inventario |
| `items[].product.quantity` | number | Condicional | Máx. 2 decimales |
| `items[].product.warehouse` | number | No | Id de bodega/almacén |
| `observations` | string | No | Máx. 4000 caracteres |

⚠️ **Regla de negocio crítica (no explícita en un campo, sino en el error `invalid_balance`):
la suma de los movimientos `Debit` debe ser igual a la suma de los movimientos `Credit`.**
El SDK oficial confirma esta estructura de item vía `JournalEntryItem` (`account`, `customer`,
`description`, `tax`, `due`, `product`, `cost_center`, `fixed_asset`, `value`).

### Ejemplo — comprobante simple (débito/crédito sin vencimientos)

```json
{
  "document": { "id": 27441 },
  "date": "2021-05-15",
  "items": [
    {
      "account": { "code": "11050501", "movement": "Debit" },
      "customer": { "identification": "13832081", "branch_office": 0 },
      "description": "Descripción Débito",
      "cost_center": 235,
      "value": 119000
    },
    {
      "account": { "code": "11100501", "movement": "Credit" },
      "customer": { "identification": "13832081", "branch_office": 0 },
      "description": "Descripción Crédito",
      "cost_center": 235,
      "value": 119000
    }
  ],
  "observations": "Comentarios del comprobante contable"
}
```

Nota: en este ejemplo el spec marca `customer` como requerido en cada línea aunque la
descripción general lo condiciona a cuentas que manejan terceros — tratarlo como
"obligatorio salvo que la cuenta explícitamente no maneje terceros" al construir el SDK.

### Respuesta (201)

```json
{
  "id": "63f918c2-ca65-4edc-a7db-66bcdd5159fb",
  "document": { "id": 27441 },
  "number": 20,
  "name": "CC-10-20",
  "date": "2021-10-10",
  "currency": { "code": "USD", "exchange_rate": 3825.03 },
  "items": [
    {
      "account": { "code": "11050501", "movement": "Credit" },
      "customer": {
        "id": "302580df-838b-4531-b8bf-dd3c98b34059",
        "identification": "13832081",
        "branch_office": 0
      },
      "cost_center": 235,
      "due": { "prefix": "FV-1", "consecutive": 68, "quote": 1, "date": "2021-04-22" },
      "tax": { "id": 13156, "name": "VAT 19%", "type": "IVA", "percentage": 19, "value": 5, "base_value": 2000 },
      "fixed_asset": { "id": 13156, "name": "Personal Computer" },
      "product": {
        "id": "63f918c2-ca65-4edc-a7db-66bcdd5159fb",
        "code": "Item-1",
        "name": "Cotton shirt",
        "warehouse": { "id": 15, "name": "Main Warehouse" },
        "quantity": 2
      },
      "description": "This is a description",
      "value": 119000
    }
  ],
  "balance": 0,
  "observations": "This is an observation",
  "metadata": { "created": "string", "last_updated": "string", "stock_updated": "string" }
}
```

`balance` en 0 indica que el asiento cuadra (débitos = créditos). El objeto de respuesta
combina en un mismo `items[]` los campos de todos los 5 "tipos" de comprobante — solo vienen
poblados los relevantes al asiento creado.

## GET /v1/journals/{id} — Consultar

`id` es un GUID. Respuesta: mismo shape que el POST.

## GET /v1/journals — Listar

### Query params

| Param | Tipo | Descripción |
|---|---|---|
| `name` | string | Filtra por nombre del comprobante (ej. `CC-10-20`) |
| `created_start` / `created_end` | string (RFC3339) | Filtra por campo `created` |
| `date_start` / `date_end` | string (RFC3339) | Filtra por campo `date` |
| `updated_start` / `updated_end` | string (RFC3339) | Filtra por campo `last_updated` |
| `page` | int32 | Página actual |
| `page_size` | int32 | Resultados por página |

Confirmado por WebFetch directo de `journal-entry/3-get-journals/` y por el SDK oficial
(coinciden exactamente). Nota: el spec `.apib` documenta además un query param `document_id`
en su tabla de la sección "Listar Comprobantes Contables" — no aparece en la página web
renderizada ni en el SDK, ver ambigüedad.

### Respuesta (200)

```json
{
  "pagination": { "page": 1, "page_size": 25, "total_results": 250 },
  "results": [ /* array de comprobantes, shape de GET /{id} */ ],
  "__links": {
    "previous": { "href": "" },
    "self": { "href": "" },
    "next": { "href": "" }
  }
}
```

Aquí la página oficial confirma la clave `__links` (doble guion bajo) — a diferencia de
vouchers/quotations/payment-receipts/accounts-payable en el spec `.apib`, que usan `_links`
(uno solo). Ver ambigüedad general sobre este campo en `08-vouchers.md`.

## GET /v1/document-types?type=CC — Catálogo de tipos de comprobante contable

| Campo | Tipo | Descripción |
|---|---|---|
| `id` / `code` / `name` / `description` / `type` | — | Identificación del tipo |
| `active` | boolean | Activo o no |
| `cost_center` / `cost_center_mandatory` / `cost_center_default` | boolean | Manejo de centro de costo |
| `automatic_number` | boolean | Si `false`, el POST debe enviar `number` |
| `consecutive` | number | Próximo consecutivo |

## Errores específicos observados en el spec

| Code | Contexto |
|---|---|
| `invalid_balance` | Los débitos y créditos del comprobante no son iguales |
| `invalid_account` | Se envió un número de cuenta contable inexistente |
| `non_editable` | Se intentó cambiar un dato no editable del comprobante |
| `date_settings` | La fecha del comprobante no está permitida según configuración |
| `entry_service` | No es posible completar la solicitud con las condiciones actuales |

## Ambigüedades / pendientes de confirmar

- El spec `.apib` documenta un query param `document_id` para `GET /v1/journals` que no aparece
  en la página web oficial renderizada (`journal-entry/3-get-journals/`) ni en el SDK JS.
  Podría ser un remanente de una versión anterior del filtro. Usar los params confirmados por
  dos fuentes (`name`, `created_start/end`, `date_start/end`, `updated_start/end`, `page`,
  `page_size`) como los oficiales; tratar `document_id` como no confirmado.
- No quedó claro si `items[].customer` es realmente obligatorio en *todas* las líneas o solo en
  las que usan cuentas de terceros (cuentas por cobrar/pagar) — la tabla de campos del spec dice
  "Campo obligatorio" sin condicionarlo, pero la descripción general del recurso sí lo
  condiciona a "cuentas relacionadas con venciminetos de cartera". El ejemplo oficial sí lo
  incluye en ambas líneas (débito y crédito) incluso sin usar `due`. Recomendado: enviarlo
  siempre que se tenga el tercero a mano, y validar contra sandbox si se puede omitir para
  cuentas puramente de bancos/gastos.
- Los 5 "tipos" de comprobante contable (sin detalle, con vencimientos, con impuestos, con
  activos fijos, con inventario) no tienen schemas separados y documentados uno por uno — el
  spec explícitamente remite a soporte (`soporteapi@siigo.com`) para ejemplos de los tipos 2-5.
  Los campos de cada tipo se infirieron combinando la tabla de campos + el modelo de respuesta
  (`JournalEntryItem` del SDK), pero no hay un ejemplo end-to-end oficial de, por ejemplo, un
  asiento con `product`/inventario.
- No se encontró documentación de anulación/reversión de comprobantes contables (ni `PUT` ni
  `DELETE` ni `annul`). No confirmado si existe alguna vía API para esto o si es exclusivamente
  manual desde Siigo Nube.
