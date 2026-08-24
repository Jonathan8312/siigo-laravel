<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\Enums;

use Jonathan8312\Siigo\DataTransferObjects\CreditNotes\CreditNoteData;

/**
 * `POST /v1/credit-notes` → `reason` — DIAN rejection-reason code,
 * required for electronic credit notes. Confirmed on the "Crear Nota
 * Crédito" page (2026-08-23) via its "Códigos de Motivo de Rechazo
 * DIAN" table, which itself is internally inconsistent: it lists codes
 * 1-4, 6, and 7 (skipping 5 entirely), while the same page's request
 * schema widget declares the allowed range as only `1 | 2 | 3 | 4 | 5 |
 * 6` (no 7). Neither `5` nor `7` could be confirmed with certainty —
 * see docs/known-issues.md. This enum only names the five codes with
 * an unambiguous, matching description; pass a raw `int` to
 * {@see CreditNoteData}
 * for `5` or `7` if your account needs them.
 */
enum CreditNoteReason: int
{
    /** Devolución parcial de los bienes y/o no aceptación parcial del servicio. */
    case PartialReturnOrRejection = 1;

    /** Anulación de factura electrónica. */
    case InvoiceAnnulment = 2;

    /** Rebaja o descuento parcial o total. */
    case Discount = 3;

    /** Ajuste de precio. */
    case PriceAdjustment = 4;

    /** Descuento comercial por pronto pago. */
    case EarlyPaymentDiscount = 6;
}
