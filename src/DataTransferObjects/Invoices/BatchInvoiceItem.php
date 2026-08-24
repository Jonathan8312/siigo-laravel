<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\DataTransferObjects\Invoices;

use Jonathan8312\Siigo\Resources\Invoices;
use Jonathan8312\Siigo\Support\IdempotencyKey;

/**
 * One invoice inside a `POST /v1/invoices/batch` request: the same
 * shape as {@see InvoiceData}, plus an `idempotency_key` field embedded
 * in the body itself — unlike the singular `POST /v1/invoices`, where
 * idempotency travels as the `Idempotency-Key` HTTP header instead
 * (`Http\Client` does not send that header for the batch endpoint).
 * Siigo echoes this key back in the webhook notification so you can
 * match each result to the invoice you sent — see
 * {@see Invoices::createBatch()}.
 */
final class BatchInvoiceItem
{
    public function __construct(
        public readonly InvoiceData $invoice,
        public readonly string $idempotencyKey,
    ) {
        IdempotencyKey::assertValid($idempotencyKey);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [...$this->invoice->toArray(), 'idempotency_key' => $this->idempotencyKey];
    }
}
