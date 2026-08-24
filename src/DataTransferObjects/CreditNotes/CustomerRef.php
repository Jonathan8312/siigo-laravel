<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\DataTransferObjects\CreditNotes;

use Jonathan8312\Siigo\Resources\Customers;

/**
 * Required together with {@see CreditNoteInvoiceData} when the credit
 * note applies to an invoice that does not exist in Siigo — the
 * customer must already exist and be active (see {@see Customers}).
 * Not needed when referencing an existing invoice via
 * {@see CreditNoteData::$invoice}, since Siigo inherits the customer
 * from it. Siigo returns a differently-shaped object for the same
 * field — see {@see CustomerSummary}.
 */
final class CustomerRef
{
    public function __construct(
        public readonly string $identification,
        public readonly int $branchOffice = 0,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'identification' => $this->identification,
            'branch_office' => $this->branchOffice,
        ];
    }
}
