<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\DataTransferObjects\PaymentReceipts;

/**
 * The supplier a payment receipt pays — `identification` must already
 * exist in `$siigo->customers()` and be active (a "customer" record
 * with `type: Supplier`, see docs/customers.md). Siigo returns a
 * differently-shaped object for the same field — see
 * {@see SupplierSummary}.
 */
final class SupplierRef
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
