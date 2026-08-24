<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\DataTransferObjects\Invoices;

use Jonathan8312\Siigo\DataTransferObjects\Support\ArrayShape;

/**
 * The warehouse Siigo returns on an invoice item — the request only
 * sends a plain id ({@see InvoiceItem::$warehouse}).
 */
final class ItemWarehouseRef
{
    public function __construct(
        public readonly int $id,
        public readonly ?string $name,
    ) {}

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: ArrayShape::int($data, 'id'),
            name: ArrayShape::nullableString($data, 'name'),
        );
    }
}
