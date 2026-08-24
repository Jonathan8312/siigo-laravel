<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\DataTransferObjects\PaymentReceipts;

use Jonathan8312\Siigo\DataTransferObjects\Support\ArrayShape;
use Jonathan8312\Siigo\Enums\PaymentReceiptType;

/**
 * A payment receipt as returned by Siigo — from `POST`, `PUT`, `GET
 * /{id}`, and each entry of the `GET` list's `results[]`.
 *
 * `type` uses `tryFrom()`, not `from()`, so an undocumented future
 * value from Siigo never throws — same rationale as `Invoices\Stamp`.
 * `payment` is nullable: confirmed against real sandbox data that it
 * can be entirely absent (e.g. an incomplete `DebtPayment` record with
 * no `items[]` either).
 */
final class PaymentReceipt
{
    /**
     * @param  list<PaymentReceiptItem>  $items
     */
    public function __construct(
        public readonly string $id,
        public readonly ?DocumentRef $document,
        public readonly ?int $number,
        public readonly ?string $name,
        public readonly ?string $date,
        public readonly ?PaymentReceiptType $type,
        public readonly ?SupplierSummary $supplier,
        public readonly ?int $costCenter,
        public readonly array $items,
        public readonly ?PaymentSummary $payment,
        public readonly ?string $observations,
        public readonly ?PaymentReceiptMetadata $metadata,
    ) {}

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $document = $data['document'] ?? null;
        $supplier = $data['supplier'] ?? null;
        $payment = $data['payment'] ?? null;
        $metadata = $data['metadata'] ?? null;
        $type = $data['type'] ?? null;

        return new self(
            id: ArrayShape::string($data, 'id'),
            document: is_array($document) ? DocumentRef::fromArray($document) : null,
            number: ArrayShape::nullableInt($data, 'number'),
            name: ArrayShape::nullableString($data, 'name'),
            date: ArrayShape::nullableString($data, 'date'),
            type: is_string($type) ? PaymentReceiptType::tryFrom($type) : null,
            supplier: is_array($supplier) ? SupplierSummary::fromArray($supplier) : null,
            costCenter: ArrayShape::nullableInt($data, 'cost_center'),
            items: self::mapList($data['items'] ?? null, PaymentReceiptItem::fromArray(...)),
            payment: is_array($payment) ? PaymentSummary::fromArray($payment) : null,
            observations: ArrayShape::nullableString($data, 'observations'),
            metadata: is_array($metadata) ? PaymentReceiptMetadata::fromArray($metadata) : null,
        );
    }

    /**
     * @template TItem
     *
     * @param  \Closure(array<array-key, mixed>): TItem  $mapItem
     * @return list<TItem>
     */
    private static function mapList(mixed $json, \Closure $mapItem): array
    {
        if (! is_array($json)) {
            return [];
        }

        $items = [];

        foreach ($json as $entry) {
            if (is_array($entry)) {
                $items[] = $mapItem($entry);
            }
        }

        return $items;
    }
}
