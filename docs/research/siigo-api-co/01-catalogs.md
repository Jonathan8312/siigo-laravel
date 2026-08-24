# Siigo API Colombia — Catálogos (reference / master data)

Referencia interna de ingeniería (síntesis propia, no copia literal) construida a partir de
`developers.siigo.com/docs/siigoapi/catalog/*`. Fuente primaria: documentación oficial,
confirmada navegando el sitio (contenido server-rendered por página, más el árbol de
navegación lateral obtenido inspeccionando el DOM ya hidratado — el sidebar de la sección
"catalog" no viene en el HTML estático, se carga vía JS).

Todos los catálogos listados abajo están **confirmados oficialmente**: existen como páginas
reales bajo `/docs/siigoapi/catalog/N-slug` con contenido propio (no son alias ni 404 blandos).
El sitio expone exactamente 11 catálogos bajo esa sección — no hay más allá de los listados
aquí (confirmado inspeccionando todos los `<a href="/docs/siigoapi/catalog/...">` del DOM
hidratado).

## Convenciones comunes a todos los catálogos

- Todos son `GET`, sin body.
- Headers obligatorios en todos: `Authorization: <token>`, `Partner-Id: <token>` (mismo
  patrón que el resto de la API — ver `00-core-auth-http.md`).
- Ninguno de estos endpoints documenta `Idempotency-Key` (no aplica a `GET`).
- La mayoría devuelve un **array plano** (`[ ... ]`), no el sobre `{ pagination, results,
  __links }` de recursos transaccionales — única excepción confirmada: `users` (ver abajo).

---

## 1. Grupos de Inventario (account groups)

- `GET https://api.siigo.com/v1/account-groups`
- Query params: ninguno.
- Uso: clasificación general de productos/servicios; el `id` se usa al crear productos
  (`account_group`).

Campos de respuesta:

| Campo | Tipo | Descripción |
|---|---|---|
| `id` | number | Id de la clasificación de inventario (usado en `products.account_group`) |
| `name` | string | Nombre de la clasificación |
| `active` | boolean | Si está en uso |

```json
[
  { "id": 1253, "name": "Productos", "active": true }
]
```

## 2. Impuestos (taxes)

- `GET https://api.siigo.com/v1/taxes`
- Query params: ninguno.
- Uso: se referencian por `id` en `products.taxes[].id` y en items de facturas/notas.

| Campo | Tipo | Descripción |
|---|---|---|
| `id` | number | Id del impuesto |
| `name` | string | Nombre del impuesto |
| `type` | string | Tipo de impuesto (ej. `IVA`) |
| `percentage` | number | Porcentaje |
| `active` | boolean | Si está activo |

```json
[
  { "id": 13156, "name": "IVA 19%", "type": "IVA", "percentage": 19, "active": true }
]
```

## 3. Listas de Precio (price lists)

- `GET https://api.siigo.com/v1/price-lists`
- Query params: ninguno.
- Uso: `id`/`position` se referencian en `products.prices[].price_list[].position` al crear
  productos (hasta 12 listas por producto — ver `02-products.md`).

| Campo | Tipo | Descripción |
|---|---|---|
| `id` | number | Identificador de la lista de precio |
| `name` | string | Nombre |
| `active` | boolean | Si está en uso |
| `position` | number | Posición (1–12) — este es el valor que se envía en `products.prices[].price_list[].position`, no el `id` |

```json
[
  { "id": 2766, "name": "Sale Price 1", "active": true, "position": 1 }
]
```

## 4. Bodegas (warehouses)

- `GET https://api.siigo.com/v1/warehouses`
- Query params: ninguno.

| Campo | Tipo | Descripción |
|---|---|---|
| `id` | number | Identificador único de la bodega |
| `name` | string | Nombre |
| `active` | boolean | Si está en uso |
| `has_movements` | boolean | Si tiene movimientos (relevante para saber si es editable/eliminable) |

```json
[
  { "id": 1270, "name": "Main Warehouse", "active": true, "has_movements": false }
]
```

## 5. Usuarios (users)

- `GET https://api.siigo.com/v1/users`
- Uso: representa vendedores, referenciados en facturas de venta.
- **Único catálogo confirmado con paginación** (`page`, `page_size` + sobre `pagination`/`results`).

Query params:

| Param | Tipo | Descripción |
|---|---|---|
| `page` | integer (`int32`) | Página actual, ej. `1` |
| `page_size` | integer (`int32`) | Resultados por página, ej. `20` |

| Campo (`results[]`) | Tipo | Descripción |
|---|---|---|
| `id` | number | Id único del usuario/vendedor |
| `username` | string | Usuario o correo |
| `first_name` | string | Nombre |
| `last_name` | string | Apellido |
| `email` | string | Correo |
| `active` | boolean | Estado |
| `identification` | string | Número de identificación |

```json
{
  "pagination": { "page": 1, "page_size": 25, "total_results": 250 },
  "results": [
    {
      "id": 35071,
      "username": "DavidYepes27",
      "first_name": "James David",
      "last_name": "Freeman Smith",
      "email": "[email protected]",
      "active": true,
      "identification": "13832082"
    }
  ]
}
```

