<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\DataTransferObjects\PaymentReceipts;

use Jonathan8312\Siigo\DataTransferObjects\Support\ArrayShape;

/**
 * One line of a `DebtPayment` payment receipt — the invoice due being
 * paid, and the amount applied to it. Not used for `AdvancePayment`
 * (no `items[]` sent) or `Detailed` (see {@see DetailedItem} instead).
 * Identical shape sent and received — confirmed against real sandbox
 * data.
 */
final class PaymentReceiptItem
{
    public function __construct(
        public readonly Due $due,
        public readonly float $value,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'due' => $this->due->toArray(),
            'value' => $this->value,
        ];
    }

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $due = $data['due'] ?? null;

        return new self(
            due: is_array($due) ? Due::fromArray($due) : new Due('', 0, 0, ''),
            value: ArrayShape::float($data, 'value'),
        );
    }
}
