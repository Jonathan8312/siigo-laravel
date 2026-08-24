# Siigo API Colombia — Customers (`/v1/customers`)

Referencia interna de ingeniería (síntesis propia, no copia literal) construida a partir de
`developers.siigo.com/docs/siigoapi/customer/*` y el SDK oficial JS de Siigo
(`github.com/SiigoSAS/siigo_sdk_javascript`, usado como referencia cruzada de nombres de
campos y métodos — no es la fuente primaria, pero está mantenido por Siigo).

## Endpoints

| Método | URL | Descripción |
|---|---|---|
| `POST` | `/v1/customers` | Crear cliente/tercero |
| `GET` | `/v1/customers` | Listar clientes (paginado + filtros) |
| `GET` | `/v1/customers/{id}` | Obtener un cliente por GUID |
| `PUT` | `/v1/customers/{id}` | Actualizar cliente (reemplazo completo) |
| `DELETE` | `/v1/customers/{id}` | Eliminar cliente |

Headers en todos: `Authorization: Bearer <token>`, `Partner-Id: <valor>`. `Content-Type:
application/json` en `POST`/`PUT`.

## POST /v1/customers — Crear cliente

### Campos del body

| Campo | Tipo | Oblig. | Notas |
|---|---|---|---|
| `type` | string | No (default `"Customer"`) | `"Customer"`, `"Supplier"`, `"Other"` |
| `person_type` | string | Sí | `"Person"` o `"Company"` |
| `id_type` | string | Sí | Código de tipo de documento (ej. `"13"` cédula, `"31"` NIT) |
| `identification` | string | Sí | Sin caracteres especiales, máx. 50 |
| `check_digit` | string | No | Solo dígitos 0-9 (DV del NIT) |
| `name` | array\<string> | Sí | Persona: 2 elementos (nombres, apellidos), máx. 100 c/u; Compañía: 1 elemento |
| `commercial_name` | string | No | Alfanumérico, espacios/caracteres especiales permitidos |
| `branch_office` | integer | No (default `0`) | Rango 0-999 |
| `active` | boolean | No (default `true`) | |
| `vat_responsible` | boolean | No (default `false`) | Responsable de IVA |
| `fiscal_responsibilities[].code` | string | Sí | Ej. `"R-99-PN"` (no responsable) |
| `address.address` | string | Sí | Máx. 256 caracteres |
| `address.city.country_code` | string | Sí | Ej. `"Co"` |
| `address.city.state_code` | string | Sí | Código de departamento |
| `address.city.city_code` | string | Sí | Código de ciudad |
| `address.postal_code` | string | No | Alfanumérico, sin espacios, máx. 10 |
| `phones[].indicative`/`number`/`extension` | string | No | Numéricos, máx. 10 c/u |
| `contacts[].first_name` | string | Sí (si hay `contacts`) | Máx. 50 |
| `contacts[].last_name` | string | No | Máx. 50 |
| `contacts[].email` | string | No | Máx. 100, sin espacios |
| `contacts[].phone` | object | No | Mismo shape que `phones[]` |
| `comments` | string | No | Máx. 4000 |
| `related_users.seller_id` | number | No | Debe existir (`GET /v1/users`) |
| `related_users.collector_id` | number | No | Debe existir (`GET /v1/users`) |
| `custom_fields[]` | array\<object> | No | `{"key": "...", "value": "..."}`. Ej. sector salud: `CUCON` |

### Ejemplo de request completo

```json
{
  "type": "Customer",
  "person_type": "Person",
  "id_type": "13",
  "identification": "13832081",
  "check_digit": "4",
  "name": ["string"],
  "commercial_name": "string",
  "branch_office": 0,
  "active": true,
  "vat_responsible": false,
  "fiscal_responsibilities": [
    { "code": "R-99-PN", "name": "Not responsible" }
  ],
  "address": {
    "address": "Cra. 18 #79A - 42",
    "city": { "country_code": "Co", "state_code": "19", "city_code": "19001" },
    "postal_code": "110911"
  },
  "phones": [
    { "indicative": "57", "number": "3006003345", "extension": "132" }
  ],
  "contacts": [
    {
      "first_name": "Marcos",
      "last_name": "Castillo",
      "email": "marcos@example.com",
      "phone": { "indicative": "57", "number": "3006003345", "extension": "132" }
    }
  ],
  "comments": "This is an additional comment",
  "related_users": { "seller_id": 625, "collector_id": 625 },
  "custom_fields": [ { "key": "YearsOld", "value": "29" } ]
}
```

### Respuesta (201)

