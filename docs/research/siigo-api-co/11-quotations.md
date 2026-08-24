# Siigo API Colombia — Quotations (Cotizaciones)

Referencia interna de ingeniería (síntesis propia, no copia literal) construida a partir de
`developers.siigo.com/docs/siigoapi/cotizaciones/5-get-quotations` y
`.../cotizaciones/2-edit-quotation` (confirmadas vía WebFetch), y del spec API Blueprint oficial
(`siigoapi.docs.apiary.io`, obtenido vía
`https://raw.githubusercontent.com/jdlar1/siigo-mcp/master/siigoapi.apib`). Recurso base:
`/v1/quotations`. Documento relativamente nuevo en la API (ver "Novedades" del spec: "Nuevo
documento: Cotización"). **No existe en el SDK JS oficial** `SiigoSAS/siigo_sdk_javascript`
(ese repo no tiene `QuotationApi` — confirmado ausente de la lista de clases del `src/api`),
consistente con ser un recurso agregado después de que ese SDK se generó.

## Endpoints

| Método | URL | Descripción |
|---|---|---|
| POST | `/v1/quotations` | Crear cotización |
| PUT | `/v1/quotations/{id}` | Editar cotización |
| GET | `/v1/quotations/{quotation_id}` | Consultar una cotización por id |
| GET | `/v1/quotations` | Listar cotizaciones |
| DELETE | `/v1/invoices/id` (⚠️ ver ambigüedad) | Borrar cotización |
| GET | `/v1/document-types?type=C` | Consultar tipos de cotización configurados |

No existe endpoint de conversión directa "cotización → factura" (`POST
/v1/quotations/{id}/convert` o similar) en ninguna fuente consultada. Ver ambigüedad al final.

## POST /v1/quotations — Crear

### Campos del body

| Campo | Tipo | Obligatorio | Notas |
|---|---|---|---|
| `document.id` | number | Sí | Ver `/document-types?type=C` |
| `date` | string (`yyyy-MM-dd`) | Sí | |
| `number` | number | Condicional | Obligatorio solo si el tipo de cotización tiene numeración manual; si se envía, no debe existir ya |
| `customer.identification` | string | Sí | Debe existir y estar activo |
| `customer.branch_office` | number | No | Default `0` |
| `seller` | number | Sí | Id del vendedor, ver `/users` |
| `cost_center` | number | No | Debe existir y estar activo |
| `currency.code` | string | No | Solo moneda extranjera |
| `currency.exchange_rate` | number | Condicional | Obligatorio si se envía `currency` |
| `items[].code` | string | Sí | Código único del producto, debe existir y estar activo |
| `items[].description` | string | No | Si no se envía, toma nombre/descripción configurada del producto |
| `items[].quantity` | number | Sí | Máx. 2 decimales, máximo `9999999.99` |
| `items[].price` | number | Sí | Máx. 6 decimales, máximo `99999999999.99` |
| `items[].discount` | number | No | Valor o porcentaje según configuración del tipo de cotización |
| `items[].taxes[].id` | number | No | Ver `/taxes`; no se permiten dos impuestos del mismo tipo en un ítem |

### Ejemplo de request

```json
{
  "document": { "id": 24446 },
  "date": "2025-09-15",
  "customer": {
    "identification": "13832081",
    "branch_office": 0
  },
  "cost_center": 235,
  "currency": { "code": "USD", "exchange_rate": 3825.03 },
  "seller": 629,
  "items": [
    {
      "code": "Item-1",
      "description": "Camiseta de algodón",
      "quantity": 1,
      "price": 1069.77,
      "discount": 0.0,
      "taxes": [ { "id": 13156 } ]
    }
  ]
}
```

### Respuesta (201)

```json
{
  "id": "63f918c2-ca65-4edc-a7db-66bcdd5159fb",
  "document": { "id": 24446 },
  "number": 22,
  "name": "C-1-22",
  "date": "2023-12-15",
  "customer": {
    "id": "6b6ceb28-b2eb-4b98-b3dd-26648a933c81",
    "identification": "13832081",
    "branch_office": 0
  },
  "cost_center": 235,
  "currency": { "code": "USD", "exchange_rate": 3825.03 },
  "total": 2546.05,
  "seller": 629,
  "items": [
    {
      "id": "63f918c2-ca65-4edc-a7db-66bcdd5159ps",
      "code": "Item-1",
      "description": "Camiseta de algodón",
      "quantity": 2,
      "price": 1069.77,
      "discount": { "percentage": 0, "value": 0 },
      "taxes": [
        { "id": 13156, "name": "IVA 19%", "percentage": 19, "value": 5, "total": 2546.05 }
      ],
      "total": 2546.05
    }
  ],
  "public_url": "https://publicview.siigo.com/document?data=MS4ruap0JuOL8dao3oKEMa",
  "metadata": {
    "created": "2020-06-15T03:33:17.208Z",
    "last_updated": "null"
  }
}
```

`public_url` es un link a la vista pública/imprimible de la cotización (útil para enviarla al
cliente directamente sin generar PDF).

## PUT /v1/quotations/{id} — Editar

Mismo body que el POST, con las siguientes restricciones documentadas explícitamente:

| Campo | Editable |
|---|---|
| `document.id` | **No** — debe ser el mismo de la cotización original |
| `number` | **No** — debe ser el mismo consecutivo |
| `customer.identification` | **No** — debe ser el mismo cliente |
| `currency.code` | **No** — debe ser la misma moneda |
| `date` | Sí |
| `customer.branch_office` | Sí |
| `seller` | Sí |
| `cost_center` | Sí |
| `currency.exchange_rate` | Sí (obligatorio si se envía `currency`) |
| `items[]` | Sí — reemplaza el detalle completo |

Respuesta: `201`, mismo shape que POST (`QuotationOut`).

## GET /v1/quotations/{quotation_id} — Consultar

### Campos de respuesta relevantes (adicionales al ejemplo de arriba)

| Campo | Tipo | Descripción |
|---|---|---|
| `items[].discount.percentage` | number | Porcentaje de descuento aplicado, si lo tuvo |
| `items[].discount.value` | number | Valor de descuento aplicado, si lo tuvo |
| `items[].taxes[].value` | number | Valor de impuesto del producto |
| `items[].taxes[].total` | number | Total del ítem incluyendo impuestos |

## GET /v1/quotations — Listar

### Query params

| Param | Tipo | Descripción |
|---|---|---|
| `created_start` | date (`yyyy-MM-dd`) | Filtra por fecha de creación ≥ valor |
| `created_end` | date (`yyyy-MM-dd`) | Filtra por fecha de creación ≤ valor |
| `name` | string | Filtra por nombre, ej. `C-003-457` |
| `customer_identification` | string | Filtra por cliente |
| `customer_branch_office` | string | Filtra por sucursal del cliente |
| `page` | int | Página |
| `page_size` | int | Resultados por página |

Confirmado mediante WebFetch directo de la página oficial `5-get-quotations` (coincide con el
spec `.apib`, salvo que la doc del `.apib` tipa `created_start`/`name`/etc. incorrectamente
como `Date` en su tabla — probablemente un error de transcripción de la doc fuente, no un tipo
real).

### Respuesta (200)

```json
{
  "pagination": { "page": 1, "page_size": 25, "total_results": 253 },
  "results": [ /* array de QuotationOut, ver arriba */ ],
  "_links": {
    "previous": { "href": "https://api.siigo.com/v1/quotations?page=4&page_size=25" },
    "self": { "href": "https://api.siigo.com/v1/quotations?page=5&page_size=25" },
    "next": { "href": "https://api.siigo.com/v1/quotations?page=6&page_size=25" }
  }
}
```

## DELETE — Borrar cotización

```
DELETE /v1/invoices/id
```

```json
// Response 200
{
  "id": "63f918c2-ca65-4edc-a7db-66bcdd5159fb",
  "deleted": true
}
```

⚠️ **Esto casi con certeza es un error de documentación**: la URL en el spec dice literalmente
`/v1/invoices/id` (recurso de facturas, no de cotizaciones, y `id` sin llaves `{}` — mismo
patrón de error tipográfico que aparece en otras partes del spec, ej. `/v1/products/id_` y
`/v1/pruchases/id`). Lo más probable es que el endpoint real sea `DELETE
/v1/quotations/{id}`. **No confirmado contra el ambiente real** — ver ambigüedad.

## GET /v1/document-types?type=C — Catálogo de tipos de cotización

| Campo | Tipo | Descripción |
|---|---|---|
| `id` | number | Id del tipo de cotización — usar en `document.id` al crear |
| `code` / `name` / `description` / `type` | string | Identificación (`type` siempre `"C"`) |
| `active` | boolean | Estado |
| `cost_center` / `cost_center_mandatory` | boolean | Manejo de centro de costo |
| `automatic_number` | boolean | Si `false`, el POST/PUT debe enviar `number` |
| `consecutive` | number | Próximo consecutivo |
| `discount_type` | string | `"Percentage"` o `"Value"` — determina cómo interpretar `items[].discount` al crear |
| `decimals` | boolean | Si el tipo de cotización maneja decimales |

## Errores específicos observados en el spec

Mismos códigos generales de validación (`parameter_required`, `invalid_date`,
`invalid_identification`, `invalid_reference` para catálogos inexistentes) más:

| Code | Contexto |
|---|---|
| `non_editable` | Se intentó cambiar `document.id`, `number`, `customer` o `currency.code` en el PUT |
| `invalid_amount` | `items[].quantity` o `items[].price` fuera de rango |

## Ambigüedades / pendientes de confirmar

- **No existe un endpoint de conversión cotización → factura** en ninguna de las fuentes
  consultadas (búsqueda exhaustiva de "quotation" en el spec `.apib` completo no arroja ningún
  endpoint de conversión). La única pista es que `QuotationOut` incluye `public_url` (vista
  pública compartible) — sugiere que el flujo de "aceptar cotización → generar factura" es
  manual dentro de Siigo Nube, no vía API. Si el SDK necesita esta funcionalidad, probablemente
  haya que crear la factura de venta desde cero copiando los `items[]` de la cotización
  consultada.
- **El endpoint de borrado de cotizaciones apunta a `/v1/invoices/id`** en el spec oficial, lo
  cual es casi con certeza un error de copiar/pegar de la sección de facturas. Antes de
  implementar `delete()` en el SDK, es indispensable confirmar contra sandbox si el endpoint
  correcto es `DELETE /v1/quotations/{id}` (patrón esperado) o si realmente el borrado de
  cotizaciones se enruta, por alguna razón interna de Siigo, a través del recurso de facturas.
- El spec `.apib` tipa varios query params de `GET /v1/quotations` (`name`,
  `customer_identification`, `customer_branch_office`) como `Date` en su tabla, lo cual es
  claramente incorrecto (son string/número). Se documentó arriba con el tipo real inferido del
  nombre/semántica del campo, no el tipo literal de la tabla fuente.
- No se encontró información sobre límites de cotizaciones por plan, ni sobre vigencia/
  expiración de una cotización (campo común en otros sistemas de cotización) — no aparece
  ningún campo tipo `valid_until` o `expiration_date` en `QuotationIn`/`QuotationOut`.
