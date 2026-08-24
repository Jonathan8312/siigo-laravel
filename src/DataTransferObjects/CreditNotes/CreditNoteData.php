<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\DataTransferObjects\CreditNotes;

use Jonathan8312\Siigo\Enums\CreditNoteReason;
use Jonathan8312\Siigo\Resources\Invoices;

/**
 * The payload sent to `POST /v1/credit-notes`. There is no `PUT` or
 * `DELETE`/annul confirmed for credit notes — see docs/known-issues.md.
 *
 * A credit note references the invoice it adjusts/cancels in one of
 * two mutually exclusive ways:
 * - {@see self::$invoice}: the GUID of an existing invoice, created via
 *   {@see Invoices}. This is the common case.
 * - {@see self::$invoiceData} + {@see self::$customer} + {@see self::$seller}:
 *   for a credit note against a sales invoice that was never
 *   registered in Siigo.
 *
 * `reason` is documented as required only for electronic credit notes,
 * but Siigo's own request schema marks it unconditionally required —
 * this DTO follows the stricter schema. Pass a
 * {@see CreditNoteReason} case, or a raw `int` for a code not covered
 * by that enum (see its docblock for why some codes are excluded).
 */
final class CreditNoteData
{
    /**
     * @param  list<CreditNoteItem>  $items
     * @param  list<CreditNotePayment>  $payments
     * @param  list<int>  $retentions  Retention/withholding tax ids.
     */
    public function __construct(
        public readonly DocumentRef $document,
        public readonly string $date,
        public readonly CreditNoteReason|int $reason,
        public readonly array $items,
        public readonly array $payments,
        public readonly ?string $invoice = null,
        public readonly ?CreditNoteInvoiceData $invoiceData = null,
        public readonly ?CustomerRef $customer = null,
        public readonly ?int $seller = null,
        public readonly ?int $number = null,
        public readonly ?string $name = null,
        public readonly ?int $costCenter = null,
        public readonly ?Currency $currency = null,
        public readonly array $retentions = [],
        public readonly ?float $advancePayment = null,
        public readonly ?string $observations = null,
        public readonly ?StampCommand $stamp = null,
        public readonly ?MailCommand $mail = null,
        public readonly ?HealthcareCompany $healthcareCompany = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'document' => $this->document->toArray(),
            'number' => $this->number,
            'name' => $this->name,
            'date' => $this->date,
            'invoice' => $this->invoice,
            'invoice_data' => $this->invoiceData?->toArray(),
            'customer' => $this->customer?->toArray(),
            'seller' => $this->seller,
            'reason' => $this->reason instanceof CreditNoteReason ? $this->reason->value : $this->reason,
            'cost_center' => $this->costCenter,
            'currency' => $this->currency?->toArray(),
            'retentions' => $this->retentions,
            'advance_payment' => $this->advancePayment,
            'observations' => $this->observations,
            'items' => array_map(static fn (CreditNoteItem $item): array => $item->toArray(), $this->items),
            'payments' => array_map(static fn (CreditNotePayment $payment): array => $payment->toArray(), $this->payments),
            'stamp' => $this->stamp?->toArray(),
            'mail' => $this->mail?->toArray(),
            'healthcare_company' => $this->healthcareCompany?->toArray(),
        ], static fn (mixed $value): bool => $value !== null && $value !== []);
    }
}