## 6. Tipos de Comprobante (document types)

- `GET https://api.siigo.com/v1/document-types`
- Query params: `type` (string). La tabla de parámetros de la doc **no** lo marca como
  `Required`, pero **está confirmado empíricamente que sí es obligatorio**: omitirlo devuelve
  `400 parameter_required` en sandbox real (ver `docs/known-issues.md`). Valores documentados
  de ejemplo: `FV` (facturas), `FC` (facturas de compra), `NC` (notas crédito), `RC` (recibos
  de caja).

Campos de respuesta (lista completa confirmada — mucho más extensa que solo id/code/name):

| Campo | Tipo | Descripción |
|---|---|---|
| `id` | number | Id del tipo de comprobante |
| `code` | string | Código |
| `name` | string | Nombre |
| `description` | string | Descripción |
| `type` | string | `FV`, `FC`, `NC`, `RC`, etc. |
| `active` | boolean | Estado |
| `seller_by_item` | boolean | Vendedor por ítem |
| `cost_center` | boolean | Si maneja centro de costo |
| `cost_center_mandatory` | boolean | Si el centro de costo es obligatorio |
| `cost_center_default` | number | Centro de costo por defecto (id) |
| `automatic_number` | boolean | Numeración automática |
| `consecutive` | number | Consecutivo actual |
| `discount_type` | string | `Value` u otro |
| `decimals` | boolean | Maneja decimales |
| `advance_payment` | boolean | Anticipo |
| `reteiva` | boolean | Retención de IVA |
| `reteica` | boolean | Retención de ICA |
| `self_withholding` | boolean | Autorretención |
| `self_withholding_limit` | number | Límite de autorretención |
| `electronic_type` | string | `NoElectronic` u otro |
| `official_book` | string | Libro oficial |
| `document_support` | boolean | Documento soporte |
| `prefix` | string | Prefijo, ej. `FV-1` |
| `global_discounts` | array\<object\> | `{ id, name, percentage, active }` |
| `global_charges` | array\<object\> | `{ id, name, percentage, active }` |

```json
[
  {
    "id": 5636, "code": "1", "name": "Factura", "description": "This is a description",
    "type": "FV", "active": true, "seller_by_item": false, "cost_center": false,
    "cost_center_mandatory": false, "cost_center_default": 1235,
    "automatic_number": true, "consecutive": 3, "discount_type": "Value",
    "decimals": true, "advance_payment": false, "reteiva": false, "reteica": false,
    "self_withholding": false, "self_withholding_limit": 0,
    "electronic_type": "NoElectronic", "official_book": "0", "document_support": false,
    "prefix": "FV-1",
    "global_discounts": [{ "id": 0, "name": "string", "percentage": 0, "active": true }],
    "global_charges": [{ "id": 0, "name": "string", "percentage": 0, "active": true }]
  }
]
```

## 7. Formas de Pago (payment types)

- `GET https://api.siigo.com/v1/payment-types` — ⚠️ nota: el path real es `payment-types`
  (plural "types"), aunque el título de la página es "Formas de Pago" y en las URLs de
  documentación aparece como `payment-methods`. **El path del endpoint real (confirmado en el
  ejemplo `curl` de la doc) es `/v1/payment-types`.**
- Query params: `document_type` (string, **requerido**) — tipo de documento (`FV` facturas,
  `NC` notas crédito, etc.); las formas de pago disponibles dependen del tipo de comprobante.
- Uso: se referencian en Factura de venta, recibos de caja y notas crédito.

| Campo | Tipo | Descripción |
|---|---|---|
| `id` | number | Id de la forma de pago |
| `name` | string | Nombre, ej. "Crédito" |
| `type` | string | `Cartera`, `Proveedor`, o `Cartera/Proveedor` |
| `active` | boolean | Estado |
| `due_date` | boolean | Si maneja fecha de vencimiento |

```json
[
  { "id": 5636, "name": "Crédito", "type": "Cartera", "active": true, "due_date": true }
]
```

## 8. Centros de Costo (cost centers)

- `GET https://api.siigo.com/v1/cost-centers`
- Query params: ninguno.
- Uso: se referencian en Factura de venta, Recibo de caja, comprobante contable y notas
  crédito (campo `cost_center`, id numérico entero — confirmado también en `00-core` vía
  ejemplos de otros recursos).

| Campo | Tipo | Descripción |
|---|---|---|
| `id` | number | Id único del centro de costo |
| `code` | string | Código |
| `name` | string | Nombre |
| `active` | boolean | Estado |

```json
[
  { "id": 13222, "code": "1112", "name": "center", "active": true }
]
```

## 9. Activos Fijos / Grupos de Activo Fijo (fixed assets)

