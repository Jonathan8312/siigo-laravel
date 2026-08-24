# Siigo API Colombia — Reports (Reportes Financieros y Contables)

Referencia interna de ingeniería (síntesis propia, no copia literal) construida a partir de
`developers.siigo.com/docs/siigoapi/reports/3-get-accounts-payable` (confirmada vía WebFetch),
del spec API Blueprint oficial (`siigoapi.docs.apiary.io`, obtenido vía
`https://raw.githubusercontent.com/jdlar1/siigo-mcp/master/siigoapi.apib`) y del SDK oficial
`SiigoSAS/siigo_sdk_javascript` (`src/api/AccountsPayableApi.js`, `src/api/TestBalanceApi.js`).

**Hallazgo clave: el grupo "Reportes" de la API solo expone 3 reportes**, no un catálogo amplio
de reportes financieros. No existen endpoints de cuentas por cobrar, balance general ni estado
de resultados en la API pública — se buscó explícitamente y no aparecen en el spec `.apib`
completo (6000+ líneas) ni en la doc web.

## Endpoints

| Método | URL | Descripción | Formato de salida |
|---|---|---|---|
| POST | `/v1/test-balance-report` | Balance de prueba general | Excel (URL de descarga) |
| POST | `/v1/test-balance-report-by-thirdparty` | Balance de prueba por tercero | Excel (URL de descarga) |
| GET | `/v1/accounts-payable` | Reporte de cuentas por pagar | JSON paginado |

Solo el reporte de cuentas por pagar devuelve JSON estructurado; los dos balances de prueba
generan un archivo Excel de forma asíncrona-ish (la respuesta trae `file_id` + `file_url`, no
el archivo en sí — el SDK lo confirma con `TestBalanceResultModel { file_id, file_url }`).

## POST /v1/test-balance-report — Balance de prueba general

### Body

| Campo | Tipo | Obligatorio | Notas |
|---|---|---|---|
| `account_start` | string | No | Cuenta contable inicial; si se omite o va vacía, toma la primera cuenta creada |
| `account_end` | string | No | Cuenta contable final; si se omite o va vacía, toma la última cuenta creada |
| `year` | number | Sí | Entero de 4 dígitos |
| `month_start` | number | Sí | Entero entre 1 y 13, no mayor a `month_end` |
| `month_end` | number | Sí | Entero entre 1 y 13, no menor a `month_start` |
| `includes_tax_difference` | boolean | Sí | Incluir cuentas contables de diferencia fiscal |

Nota: `month` acepta hasta `13` — probablemente el "mes 13" es un ajuste contable de cierre de
año (patrón común en sistemas contables latinoamericanos), no confirmado explícitamente en la
doc.

### Ejemplo de request

```json
{
  "account_start": "11050501",
  "account_end": "41350501",
  "year": 2023,
  "month_start": 1,
  "month_end": 13,
  "includes_tax_difference": false
}
```

### Respuesta (201)

```json
{
  "file_id": "24880c55-65c9-4bf0-83a1-137e12671813",
  "file_url": "https://reportsexcelprod.blob.core.windows.net/pilotocalidadnube/Balance de prueba general-20230111125739.xlsx"
}
```

`file_url` apunta a Azure Blob Storage — probablemente con una URL firmada de vigencia
limitada (no confirmado el tiempo de expiración).

## POST /v1/test-balance-report-by-thirdparty — Balance de prueba por tercero

Mismo body que el reporte general, más:

| Campo | Tipo | Obligatorio | Notas |
|---|---|---|---|
| `customer.identification` | string | No | Sin dígito de verificación |
| `customer.branch_office` | number | No | Entre 0 y 999 |

Si se omite el objeto `customer` completo, el reporte se genera para todos los terceros.

### Ejemplo de request

```json
{
  "account_start": "11050501",
  "account_end": "41350501",
  "year": 2023,
  "month_start": 1,
  "month_end": 13,
  "includes_tax_difference": false,
  "customer": {
    "identification": "13832081",
    "branch_office": 0
  }
}
```

Respuesta: idéntica a `test-balance-report` (`file_id` + `file_url`).

## GET /v1/accounts-payable — Reporte de cuentas por pagar

### Query params

| Param | Tipo | Descripción |
|---|---|---|
| `due_date_start` | date | Filtra por `due_date` ≥ valor |
| `due_date_end` | date | Filtra por `due_date` ≤ valor |
| `provider_identification` | string | Filtra por proveedor |
| `provider_branch_office` | number | Filtra por sucursal del proveedor (0–999); requiere enviar `provider_identification` primero |
| `page` | int32 | Página |
| `page_size` | int32 | Resultados por página |

Formato de fecha: `yyyy-MM-dd`; fecha+hora UTC: `yyyy-MM-ddTHH:mm:ssZ`. Confirmado por WebFetch
directo de la página oficial + SDK (coinciden, salvo que la doc web usa `due_date_start`/
`date_end` de forma inconsistente en su propia tabla — ver ambigüedad).

### Respuesta (200 según SDK / 201 según spec `.apib` — ver ambigüedad)

