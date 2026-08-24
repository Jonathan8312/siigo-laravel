<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\DataTransferObjects\Invoices;

use Jonathan8312\Siigo\DataTransferObjects\Support\ArrayShape;

/**
 * An item's discount as returned on an invoice response — Siigo expands
 * the plain number sent on the request ({@see InvoiceItem::$discount})
 * into `{percentage, value}`.
 */
final class ItemDiscount
{
    public function __construct(
        public readonly float $percentage,
        public readonly float $value,
    ) {}

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            percentage: ArrayShape::float($data, 'percentage'),
            value: ArrayShape::float($data, 'value'),
        );
    }
}
