<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\DataTransferObjects\Invoices;

use Jonathan8312\Siigo\DataTransferObjects\Support\ArrayShape;

/**
 * A line item as returned on an invoice response.
 */
final class InvoiceItemDetails
{
    /**
     * @param  list<InvoiceItemTax>  $taxes
     */
    public function __construct(
        public readonly ?string $id,
        public readonly string $code,
        public readonly float $quantity,
        public readonly float $price,
        public readonly ?int $seller,
        public readonly ?string $description,
        public readonly ?ItemDiscount $discount,
        public readonly array $taxes,
        public readonly ?ItemWarehouseRef $warehouse,
        public readonly ?float $total,
    ) {}

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $discount = $data['discount'] ?? null;
        $warehouse = $data['warehouse'] ?? null;
        $taxes = $data['taxes'] ?? null;
        $items = [];

        if (is_array($taxes)) {
            foreach ($taxes as $entry) {
                if (is_array($entry)) {
                    $items[] = InvoiceItemTax::fromArray($entry);
                }
            }
        }

        return new self(
            id: ArrayShape::nullableString($data, 'id'),
            code: ArrayShape::string($data, 'code'),
            quantity: ArrayShape::float($data, 'quantity'),
            price: ArrayShape::float($data, 'price'),
            seller: ArrayShape::nullableInt($data, 'seller'),
            description: ArrayShape::nullableString($data, 'description'),
            discount: is_array($discount) ? ItemDiscount::fromArray($discount) : null,
            taxes: $items,
            warehouse: is_array($warehouse) ? ItemWarehouseRef::fromArray($warehouse) : null,
            total: ArrayShape::nullableFloat($data, 'total'),
        );
    }
}
