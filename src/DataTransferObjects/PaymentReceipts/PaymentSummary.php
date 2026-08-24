<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\DataTransferObjects\PaymentReceipts;

use Jonathan8312\Siigo\DataTransferObjects\Support\ArrayShape;

/**
 * The payment method as returned on a payment receipt response. Absent
 * entirely on some real sandbox records (e.g. an incomplete
 * `DebtPayment` with no `items[]`) — {@see PaymentReceipt::$payment} is
 * nullable to account for this.
 */
final class PaymentSummary
{
    public function __construct(
        public readonly int $id,
        public readonly ?string $name,
        public readonly float $value,
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
        );
    }
}
