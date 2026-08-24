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

## Paginación

### `page_size` del query param no siempre se refleja en la respuesta
Al pedir `GET /v1/customers?page=1&page_size=1` o `page_size=2`, la respuesta reportó
`"pagination": {"page_size": 10, ...}` (el valor por defecto), no el `page_size` solicitado.
**Pendiente de investigar a fondo en Fase 2/3**: podría deberse a un mínimo de `page_size`
distinto al esperado, a un nombre de parámetro diferente, o a un comportamiento real del
recurso `customers` en particular. No asumir que `page_size` funciona igual en todos los
recursos hasta confirmarlo endpoint por endpoint.
