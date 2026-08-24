<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\DataTransferObjects\PaymentReceipts;

/**
 * One accounting-entry line of a `Detailed` payment receipt. `due`
 * applies only to the line representing the payment against a purchase
 * invoice due; `tax` applies only to the line representing a tax
 * movement. Not verified against sandbox (a real, permanent write
 * would require valid chart-of-accounts codes this SDK has no way to
 * discover — see docs/known-issues.md) — modeled strictly from
 * docs/research/siigo-api-co/09-payment-receipts.md.
 */
final class DetailedItem
{
    public function __construct(
        public readonly AccountRef $account,
        public readonly string $description,
        public readonly float $value,
        public readonly ?Due $due = null,
        public readonly ?TaxRef $tax = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'account' => $this->account->toArray(),
            'due' => $this->due?->toArray(),
            'description' => $this->description,
            'value' => $this->value,
            'tax' => $this->tax?->toArray(),
        ], static fn (mixed $value): bool => $value !== null);
    }
}
