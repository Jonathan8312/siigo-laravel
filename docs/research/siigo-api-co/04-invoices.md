# Siigo API Colombia — Invoices (`/v1/invoices`)

Referencia interna de ingeniería (síntesis propia, no copia literal) construida a partir de
`developers.siigo.com/docs/siigoapi/invoice/*` y el SDK oficial JS de Siigo
(`github.com/SiigoSAS/siigo_sdk_javascript`) como referencia cruzada de nombres exactos de
endpoints/campos.

## Endpoints

| Método | URL | Descripción |
|---|---|---|
| `POST` | `/v1/invoices` | Crear factura de venta |
| `GET` | `/v1/invoices` | Listar facturas (paginado + filtros) |
| `GET` | `/v1/invoices/{id}` | Obtener una factura por GUID |
| `PUT` | `/v1/invoices/{id}` | Actualizar factura |
| `DELETE` | `/v1/invoices/{id}` | Eliminar factura |
| `POST` | `/v1/invoices/{id}/annul` | Anular factura |
| `POST` | `/v1/invoices/{id}/stamp` | Enviar/timbrar factura electrónica ante la DIAN |
| `GET` | `/v1/invoices/{id}/stamp/errors` | Consultar errores de rechazo DIAN |
| `GET` | `/v1/invoices/{id}/pdf` | Obtener PDF de la factura |
| `POST` | `/v1/invoices/{id}/mail` | Enviar factura por correo |

Headers en todos: `Authorization: Bearer <token>`, `Partner-Id: <valor>`. `Content-Type:
application/json` en requests con body. `Idempotency-Key` soportado en `POST /v1/invoices`
(ver `00-core-auth-http.md`).

## POST /v1/invoices — Crear factura

### Campos principales del body

| Campo | Tipo | Oblig. | Notas |
|---|---|---|---|
| `document.id` | number | Sí | Tipo de documento, debe existir en la cuenta |
| `number` | number | No | Consecutivo manual; solo si el tipo de documento lo permite y no existe ya |
| `date` | string (`yyyy-MM-dd`) | Sí | Para facturación electrónica no puede ser anterior a hoy |
| `customer.identification` | string | Sí | Debe existir y estar activo |
| `customer.branch_office` | integer | No | |
| `seller` | number | Sí | Debe existir en Siigo |
| `cost_center` | number | No | |
| `currency.code` / `currency.exchange_rate` | string / number | No | Moneda extranjera |
| `observations` | string | No | Máx. 4000 |
| `advance_payment` | number | No | Anticipo aplicado |
| `items[].code` | string | Sí | Debe existir y estar activo |
| `items[].description` | string | No | |
| `items[].quantity` | number | Sí | Máx. 2 decimales |
| `items[].price` | number | Sí | Máx. 6 decimales |
| `items[].discount` | number | No | Descuento por ítem |
| `items[].warehouse` | number | No | Bodega |
| `items[].taxes[].id` | number | No | Máx. 2 impuestos por ítem (dos impuestos del mismo tipo no permitidos) |
| `payments[].id` | number | Sí | Método de pago (debe existir) |
| `payments[].value` | number | Sí | Máx. 2 decimales |
| `payments[].due_date` | string | No | Solo un método de pago con fecha de vencimiento permitido por factura |
| `stamp.send` | boolean | No | `true` para timbrar ante DIAN al crear |
| `mail.send` | boolean | No | `true` para enviar por correo al crear |
| `global_discounts[].id` / `.percentage` | number | No | Descuentos a nivel documento |
| `retentions[]` | array | No | IDs de retenciones |

Sector salud (opcional): objeto `healthcare_company` con `operation_type`
(`"SS-CUFE"`/`"SS-SinAporte"`/`"SS-Recaudo"`), `period_start`/`period_end` (`yyyy-MM-dd`),
`payment_method` (código 01-04), `service_plan` (código 02-17), `contract_number` o
`policy_number`, `copayment`, `coinsurance`, `cost_sharing`, `recovery_charge`.

### Ejemplo de request completo