```json
{
  "pagination": { "page": 1, "page_size": 25, "total_results": 11 },
  "results": [
    {
      "due": {
        "prefix": "FC",
        "consecutive": 4,
        "quote": 1,
        "date": "2023-09-05T12:00:00.0000000+00:00",
        "balance": 200000
      },
      "provider": {
        "id": "3e55cf7f-f7a1-4397-bb02-dbf0acb94edd",
        "identification": "1061695940",
        "branch_office": 0,
        "name": "David Santiago Corchuelo Castro"
      },
      "cost_center": { "code": "24088", "name": "Alienz" },
      "currency": { "money_code": "USD", "balance": "100" }
    }
  ],
  "_links": {
    "previous": { "href": "https://api.siigo.com/v1/accounts-payable?page=4&page_size=25" },
    "self": { "href": "https://api.siigo.com/v1/accounts-payable?page=5&page_size=25" },
    "next": { "href": "https://api.siigo.com/v1/accounts-payable?page=6&page_size=25" }
  }
}
```

Campos:

| Campo | Tipo | Descripción |
|---|---|---|
| `due.prefix` / `due.consecutive` / `due.quote` | — | Identifican la factura de compra origen |
| `due.date` | string | Fecha del vencimiento |
| `due.balance` | number | Saldo pendiente en moneda local |
| `provider.id` | string | GUID interno del proveedor |
| `provider.identification` | string | NIT/cédula del proveedor |
| `provider.branch_office` | number | Sucursal |
| `provider.name` | string | Nombre/razón social |
| `cost_center.code` / `cost_center.name` | — | Centro de costo asociado |
| `currency.money_code` | string | Código de moneda extranjera del vencimiento, si aplica |
| `currency.balance` | string | Saldo en moneda extranjera |

## Errores específicos observados en el spec

| Code | Contexto |
|---|---|
| `invalid_account` | En balance de prueba: `account_start` es una cuenta mayor que `account_end` |
| `invalid_range` | `month_start`/`month_end` fuera del rango 1–13 |
| `invalid_reference` | `account_start`/`account_end` referencian una cuenta contable inexistente |
| `invalid_date_range` | Rango de fechas inválido (aplica a `due_date_start`/`due_date_end`) |

## Ambigüedades / pendientes de confirmar

- **Código de éxito inconsistente entre fuentes para `GET /v1/accounts-payable`**: el spec
  `.apib` documenta `Response 201`, pero un `GET` que no crea nada retornando `201 Created` es
  semánticamente extraño — lo esperable es `200`. El SDK oficial no especifica el código
  explícitamente (delega en `ApiClient`). Tratar como `200` por convención HTTP hasta confirmar
  contra sandbox; no descartar que la API real efectivamente devuelva `201` por inconsistencia
  del backend (ya se documentó un caso similar en `/auth` — ver `00-core-auth-http.md`).
- El campo de query `due_date_end` aparece en el SDK y en la tabla de filtros del spec, pero el
  cuerpo de la sección de docs web (`reports/3-get-accounts-payable`, según fetch previo)
  nombra el parámetro simplemente `date_end` en la tabla de parámetros. Usar `due_date_end`
  (confirmado por dos fuentes: SDK + spec `.apib`) como el nombre correcto; `date_end` parece
  ser un error de transcripción de esa página puntual.
- **No se encontró ningún reporte de cuentas por cobrar** (`accounts-receivable` o similar),
  pese a que sería el análogo natural de `accounts-payable`. Búsqueda exhaustiva en el spec
  `.apib` completo no arroja ningún endpoint `/v1/accounts-receivable`. Es posible que:
  (a) no exista y haya que derivarlo de facturas de venta con saldo pendiente vía
  `GET /v1/invoices` con algún filtro de saldo, o (b) exista pero no esté en el spec `.apib`
  consultado (que podría estar desactualizado, como ya se vio con payment-receipts vs. el SDK
  JS). Recomendado confirmar directamente con soporte de Siigo o contra sandbox con distintas
  URLs candidatas (`/v1/accounts-receivable`, `/v1/receivables`).
- Tampoco se encontró **estado de resultados** ni **balance general** (balance sheet) como
  reportes de la API — solo "balance de prueba" (trial balance), que es un reporte contable
  distinto (lista saldos por cuenta, no un P&L ni un balance clasificado). Si el SDK necesita
  estos reportes, probablemente no existan vía API pública y haya que construirlos client-side
  a partir de journals/vouchers, o generarlos manualmente desde Siigo Nube.
- No se confirmó el tiempo de vigencia de la URL firmada (`file_url`) de los reportes Excel, ni
  si hay un endpoint separado para re-consultar un reporte ya generado por `file_id` (no se
  encontró `GET /v1/test-balance-report/{file_id}` ni similar en el spec).
- El campo `currency.balance` en la respuesta de `accounts-payable` viene tipado como `string`
  en el spec (`"100"`) pese a representar un valor numérico — posible inconsistencia de tipos
  del backend real; el SDK no lo contradice (no tiene esta API implementada). Manejar como
  string-o-number defensivamente en el SDK.
