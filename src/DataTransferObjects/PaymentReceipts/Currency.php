<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\DataTransferObjects\PaymentReceipts;

use Jonathan8312\Siigo\DataTransferObjects\Support\ArrayShape;

/**
 * Foreign currency for the payment receipt, e.g. `{code: "USD",
 * exchange_rate: 3825.03}`. Identical shape sent and received.
 */
final class Currency
{
    public function __construct(
        public readonly string $code,
        public readonly ?float $exchangeRate = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'code' => $this->code,
            'exchange_rate' => $this->exchangeRate,
        ], static fn (mixed $value): bool => $value !== null);
    }

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            code: ArrayShape::string($data, 'code'),
            exchangeRate: ArrayShape::nullableFloat($data, 'exchange_rate'),
        );
    }
}