```json
{
  "document": { "id": 22 },
  "number": 25,
  "date": "2021-10-15",
  "customer": { "identification": "13832081", "branch_office": 0 },
  "seller": 629,
  "cost_center": 235,
  "currency": { "code": "USD", "exchange_rate": 3825.03 },
  "observations": "Additional comments",
  "advance_payment": 33.3,
  "items": [
    {
      "code": "Item-1",
      "description": "Product description",
      "quantity": 2,
      "price": 50,
      "discount": 13,
      "warehouse": 15,
      "taxes": [ { "id": 13156 } ]
    }
  ],
  "payments": [
    { "id": 5636, "value": 1273.03, "due_date": "2021-03-19" }
  ],
  "stamp": { "send": true },
  "mail": { "send": true },
  "global_discounts": [ { "id": 13156, "percentage": 5 } ]
}
```

### Respuesta (201)

```json
{
  "id": "63f918c2-ca65-4edc-a7db-66bcdd5159fb",
  "document": { "id": 22 },
  "prefix": "FV",
  "number": 25,
  "name": "FV-2-22",
  "date": "2021-10-10",
  "customer": { "id": "302580df-838b-4531-b8bf-dd3c98b34059", "identification": "13832081", "branch_office": 0 },
  "seller": 629,
  "total": 25.5,
  "balance": 30302.24,
  "items": [
    {
      "id": "63f918c2-ca65-4edc-a7db-66bcdd5159fb",
      "code": "Item-1",
      "quantity": 2,
      "price": 50,
      "discount": { "percentage": 13, "value": 130 },
      "taxes": [ { "id": 13156, "name": "VAT 19%", "percentage": 19, "value": 5 } ]
    }
  ],
  "payments": [ { "id": 5636, "name": "Credit", "value": 1273.03, "due_date": "2021-03-19" } ],
  "stamp": { "status": "string", "cufe": "string" },
  "metadata": { "created": "string", "last_updated": "string" }
}
```

### Estados de facturación electrónica (`stamp.status`)

| Estado | Recibido por DIAN | Descripción |
|---|---|---|
| `Draft` | No | Creada, no enviada |
| `Accepted` | Sí | Enviada y aprobada |
| `Rejected` | No | Falló el envío; requiere corrección |

### Reglas de validación mencionadas en la doc

- Cliente debe existir y estar activo (o crearse inline con datos completos — no confirmado el shape exacto de creación inline dentro del payload de factura, ver ambigüedades).
- Tipo de documento (`document.id`) debe existir.
- `seller` debe ser válido.
- Códigos de producto (`items[].code`) deben existir y estar activos.
- Para facturas electrónicas: la fecha no puede ser anterior a la actual.
- Solo un método de pago con fecha de vencimiento por factura.
- Máximo 2 impuestos por ítem, no duplicados del mismo tipo.

## GET /v1/invoices — Listar facturas

### Query params

| Param | Tipo | Descripción |
|---|---|---|
| `document_id` | integer (int64) | ID del tipo de documento |
| `customer_identification` | string | Identificación del cliente |
| `customer_branch_office` | integer | Sucursal del cliente |
| `name` | string | Nombre del documento, ej. `"FV-003-457"` |
| `created_start` / `created_end` | date-time RFC3339 | Rango de fecha de creación (`created` ≥/≤) |
| `date_start` / `date_end` | date-time RFC3339 | Rango de fecha de elaboración |
| `updated_start` / `updated_end` | date-time RFC3339 | Rango de última modificación |
| `page` | integer | Página |
| `page_size` | integer | Tamaño de página |

Formato de fecha aceptado: `yyyy-MM-dd` o `yyyy-MM-ddTHH:mm:ssZ` (UTC).

### Respuesta (200) — shape completo de cada item de `results[]`

