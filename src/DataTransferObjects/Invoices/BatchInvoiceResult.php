<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\DataTransferObjects\Invoices;

use Jonathan8312\Siigo\DataTransferObjects\Support\ArrayShape;
use Jonathan8312\Siigo\Exceptions\SiigoError;

/**
 * One invoice's outcome inside a batch webhook notification
 * ({@see InvoiceBatchNotification}). `idempotencyKey` matches the value
 * you sent on the corresponding {@see BatchInvoiceItem}. `statusCode`
 * `"201"` means success — `invoice` is populated and `errors` is empty;
 * any other value means failure — `invoice` is null and `errors`
 * describes why, in the same shape as a synchronous error response
 * (`SiigoError`), though the batch notification encodes it in
 * `snake_case` rather than the PascalCase used by synchronous error
 * responses (see docs/known-issues.md).
 */
final class BatchInvoiceResult
{
    /**
     * @param  list<SiigoError>  $errors
     */
    public function __construct(
        public readonly ?string $idempotencyKey,
        public readonly ?string $statusCode,
        public readonly ?Invoice $invoice,
        public readonly ?string $publicUrl,
        public readonly array $errors,
    ) {}

    public function successful(): bool
    {
        return $this->statusCode === '201';
    }

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $error = $data['error'] ?? null;
        $errors = [];

        if (is_array($error) && is_array($error['errors'] ?? null)) {
            foreach ($error['errors'] as $entry) {
                if (is_array($entry)) {
                    $errors[] = self::errorFromArray($entry);
                }
            }
        }

        $invoice = $data['id'] ?? null;

        return new self(
            idempotencyKey: ArrayShape::nullableString($data, 'idempotency_key'),
            statusCode: ArrayShape::nullableString($data, 'status_code'),
            invoice: is_string($invoice) ? Invoice::fromArray($data) : null,
            publicUrl: ArrayShape::nullableString($data, 'public_url'),
            errors: $errors,
        );
    }

    /**
     * @param  array<array-key, mixed>  $data
     */
    private static function errorFromArray(array $data): SiigoError
    {
        $params = is_array($data['params'] ?? null) ? $data['params'] : [];

        return new SiigoError(
            code: ArrayShape::nullableString($data, 'code'),
            message: ArrayShape::nullableString($data, 'message'),
            params: array_values(array_filter($params, 'is_string')),
            detail: ArrayShape::nullableString($data, 'detail'),
        );
    }
}
