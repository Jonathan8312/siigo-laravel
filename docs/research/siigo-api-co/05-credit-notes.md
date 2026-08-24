# Siigo API Colombia — Credit Notes (`/v1/credit-notes`)

Referencia interna de ingeniería (síntesis propia, no copia literal) construida a partir de
`developers.siigo.com/docs/siigoapi/credit-note/*` y el SDK oficial JS de Siigo
(`github.com/SiigoSAS/siigo_sdk_javascript`, archivo `CreditNoteApi.md` +
`CreateCreditNoteCommand.md` + `CreditNoteViewModel.md`) como referencia cruzada exacta de
nombres de campos.

## Endpoints

| Método | URL | Descripción |
|---|---|---|
| `POST` | `/v1/credit-notes` | Crear nota crédito |
| `GET` | `/v1/credit-notes` | Listar notas crédito (paginado + filtros) |
| `GET` | `/v1/credit-notes/{id}` | Obtener una nota crédito por GUID |
| `GET` | `/v1/credit-notes/{id}/pdf` | Obtener PDF de la nota crédito |

**No existe `PUT` ni `DELETE`/anulación dedicada para notas crédito** — confirmado
indirectamente: el SDK oficial JS solo expone estos 4 métodos en `CreditNoteApi`, no hay
`updateCreditNote` ni `deleteCreditNote`/`annulCreditNote`. Ver ambigüedades.

Headers en todos: `Authorization: Bearer <token>`, `Partner-Id: <valor>`. `Content-Type:
application/json` en `POST`. `Idempotency-Key` soportado en `POST /v1/credit-notes` (ver
`00-core-auth-http.md`).

## Referencia a la factura original

La nota crédito se vincula a la factura que ajusta/anula mediante el campo `invoice`
(string, GUID de la factura original — **no** el número de documento, sino el `id` interno
devuelto por `POST/GET /v1/invoices`). En la respuesta, este campo se expande a un objeto
`InvoiceModel` con `id` y `name` (ej. `"FV-2-20"`).

Para notas crédito que referencian facturas **no registradas en Siigo** (facturas externas),
la doc menciona un campo alternativo `invoice_data` (objeto) en vez de `invoice` (string),
que requeriría datos del cliente/vendedor inline — **no se pudo confirmar el shape exacto de
`invoice_data`**, ver ambigüedades.

## POST /v1/credit-notes — Crear nota crédito

### Campos del body (`CreateCreditNoteCommand`)

| Campo | Tipo | Notas |
|---|---|---|
| `document.id` | number | Tipo de documento de nota crédito, debe existir en la cuenta |
| `number` | number | Consecutivo manual (opcional) |
| `name` | string | Opcional |
| `date` | string (`yyyy-MM-dd`) | No puede ser anterior a la fecha actual para notas electrónicas |
| `invoice` | string (GUID) | Factura original que se está ajustando/anulando |
| `reason` | integer (1-6) | Motivo DIAN — enum `DianReason`, valores no documentados por nombre (solo enteros 1-6 confirmados; ver ambigüedades) |
| `seller` | number | ID del vendedor |
| `cost_center` | number | Opcional |
| `currency.code` / `currency.exchange_rate` | string / number | Opcional |
| `retentions[]` | array | IDs de retenciones |
| `advance_payment` | number | Opcional |
| `observations` | string | Opcional |
| `items[].code`, `.quantity`, `.price` | string / number / number | Obligatorios por ítem |
| `items[].discount`, `.taxes[]` | number / array | Opcionales |
| `payments[].id`, `.value` | number / number | Obligatorios por pago |
| `payments[].due_date` | string | Opcional |

### Ejemplo de request completo

```json
{
  "document": { "id": 22 },
  "date": "2021-10-15",
  "invoice": "63f918c2-ca65-4edc-a7db-66bcdd5159fb",
  "reason": 1,
  "seller": 629,
  "items": [
    {
      "code": "Item-1",
      "description": "Product description",
      "quantity": 2,
      "price": 50,
      "discount": 13,
      "taxes": [ { "id": 13156 } ]
    }
  ],
  "payments": [
    { "id": 5636, "value": 1273.03, "due_date": "2021-03-19" }
  ]
}
```

### Respuesta (201)

```json
{
  "id": "63f918c2-ca65-4edc-a7db-66bcdd5159fb",
  "document": { "id": 22 },
  "name": "NC-2-22",
  "date": "2021-10-15",
  "invoice": {
    "id": "302580df-838b-4531-b8bf-dd3c98b34059",
    "name": "FV-2-20"
  },
  "total": 25.5,
  "stamp": { "status": "string", "cufe": "string" }
}
```

Nota: el objeto de respuesta completo (`CreditNoteViewModel`, confirmado vía SDK) incluye
además, aunque no aparecieron en el ejemplo mostrado por la doc: `number`, `customer`
(objeto `InvoiceCustomerModel`, heredado de la factura original), `cost_center`, `currency`,
`seller`, `retentions[]`, `advance_payment`, `observations`, `items[]`, `payments[]`,
`metadata`. El shape de `items[]`/`payments[]` en la respuesta sigue el mismo patrón que
`invoices` (ver `04-invoices.md`).

## GET /v1/credit-notes — Listar notas crédito

### Query params

