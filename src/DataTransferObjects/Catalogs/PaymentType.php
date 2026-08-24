<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\DataTransferObjects\Catalogs;

use Jonathan8312\Siigo\DataTransferObjects\Support\ArrayShape;
use Jonathan8312\Siigo\Enums\PaymentTypeApplicability;

/**
 * `GET /v1/payment-types` (requires `document_type`) — referenced in
 * sales invoices, credit notes, and vouchers.
 */
final class PaymentType
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly ?PaymentTypeApplicability $applicability,
        public readonly bool $active,
        public readonly bool $dueDate,
    ) {}

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $type = ArrayShape::nullableString($data, 'type');

        return new self(
            id: ArrayShape::int($data, 'id'),
            name: ArrayShape::string($data, 'name'),
            // tryFrom, not from: an undocumented future value from Siigo
            // should not crash the SDK, just come back as null.
            applicability: $type !== null ? PaymentTypeApplicability::tryFrom($type) : null,
            active: ArrayShape::bool($data, 'active'),
            dueDate: ArrayShape::bool($data, 'due_date'),
        );
    }
}
