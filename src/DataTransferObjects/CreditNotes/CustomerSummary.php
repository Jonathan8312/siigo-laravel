<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\DataTransferObjects\CreditNotes;

use Jonathan8312\Siigo\DataTransferObjects\Support\ArrayShape;

/**
 * The customer as returned on a credit note response, inherited from
 * the original invoice (or from {@see CustomerRef} when the credit note
 * was created against an unregistered invoice).
 */
final class CustomerSummary
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
