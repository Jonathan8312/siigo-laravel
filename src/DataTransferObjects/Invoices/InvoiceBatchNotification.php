<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\DataTransferObjects\Invoices;

use Jonathan8312\Siigo\DataTransferObjects\Support\ArrayShape;
use Jonathan8312\Siigo\Resources\Invoices;

/**
 * The payload Siigo sends to the `notificationUrl` you gave
 * {@see Invoices::createBatch()}, once
 * the whole batch finishes processing.
 *
 * The SDK does not register a route or listen for this itself — per
 * the project's webhooks are a later phase; wire your own route to
 * `notificationUrl` and call {@see self::fromArray()} on its JSON body:
 *
 * ```php
 * Route::post('/siigo/invoices-batch', function (Request $request) {
 *     $notification = InvoiceBatchNotification::fromArray($request->json()->all());
 *     foreach ($notification->results as $result) {
 *         // match $result->idempotencyKey back to your own record
 *     }
 * });
 * ```
 */
final class InvoiceBatchNotification
{
    /**
     * @param  list<BatchInvoiceResult>  $results
     */
    public function __construct(
        public readonly string $id,
        public readonly ?string $status,
        public readonly ?string $statusAt,
        public readonly ?string $notificationUrl,
        public readonly array $results,
    ) {}

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $invoices = $data['invoices'] ?? null;
        $results = [];

        if (is_array($invoices)) {
            foreach ($invoices as $entry) {
                if (is_array($entry)) {
                    $results[] = BatchInvoiceResult::fromArray($entry);
                }
            }
        }

        return new self(
            id: ArrayShape::string($data, 'id'),
            status: ArrayShape::nullableString($data, 'status'),
            statusAt: ArrayShape::nullableString($data, 'status_at'),
            notificationUrl: ArrayShape::nullableString($data, 'notification_url'),
            results: $results,
        );
    }
}
