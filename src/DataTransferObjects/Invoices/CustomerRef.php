<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\DataTransferObjects\Invoices;

use Jonathan8312\Siigo\Resources\Customers;

/**
 * The customer reference sent when creating/updating an invoice — the
 * customer must already exist and be active (see
 * {@see Customers}). Siigo returns a
 * differently-shaped object for the same field — see {@see CustomerSummary}.
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
