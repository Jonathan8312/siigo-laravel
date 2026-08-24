# Siigo API Colombia — Core: autenticación, headers, errores, paginación, límites

Referencia interna de ingeniería (síntesis propia, no copia literal) construida a partir de
`developers.siigo.com/docs/siigoapi` y verificación real contra el ambiente de pruebas.
Fuente primaria: documentación oficial. Ver `docs/known-issues.md` para discrepancias
confirmadas contra el comportamiento real.

## Autenticación

- `POST https://api.siigo.com/auth` (sin prefijo `/v1`).
- Body: `{ "username": "...", "access_key": "..." }`.
- Respuesta exitosa (real: `200`, doc dice `201`):
  ```json
  { "access_token": "<jwt>", "expires_in": 86400, "token_type": "Bearer", "scope": "SiigoAPI" }
  ```
- `expires_in` es fijo: 86400 segundos (24h).
- No hay endpoint de refresh — se vuelve a autenticar cuando el token expira.
- El `access_token` se usa como `Authorization: Bearer <token>` en todos los requests
  posteriores.
- `Partner-Id` **no** es necesario en el propio `POST /auth` (confirmado).

## Headers comunes (recursos, no auth)

- `Authorization: Bearer <token>` — obligatorio.
- `Partner-Id: <valor>` — obligatorio, **estrictamente alfanumérico** (guiones ya son
  inválidos, confirmado — ver known-issues.md), 3–100 caracteres, debe representar el nombre
  real de la integración (Siigo monitorea y bloquea integraciones con valores falsos).
- `Content-Type: application/json` en requests con body.
- `Idempotency-Key: <valor>` — opcional, solo en `POST` de: facturas de venta, notas
  crédito, comprobantes contables (journals), recibos de caja (vouchers). Alfanumérico,
  máx. 30 caracteres, sin espacios ni caracteres especiales. Inútil/prohibido en GET/PUT/DELETE.

## Formato de errores (confirmado real)

```json
{
  "Status": 400,
  "Errors": [
    {
      "Code": "parameter_required",
      "Message": "The field code is required",
      "Params": ["code"],
      "Detail": "Check the API documentation: https://developer.siigo.com/introduction/codigos-de-error/<code>"
    }
  ]
}
```
Claves en PascalCase (excepción al resto de la API, que usa snake_case). `Errors` es un
**array** — un mismo request puede fallar por varios campos simultáneamente. `Detail` apunta
a una página de documentación específica por `Code`, útil para mapear excepciones.

Categorías de `Code` observadas en la documentación:
- Validación: `parameter_required`, `invalid_email`, `invalid_date`, `length_max`/`length_min`,
  `header_required`, `invalid_partner_id`.
- Configuración de la cuenta: `company_settings`, `document_settings`, `warehouse_settings`.
- Negocio: `already_exists`, `duplicated_document`, `delete_not_allowed`, `blocked_transactions`.
- Servicio: `service_unavailable`, `request_timeout`, `unhandled_error`.
- Autorización: `unauthorized`, `parameter_inactive`.

## Códigos de estado HTTP documentados

| Código | Significado |
|---|---|
| 200 | Éxito |
| 201 | Creado (documentado para `/auth`, pero `/auth` real devuelve 200 — ver known-issues) |
| 400 | Request inválido (Siigo usa 400 para validación, **no 422**) |
| 401 | Sin token válido |
| 403 | Sin permisos para la operación |
| 404 | Recurso no existe |
| 408 | Timeout de la solicitud |
| 409 | Conflicto / estado inconsistente |
| 415 | Content-Type no soportado (ej. XML en vez de JSON) |
| 429 | Rate limit excedido (máx. 100/min producción, 10/min cuenta de pruebas) |
| 500 | Error no controlado del servidor |
| 503 | Servicio no disponible (sobrecarga/mantenimiento) |
| 504 | Timeout del servidor |

No hay documentación de header `Retry-After` en `429` — no confirmado si Siigo lo envía.

## Rate limiting y bloqueo de cuenta

- 100 requests/minuto por empresa en producción; 10/minuto en cuenta de pruebas (confirmado
  con la cuenta sandbox: ver `.env.staging.local`).
- Si en 7 días consecutivos la tasa de error supera el 80% de las solicitudes, Siigo bloquea
  la cuenta hasta que se corrija (notificación por correo). Implicación directa para el SDK:
  nunca reintentar a ciegas, validar estructuralmente antes de enviar cuando sea razonable.

## Idempotencia

Ver arriba (headers). Si se reenvía la misma `Idempotency-Key` sobre un documento ya creado,
Siigo devuelve el documento original en vez de crear uno duplicado. Solo confirmado en los
4 endpoints de documentos listados — no asumir que aplica a `customers`, `products` ni
`purchases` sin confirmarlo en su fase correspondiente.

## Paginación (patrón general, confirmado en `invoices` y `customers`)

Query params: `page` (desde 1), `page_size`. Respuesta:
```json
{
  "pagination": { "page": 1, "page_size": 25, "total_results": 250 },
  "results": [ ... ],
  "__links": { "previous": {"href": ""}, "self": {"href": ""}, "next": {"href": ""} }
}
```
⚠️ Ver `docs/known-issues.md`: en pruebas reales sobre `customers`, `page_size` del query
param no se reflejó en la respuesta (quedó en 10 por defecto). Confirmar por endpoint en
fases posteriores antes de construir `PaginatedResponse` de forma genérica.

## Ambientes

No existe una base URL de sandbox separada confirmada: mismo dominio `api.siigo.com`, la
cuenta de pruebas simplemente tiene el rate limit reducido (10/min). Fuera de alcance de
este proyecto: Siigo México tiene documentación separada (`siigoapimexico`), no investigada.