| Param | Tipo | Descripción |
|---|---|---|
| `name` | string | Nombre del documento (ej. `"NC-2-22"`) |
| `created_start` / `created_end` | date | Rango de fecha de creación |
| `date_start` / `date_end` | date | Rango de fecha de la nota crédito |
| `updated_start` / `updated_end` | date | Rango de última actualización |
| `page` | integer | Página |
| `page_size` | integer | Tamaño de página |

Respuesta: shape de paginación genérico (`pagination`, `results[]`, `__links`), cada
elemento con el shape completo de `CreditNoteViewModel` descrito arriba.

## GET /v1/credit-notes/{id} — Obtener una nota crédito

Path param: `id` (GUID). Respuesta: objeto `CreditNoteViewModel` completo (no envuelto en
paginación).

## GET /v1/credit-notes/{id}/pdf — PDF de la nota crédito

Path param: `id` (GUID). Respuesta modelada como `CreditNotePdfViewModel` en el SDK oficial
— shape exacto (base64 vs. binario) no confirmado contra developers.siigo.com directamente
(mismo caso que el PDF de facturas, ver `04-invoices.md`).

## Ambigüedades / pendientes de confirmar

- **No se confirmó si existe anulación/eliminación de notas crédito.** El SDK oficial JS no
  expone ningún método de update/delete/annul para `CreditNoteApi`, lo que sugiere que las
  notas crédito son inmutables una vez creadas (comportamiento típico de documentos
  electrónicos aceptados por la DIAN), pero no se encontró una declaración explícita de esto
  en la documentación textual — podría simplemente no estar cubierto por este SDK en
  particular.
- **Valores del enum `reason` (1-6, `DianReason`) sin nombre confirmado.** La doc solo
  confirma que es un entero entre 1 y 6 correspondiente a "DIAN rejection reasons" pero no
  se pudo obtener el mapeo semántico (ej. cuál código es "devolución total", cuál es
  "descuento", etc.). Esto es crítico para el SDK — sin este mapeo no se puede construir un
  enum/const útil para el usuario final; debe confirmarse antes de implementar.
- **Shape exacto de `invoice_data`** (para notas crédito sobre facturas no registradas en
  Siigo) no confirmado — no se encontró un ejemplo de payload completo. Se infiere, por
  analogía con otros documentos Siigo, que requeriría al menos identificación del cliente y
  posiblemente `customer` inline, pero esto es especulación no verificada.
- No se confirmó si `POST /v1/credit-notes` soporta crear una nota crédito "abierta" (sin
  `invoice` de referencia) para ajustes generales de cartera, o si `invoice`/`invoice_data`
  es siempre obligatorio.
- No se pudo determinar el código HTTP/estructura de error específico cuando se intenta
  crear una nota crédito sobre una factura ya anulada o con otra nota crédito total
  existente.

## Actualización tras implementación (2026-08-23)

Investigación adicional directa contra `developers.siigo.com/docs/siigoapi/credit-note/*`
(sitio fumadocs, navegado con browser) resolvió la mayoría de las ambigüedades de arriba:

- **`reason` (Códigos de Motivo de Rechazo DIAN)**, confirmado con nombre por código en la
  página "Crear Nota Crédito": `1` Devolución parcial de los bienes y/o no aceptación parcial
  del servicio, `2` Anulación de factura electrónica, `3` Rebaja o descuento parcial o total,
  `4` Ajuste de precio, `6` Descuento comercial por pronto pago, `7` Descuento comercial por
  volumen de ventas. **El código `5` no aparece en absoluto** en esta tabla, y el widget de
  schema del propio endpoint declara el rango permitido como `1-6` (sin el `7`) —
  inconsistencia de la propia documentación de Siigo, no nuestra. Ver
  `docs/known-issues.md`.
- **Shape de `invoice_data` confirmado**: `{date (obligatorio), prefix (opcional), number
  (obligatorio solo si reason=2), cufe (obligatorio solo si reason=2, máx. 200 caracteres)}`.
  Debe enviarse junto con `customer.identification`, `customer.branch_office` (opcional,
  default `0`) y `seller` a nivel raíz del payload — confirmado con un ejemplo de request
  completo en la documentación.
- **No existe anulación/eliminación**: confirmado también por la navegación de
  `developers.siigo.com`, que solo lista Crear, Consultar Tipos (= `GET /v1/document-types?
  type=NC`, mismo endpoint que catálogos), Consultar, Listar, y PDF — cinco operaciones, sin
  update/delete/annul.
- **Campos nuevos no cubiertos en la investigación original**: soporte para ítems de
  obsequio/regalo (`items[].tax_base`, `items[].taxpayer`, requeridos cuando `price: 0`), y
  el objeto `healthcare_company` (sector salud, Resolución 948), con el mismo shape que en
  facturas de venta.
- **`GET /v1/credit-notes/{id}/pdf` responde `{id, cude, base64}`** — usa `cude`, no `cufe`
  (a diferencia del PDF de facturas). El objeto `stamp` de la respuesta completa de creación/
  consulta, sin embargo, sí declara ambos campos (`cufe` y `cude`) simultáneamente.

Ver `docs/credit-notes.md` para la implementación final y `docs/known-issues.md` para el
detalle de cada inconsistencia encontrada en la documentación de Siigo.