```json
{
  "id": "63f918c2-ca65-4edc-a7db-66bcdd5159fb",
  "document": { "id": 22 },
  "prefix": "FV",
  "number": 25,
  "name": "FV-2-22",
  "date": "2021-10-10",
  "customer": { "id": "302580df-838b-4531-b8bf-dd3c98b34059", "identification": "13832081", "branch_office": 0 },
  "cost_center": 235,
  "currency": { "code": "USD", "exchange_rate": 3825.03 },
  "seller": 629,
  "retentions": [ { "id": 13156, "name": "VAT 19%", "type": "Retefuente", "percentage": 19, "value": 5 } ],
  "advance_payment": 33.3,
  "total": 25.5,
  "balance": 30302.24,
  "observations": "This is an observation",
  "items": [
    {
      "id": "63f918c2-ca65-4edc-a7db-66bcdd5159fb",
      "code": "Item-1",
      "quantity": 2,
      "price": 50,
      "seller": 629,
      "description": "This is a description",
      "discount": { "percentage": 13, "value": 130 },
      "taxes": [ { "id": 13156, "name": "VAT 19%", "type": "IVA", "percentage": 19, "value": 5, "base_value": 2000 } ],
      "warehouse": { "id": 15, "name": "Main Warehouse" },
      "total": 119000
    }
  ],
  "global_charges": [ { "id": 0, "name": "string", "percentage": 0, "value": 0 } ],
  "global_discounts": [ { "id": 0, "name": "string", "percentage": 0, "value": 0 } ],
  "payments": [ { "id": 5636, "name": "Credit", "value": 1273.03, "due_date": "2021-03-19" } ],
  "additional_fields": {
    "purchase_order": { "prefix": "OC", "number": "27" },
    "delivery_order": { "prefix": "OE", "number": "27", "date": "2021-05-19" }
  },
  "stamp": { "status": "string", "cufe": "string", "cude": "string", "observations": "string", "errors": "string" },
  "mail": { "status": "string", "observations": "string" },
  "metadata": { "created": "string", "last_updated": "string", "stock_updated": "string" },
  "annulled": true
}
```

`additional_fields.purchase_order`/`delivery_order` son las referencias a orden de
compra/entrega mencionadas en el alcance. `stamp` trae `cufe` (factura) y también `cude`
(campo presente en el modelo, normalmente asociado a notas — su presencia aquí no está
explicada por la doc, ver ambigüedades). `annulled` (boolean) indica si la factura fue
anulada.

## GET /v1/invoices/{id} — Obtener una factura

Path param: `id` (GUID). Respuesta: mismo shape completo de arriba (objeto único, no
envuelto en paginación).

## PUT /v1/invoices/{id} — Actualizar factura

Path param: `id` (GUID). Body: mismo shape que `POST` (document, number, date, customer,
seller, cost_center, currency, advance_payment, observations, retentions, items, payments).

### Restricciones documentadas

- **Campos no modificables:** `document.id` (tipo de documento), `customer.identification`,
  `currency.code`, número de documentos con numeración manual.
- **No editable si:** la factura está en proceso de transmisión a la DIAN o ya fue aceptada
  (tiene CUFE asignado).
- Si existen documentos relacionados (notas crédito/débito, recibos de caja, ajustes de
  cartera), deben eliminarse primero antes de poder editar la factura.

Respuesta: `200` con el objeto factura actualizado completo.

## DELETE /v1/invoices/{id}

**Confirmado directamente (página `invoice/6-delete-invoice`)**. Mismas reglas de negocio y
mismo shape de respuesta que `annul` (arriba): no se puede borrar una factura en proceso de
envío a la DIAN/aceptada, ni con documentos relacionados sin eliminar primero.

```json
{ "id": "63f918c2-ca65-4edc-a7db-66bcdd5159fb", "deleted": true }
```

## POST /v1/invoices/{id}/annul — Anular factura

**Confirmado directamente contra `developers.siigo.com` (2026-08-23, sidebar renderizado,
página `invoice/5-annul-invoice`)**: no requiere body (solo el `id` en el path). Respuesta
`200`:

```json
{ "id": "63f918c2-ca65-4edc-a7db-66bcdd5159fb", "deleted": true }
```

Reglas de negocio documentadas explícitamente en esta página: no se puede anular una
factura en proceso de envío a la DIAN o ya aceptada (con CUFE), ni una factura con
documentos relacionados (notas crédito/débito, recibos de caja, ajustes de cartera) — esos
deben eliminarse primero en Siigo Nube.

## No existe un endpoint de "timbrado" (`stamp`) independiente