```json
{
  "id": "63f918c2-ca65-4edc-a7db-66bcdd5159fb",
  "type": "Customer",
  "person_type": "Person",
  "id_type": { "code": "13", "name": "Cédula de ciudadanía" },
  "identification": "13832081",
  "branch_office": 0,
  "check_digit": "4",
  "name": ["string"],
  "commercial_name": "string",
  "active": true,
  "vat_responsible": false,
  "fiscal_responsibilities": [ { "code": "R-99-PN", "name": "Not responsible" } ],
  "address": {
    "address": "Cra. 18 #79A - 42",
    "city": {
      "country_code": "Co", "country_name": "Colombia",
      "state_code": "19", "state_name": "Cauca",
      "city_code": "19001", "city_name": "Popayán"
    },
    "postal_code": "110911"
  },
  "phones": [ { "indicative": "57", "number": "3006003345", "extension": "132" } ],
  "contacts": [
    {
      "first_name": "Marcos", "last_name": "Castillo", "email": "marcos@example.com",
      "phone": { "indicative": "57", "number": "3006003345", "extension": "132" }
    }
  ],
  "comments": "This is an additional comment",
  "related_users": { "seller_id": 625, "collector_id": 625 },
  "custom_fields": [ { "key": "YearsOld", "value": "29" } ],
  "metadata": { "created": "2020-06-15T03:33:17.0000000+00:00", "last_updated": null }
}
```

Nota: en la respuesta, `id_type` pasa de string (request) a objeto `{code, name}`, y
`address.city` se enriquece con `*_name` derivados del código enviado. Mismo patrón que en
`invoices`.

## GET /v1/customers — Listar clientes

### Query params

| Param | Tipo | Descripción |
|---|---|---|
| `identification` | string | Filtra por identificación exacta |
| `branch_office` | integer | Default `0` |
| `active` | string (`"true"`/`"false"`) | Default `true` |
| `type` | string | `Customer`/`Supplier`/`Other`, default `Customer` |
| `person_type` | string | `Person`/`Company` |
| `created_start` / `created_end` | date-time RFC3339 | Rango de fecha de creación |
| `date_start` / `date_end` | date-time RFC3339 | Rango de fecha del registro |
| `updated_start` / `updated_end` | date-time RFC3339 | Rango de última actualización |
| `page` | integer | Página (desde 1) |
| `page_size` | integer | Tamaño de página — ⚠️ ver `known-issues.md`, no confirmado que se respete siempre |

Respuesta: mismo shape de paginación genérico (`pagination`, `results[]`, `__links`) descrito
en `00-core-auth-http.md`, con cada elemento de `results[]` igual al objeto cliente completo
(mismo shape que la respuesta de `POST`/`GET /{id}`).

## GET /v1/customers/{id} — Obtener un cliente

Path param: `id` (GUID, formato `00000000-0000-0000-0000-000000000000`).
Respuesta: mismo objeto cliente completo (ver arriba).

## PUT /v1/customers/{id} — Actualizar cliente

Path param: `id` (GUID). Mismo body/campos que `POST` (documentación indica: **reemplazo
completo** — "must send equal fields as in creation because it replaces the data. Empty
fields will remain empty"). Es decir, no es un PATCH parcial: campos omitidos quedan vacíos,
no se preservan del estado anterior. Respuesta: `200` con el objeto actualizado, mismo shape.

## DELETE /v1/customers/{id}

Confirmado en el SDK oficial JS (`CustomerApi.deleteCustomer`, modelo de respuesta
`CustomerDeleteViewModel`) pero **no se pudo verificar el shape exacto de la respuesta ni
código HTTP en `developers.siigo.com`** directamente — no se encontró la página oficial vía
búsqueda. Tratar como confirmado-parcial: el endpoint existe, pero el contrato de respuesta
no está confirmado contra la doc oficial.

## Ambigüedades / pendientes de confirmar

- No se pudo cargar directamente la página oficial `developers.siigo.com/docs/siigoapi/customer/2-get-customers` ni `.../3-get-customer` ni `.../5-delete-customer` (asumiendo esa numeración) — el contenido reportado arriba para list/get-single proviene de fetches exitosos a slugs `2-get-customers` y `3-get-customer` que sí resolvieron, pero no se verificó que esos sean los slugs canónicos actuales (podrían haber cambiado).
- `DELETE /v1/customers/{id}` — endpoint confirmado por el SDK oficial JS de Siigo, pero sin verificación directa en la doc de developers.siigo.com (no indexado en las búsquedas realizadas). No se confirmó si Siigo permite eliminar clientes con movimientos asociados (facturas, etc.) o si devuelve `409`/`delete_not_allowed` en ese caso — es razonable esperar el mismo patrón de error que otros documentos (`delete_not_allowed`, ver `00-core-auth-http.md`), pero no está confirmado específicamente para clientes.
- No quedó claro si `Idempotency-Key` aplica a `POST /v1/customers` — la investigación previa (`00-core-auth-http.md`) solo confirmó idempotencia en facturas de venta, notas crédito, journals y vouchers; no se menciona `customers` ni se descartó explícitamente.
- El campo `check_digit` en la respuesta de creación es idéntico al enviado — no se confirmó si Siigo lo recalcula/valida contra el NIT enviado o simplemente lo persiste tal cual.
- No se encontró documentación sobre duplicados: la única mención indirecta es que "duplicate identifications are permitted only for new branch offices" — no se confirmó el código de error exacto (`already_exists`, `duplicated_document`?) que se dispara si se intenta crear un cliente con `identification`+`branch_office` ya existentes.
