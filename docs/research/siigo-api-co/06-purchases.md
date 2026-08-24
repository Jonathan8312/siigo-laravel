# Siigo API Colombia — Purchases (`/v1/purchases`)

Referencia interna de ingeniería (síntesis propia, no copia literal) construida a partir de
`developers.siigo.com/docs/siigoapi/purchase/*`. A diferencia de `customers`/`invoices`/
`credit-notes`, este recurso **no está cubierto por el SDK oficial JS de Siigo**
(`SiigoSAS/siigo_sdk_javascript` no tiene `PurchaseApi.md` ni modelos `Purchase*` en su
carpeta `docs/` — se verificó el listado completo del directorio y no aparece), así que la
confirmación cruzada disponible para otros recursos no aplica aquí. Todo lo documentado
abajo proviene directamente de fetches a `developers.siigo.com`, salvo donde se indique lo
contrario.

## Endpoints

| Método | URL | Descripción | Confirmado vía |
|---|---|---|---|
| `POST` | `/v1/purchases` | Crear factura de compra | developers.siigo.com (directo) |
| `GET` | `/v1/purchases` | Listar facturas de compra | Inferencia + fuentes de terceros (ver ambigüedades) |
| `GET` | `/v1/purchases/{id}` | Obtener una factura de compra por GUID | developers.siigo.com (directo) |
| `PUT` | `/v1/purchases/{id}` | Actualizar factura de compra | developers.siigo.com (directo) |
| `DELETE` | `/v1/purchases/{id}` | Eliminar factura de compra | No confirmado oficialmente (ver ambigüedades) |

Headers: `Authorization: Bearer <token>`, `Partner-Id: <valor>`, `Content-Type:
application/json` en `POST`/`PUT`. No hay indicación de que `Idempotency-Key` aplique a
`purchases` (la lista confirmada en `00-core-auth-http.md` es solo facturas de venta, notas
crédito, journals y vouchers).

## Diferencia estructural con Invoices

`purchases` usa `supplier` (proveedor) en vez de `customer`, y agrega `provider_invoice`
(referencia al número de factura física/externa del proveedor — `prefix` + `number`), ya que
la factura de compra registra un documento que originalmente emitió un tercero.

## POST /v1/purchases — Crear factura de compra

### Ejemplo de request completo

```json
{
  "document": { "id": 58246 },
  "date": "2024-05-22",
  "supplier": {
    "identification": "101020201",
    "branch_office": 0
  },
  "provider_invoice": {
    "prefix": "VEN",
    "number": "987"
  },
  "items": [
    {
      "type": "Product",
      "code": "SGNB002",
      "description": "Prod SiigoNube_Mod 002",
      "quantity": 8,
      "price": 1000
    }
  ],
  "payments": [
    { "id": 51279, "value": 8000, "name": "MercadoPago" }
  ]
}
```

### Campos observados

| Campo | Tipo | Notas |
|---|---|---|
| `document.id` | number | Tipo de documento de compra, debe existir |
| `date` | string (`yyyy-MM-dd`) | |
| `supplier.identification` | string | Debe existir como tercero tipo `Supplier` |
| `supplier.branch_office` | integer | |
| `provider_invoice.prefix` | string | Prefijo de la factura física del proveedor |
| `provider_invoice.number` | string | Número de la factura física del proveedor |
| `items[].type` | string | Ej. `"Product"` — no se confirmaron otros valores posibles (¿`"Service"`?) |
| `items[].code` | string | Código de producto/servicio |
| `items[].description` | string | |
| `items[].quantity` | number | |
| `items[].price` | number | |
| `payments[].id` | number | Método de pago |
| `payments[].value` | number | |
| `payments[].name` | string | Presente en el ejemplo del request (inusual — en `invoices` el `name` del pago solo aparece en la *respuesta*, no en el request; no confirmado si es obligatorio enviarlo o si Siigo lo ignora) |

### Respuesta (201)

```json
{
  "id": "dd3ee2cf-c313-4f13-ac9c-b5aa8db5f41c",
  "document": { "id": 58246 },
  "number": 73,
  "name": "FC-1-73",
  "date": "2024-05-22",
  "supplier": {
    "id": "17eca20f-9c14-4e75-8a72-8ac8bdca6d2c",
    "identification": "1065600600",
    "branch_office": 10
  },
  "total": 3000,
  "balance": 0,
  "provider_invoice": { "prefix": "VEN", "number": "987" },
  "items": [
    {
      "id": "ab491976-9bd8-49fc-b8a0-caacd700efc6",
      "type": "item",
      "code": "SGNB002",
      "quantity": 3,
      "price": 1000,
      "description": "Prod SiigoNube_Mod 002",
      "total": 3000
    }
  ],
  "payments": [ { "id": 51279, "name": "MercadoPago", "value": 3000 } ],
  "metadata": { "created": "2024-05-23T12:57:42.0000000+00:00" }
}
```

