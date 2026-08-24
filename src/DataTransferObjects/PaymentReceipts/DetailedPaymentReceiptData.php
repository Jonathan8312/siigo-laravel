<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\DataTransferObjects\PaymentReceipts;

use Jonathan8312\Siigo\Enums\PaymentReceiptType;

/**
 * The payload sent to `POST /v1/payment-receipts` for the advanced,
 * explicit-accounting-entries variant (`type: Detailed`) — an
 * alternative to {@see PaymentReceiptData} for postings that touch
 * multiple accounts (banks, dues, taxes) directly via
 * {@see self::$items}`[].account`, instead of the simple `due`/`payment`
 * model. `type` is fixed to `Detailed` and not a constructor parameter.
 *
 * Not verified against sandbox — see {@see DetailedItem}.
 */
final class DetailedPaymentReceiptData
{
    /**
     * @param  list<DetailedItem>  $items
     */
    public function __construct(
        public readonly DocumentRef $document,
        public readonly string $date,
        public readonly SupplierRef $supplier,
        public readonly array $items,
        public readonly ?int $number = null,
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
            'type' => PaymentReceiptType::Detailed->value,
            'supplier' => $this->supplier->toArray(),
            'items' => array_map(static fn (DetailedItem $item): array => $item->toArray(), $this->items),
            'observations' => $this->observations,
        ], static fn (mixed $value): bool => $value !== null && $value !== []);
    }
}
