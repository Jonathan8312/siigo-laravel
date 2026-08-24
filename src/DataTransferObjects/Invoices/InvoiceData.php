<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\DataTransferObjects\Invoices;

use Jonathan8312\Siigo\Resources\Customers;

/**
 * The payload sent to `POST /v1/invoices` and `PUT /v1/invoices/{id}`.
 *
 * `document.id`, `customer.identification`, `currency.code`, and a
 * manually-numbered document's `number` cannot be changed on update —
 * Siigo rejects the change rather than the SDK validating it upfront.
 * An invoice cannot be edited once it is being transmitted to the DIAN
 * or has already been accepted (has a CUFE); related documents (credit/
 * debit notes, cash receipts, portfolio adjustments) must be deleted
 * first. See docs/invoices.md and docs/known-issues.md.
 *
 * There is no confirmed way to create a customer inline — `customer`
 * must reference an existing, active customer via
 * {@see Customers}.
 */
final class InvoiceData
{
    /**
     * @param  list<InvoiceItem>  $items
     * @param  list<InvoicePayment>  $payments
     * @param  list<GlobalCharge>  $globalDiscounts
     * @param  list<int>  $retentions  Retention/withholding tax ids.
     */
    public function __construct(
        public readonly DocumentRef $document,
        public readonly string $date,
        public readonly CustomerRef $customer,
        public readonly int $seller,
        public readonly array $items,
        public readonly array $payments,
        public readonly ?int $number = null,
        public readonly ?int $costCenter = null,
        public readonly ?Currency $currency = null,
        public readonly ?string $observations = null,
        public readonly ?float $advancePayment = null,
        public readonly ?StampCommand $stamp = null,
        public readonly ?MailCommand $mail = null,
        public readonly array $globalDiscounts = [],
        public readonly array $retentions = [],
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
            'date' => $this->date,
            'customer' => $this->customer->toArray(),
            'seller' => $this->seller,
            'cost_center' => $this->costCenter,
            'currency' => $this->currency?->toArray(),
            'observations' => $this->observations,
            'advance_payment' => $this->advancePayment,
            'items' => array_map(static fn (InvoiceItem $item): array => $item->toArray(), $this->items),
            'payments' => array_map(static fn (InvoicePayment $payment): array => $payment->toArray(), $this->payments),
            'stamp' => $this->stamp?->toArray(),
            'mail' => $this->mail?->toArray(),
            'global_discounts' => array_map(static fn (GlobalCharge $charge): array => $charge->toArray(), $this->globalDiscounts),
            'retentions' => $this->retentions,
            'healthcare_company' => $this->healthcareCompany?->toArray(),
        ], static fn (mixed $value): bool => $value !== null && $value !== []);
    }
}
