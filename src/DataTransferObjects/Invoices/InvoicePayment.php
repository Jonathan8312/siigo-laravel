<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\DataTransferObjects\Invoices;

/**
 * A payment method sent when creating/updating an invoice — `id` from
 * `Catalogs::paymentTypes()`. Only one payment with `dueDate` is
 * allowed per invoice. Siigo returns the payment method's name
 * alongside the same fields — see {@see InvoicePaymentDetails}.
 */
final class InvoicePayment
{
    public function __construct(
        public readonly int $id,
        public readonly float $value,
        public readonly ?string $dueDate = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'id' => $this->id,
            'value' => $this->value,
            'due_date' => $this->dueDate,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
