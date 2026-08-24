<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\DataTransferObjects\Catalogs;

use Jonathan8312\Siigo\DataTransferObjects\Support\ArrayShape;

/**
 * `GET /v1/cost-centers` — referenced by id in invoices, vouchers,
 * journals, and credit notes.
 */
final class CostCenter
{
    public function __construct(
        public readonly int $id,
        public readonly string $code,
        public readonly string $name,
        public readonly bool $active,
    ) {}

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: ArrayShape::int($data, 'id'),
            code: ArrayShape::string($data, 'code'),
            name: ArrayShape::string($data, 'name'),
            active: ArrayShape::bool($data, 'active'),
        );
    }
}
