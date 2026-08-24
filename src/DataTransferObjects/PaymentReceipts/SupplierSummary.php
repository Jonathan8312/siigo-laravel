<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\DataTransferObjects\PaymentReceipts;

use Jonathan8312\Siigo\DataTransferObjects\Support\ArrayShape;

/**
 * The supplier as returned on a payment receipt response — Siigo
 * expands {@see SupplierRef} into an object that also carries its
 * internal `id`.
 */
final class SupplierSummary
{
    public function __construct(
        public readonly ?string $id,
        public readonly ?string $identification,
        public readonly ?int $branchOffice,
    ) {}

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: ArrayShape::nullableString($data, 'id'),
            identification: ArrayShape::nullableString($data, 'identification'),
            branchOffice: ArrayShape::nullableInt($data, 'branch_office'),
        );
    }
}
