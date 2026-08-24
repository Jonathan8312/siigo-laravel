<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\DataTransferObjects\Catalogs;

use Jonathan8312\Siigo\DataTransferObjects\Support\ArrayShape;

/**
 * `GET /v1/taxes` — referenced by id in product and invoice item taxes.
 */
final class Tax
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $type,
        public readonly float $percentage,
        public readonly bool $active,
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
            active: ArrayShape::bool($data, 'active'),
        );
    }
}
