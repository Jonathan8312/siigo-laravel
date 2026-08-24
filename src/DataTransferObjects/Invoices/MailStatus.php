<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\DataTransferObjects\Invoices;

use Jonathan8312\Siigo\DataTransferObjects\Support\ArrayShape;
use Jonathan8312\Siigo\Resources\Invoices;

/**
 * Email delivery status — used both for an invoice's embedded `mail`
 * field and as the response of {@see Invoices::mail()}
 * (identical shape).
 */
final class MailStatus
{
    public function __construct(
        public readonly ?string $status,
        public readonly ?string $observations,
    ) {}

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            status: ArrayShape::nullableString($data, 'status'),
            observations: ArrayShape::nullableString($data, 'observations'),
        );
    }
}
