<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\DataTransferObjects\Invoices;

/**
 * A document-level discount sent when creating/updating an invoice
 * (`global_discounts[]`). Siigo returns an enriched, differently-shaped
 * object for both `global_discounts` and `global_charges` — see
 * {@see GlobalChargeDetails}.
 */
final class GlobalCharge
{
    public function __construct(
        public readonly int $id,
        public readonly float $percentage,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return ['id' => $this->id, 'percentage' => $this->percentage];
    }
}