**Confirmado navegando el sidebar completo y renderizado de la sección "Facturas de Venta"
(11 páginas exactas, 2026-08-23)**: no hay ninguna página `POST /v1/invoices/{id}/stamp`.
La suposición previa (Fase 0, basada en el SDK oficial JS) de que existía un endpoint
separado para timbrar una factura creada sin `stamp.send: true` **no se pudo confirmar
oficialmente y probablemente no existe como endpoint público documentado** — el único
mecanismo confirmado para solicitar el timbrado electrónico es el campo `stamp.send` dentro
de `POST`/`PUT /v1/invoices`. El SDK no implementa un método `stamp()` separado hasta
confirmar lo contrario.

## GET /v1/invoices/{id}/stamp/errors — Errores de rechazo DIAN

**Confirmado directamente (página `invoice/8-get-electronic-invoice-errors`)**. Respuesta:

```json
{
  "id": "63f918c2-ca65-4edc-a7db-66bcdd5159fb",
  "errors": [ { "message": "This is an Error" } ]
}
```

## GET /v1/invoices/{id}/pdf — PDF de factura

**Confirmado directamente (página `invoice/9-get-invoice-p-d-f`)**. Respuesta:

```json
{ "id": "63f918c2-ca65-4edc-a7db-66bcdd5159fb", "cufe": "123456789012", "base64": "string" }
```

## GET /v1/invoices/{id}/xml — XML de factura (endpoint no detectado en la Fase 0)

**Nuevo, confirmado directamente (página `invoice/get-invoice-x-m-l`)** — no estaba en el
listado original de endpoints de la Fase 0. Mismo shape que PDF:

```json
{ "id": "63f918c2-ca65-4edc-a7db-66bcdd5159fb", "cufe": "123456789012", "base64": "string" }
```

## POST /v1/invoices/{id}/mail — Enviar factura por correo

```json
{
  "guid": "a84cb564-8217-4061-98d6-4e0128e517c1",
  "mail_to": "cliente@example.com",
  "copy_to": "a@example.com;b@example.com;c@example.com;d@example.com;e@example.com"
}
```

- `guid`: GUID de la factura (coincide con `{id}` de la ruta).
- `mail_to`: destinatario principal.
- `copy_to`: hasta 5 direcciones adicionales en copia, separadas por `;`.

Respuesta (200):

```json
{ "status": "string", "observations": "string" }
```

## ¿Facturación por lotes ("batch")?

**Confirmado que NO existe** un endpoint nativo de creación masiva/batch de facturas en la
documentación oficial (sidebar completo de "Facturas de Venta" inspeccionado, 11 páginas,
ninguna es de batch — ver arriba). Una búsqueda web encontró una operación
`siigo_create_invoice_batch` mencionada en un MCP server de terceros no oficial
(`github.com/jdlar1/siigo-mcp`), pero eso es un wrapper de conveniencia de ese proyecto (muy
probablemente iterando sobre `POST /v1/invoices` una factura a la vez), no una funcionalidad
real de la API de Siigo. **El SDK no debe fabricar un método "batch" que no existe en la
API** — la forma correcta de crear muchas facturas es invocar `create()` repetidamente,
usando `Idempotency-Key` (soportado en este endpoint) para que reintentos ante fallos de red
no dupliquen facturas, y respetando el rate limit documentado (100/min producción, 10/min
prueba). Ver `docs/invoices.md` para la guía de uso recomendada.

## Ambigüedades / pendientes de confirmar

- El campo `cude` visible en el modelo `stamp` de la respuesta de listado normalmente
  corresponde a notas crédito/débito (CUDE) más que a facturas (CUFE) — no está claro si
  Siigo simplemente reutiliza el mismo sub-modelo `stamp` para todos los documentos
  electrónicos y el campo queda vacío en facturas, o si tiene un uso real ahí.
- No se confirmó si es posible crear un cliente "inline" (objeto completo en vez de solo
  `identification`) dentro del payload de `POST /v1/invoices` — la doc dice que el cliente
  "debe existir" pero se detectó una mención ambigua a creación inline sin ejemplo completo.
  Tratar como no soportado hasta confirmar.
- No se confirmó si `Idempotency-Key` también aplica a `PUT /v1/invoices/{id}` (el core
  research solo confirma `POST` para los 4 endpoints de documentos electrónicos).
