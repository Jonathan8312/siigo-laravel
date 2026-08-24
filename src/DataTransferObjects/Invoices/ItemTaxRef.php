<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\DataTransferObjects\Invoices;

/**
 * A tax reference on an invoice item (request side) — max 2 per item,
 * no two of the same type. Siigo returns a differently-shaped, enriched
 * object for the same field — see {@see InvoiceItemTax}.
 */
final class ItemTaxRef
{
    public function __construct(
        public readonly int $id,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return ['id' => $this->id];
    }
}
