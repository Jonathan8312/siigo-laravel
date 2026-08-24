<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\DataTransferObjects\CreditNotes;

/**
 * Optional healthcare-sector fields for `POST /v1/credit-notes`,
 * required since 2025-07-22 for electronic credit notes issued by
 * healthcare companies (Resolución 948). `operationType` is documented
 * as one of `SS-CUFE`, `SS-SinAporte`, `SS-Recaudo`; `paymentMethod`
 * and `servicePlan` are numeric codes whose full catalogs were
 * confirmed (`01`-`04` and `02`-`17` respectively) but kept as plain
 * strings rather than enums, matching
 * {@see \Jonathan8312\Siigo\DataTransferObjects\Invoices\HealthcareCompany}.
 */
final class HealthcareCompany
{
    public function __construct(
        public readonly string $operationType,
        public readonly ?string $periodStart = null,
        public readonly ?string $periodEnd = null,
        public readonly ?string $paymentMethod = null,
        public readonly ?string $servicePlan = null,
        public readonly ?string $contractNumber = null,
        public readonly ?string $policyNumber = null,
        public readonly ?string $nonContractInvoiceReason = null,
        public readonly ?float $copayment = null,
        public readonly ?float $coinsurance = null,
        public readonly ?float $costSharing = null,
        public readonly ?float $recoveryCharge = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'operation_type' => $this->operationType,
            'period_start' => $this->periodStart,
            'period_end' => $this->periodEnd,
            'payment_method' => $this->paymentMethod,
            'service_plan' => $this->servicePlan,
            'contract_number' => $this->contractNumber,
            'policy_number' => $this->policyNumber,
            'non_contract_invoice_reason' => $this->nonContractInvoiceReason,
            'copayment' => $this->copayment,
            'coinsurance' => $this->coinsurance,
            'cost_sharing' => $this->costSharing,
            'recovery_charge' => $this->recoveryCharge,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