⚠️ Nota: en el ejemplo oficial, la respuesta trae `quantity: 3` y `total/balance: 3000`
mientras el request enviado tenía `quantity: 8` / `payments.value: 8000` — esta
inconsistencia parece un error de la documentación de Siigo (ejemplos de request/response no
sincronizados), no un comportamiento real de la API. No construir lógica de negocio
asumiendo que Siigo recalcula cantidades.

El prefijo del documento generado (`"FC"` en el ejemplo, "Factura de Compra") es asignado
por el tipo de documento configurado en `document.id`, igual que `"FV"` en invoices.

## GET /v1/purchases/{id} — Obtener una factura de compra

Path param: `id` (GUID, formato `00000000-0000-0000-0000-000000000000`). Confirmado
directamente en developers.siigo.com. Respuesta: mismo shape que la respuesta de `POST`.

## PUT /v1/purchases/{id} — Actualizar factura de compra

Path param: `id` (GUID). Body: mismo shape que `POST` (document, date, supplier,
provider_invoice, items, payments).

### Campos no modificables (confirmado explícitamente en la doc)

- `document.id`
- `supplier.identification`
- `currency.code`
- `number`

Respuesta: `200` con el objeto actualizado, mismo shape que `POST`.

## GET /v1/purchases — Listar facturas de compra

**No se pudo cargar la página oficial** (404 en los slugs probados: `3-get-purchases`,
`1-get-purchases`). Estructura inferida por analogía directa con `GET /v1/invoices` (mismo
patrón general de paginación de Siigo) y corroborada parcialmente por fuentes de terceros
(MCP servers no oficiales que documentan `page`/`page_size` como únicos parámetros
confirmados):

| Param (inferido) | Tipo | Confirmado |
|---|---|---|
| `page` | integer | Sí (terceros) |
| `page_size` | integer | Sí (terceros) |
| `document_id` | integer | No — inferido por analogía con `invoices` |
| `supplier_identification` | string | No — inferido por analogía con `customer_identification` de invoices |
| `supplier_branch_office` | integer | No — inferido |
| `name` | string | No — inferido |
| `created_start`/`created_end`, `date_start`/`date_end`, `updated_start`/`updated_end` | date-time | No — inferido por el patrón general de paginación de Siigo |

**Tratar todos los parámetros salvo `page`/`page_size` como no confirmados** hasta verificar
directamente contra `developers.siigo.com` o contra una cuenta de pruebas real.

Respuesta esperada (no confirmada, por analogía): shape de paginación genérico
(`pagination`, `results[]`, `__links`) con cada elemento igual al shape de respuesta de
`POST`/`GET /{id}`.

## DELETE /v1/purchases/{id}

Mencionado únicamente por fuentes de terceros (MCP servers no oficiales) como
`DELETE /v1/purchases/{id}`. **No confirmado contra developers.siigo.com** — no se encontró
la página oficial ni referencia en el SDK oficial JS (que no cubre `purchases` en absoluto).
Tratar como no confirmado.

## Ambigüedades / pendientes de confirmar

- **`GET /v1/purchases` (listado) no se pudo verificar oficialmente** — ni la URL exacta de
  la página de doc, ni el set completo de query params de filtro. Solo `page`/`page_size`
  tienen algo de respaldo (fuentes de terceros). Esto es un hueco real de la investigación:
  antes de construir el método `list()` del SDK para este recurso, se debería hacer una
  llamada real de prueba contra la cuenta sandbox para descubrir empíricamente los filtros
  soportados (probablemente sean análogos a `invoices`, pero no está garantizado).
- **`DELETE /v1/purchases/{id}` no confirmado oficialmente** — ni el código de respuesta, ni
  si existe una restricción de negocio equivalente a `delete_not_allowed` cuando hay
  documentos relacionados (pagos, notas de ajuste, etc.).
- No existe documentación encontrada sobre anulación de facturas de compra (`annul`), a
  diferencia de `invoices` que sí tiene `POST /{id}/annul`. No está claro si las compras se
  anulan (mismo patrón) o solo se eliminan (`DELETE`).
- El campo `items[].type` solo se vio con el valor `"Product"` en el request y `"item"`
  (minúscula, distinto) en la respuesta — inconsistencia de mayúsculas no explicada; no se
  confirmaron otros valores posibles del campo.
- El SDK oficial JS de Siigo (mantenido por Siigo) **no cubre `purchases` en absoluto** —
  ninguna clase `PurchaseApi`, ningún modelo `Purchase*`. Esto sugiere que la API de compras
  podría ser más nueva o menos prioritaria para Siigo que `invoices`/`credit-notes`/
  `customers`; vale la pena verificar directamente con soporte de Siigo (`soporteapi@siigo.com`,
  visto en la doc de documentos soporte) antes de comprometerse a un contrato de API para
  este recurso en el SDK de Laravel.
- No se confirmó si `purchases` soporta PDF (`GET /v1/purchases/{id}/pdf`) ni envío por
  correo, a diferencia de `invoices`.
- No se confirmó si aplica `Idempotency-Key` a `POST /v1/purchases`.
