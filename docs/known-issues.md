# Known Issues — discrepancias entre documentación Siigo y comportamiento real

Este archivo documenta diferencias confirmadas entre `developers.siigo.com/docs/siigoapi`
y el comportamiento real de la API, verificadas contra el ambiente de pruebas (sandbox)
de Siigo. Cada entrada indica cómo lo implementa el SDK.

## Autenticación

### `POST /auth` responde `200`, no `201`
La documentación indica que una autenticación exitosa devuelve `201 Created`. En pruebas
reales contra sandbox (2026-08-23), el status real es `200 OK`.

**Cómo lo maneja el SDK**: el cliente de autenticación acepta `200` como éxito, no asume `201`.

### `scope` devuelto es `"SiigoAPI"` (sin espacio)
El ejemplo de la documentación muestra `"scope": "Siigo API"` (con espacio). El valor real
observado es `"SiigoAPI"`. No afecta lógica del SDK (el campo no se usa para tomar decisiones),
solo se anota por si alguna validación estricta llegara a depender de él.

## Header `Partner-Id`

### Es obligatorio también fuera de `/auth`, y valida el *valor*, no solo su presencia
Confirmado: un request a un recurso (`GET /v1/customers`) sin `Partner-Id` devuelve:
```json
{
  "Status": 400,
  "Errors": [{
    "Code": "header_required",
    "Message": "The header Partner-Id is required",
    "Params": ["Partner-Id"],
    "Detail": "Check the API documentation: https://developer.siigo.com/introduction/codigos-de-error/header_required"
  }]
}
```

### El valor debe ser estrictamente alfanumérico — un guion (`-`) ya lo invalida
Un valor como `siigo-laravel-sdk-dev` (con guiones) es **rechazado**:
```json
{
  "Status": 400,
  "Errors": [{
    "Code": "invalid_partner_id",
    "Message": "The header Partner-Id has an invalid value",
    "Params": ["Partner-Id"],
    "Detail": "Check the API documentation: https://developer.siigo.com/introduction/codigos-de-error/invalid_partner_id"
  }]
}
```
Un valor sin guiones ni caracteres especiales (ej. `siigolaravelsdk`) funciona correctamente.
La documentación dice "alfanumérico, sin espacios ni caracteres especiales" pero no aclara
explícitamente que el guion cuenta como carácter especial inválido — confirmado empíricamente.

**Cómo lo maneja el SDK**: `config/siigo.php` debe advertir/validar que `SIIGO_PARTNER_ID`
sea estrictamente `^[A-Za-z0-9]+$` antes de enviarlo, para fallar rápido con un mensaje claro
en vez de dejar que Siigo devuelva `invalid_partner_id`.

### El `POST /auth` en sí NO requiere `Partner-Id`
Confirmado: la autenticación funciona sin enviar `Partner-Id`. Solo es obligatorio en los
requests posteriores a los recursos de la API.

## Header `Idempotency-Key`

### También debe ser estrictamente alfanumérico — mismo patrón que `Partner-Id`
Confirmado contra sandbox real (2026-08-23) creando una factura: un valor con guiones
(`siigo-laravel-sdk-staging-123456`) es **rechazado**:
```json
{
  "Status": 400,
  "Errors": [{
    "Code": "invalid_idempotency_key",
    "Message": "The header Idempotency-Key has an invalid value",
    "Params": [],
    "Detail": null
  }]
}
```
La documentación dice "alfanumérico, sin caracteres especiales, sin espacios en blanco" pero,
igual que con `Partner-Id`, no aclaraba explícitamente que el guion cuenta como carácter
especial inválido.

**Cómo lo maneja el SDK**: `Http\Client::post()` valida `idempotencyKey` contra
`^[A-Za-z0-9]{1,30}$` antes de enviarlo, lanzando `InvalidArgumentException` con un mensaje
claro en vez de dejar que Siigo devuelva `invalid_idempotency_key`.

## Formato de errores

Confirmado tal cual lo documenta Siigo: claves en PascalCase (`Status`, `Errors`, `Code`,
`Message`, `Params`, `Detail`), a diferencia del resto de la API que usa `snake_case`. El
campo `Detail` incluye una URL a `developer.siigo.com` (dominio distinto, sin "s", al de
`developers.siigo.com` donde vive la documentación general) con la página específica de ese
código de error — útil como referencia programática por `Code`.

## Catálogos

