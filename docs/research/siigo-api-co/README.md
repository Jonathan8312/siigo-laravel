# Investigación Siigo API Colombia — índice (Fase 0)

Notas técnicas internas de ingeniería, construidas a partir de `developers.siigo.com/docs/siigoapi`
(fuente primaria), contrastadas cuando fue posible con el spec API Blueprint público de Siigo
y el SDK oficial `SiigoSAS/siigo_sdk_javascript` (fuentes secundarias, solo para llenar huecos
que la doc web no cubre). Todo redactado en palabras propias — ver `docs/known-issues.md` para
discrepancias confirmadas contra la API real.

Este contenido es insumo para diseñar el SDK, no es la documentación pública final del paquete
(esa vive en `/docs/*.md` de nivel superior y se escribe fase por fase).

## Archivos

| Archivo | Contenido |
|---|---|
| `00-core-auth-http.md` | Auth, headers, errores, códigos HTTP, rate limit, idempotencia, paginación |
| `01-catalogs.md` | 11 catálogos confirmados (account-groups, taxes, price-lists, warehouses, users, document-types, payment-types, cost-centers, fixed-assets, expenses, misc-incomes) |
| `02-products.md` | CRUD completo de productos/servicios |
| `03-customers.md` | CRUD de clientes |
| `04-invoices.md` | CRUD + annul + stamp DIAN + pdf + mail |
| `05-credit-notes.md` | create/list/get/pdf, referencia a factura original |
| `06-purchases.md` | create/get/update confirmados; list/delete con huecos |
| `07-purchase-support-documents.md` | Sin página propia en el developer portal — contenido especulativo, marcado como tal |
| `08-vouchers.md` | Recibos de caja |
| `09-payment-receipts.md` | Recibos de pago/egreso |
| `10-journals.md` | Comprobantes contables |
| `11-quotations.md` | Cotizaciones |
| `12-reports.md` | Solo 3 reportes confirmados: balance de prueba general, balance de prueba por tercero, cuentas por pagar |

## Decisiones de arquitectura tomadas durante esta investigación

### `Partner-Id`: default `TREBOLDEV`, siempre sobreescribible

Confirmado que `Partner-Id` debe representar la integración real del cliente final (no el
fabricante del SDK), y que Siigo monitorea/bloquea integraciones con datos falsos ahí (ver
`docs/known-issues.md`). Decisión: `config/siigo.php` usará `TREBOLDEV` como valor por
defecto de `SIIGO_PARTNER_ID` si el consumidor no lo configura, pero **siempre** debe poder
sobreescribirse vía `.env` — nunca queda hardcodeado sin posibilidad de cambio. Se documentará
explícitamente en `docs/configuration.md` (Fase 1) que cada integrador debería configurar su
propio valor real, para evitar el riesgo de bloqueo por reporte de datos no reales que la
propia doc de Siigo advierte.

## Huecos y ambigüedades críticas para fases posteriores (agregado de los 3 reportes)

Antes de implementar cada módulo (Fase 2+), confirmar contra sandbox o soporte Siigo:

1. **Purchase Support Documents**: no se encontró página oficial en el developer portal. Podría
   ser un recurso independiente o un alias de `purchases` con un flag. Confirmar antes de
   diseñar sus DTOs/Resource.
2. **Purchases**: `GET /v1/purchases` (listado) y `DELETE` no están respaldados por el SDK
   oficial ni por la doc web con el mismo nivel de detalle que create/get/update. Hay una
   inconsistencia en el ejemplo oficial (cantidad 8 en request vs. 3 en response).
3. **Credit Notes**: el enum `reason` (1–6) no tiene mapeo semántico confirmado oficialmente.
4. **Quotations**: el spec oficial de Siigo apunta el endpoint de borrado a `/v1/invoices/id`
   (casi con certeza un bug de documentación de Siigo, no algo a replicar). No existe endpoint
   de conversión cotización→factura en ninguna fuente revisada.
5. **Vouchers**: contradicción sin resolver sobre si el tipo `Detailed` sigue vigente (un
   changelog dice que se eliminó, un ejemplo en la misma sección lo sigue usando).
6. **Payment Receipts**: ~~la doc promete edición pero no hay ningún `PUT` en el spec.~~
   **Resuelto** (Fase Payment Receipts, 2026-08-24): `PUT` sí existe, confirmado contra
   sandbox de punta a punta. Además se descubrió que `GET /v1/payment-types?document_type=RP`
   no existe (404) — usar `document_type=FC`. Ver `docs/known-issues.md`.
7. **Paginación**: inconsistencia de nombre de clave (`_links` vs `__links`) según la fuente;
   además `page_size` no se reflejó en un test real contra `customers` (ver known-issues.md).
8. **Reports**: la API pública solo expone 3 reportes — no asumir que existen cuentas por
   cobrar, balance general o estado de resultados como endpoint de la API.
9. **Products**: inconsistencia de `type` válido (`Combo` vs `ConsumerGood` según la página) y
   asimetría de tipos entre request/response en `unit`/`taxes`/`value`/`quantity`.

Ninguno de estos puntos bloquea el Core (Fase 1), que no depende de ningún recurso de negocio.
Sí deben resolverse, uno por uno, en el "Paso 1: leer documentación oficial" de cada fase
correspondiente (sección 26 del CLAUDE.md del proyecto) antes de dar por cerrado ese módulo.
