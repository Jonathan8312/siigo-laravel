<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\DataTransferObjects\PaymentReceipts;

use Jonathan8312\Siigo\DataTransferObjects\Support\ArrayShape;

/**
 * Kept as a raw string rather than parsed into DateTimeImmutable — same
 * rationale as `Invoices\InvoiceMetadata`. Confirmed against sandbox
 * that only `created` is populated on `GET`/list responses.
 */
final class PaymentReceiptMetadata
{
    public function __construct(
        public readonly ?string $created,
    ) {}

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            created: ArrayShape::nullableString($data, 'created'),
        );
    }
}
