# Siigo API Colombia — Vouchers (Recibos de Caja)

Referencia interna de ingeniería (síntesis propia, no copia literal) construida a partir de
`developers.siigo.com/docs/siigoapi` y del spec API Blueprint oficial (`siigoapi.docs.apiary.io`,
mismo contenido fuente que alimenta el sitio de docs — verificado contra
`https://raw.githubusercontent.com/jdlar1/siigo-mcp/master/siigoapi.apib`) y contra el SDK
oficial `SiigoSAS/siigo_sdk_javascript` (generado por OpenAPI Generator desde el spec real de
Siigo). Recurso base: `/v1/vouchers`.

## Novedad importante (breaking change ya aplicado)

Según la sección "Novedades" del spec oficial: **se eliminó el tipo de recibo de caja avanzado
(`Detailed`)**. El request de abono a deuda ahora incluye impuestos y descuentos directamente
en `items` (en vez de usar cuentas contables sueltas). Se agregó un tipo nuevo `MiscIncome`
para "otros ingresos", asociado al catálogo `/misc-income` (fuera de alcance de este archivo).

## Endpoints

| Método | URL | Descripción |
|---|---|---|
| POST | `/v1/vouchers` | Crear recibo de caja (abono a deuda / anticipo) |
| POST | `/v1/vouchers` | Crear recibo de caja de "Otros ingresos" (`type: MiscIncome`) |
| GET | `/v1/vouchers/{id}` | Consultar un recibo de caja por id (Guid) |
| GET | `/v1/vouchers` | Listar recibos de caja |
| GET | `/v1/document-types?type=RC` | Consultar tipos de recibo de caja configurados |

No existe endpoint de edición (`PUT`) ni de anulación/borrado para vouchers en el spec oficial
(a diferencia de facturas, que sí tienen `annul` y `DELETE`). Ver sección de ambigüedades sobre
dos endpoints adicionales (`stamp`, `mail`) que aparecen en el SDK JS oficial pero no en el spec
`.apib` consultado.

## POST /v1/vouchers — Crear recibo de caja

### Headers
`Authorization`, `Partner-Id`, `Content-Type: application/json`. Soporta `Idempotency-Key`
(confirmado en `00-core-auth-http.md`).

### Tipos de recibo (`type`)

| Valor | Significado |
|---|---|
| `DebtPayment` | Abono a una o varias cuotas/vencimientos |
| `AdvancePayment` | Anticipo del cliente sin asociar a un vencimiento |
| `MiscIncome` | Otros ingresos (requiere `income.id` del catálogo `/misc-income`) |
| ~~`Detailed`~~ | Eliminado — no usar |

### Campos del body

| Campo | Tipo | Obligatorio | Notas |
|---|---|---|---|
| `document.id` | number | Sí | Id del tipo de comprobante, consultar en `/document-types?type=RC` |
| `date` | string (`yyyy-MM-dd`) | Sí | Fecha del comprobante |
| `type` | string | Sí | `DebtPayment`, `AdvancePayment` o `MiscIncome` |
| `customer.identification` | string | Sí | Debe existir y estar activo en Siigo Nube |
| `customer.branch_office` | number | No | Default `0` |
| `currency.code` | string | No | Solo si la empresa maneja moneda extranjera |
| `currency.exchange_rate` | number | No | Obligatorio si se envía `currency` |
| `cost_center` | number | No | Debe existir y estar activo |
| `items` | array | Obligatorio solo si `type = DebtPayment` | Vencimientos a los que se aplica el abono |
| `items[].due.prefix` | string | Sí (dentro de items) | Prefijo del vencimiento/factura |
| `items[].due.consecutive` | number | Sí | Consecutivo de la factura |
| `items[].due.quote` | number | Sí | Número de cuota |
| `items[].due.date` | string | No | Fecha del vencimiento |
| `items[].taxes[].id` | number | No | Ajustes de impuesto al saldo del vencimiento |
| `items[].taxes[].base` | number | No | Valor base del impuesto |
| `items[].discounts[].id` | number | No | Descuento del catálogo `/expenses` |
| `items[].discounts[].value` | number | No | Valor del descuento |
| `items[].value` | number | Sí | Valor total del ítem |
| `payment.id` | number | Sí | Forma de pago, ver `/payment-types` |
| `payment.value` | number | Sí | Numérico, máx. 2 decimales |
| `income.id` | number | Sí, solo si `type = MiscIncome` | Concepto de ingreso, ver `/misc-income` |
| `observations` | string | No | Comentarios adicionales |

