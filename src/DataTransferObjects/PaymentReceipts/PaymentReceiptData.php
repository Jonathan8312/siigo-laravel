<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\DataTransferObjects\PaymentReceipts;

use Jonathan8312\Siigo\Enums\PaymentReceiptType;

/**
 * The payload sent to `POST /v1/payment-receipts` (and `PUT
 * /v1/payment-receipts/{id}`, confirmed to exist against sandbox
 * despite `PUT` being undocumented in Siigo's `.apib` spec — see
 * docs/known-issues.md) for the two simple receipt types.
 *
 * For {@see PaymentReceiptType::DebtPayment}, {@see self::$items} is
 * required — one entry per invoice due being paid. For
 * {@see PaymentReceiptType::AdvancePayment}, leave `items` empty; there
 * is no invoice to reference.
 *
 * For the advanced accounting-entries variant
 * ({@see PaymentReceiptType::Detailed}), use
 * {@see DetailedPaymentReceiptData} instead.
 */
final class PaymentReceiptData
{
    /**
     * @param  list<PaymentReceiptItem>  $items  Required when `type` is DebtPayment, empty when AdvancePayment.
     */
    public function __construct(
        public readonly DocumentRef $document,
        public readonly string $date,
        public readonly PaymentReceiptType $type,
        public readonly SupplierRef $supplier,
        public readonly Payment $payment,
        public readonly array $items = [],
        public readonly ?int $number = null,
        public readonly ?int $costCenter = null,
        public readonly ?Currency $currency = null,
        public readonly ?string $observations = null,
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
            'type' => $this->type->value,
            'supplier' => $this->supplier->toArray(),
            'cost_center' => $this->costCenter,
            'currency' => $this->currency?->toArray(),
            'items' => array_map(static fn (PaymentReceiptItem $item): array => $item->toArray(), $this->items),
            'payment' => $this->payment->toArray(),
            'observations' => $this->observations,
        ], static fn (mixed $value): bool => $value !== null && $value !== []);
    }
}
