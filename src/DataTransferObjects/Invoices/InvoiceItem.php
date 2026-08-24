<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\DataTransferObjects\Invoices;

/**
 * A line item sent when creating/updating an invoice. `discount` is a
 * plain number here — whether Siigo interprets it as a percentage or
 * an absolute value depends on the invoice's document type
 * (`Catalogs\DocumentType::$discountType`). `warehouse` is a plain id.
 * Siigo returns both enriched/expanded on the response — see
 * {@see InvoiceItemDetails}.
 */
final class InvoiceItem
{
    /**
     * @param  list<ItemTaxRef>  $taxes  Max 2 per item, no two of the same type.
     */
    public function __construct(
        public readonly string $code,
        public readonly float $quantity,
        public readonly float $price,
        public readonly ?string $description = null,
        public readonly ?float $discount = null,
        public readonly ?int $warehouse = null,
        public readonly array $taxes = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'code' => $this->code,
            'description' => $this->description,
            'quantity' => $this->quantity,
            'price' => $this->price,
            'discount' => $this->discount,
            'warehouse' => $this->warehouse,
            'taxes' => array_map(static fn (ItemTaxRef $tax): array => $tax->toArray(), $this->taxes),
        ], static fn (mixed $value): bool => $value !== null && $value !== []);
    }
}
