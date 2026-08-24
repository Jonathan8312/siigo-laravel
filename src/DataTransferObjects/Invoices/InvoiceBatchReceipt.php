<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\DataTransferObjects\Invoices;

use Jonathan8312\Siigo\DataTransferObjects\Support\ArrayShape;

/**
 * The immediate response of `POST /v1/invoices/batch` — confirmed
 * against real production usage (not yet independently confirmed
 * against the Apiary spec's own response example). Only an
 * acknowledgment that Siigo received the batch: `status` reflects
 * receipt (e.g. "Received"), not completion. The actual per-invoice
 * results arrive later via the webhook you provided as
 * `notificationUrl` — see {@see InvoiceBatchNotification}.
 */
final class InvoiceBatchReceipt
{
    public function __construct(
        public readonly string $id,
        public readonly ?string $status,
        public readonly ?string $receivedAt,
    ) {}

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: ArrayShape::string($data, 'id'),
            status: ArrayShape::nullableString($data, 'status'),
            receivedAt: ArrayShape::nullableString($data, 'received_at'),
        );
    }
}