⚠️ Error específico: `invalid_payment` se dispara si se envía `payment` en un recibo de caja
avanzado (remanente del tipo `Detailed` eliminado) — indica que el campo `payment` y el modelo
de cuentas contables sueltas son mutuamente excluyentes.

### Ejemplo — Anticipo (`AdvancePayment`)

```json
{
  "document": { "id": 27234 },
  "type": "AdvancePayment",
  "date": "2021-04-12",
  "customer": {
    "identification": "109048401",
    "branch_office": "0"
  },
  "payment": {
    "id": 5638,
    "value": 10000
  },
  "observations": "observación de prueba"
}
```

### Ejemplo — Abono a deuda (`DebtPayment`) con impuestos y descuentos

```json
{
  "document": { "id": 7714 },
  "date": "2021-04-22",
  "type": "DebtPayment",
  "customer": {
    "identification": "209048401",
    "branch_office": 0
  },
  "currency": { "code": "USD", "exchange_rate": 3825.03 },
  "cost_center": 235,
  "items": [
    {
      "due": { "prefix": "FV-1", "consecutive": 68, "quote": 1, "date": "2021-04-22" },
      "taxes": [
        { "id": 13156, "name": "IVA 19%", "percentage": 19, "base": 100000, "value": 19000 }
      ],
      "discounts": [
        { "id": 156, "name": "Descuento pronto pago", "value": 1000 }
      ],
      "value": 119000
    }
  ],
  "payment": { "id": 5636, "value": 119000 },
  "observations": "Observaciones"
}
```

### Ejemplo — Otros ingresos (`MiscIncome`)

```json
{
  "document": { "id": 170674 },
  "type": "MiscIncome",
  "date": "2026-03-19",
  "customer": { "identification": "1152489636", "branch_office": "0" },
  "income": { "id": 174 },
  "payment": { "id": 3720, "value": 12000 },
  "observations": "observación de prueba"
}
```

### Respuesta (201)

```json
{
  "id": "63f918c2-ca65-4edc-a7db-66bcdd5159fb",
  "document": { "id": 7714 },
  "number": 22,
  "name": "RC-2-22",
  "date": "2021-04-22",
  "type": "DebtPayment",
  "cost_center": 235,
  "customer": {
    "id": "302580df-838b-4531-b8bf-dd3c98b34059",
    "identification": "209048401",
    "branch_office": 0
  },
  "currency": { "code": "USD", "exchange_rate": 3825.03 },
  "items": [
    {
      "due": { "prefix": "FV-1", "consecutive": 68, "quote": 1, "date": "2021-04-22" },
      "taxes": [
        { "id": 13156, "name": "IVA 19%", "type": "IVA", "percentage": 19, "base": 100000, "value": 19000 }
      ],
      "discounts": [
        { "id": 156, "name": "Descuento pronto pago", "value": 1000 }
      ],
      "description": "This is a description",
      "value": 119000
    }
  ],
  "payment": { "id": 5636, "name": "Crédito", "value": 119000, "due_date": "2021-03-19" },
  "balance": 0,
  "observations": "Observaciones",
  "metadata": { "created": "string", "last_updated": "string", "stock_updated": "string" }
}
```

`balance` representa el saldo pendiente del recibo (0 = totalmente aplicado).

## GET /v1/vouchers/{id} — Consultar

`id` es un GUID (`00000000-0000-0000-0000-000000000000`). Respuesta: mismo shape que el POST
(`VoucherOut`).

## GET /v1/vouchers — Listar

### Query params (unión spec + SDK oficial; el spec público solo documenta los 4 de fecha, el
SDK generado desde el OpenAPI real incluye también `name`, `date_start`/`date_end`)

| Param | Tipo | Descripción |
|---|---|---|
| `name` | string | Nombre del recibo, ej. `RC-01-45` |
| `created_start` / `created_end` | date (RFC3339) | Filtra por campo `created` |
| `date_start` / `date_end` | date (RFC3339) | Filtra por campo `date` del comprobante |
| `updated_start` / `updated_end` | date (RFC3339) | Filtra por campo `last_updated` |
| `page` | int32 | Página actual |
| `page_size` | int32 | Resultados por página |

