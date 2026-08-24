<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\DataTransferObjects\CreditNotes;

use Jonathan8312\Siigo\Enums\CreditNoteTaxpayer;

/**
 * A line item sent when creating a credit note. `discount` is a plain
 * number here — whether Siigo interprets it as a percentage or an
 * absolute value depends on the invoice's document type. `warehouse`
 * is a plain id. Siigo returns both enriched/expanded on the response
 * — see {@see CreditNoteItemDetails}.
 *
 * `taxBase` and `taxpayer` are required together when `price` is `0`
 * — a gift/obsequio line — to state the item's real value for DIAN
 * purposes and who bears its VAT.
 */
final class CreditNoteItem
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
        public readonly ?float $taxBase = null,
        public readonly ?CreditNoteTaxpayer $taxpayer = null,
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
            'tax_base' => $this->taxBase,
            'taxpayer' => $this->taxpayer?->value,
        ], static fn (mixed $value): bool => $value !== null && $value !== []);
    }
}
