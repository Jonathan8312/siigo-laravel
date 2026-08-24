<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\DataTransferObjects\Invoices;

/**
 * Purchase order / delivery order references, seen on invoice list/get
 * responses. Not confirmed to be settable on `POST`/`PUT` — the request
 * body table for creating/updating an invoice never mentions
 * `additional_fields`, so {@see InvoiceData} does not expose it. Treat
 * as read-only until confirmed otherwise.
 */
final class AdditionalFields
{
    public function __construct(
        public readonly ?PurchaseOrderRef $purchaseOrder,
        public readonly ?DeliveryOrderRef $deliveryOrder,
    ) {}

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $purchaseOrder = $data['purchase_order'] ?? null;
        $deliveryOrder = $data['delivery_order'] ?? null;

        return new self(
            purchaseOrder: is_array($purchaseOrder) ? PurchaseOrderRef::fromArray($purchaseOrder) : null,
            deliveryOrder: is_array($deliveryOrder) ? DeliveryOrderRef::fromArray($deliveryOrder) : null,
        );
    }
}