### Respuesta (200)

```json
{
  "pagination": { "page": 1, "page_size": 25, "total_results": 250 },
  "results": [ /* array de VoucherOut, ver arriba */ ],
  "_links": {
    "previous": { "href": "https://api.siigo.com/v1/vouchers?page=4&page_size=25" },
    "self": { "href": "https://api.siigo.com/v1/vouchers?page=5&page_size=25" },
    "next": { "href": "https://api.siigo.com/v1/vouchers?page=6&page_size=25" }
  }
}
```

Nota: en el spec `.apib` la clave es `_links` (con guion bajo), mientras en `00-core-auth-http.md`
(basado en `journal-entry`/`quotations`) se documentó como `__links` (doble guion bajo) para
otros recursos. Ver ambigüedad abajo.

## GET /v1/document-types?type=RC — Catálogo de tipos de recibo de caja

Devuelve `id`, `document.id`, `number`, `name`, `date`, `customer.*`, `items.taxes.*`,
`items.discounts.*`, `payments.*`, `cost_center`, `observations` — es la configuración del
tipo de comprobante, no un listado de recibos ya creados.

## Errores específicos observados en el spec

| Code | Contexto |
|---|---|
| `invalid_payment` | Forma de pago inválida para el tipo de comprobante, o se envía `payment` en un recibo avanzado (tipo eliminado) |
| `invalid_document` | El `document.id` no corresponde al tipo de comprobante que se está creando |
| `invalid_cost_center` | `cost_center` no existe, ver `/cost-centers` |
| `invalid_amount` | Valor fuera de rango o no coincide con la suma de cuotas/saldos |
| `already_exists` / `duplicated_document` | Documento duplicado (mismo `Idempotency-Key` o mismo consecutivo manual) |

## Ambigüedades / pendientes de confirmar

- El spec oficial anuncia la eliminación del tipo `Detailed`, pero la misma página de "Crear
  Recibo de caja - Otros ingresos" (`type: MiscIncome`) referencia internamente los modelos
  `VoucherInDetailed`/`VoucherOutDetailed` (con `items[].account.code` + `movement` en vez de
  `due`/`payment`). No quedó claro si el ejemplo JSON de esa sección (que usa `income.id` +
  `payment`) es el shape real vigente, o si es un remanente de documentación desactualizada que
  mezcla el modelo viejo (`Detailed`, con `account`/`movement`) con el nuevo (`MiscIncome`, con
  `income`/`payment`). Recomendado: probar contra sandbox antes de implementar `MiscIncome` en
  el SDK.
- El SDK JS oficial (`VoucherApi.js`) expone dos endpoints que **no aparecen** en el spec
  `.apib` revisado: `POST /v1/vouchers/{id}/stamp` (`sendElectronicVoucher`, envía el
  "voucher electrónico" con `mail_to`/`copy_to`) y `POST /v1/vouchers/{id}/mail`
  (`sendVoucherByEmail`, mismo shape). Podrían ser vestigios de una versión anterior del
  producto (facturación electrónica de recibos de caja no es un concepto DIAN estándar) o
  functionality real no documentada públicamente. No confirmado contra el ambiente real.
- Inconsistencia de nombre de clave de paginación: `_links` (vouchers, quotations, accounts-
  payable, payment-receipts, journals en el spec `.apib`) vs. `__links` (journals según
  `00-core-auth-http.md`, basado en fetch directo de la página web). Podría ser un error de
  transcripción en uno de los dos; verificar contra una respuesta real antes de fijar el nombre
  del campo en el SDK — usar acceso dinámico/case-insensitive como salvaguarda.
- No se encontró documentación de un endpoint de anulación o borrado de recibos de caja
  (a diferencia de facturas que sí tienen `annul` y payment-receipts que sí tienen `DELETE`).
  No confirmado si existe y simplemente no está documentado, o si un recibo de caja creado por
  error se debe corregir por otra vía (UI de Siigo Nube).
- El campo `income` (para `MiscIncome`) y el catálogo `/misc-income` no se investigaron en
  profundidad — están fuera del alcance de este research (catálogos), pero son prerrequisito
  funcional para ese tipo de recibo.
