# Siigo API Colombia — Payment Receipts (Recibos de Pago/Egreso)

Referencia interna de ingeniería (síntesis propia, no copia literal) construida a partir de
`developers.siigo.com/docs/siigoapi` y del spec API Blueprint oficial (`siigoapi.docs.apiary.io`)
obtenido vía `https://raw.githubusercontent.com/jdlar1/siigo-mcp/master/siigoapi.apib`. Recurso
base: `/v1/payment-receipts`. Es el equivalente de vouchers pero para pagos a proveedores
(egresos) en vez de cobros a clientes.

Nota: este recurso es relativamente nuevo (ver "Novedades" del spec: "Nuevo endpoint de recibos
de pago/egreso") y **no existe en el SDK JS oficial** `SiigoSAS/siigo_sdk_javascript`
(confirmado — ese repo solo tiene `VoucherApi`, `JournalEntryApi`, `AccountsPayableApi`,
`TestBalanceApi`; no tiene `PaymentReceiptApi`), lo que sugiere que ese SDK está desactualizado
respecto al recurso. Toda la información de este archivo viene del spec `.apib` oficial.

## Endpoints

| Método | URL | Descripción |
|---|---|---|
| POST | `/v1/payment-receipts` | Crear recibo de pago/egreso (abono a deuda / anticipo) |
| POST | `/v1/payment-receipts` | Crear recibo de pago/egreso **avanzado** (`type: Detailed`) |
| GET | `/v1/payment-receipts/{id}` | Consultar un recibo por id |
| GET | `/v1/payment-receipts` | Listar recibos |
| DELETE | `/v1/payment-receipts/{id}` | Borrar un recibo |
| GET | `/v1/document-types?type=RP` | Consultar tipos de recibo de pago/egreso configurados |

A diferencia de vouchers, aquí **sí existe DELETE** y **sí existe el tipo avanzado `Detailed`**
(vouchers lo eliminó, payment-receipts lo mantiene — ver ambigüedad al final). No se documentó
endpoint de edición (`PUT`) pese a que la tabla resumen inicial del spec (`# Siigo API`) dice
"Crear, editar, eliminar y consultar" — ver ambigüedad.

## POST /v1/payment-receipts — Crear (simple: abono / anticipo)

### Tipos de recibo (`type`)

| Valor | Significado |
|---|---|
| `DebtPayment` | Abono a una o varias facturas de compra |
| `AdvancePayment` | Anticipo al proveedor |
| `Detailed` | Avanzado — múltiples cuentas contables (bancos, vencimientos, impuestos) |

### Campos del body (variante simple)

| Campo | Tipo | Obligatorio | Notas |
|---|---|---|---|
| `document.id` | number | Sí | Ver `/document-types?type=RP` |
| `date` | string (`yyyy-MM-dd`) | Sí | |
| `type` | string | Sí | `DebtPayment`, `AdvancePayment` o `Detailed` |
| `supplier.identification` | string | Sí | Debe existir y estar activo |
| `supplier.branch_office` | number | No | Default `0` |
| `currency.code` | string | No | Solo moneda extranjera |
| `currency.exchange_rate` | number | No | Obligatorio si se envía `currency` |
| `cost_center` | number | No | Debe existir y estar activo |
| `items[].due.prefix/consecutive/quote/date` | — | Sí (si `DebtPayment`) | Vencimiento de la factura de compra a pagar |
| `payment.id` | number | Sí | Ver `/payment-types` |
| `payment.value` | number | Sí | Máx. 2 decimales |
| `observations` | string | No | |

### Ejemplo — Anticipo

```json
{
  "document": { "id": 27234 },
  "type": "AdvancePayment",
  "date": "2025-01-12",
  "supplier": { "identification": "109048401", "branch_office": "0" },
  "payment": { "id": 5638, "value": 10000 },
  "observations": "observación de prueba"
}
```

## POST /v1/payment-receipts — Crear (Avanzado, `type: Detailed`)

Permite asociar múltiples registros contables con cuentas de bancos, vencimientos e impuestos
directamente por `items[].account`, en vez de usar el modelo `due`/`payment` simple.

### Ejemplo completo (con 4 movimientos: base, vencimiento, impuesto, contrapartida)

```json
{
  "document": { "id": 24445 },
  "date": "2015-01-15",
  "type": "Detailed",
  "supplier": { "identification": "8694251", "branch_office": 0 },
  "items": [
    {
      "account": { "code": "11100501", "movement": "Credit" },
      "description": "FC-2 Base",
      "value": 50
    },
    {
      "account": { "code": "13050501", "movement": "Debit" },
      "due": { "prefix": "FC-1", "consecutive": 684, "quote": 1, "date": "2020-02-15" },
      "description": "FC-2 Base",
      "value": 50
    },
    {
      "account": { "code": "24081001", "movement": "Debit" },
      "tax": { "id": 13156 },
      "description": "FC-2 Base",
      "value": 19
    },
    {
      "account": { "code": "11100501", "movement": "Credit" },
      "description": "FC-2 Base",
      "value": 19
    }
  ],
  "observations": "observación de prueba"
}
```

`items[].account.code` es la cuenta contable; `items[].account.movement` es `Debit` o `Credit`.
`due` solo aplica en la línea que representa el pago a la factura de compra. `tax.id` solo
aplica en la línea del impuesto (sin `base`/`percentage`/`value` explícitos en el request — se
calculan server-side a partir del `value` de la línea).

### Respuesta (201) — variante avanzada

Mismo shape que el request pero con `id`, `number`, `name`, `balance` y `metadata` agregados
(análogo a `VoucherOutDetailed`, ver `08-vouchers.md`).

## GET /v1/document-types?type=RP — Catálogo de tipos de recibo de pago/egreso

| Campo | Tipo | Descripción |
|---|---|---|
| `id` | string | Id del tipo de recibo |
| `code` | number | Código |
| `name` | string | Nombre interno |
| `description` | string | Descripción |
| `type` | string | Tipo |
| `active` | boolean | Activo o no |
| `cost_center` / `cost_center_mandatory` / `cost_center_default` | boolean | Manejo de centro de costo |
| `automatic_number` | boolean | Si es `false`, el POST debe enviar `number` |
| `consecutive` | number | Próximo consecutivo |

## GET /v1/payment-receipts/{id} — Consultar

### Campos de respuesta relevantes

| Campo | Tipo | Descripción |
|---|---|---|
| `id` | string | Id del recibo |
| `document.id` | number | Tipo de comprobante |
| `number` | number | Consecutivo |
| `name` | string | Ej. `RP-1-22` |
| `date` | string | Fecha |
| `supplier.id` / `supplier.identification` / `supplier.branch_office` | — | Proveedor |
| `payments[].id/name/value` | — | Forma de pago |
| `cost_center` | number | Centro de costo |
| `observations` | string | |

## GET /v1/payment-receipts — Listar

### Query params

| Param | Tipo | Descripción |
|---|---|---|
| `created_start` / `created_end` | Date | Filtra por campo `created` |
| `updated_start` / `updated_end` | Date | Filtra por campo `last_updated` |

Formato de fecha: `yyyy-MM-dd`; fecha+hora UTC: `yyyy-MM-ddTHH:mm:ssZ`. Nota: a diferencia de
vouchers/journals, el spec **no documenta** `page`/`page_size` explícitamente en esta sección
puntual, pero la respuesta sí trae `pagination` — asumir que `page`/`page_size` funcionan igual
que en el resto de la API (patrón general confirmado en `00-core-auth-http.md`).

### Respuesta (200)

```json
{
  "pagination": { "page": 1, "page_size": 25, "total_results": 0 },
  "results": [ /* array de recibos, shape de GET /{id} */ ],
  "_links": {
    "previous": { "href": "https://api.siigo.com/v1/payment-receipts?page=4&page_size=25" },
    "self": { "href": "https://api.siigo.com/v1/payment-receipts?page=5&page_size=25" },
    "next": { "href": "https://api.siigo.com/v1/payment-receipts?page=6&page_size=25" }
  }
}
```

## DELETE /v1/payment-receipts/{id} — Borrar

```json
// Response 200
{
  "id": "63f918c2-ca65-4edc-a7db-66bcdd5159fb",
  "deleted": true
}
```

## Errores específicos observados en el spec

Mismos códigos generales que vouchers (`invalid_payment`, `invalid_document`,
`invalid_cost_center`, `invalid_amount`, `already_exists`/`duplicated_document`). Adicional:

| Code | Contexto |
|---|---|
| `invalid_account` | En la variante avanzada, cuenta contable relacionada con grupos de inventario/activos fijos/impuestos usada incorrectamente (mencionado en el contexto de facturas de compra, aplicable por analogía a `Detailed`) |
| `delete_not_allowed` | El recibo tiene documentos relacionados que impiden el borrado |

## Ambigüedades / pendientes de confirmar

- La tabla resumen inicial del spec dice explícitamente "Recibos de pago/egreso: Crear, editar,
  eliminar y consultar", y la sección "Novedades" repite "Ahora podrás crear, editar, consultar
  y eliminar recibos de pago/egreso" — pero el `Group Recibos de pago o egreso` del spec
  **no contiene ningún endpoint `PUT`**. No confirmado si el endpoint de edición existe pero no
  quedó documentado en este `.apib`, o si la promesa de "editar" en el texto de novedades quedó
  desactualizada. Recomendado: probar `PUT /v1/payment-receipts/{id}` directamente contra
  sandbox antes de asumir que no existe.
- No quedó claro por qué vouchers eliminó el tipo `Detailed` (según su sección de Novedades)
  mientras payment-receipts lo mantiene activo y documentado con ejemplo completo. Podría ser
  una asimetría real de producto (Siigo migró primero los recibos de caja) o una inconsistencia
  de documentación. Verificar contra sandbox si `POST /v1/vouchers` con `type: Detailed` es
  realmente rechazado.
- El ejemplo de la variante avanzada no muestra el campo `tax.id` con más detalle (`base`,
  `percentage`, `value`) en el *request* — no confirmado si estos sub-campos son aceptados/
  ignorados o si el cálculo del impuesto es 100% server-side a partir de `value` + `tax.id`.
- No se encontró en el spec una sección de errores 100% dedicada a `payment-receipts`; los
  códigos listados arriba se infieren por analogía con `vouchers` y `purchases`. Confirmar con
  pruebas reales antes de mapear excepciones específicas en el SDK.
