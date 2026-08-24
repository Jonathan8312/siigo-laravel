<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\DataTransferObjects\Invoices;

use Jonathan8312\Siigo\DataTransferObjects\Customers\CustomerMetadata;
use Jonathan8312\Siigo\DataTransferObjects\Support\ArrayShape;

/**
 * Kept as raw strings rather than parsed into DateTimeImmutable — same
 * rationale as {@see CustomerMetadata}.
 */
final class InvoiceMetadata
{
    public function __construct(
        public readonly ?string $created,
        public readonly ?string $lastUpdated,
        public readonly ?string $stockUpdated,
    ) {}

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            created: ArrayShape::nullableString($data, 'created'),
            lastUpdated: ArrayShape::nullableString($data, 'last_updated'),
            stockUpdated: ArrayShape::nullableString($data, 'stock_updated'),
        );
    }
}