### `GET /v1/document-types` — `type` es en realidad obligatorio, no opcional
La documentación oficial no marca `type` como `Required` en la tabla de parámetros de este
endpoint. En pruebas reales contra sandbox (2026-08-23), omitirlo devuelve:
```json
{
  "Status": 400,
  "Errors": [{
    "Code": "parameter_required",
    "Message": "The field type is required",
    "Params": [],
    "Detail": null
  }]
}
```

**Cómo lo maneja el SDK**: `Catalogs::documentTypes(string $type)` exige el parámetro
(no es nullable/opcional), evitando que el consumidor del SDK dispare una llamada que Siigo
siempre va a rechazar.

## Customers

### `DELETE /v1/customers/{id}` puede estar deshabilitado por cuenta
El endpoint existe (confirmado también por el SDK oficial JS de Siigo), pero en pruebas
reales contra la cuenta sandbox (2026-08-23) fue rechazado con:
```json
{
  "Status": 403,
  "Errors": [{
    "Code": "disabled_functionality",
    "Message": "This functionality is temporarily disabled.",
    "Params": [],
    "Detail": null
  }]
}
```
No está claro si esto es una restricción específica de esta cuenta/momento o una política
general de Siigo para el endpoint de borrado de clientes — no se encontró documentación
oficial al respecto. El SDK mapea correctamente este caso a `RequestException` (403, catch-all)
con `errorCode() === 'disabled_functionality'`.

**Cómo lo maneja el SDK**: `Customers::delete()` se implementa igual (el endpoint es real),
pero el test de staging correspondiente trata este código de error específico como una
limitación conocida (`markTestIncomplete`) en vez de una falla del SDK, y no oculta ningún
otro tipo de error en `delete()`. Un cliente de prueba creado durante ese test quedó sin poder
borrarse por esta restricción — dato inofensivo, pero queda en la cuenta sandbox.

### `PUT /v1/customers/{id}` confirmado como reemplazo completo
Confirmado contra sandbox real: enviar un `CustomerData` con un `name` distinto reemplaza
`name` correctamente, consistente con lo que documenta Siigo ("must send equal fields as in
creation because it replaces the data"). No se probó exhaustivamente qué pasa con campos
omitidos que sí tenían valor previo (ej. dejar `contacts` fuera de un update) — tratar
`CustomerData` siempre como el estado completo deseado, nunca como un parche parcial.

## Invoices

### `POST /v1/invoices/batch` existe, pero no está en `developers.siigo.com`
La investigación inicial de este SDK concluyó (incorrectamente) que Siigo no tenía un
endpoint de creación masiva de facturas, basada solo en `developers.siigo.com` — el sitio de
documentación nuevo (fumadocs), donde efectivamente no aparece. El usuario del proyecto
confirmó que sí existe y lo usa en producción, y se verificó contra dos fuentes independientes:

1. `siigoapi.docs.apiary.io/#reference/facturas-de-venta/crear-lote-de-facturas-de-venta` —
   el sitio de documentación **paralelo** de Siigo (basado en Apiary), donde el endpoint sí
   está documentado, incluyendo una nota de que es una funcionalidad nueva.
2. Uso real en producción en un proyecto del usuario (`pixie-admin`), que coincide
   exactamente con lo documentado en Apiary.

**Lección**: Siigo mantiene (al menos temporalmente) dos sitios de documentación no
sincronizados entre sí. Cuando `developers.siigo.com` no confirme algo, revisar también
`siigoapi.docs.apiary.io` antes de descartarlo como no oficial o inexistente — ver
`docs/research/siigo-api-co/04-invoices.md` para el detalle completo del endpoint.

### `invoices[].idempotency_key` del batch — inconsistencia de 30 vs 32 caracteres
Dentro de la misma página de Apiary, la tabla de campos del endpoint de lote dice máximo 30
caracteres para `idempotency_key`, pero un mensaje de error genérico documentado en otra
sección de la misma doc dice "máximo 32 caracteres" para el concepto de idempotencia en
general. El SDK valida contra 30 (el límite documentado específicamente para el campo, y el
mismo confirmado empíricamente para el header `Idempotency-Key` de la request singular).

## Paginación

### `page_size` del query param no siempre se refleja en la respuesta
Al pedir `GET /v1/customers?page=1&page_size=1` o `page_size=2`, la respuesta reportó
`"pagination": {"page_size": 10, ...}` (el valor por defecto), no el `page_size` solicitado.
**Pendiente de investigar a fondo en Fase 2/3**: podría deberse a un mínimo de `page_size`
distinto al esperado, a un nombre de parámetro diferente, o a un comportamiento real del
recurso `customers` en particular. No asumir que `page_size` funciona igual en todos los
recursos hasta confirmarlo endpoint por endpoint.
