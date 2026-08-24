<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\DataTransferObjects\Products;

use Jonathan8312\Siigo\DataTransferObjects\Support\ArrayShape;

/**
 * A tax as returned on a product response — expanded with `name`,
 * `type`, and `percentage`; `milliliters`/`rate` (request-only, see
 * {@see ProductTax}) are not echoed back.
 */
final class ProductTaxDetails
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $type,
        public readonly float $percentage,
    ) {}

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: ArrayShape::int($data, 'id'),
            name: ArrayShape::string($data, 'name'),
            type: ArrayShape::string($data, 'type'),
            percentage: ArrayShape::float($data, 'percentage'),
        );
    }
}
