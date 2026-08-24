<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\DataTransferObjects\Catalogs;

use Jonathan8312\Siigo\DataTransferObjects\Support\ArrayShape;

/**
 * `GET /v1/price-lists` — a product can carry up to 12; `position` (not
 * `id`) is the value sent back in `products.prices[].price_list[].position`.
 */
final class PriceList
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly bool $active,
        public readonly int $position,
    ) {}

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: ArrayShape::int($data, 'id'),
            name: ArrayShape::string($data, 'name'),
            active: ArrayShape::bool($data, 'active'),
            position: ArrayShape::int($data, 'position'),
        );
    }
}
