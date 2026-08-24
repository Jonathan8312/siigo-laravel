<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\DataTransferObjects\CreditNotes;

use Jonathan8312\Siigo\Enums\CreditNoteReason;

/**
 * Replaces {@see CreditNoteData::$invoice} (a GUID) when the credit
 * note applies to a sales invoice that was never registered in Siigo.
 * Must be paired with {@see CreditNoteData::$customer} and
 * {@see CreditNoteData::$seller}. `number` and `cufe` are required
 * when `reason` is {@see CreditNoteReason::InvoiceAnnulment}
 * (code 2), optional otherwise. `date` must be earlier than the credit
 * note's own `date`.
 */
final class CreditNoteInvoiceData
{
    public function __construct(
        public readonly string $date,
        public readonly ?string $prefix = null,
        public readonly ?string $number = null,
        public readonly ?string $cufe = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'date' => $this->date,
            'prefix' => $this->prefix,
            'number' => $this->number,
            'cufe' => $this->cufe,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
