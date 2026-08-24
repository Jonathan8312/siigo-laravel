<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\DataTransferObjects\Invoices;

use Jonathan8312\Siigo\DataTransferObjects\Support\ArrayShape;

final class PurchaseOrderRef
{
    public function __construct(
        public readonly ?string $prefix,
        public readonly ?string $number,
    ) {}

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            prefix: ArrayShape::nullableString($data, 'prefix'),
            number: ArrayShape::nullableString($data, 'number'),
        );
    }
}
