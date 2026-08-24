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

### `document_settings` en `POST /v1/invoices` casi siempre significa "falta la resolución DIAN"
Confirmado contra sandbox real (2026-08-23): crear una factura con un `document.id` cuyo tipo
de comprobante tiene `electronic_type` distinto de `NoElectronic` (es decir, cualquier tipo de
facturación electrónica) devuelve:
```json
{
  "Status": 400,
  "Errors": [{
    "Code": "document_settings",
    "Message": "The document.id cannot be used, you must verify the document settings",
    "Params": [],
    "Detail": null
  }]
}
```
Se probó con dos tipos de comprobante electrónicos distintos (uno del sector transporte y uno
estándar) y ambos fallaron igual — descartando que fuera un problema de un tipo de comprobante
específico. Con un tipo `NoElectronic`, la creación funcionó de punta a punta (factura real
creada y luego borrada exitosamente). Esto indica que el error es específico de facturación
electrónica, casi con certeza porque **no hay una resolución de numeración DIAN vigente
asociada** a esos tipos de comprobante en esta cuenta — es configuración de Siigo Nube
(**Configuración → Documentos → Facturación electrónica de venta**), no algo resoluble desde
el SDK ni desde la API.

**Cómo lo maneja el SDK**: nada que hacer del lado del SDK — el error se propaga
correctamente como `ValidationException` con `errorCode() === 'document_settings'`. El test de
staging usa deliberadamente un tipo de comprobante `NoElectronic` para no depender de que la
cuenta tenga una resolución DIAN configurada.

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

## Credit Notes

### El catálogo de `reason` (motivo DIAN) es internamente inconsistente en la propia página
Confirmado en `developers.siigo.com/docs/siigoapi/credit-note/1-create-credit-note/`
(2026-08-23): la tabla legible "Códigos de Motivo de Rechazo DIAN" en el cuerpo de la página
lista los códigos `1, 2, 3, 4, 6, 7` (**salta el `5`**), mientras que el widget de schema del
mismo request (generado a partir del OpenAPI de Siigo) declara el rango permitido como
`1 | 2 | 3 | 4 | 5 | 6` (**sin el `7`**). Ninguna de las dos fuentes es consistente con la otra,
y no se encontró una tercera fuente (SDK oficial JS, Apiary) que resolviera la ambigüedad — el
SDK JS solo expone `DianReason` como un enum de enteros sin nombres semánticos.

**Cómo lo maneja el SDK**: `Enums\CreditNoteReason` solo incluye los cinco códigos cuyo
nombre es inequívoco en la tabla (`1`, `2`, `3`, `4`, `6`). `CreditNoteData::$reason` acepta
`CreditNoteReason|int`, así que un consumidor puede seguir enviando `5` o `7` como entero
crudo si su cuenta los necesita — el SDK no los bloquea, solo no les da nombre.

### La descripción documentada del campo `invoice` no corresponde a lo que el campo hace
En la misma página, la tabla "Campos del JSON" describe el campo `invoice` como "Código del
tipo de nota crédito" — una descripción que en realidad corresponde a `document.id`. El campo
`invoice` real es el GUID de la factura que la nota crédito ajusta/anula (confirmado por el
ejemplo de request/response completo más abajo en la misma página, y por el shape de
`invoice_data` documentado como su alternativa). Aparente error de copiar/pegar en la
documentación de Siigo.

**Cómo lo maneja el SDK**: `CreditNoteData::$invoice` se documenta y tipa según el
comportamiento real (GUID de factura), no según la descripción textual incorrecta.

### `reason` se documenta como opcional para notas no electrónicas, pero el schema lo marca `Required` sin condición
La misma tabla dice "Obligatorio para documentos electrónicos" para `reason`, pero el widget
de schema del request lo marca `Required` sin ninguna condición. No se pudo confirmar contra
sandbox si Siigo realmente rechaza una nota crédito no electrónica sin `reason` — ver
`CreditNotesStagingTest`, que siempre envía `reason` para evitar el riesgo.

**Cómo lo maneja el SDK**: `CreditNoteData::$reason` es un parámetro obligatorio del
constructor (sin valor por defecto), siguiendo la declaración más estricta (el schema) en vez
de la más permisiva (el texto).

### El PDF de una nota crédito usa `cude`, no `cufe`
Confirmado en `GET /v1/credit-notes/{id}/pdf`: la respuesta es `{id, cude, base64}` — a
diferencia de `GET /v1/invoices/{id}/pdf`, que devuelve `cufe`. Consistente con que CUDE
("Código Único de Documento Electrónico") es el identificador usado para notas crédito/débito,
mientras que CUFE ("Código Único de Factura Electrónica") es específico de facturas de venta.
El objeto `stamp` de la respuesta completa de una nota crédito, sin embargo, sí documenta
ambos campos (`cufe` y `cude`) simultáneamente — no confirmado si Siigo realmente popula
`cufe` ahí o siempre lo deja vacío.

**Cómo lo maneja el SDK**: `CreditNoteFile` usa `cude` (no `cufe`), a diferencia de
`Invoices\InvoiceFile`. `CreditNoteStamp` mantiene ambos campos, igual que `Invoices\Stamp`.

### No existe `PUT`, `DELETE`, ni anulación para notas crédito
Confirmado por triple fuente: el SDK oficial JS (`CreditNoteApi` solo expone
create/list/find/pdf), la navegación de `developers.siigo.com` (mismas cuatro operaciones más
el catálogo de tipos, que ya cubre `Catalogs::documentTypes('NC')`), y la ausencia de cualquier
mención a edición/borrado en el cuerpo de la documentación. Una nota crédito creada contra
sandbox durante el desarrollo de este módulo (`CreditNotesStagingTest`) queda permanente en la
cuenta — y, una vez creada, la factura que referencia tampoco se puede borrar (ver la sección
de Invoices arriba: "relacionados... deben borrarse primero").