- `GET https://api.siigo.com/v1/fixed-assets`
- Query params: ninguno.
- Uso: comprobantes contables (subir saldos o modificar activos fijos).
- Nota: este endpoint devuelve los **activos fijos individuales**, cada uno con el nombre de
  su grupo como string (`group`) — no es un catálogo separado de "grupos de activos fijos"
  con su propio id; el grupo viene embebido como texto plano en cada activo.

| Campo | Tipo | Descripción |
|---|---|---|
| `id` | number | Id único del activo fijo |
| `name` | string | Nombre del activo fijo |
| `group` | string | Nombre del grupo de activo fijo (texto, sin id propio) |
| `active` | boolean | Si está en uso |

```json
[
  { "id": 13156, "name": "Personal Computer", "group": "fixed assets of the office", "active": true }
]
```

## 10. Descuentos en recibos de caja (expenses)

*No estaba en la lista de candidatos original — descubierto navegando el sidebar real de la
sección catalog (11 entradas totales).*

- `GET https://api.siigo.com/v1/expenses`
- Query params: ninguno.
- Uso: se relacionan en recibos de caja de tipo "Abono a una deuda" (`DebtPayment`).

| Campo | Tipo | Descripción |
|---|---|---|
| `id` | number | Identificador del descuento |
| `name` | string | Nombre del descuento (suele incluir código contable, ej. "51451001 - Mantenimientos...") |

```json
[
  { "id": 1576, "name": "51451001 - Mantenimientos - Construcciones y edificaciones" },
  { "id": 1574, "name": "51400501 - Notariales" }
]
```

## 11. Otros ingresos (misc incomes)

*Tampoco estaba en la lista de candidatos original — mismo hallazgo que el anterior.*

- `GET https://api.siigo.com/v1/misc-incomes`
- Query params: ninguno.
- La página oficial no documenta explícitamente en qué comprobante se usa este catálogo
  (solo muestra la tabla de campos del response) — ver ambigüedades.

| Campo | Tipo | Descripción |
|---|---|---|
| `id` | number | Identificador de "otro ingreso" |
| `name` | string | Nombre del ingreso |

```json
[
  { "id": 17830, "name": "42950502 - Ajuste al peso" }
]
```

---

## Ambigüedades / pendientes de confirmar

- **`payment-types` vs `payment-methods`**: el slug de la URL de documentación es
  `catalog/7-get-payment-methods`, el título visible es "Formas de Pago", pero el endpoint
  real documentado en el ejemplo `curl` es `GET /v1/payment-types` con query param
  `document_type`. No hay inconsistencia real en el endpoint (está claro), pero sí en el
  naming de la doc — anotar para que el SDK use `payment-types` como nombre del recurso/cliente
  y no `payment-methods`.
- **`fixed-assets` vs "grupos de activos fijos"**: el enunciado original de la tarea pedía
  verificar "grupos de activos fijos" como catálogo separado. Lo único confirmado
  oficialmente es `GET /v1/fixed-assets`, que devuelve activos fijos individuales con el
  nombre de su grupo como string embebido (`group`). **No existe** un endpoint
  `/v1/fixed-asset-groups` confirmado oficialmente — si se necesita, habría que derivarlo
  agrupando por el campo `group` de este mismo endpoint.
- **`expenses` y `misc-incomes`**: confirmados oficialmente (existen como páginas reales con
  contenido propio), pero la documentación oficial es muy escueta (no explica reglas de
  negocio más allá de una frase, y `misc-incomes` ni siquiera dice dónde se usa). No se
  encontró ninguna otra fuente oficial con más detalle.
- **`document-types` — parámetro `type`**: confirmado que es obligatorio en la práctica (ver
  `docs/known-issues.md`), aunque la doc no lo marca como `Required`. Sigue sin confirmarse la
  lista cerrada de valores válidos — la prosa da como ejemplo "FV, FC, NC, RC" pero no publica
  el catálogo completo (parece ser la misma lista de `type` usada en otros recursos
  transaccionales, no confirmado exhaustivamente).
- **Catálogos NO confirmados oficialmente** (solo vistos en SDKs/MCPs de terceros, mencionados
  en resultados de búsqueda pero sin página oficial verificada en `developers.siigo.com`):
  - "get cities" / ciudades — visto en `glama.ai/mcp/servers/jdlar1/siigo-mcp` (MCP de
    terceros, no oficial). No se encontró página equivalente en la doc oficial navegando el
    sidebar real de `catalog/*` (que tiene exactamente 11 entradas, ninguna de ciudades).
  - Cualquier catálogo de "cuentas contables" (chart of accounts) genérico más allá de
    `account-groups` — no se encontró.
- **Estructura de sidebar no server-renderizada**: el árbol de navegación de la sección API
  reference (catalog, productos, etc.) se genera client-side vía React/Next (fumadocs-openapi)
  y no aparece en el HTML estático — para futuras fases de investigación de otros recursos,
  hay que renderizar la página (browser real) e inspeccionar
  `document.querySelectorAll('a[href^="/docs/siigoapi/<seccion>"]')` en vez de confiar en
  WebFetch/WebSearch solos, que no ven ese árbol.
