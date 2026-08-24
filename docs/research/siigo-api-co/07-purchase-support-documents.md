# Siigo API Colombia — Purchase Support Documents (`/v1/purchase-support-documents`)

Referencia interna de ingeniería (síntesis propia, no copia literal). **Advertencia de
cobertura:** este es el recurso peor documentado de los 5 investigados. No se logró ubicar
una página bajo `developers.siigo.com/docs/siigoapi/...` indexada para este recurso
(múltiples búsquedas `site:developers.siigo.com` con distintas variantes no devolvieron
ninguna página de referencia técnica del developer portal). La única fuente encontrada con
contenido específico de la API fue el portal de soporte al cliente de Siigo
(`siigonube.portaldeclientes.siigo.com/gestionar-documentos-soporte-en-api/`), que es
documentación de producto orientada a usuario final, no la referencia técnica del developer
portal — pero sí confirma URLs y métodos HTTP reales, por lo que se usa como fuente aquí con
esa salvedad explícita. Tampoco está cubierto por el SDK oficial JS de Siigo (no existe
`PurchaseSupportDocumentApi.md` en su repositorio).

**Concepto de negocio:** el "documento soporte electrónico" registra compras/pagos a
proveedores que NO están obligados a emitir factura electrónica (ej. personas naturales no
facturadoras). Es el mecanismo DIAN para dejar soporte contable/fiscal de esas operaciones
cuando no se recibe una factura electrónica ni documento equivalente del proveedor.

## Endpoints (confirmados solo por el portal de soporte, no por developer docs)

| Método | URL | Descripción |
|---|---|---|
| `POST` | `/v1/purchase-support-documents` | Crear documento soporte |
| `GET` | `/v1/purchase-support-documents` | Listar documentos soporte (inferido — ver ambigüedades) |
| `GET` | `/v1/purchase-support-documents/{id}` | Obtener un documento soporte por id |
| `PUT` | `/v1/purchase-support-documents/{id}` | Editar documento soporte |
| `DELETE` | `/v1/purchase-support-documents/{id}` | Eliminar documento soporte |
| `GET` | `/v1/document-types?type=DS` | Consultar tipos de comprobante de documento soporte configurados en la cuenta (`type=DS`) |

Headers esperados (por analogía con el resto de la API, no verificados específicamente para
este recurso): `Authorization: Bearer <token>`, `Partner-Id: <valor>`, `Content-Type:
application/json` en `POST`/`PUT`.

## Payload — estructura inferida

**No se encontró ningún ejemplo de JSON completo (request ni response) para este recurso en
ninguna fuente consultada.** Lo único confirmado textualmente por el portal de soporte:

- Campos no modificables al editar (`PUT`): `document.id`, `supplier.identification`,
  `currency.code`, `number` — **idéntica lista a la de `purchases`** (`06-purchases.md`),
  lo que sugiere fuertemente que `purchase-support-documents` comparte el mismo shape de
  payload que `purchases` (`supplier`, `items[]`, `payments[]`, `document`, `date`, etc.),
  con probablemente un campo adicional específico del documento soporte.
- Existe un campo `Stamp` (booleano, opcional, default `false`) para indicar si el documento
  debe reportarse electrónicamente a la DIAN — análogo a `stamp.send` en `invoices`/
  `purchases`, aunque aquí el portal lo menciona como campo raíz `"Stamp"` (mayúscula
  inicial) en vez de un objeto anidado `stamp.send` — no se pudo confirmar cuál de las dos
  formas es la real, podría ser un error de transcripción del portal de soporte.
- `GET /v1/document-types?type=DS` permite validar qué tipos de comprobante "DS" (Documento
  Soporte) están configurados en la cuenta antes de crear el documento — análogo a validar
  `document.id` antes de crear una factura/compra.

### Payload de ejemplo — construcción especulativa por analogía con `purchases`

⚠️ **Este bloque NO es un ejemplo verificado contra la documentación oficial.** Se construye
únicamente por analogía estructural con `POST /v1/purchases` (mismo patrón `supplier` +
`items[]` + `payments[]`) más el campo `Stamp` confirmado por el portal de soporte. No usar
como contrato definitivo sin validar contra una llamada real de prueba.

```json
{
  "document": { "id": 0 },
  "date": "2024-05-22",
  "supplier": {
    "identification": "101020201",
    "branch_office": 0
  },
  "items": [
    {
      "code": "SGNB002",
      "description": "Prod SiigoNube_Mod 002",
      "quantity": 8,
      "price": 1000
    }
  ],
  "payments": [
    { "id": 51279, "value": 8000 }
  ],
  "stamp": { "send": true }
}
```

## GET /v1/purchase-support-documents — Listado

No confirmado en ninguna fuente si existe realmente como endpoint separado con paginación
(se infiere por el patrón general de la API y porque el portal de soporte menciona "consultar
todos los documentos" como una de las operaciones), o si `GET /v1/purchase-support-documents`
sin `{id}` simplemente no está soportado y solo existe consulta por id. Tratar como no
confirmado.

## Ambigüedades / pendientes de confirmar

- **No se encontró la página oficial del developer portal para este recurso.** Es la brecha
  más grande de esta investigación. Antes de implementar este recurso en el SDK, se
  recomienda: (a) contactar soporte de Siigo directamente (`soporteapi@siigo.com` /
  `soporteapi.aliados@siigo.com`, vistos en la doc de producto), o (b) hacer una llamada de
  prueba real contra la cuenta sandbox para descubrir el contrato exacto empíricamente.
- **Ningún ejemplo de JSON de request/response fue localizado** — todo el payload propuesto
  arriba es inferencia por analogía con `purchases`, no un hecho confirmado.
- No se confirmó el nombre exacto del campo de timbrado (`Stamp` raíz vs. `stamp.send`
  anidado como en el resto de la API) — inconsistencia entre la fuente (portal de soporte,
  documentación no técnica) y el patrón usado en el resto de recursos.
- No se confirmó si `purchase-support-documents` es en realidad un recurso independiente en
  el backend de Siigo, o si internamente es una variante de `purchases` con un flag
  (`document_support`) — se vio una mención de un campo `document_support` en contexto de
  tipos de documento, lo que podría indicar que el "documento soporte" es simplemente un
  `purchase` cuyo `document.id` apunta a un tipo de documento con `document_support: true`,
  en cuyo caso el endpoint separado `/v1/purchase-support-documents` podría ser solo un alias
  de conveniencia sobre `/v1/purchases`. Esto cambiaría significativamente el diseño del SDK
  (¿un recurso propio, o un parámetro sobre `PurchaseResource`?) y debe resolverse antes de
  implementar.
- No se confirmó si existe envío por correo o PDF para este recurso.
- No se confirmó el formato de respuesta de `GET /v1/document-types?type=DS` (shape de cada
  tipo de documento retornado).
- No se confirmaron códigos de error específicos de este recurso.
