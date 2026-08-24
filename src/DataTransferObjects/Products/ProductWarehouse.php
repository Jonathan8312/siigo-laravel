<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\DataTransferObjects\Products;

use Jonathan8312\Siigo\DataTransferObjects\Support\ArrayShape;

/**
 * Stock for one warehouse on a product response — response-only,
 * there is no way to set stock directly through the product endpoints.
 * `quantity` is cast defensively: Siigo's docs disagree on whether it
 * is a `number` or a `string` (see docs/research/siigo-api-co/02-products.md).
 */
final class ProductWarehouse
{
    public function __construct(
        public readonly int $id,
        public readonly ?string $name,
        public readonly float $quantity,
    ) {}

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: ArrayShape::int($data, 'id'),
            name: ArrayShape::nullableString($data, 'name'),
            quantity: ArrayShape::float($data, 'quantity'),
        );
    }
}
