<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\DataTransferObjects\Invoices;

use Jonathan8312\Siigo\DataTransferObjects\Support\ArrayShape;

/**
 * The customer as returned on an invoice response — adds the customer's
 * own `id`, not present on the request side (see {@see CustomerRef}).
 */
final class CustomerSummary
{
    public function __construct(
        public readonly ?string $id,
        public readonly string $identification,
        public readonly int $branchOffice,
    ) {}

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: ArrayShape::nullableString($data, 'id'),
            identification: ArrayShape::string($data, 'identification'),
            branchOffice: ArrayShape::int($data, 'branch_office'),
        );
    }
}
