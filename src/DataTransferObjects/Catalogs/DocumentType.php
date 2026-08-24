<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\DataTransferObjects\Catalogs;

use Jonathan8312\Siigo\DataTransferObjects\Support\ArrayShape;

/**
 * `GET /v1/document-types` — the configuration behind each voucher type
 * (`FV` sales invoices, `FC` purchase invoices, `NC` credit notes, `RC`
 * cash receipts, ...). Referenced when creating an invoice (`document.id`).
 *
 * `type`, `discount_type`, and `electronic_type` are kept as plain
 * strings rather than enums: Siigo's docs give example values for each
 * but never publish the closed list, so enumerating them would be
 * guessing (see docs/research/siigo-api-co/01-catalogs.md).
 */
final class DocumentType
{
    public function __construct(
        public readonly int $id,
        public readonly string $code,
        public readonly string $name,
        public readonly ?string $description,
        public readonly string $type,
        public readonly bool $active,
        public readonly bool $sellerByItem,
        public readonly bool $costCenter,
        public readonly bool $costCenterMandatory,
        public readonly ?int $costCenterDefault,
        public readonly bool $automaticNumber,
        public readonly int $consecutive,
        public readonly ?string $discountType,
        public readonly bool $decimals,
        public readonly bool $advancePayment,
        public readonly bool $reteiva,
        public readonly bool $reteica,
        public readonly bool $selfWithholding,
        public readonly float $selfWithholdingLimit,
        public readonly ?string $electronicType,
        public readonly ?string $officialBook,
        public readonly bool $documentSupport,
        public readonly ?string $prefix,
        /** @var list<DocumentTypeCharge> */
        public readonly array $globalDiscounts,
        /** @var list<DocumentTypeCharge> */
        public readonly array $globalCharges,
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
            description: ArrayShape::nullableString($data, 'description'),
            type: ArrayShape::string($data, 'type'),
            active: ArrayShape::bool($data, 'active'),
            sellerByItem: ArrayShape::bool($data, 'seller_by_item'),
            costCenter: ArrayShape::bool($data, 'cost_center'),
            costCenterMandatory: ArrayShape::bool($data, 'cost_center_mandatory'),
            costCenterDefault: ArrayShape::nullableInt($data, 'cost_center_default'),
            automaticNumber: ArrayShape::bool($data, 'automatic_number'),
            consecutive: ArrayShape::int($data, 'consecutive'),
            discountType: ArrayShape::nullableString($data, 'discount_type'),
            decimals: ArrayShape::bool($data, 'decimals'),
            advancePayment: ArrayShape::bool($data, 'advance_payment'),
            reteiva: ArrayShape::bool($data, 'reteiva'),
            reteica: ArrayShape::bool($data, 'reteica'),
            selfWithholding: ArrayShape::bool($data, 'self_withholding'),
            selfWithholdingLimit: ArrayShape::float($data, 'self_withholding_limit'),
            electronicType: ArrayShape::nullableString($data, 'electronic_type'),
            officialBook: ArrayShape::nullableString($data, 'official_book'),
            documentSupport: ArrayShape::bool($data, 'document_support'),
            prefix: ArrayShape::nullableString($data, 'prefix'),
            globalDiscounts: DocumentTypeCharge::manyFromArray($data['global_discounts'] ?? null),
            globalCharges: DocumentTypeCharge::manyFromArray($data['global_charges'] ?? null),
        );
    }
}
