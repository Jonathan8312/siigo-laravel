<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\DataTransferObjects\CreditNotes;

/**
 * A payment method sent when creating a credit note — `id` from
 * `Catalogs::paymentTypes('NC')`. Siigo returns the payment method's
 * name alongside the same fields — see {@see CreditNotePaymentDetails}.
 */
final class CreditNotePayment
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
