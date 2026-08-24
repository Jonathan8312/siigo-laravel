<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\DataTransferObjects\PaymentReceipts;

/**
 * The payment method applied — `id` from
 * `Catalogs::paymentTypes('FC')`. Confirmed against sandbox that
 * `document_type=RP` is rejected by `GET /v1/payment-types` (404
 * `not_found`) — `FC` (compra) is the value that actually returns the
 * supplier-side (`CarteraProveedor`) payment methods used by real
 * payment receipts. See docs/known-issues.md. Unlike
 * `Invoices\InvoicePayment`/`CreditNotes\CreditNotePayment`, there is
 * no `due_date` field here — Siigo returns the method's name alongside
 * the same fields, see {@see PaymentSummary}.
 */
final class Payment
{
    public function __construct(
        public readonly int $id,
        public readonly float $value,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'value' => $this->value,
        ];
    }
}
