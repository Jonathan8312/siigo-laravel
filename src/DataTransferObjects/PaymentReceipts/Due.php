<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\DataTransferObjects\PaymentReceipts;

use Jonathan8312\Siigo\DataTransferObjects\Support\ArrayShape;

/**
 * The purchase invoice installment a `DebtPayment` item pays —
 * `{prefix, consecutive, quote, date}` identifies one due/quota of an
 * invoice already registered in Siigo. Identical shape sent and
 * received.
 */
final class Due
{
    public function __construct(
        public readonly string $prefix,
        public readonly int $consecutive,
        public readonly int $quote,
        public readonly string $date,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'prefix' => $this->prefix,
            'consecutive' => $this->consecutive,
            'quote' => $this->quote,
            'date' => $this->date,
        ];
    }

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            prefix: ArrayShape::string($data, 'prefix'),
            consecutive: ArrayShape::int($data, 'consecutive'),
            quote: ArrayShape::int($data, 'quote'),
            date: ArrayShape::string($data, 'date'),
        );
    }
}