**Cómo lo maneja el SDK**: `Resources\CreditNotes` no expone `update()`, `delete()`, ni
`annul()`. `CreditNotesStagingTest` documenta explícitamente esta limitación y acepta dejar
datos de prueba permanentes en el sandbox como consecuencia.

## Payment Receipts

### `PUT /v1/payment-receipts/{id}` sí existe, pese a no estar en el spec `.apib`
`09-payment-receipts.md` dejó como ambigüedad sin resolver si el endpoint de edición
prometido por el texto de "Novedades" del spec realmente existe, ya que el `Group Recibos de
pago o egreso` del `.apib` no documenta ningún `PUT`. Confirmado contra sandbox
(2026-08-24): `PUT /v1/payment-receipts/{id-inexistente}` devuelve `400 parameter_required`
("The field Document is required"), no un 404/405 de ruteo — es decir, el endpoint existe y
valida el body antes de resolver el id. Confirmado también de punta a punta contra un recibo
real (`PaymentReceiptsStagingTest`): crear → `PUT` cambiando `observations` → el `GET`
posterior refleja el cambio.

**Cómo lo maneja el SDK**: `Resources\PaymentReceipts::update()` existe (a diferencia de
`CreditNotes`, que no lo expone).

### `GET /v1/payment-types?document_type=RP` no existe — usar `FC`
El catálogo de tipos de pago documenta `document_type` como obligatorio pero no aclara qué
valores acepta más allá de los confirmados para otros módulos (`FV`, `FC`, `NC`, `RC` — ver
`01-catalogs.md`). Confirmado contra sandbox que `RP` **no** es un valor válido: `GET
/v1/payment-types?document_type=RP` devuelve `404 not_found`. En cambio, `document_type=FC`
(compra) sí funciona y devuelve las formas de pago del lado proveedor (`type:
"CarteraProveedor"`/`"Proveedor"`) — confirmado que sus ids coinciden con los `payment.id`
realmente usados en recibos de pago reales existentes en la cuenta sandbox.

**Cómo lo maneja el SDK**: `DataTransferObjects\PaymentReceipts\Payment` documenta usar
`Catalogs::paymentTypes('FC')`, no `'RP'`. `PaymentReceiptsStagingTest` lo hace así.

### El campo `payment` puede estar ausente por completo en la respuesta
A diferencia de lo documentado (`payment.id`/`payment.value` obligatorios en el request),
varios recibos reales devueltos por `GET /v1/payment-receipts` (listado, 2026-08-24) no traen
la clave `payment` en absoluto — ocurre en registros con `items: []` también, sugiriendo un
recibo cuyo pago no terminó de procesarse del lado de Siigo.

**Cómo lo maneja el SDK**: `PaymentReceipt::$payment` es nulable (`?PaymentSummary`), a
diferencia de `PaymentReceiptData::$payment` (obligatorio en el request).

### La variante avanzada (`type: Detailed`) no se verificó contra sandbox
El spec documenta un ejemplo completo de request para `Detailed` (movimientos contables
explícitos por `items[].account`), pero probarlo contra sandbox requeriría códigos de plan de
cuentas reales de la cuenta de pruebas, que no hay forma de descubrir vía la API (no existe un
catálogo de cuentas contables entre los 11 catálogos confirmados en `01-catalogs.md`) — y sería
una escritura contable permanente sin forma segura de validarla primero. `DetailedItem`,
`DetailedPaymentReceiptData`, `AccountRef` y `TaxRef` están modelados estrictamente según el
spec, sin verificación empírica. Ver también la ambigüedad ya documentada en
`09-payment-receipts.md` sobre si `Detailed` sigue vigente para `vouchers` (recurso aún no
implementado) de la misma forma que para `payment-receipts`.

**Cómo lo maneja el SDK**: el docblock de `DetailedPaymentReceiptData` señala explícitamente
que no está verificado. Confirmar contra la cuenta real del consumidor antes de depender de
esta variante en producción.

## Paginación

### `page_size` del query param no siempre se refleja en la respuesta
Al pedir `GET /v1/customers?page=1&page_size=1` o `page_size=2`, la respuesta reportó
`"pagination": {"page_size": 10, ...}` (el valor por defecto), no el `page_size` solicitado.
Reproducido también en `payment-receipts` (2026-08-24): `GET
/v1/payment-receipts?page=1&page_size=2` devolvió `"pagination": {"page_size": 10, ...}` y 10
resultados reales, no 2 — confirma que no es un comportamiento específico de `customers`, sino
un límite mínimo de `page_size` (aparentemente 10) aplicado de forma transversal. No asumir que
`page_size` funciona igual en todos los recursos hasta confirmarlo endpoint por endpoint.

### El filtro `type` de `GET /v1/customers` no siempre filtra
Observado de paso durante la investigación de Payment Receipts (2026-08-24, no el foco de esa
investigación, pendiente de confirmar a fondo si toca el módulo Customers): `GET
/v1/customers?type=Supplier&active=true` devolvió una mezcla de registros `type: "Supplier"` y
`type: "Customer"` en `results[]`, en vez de solo proveedores. No se investigó si es un bug
real o un `page_size`/`page` insuficiente para ver el patrón completo.

**Cómo lo maneja el SDK**: `PaymentReceiptsStagingTest` filtra por `type === Supplier` en
memoria sobre el resultado de `Customers::all(type: CustomerType::Supplier, ...)`, sin confiar
en que el filtro del query ya hizo el trabajo — mismo patrón defensivo que
`CreditNotesStagingTest` usa para tipos de documento.
