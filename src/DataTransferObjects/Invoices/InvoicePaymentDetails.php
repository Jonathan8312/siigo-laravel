<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\DataTransferObjects\Invoices;

use Jonathan8312\Siigo\DataTransferObjects\Support\ArrayShape;

final class InvoicePaymentDetails
{
    public function __construct(
        public readonly int $id,
        public readonly ?string $name,
        public readonly float $value,
        public readonly ?string $dueDate,
    ) {}

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: ArrayShape::int($data, 'id'),
            name: ArrayShape::nullableString($data, 'name'),
            value: ArrayShape::float($data, 'value'),
            dueDate: ArrayShape::nullableString($data, 'due_date'),
        );
    }
}
