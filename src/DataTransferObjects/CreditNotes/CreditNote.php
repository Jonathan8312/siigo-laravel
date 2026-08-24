<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\DataTransferObjects\CreditNotes;

use Jonathan8312\Siigo\DataTransferObjects\Support\ArrayShape;

/**
 * A credit note as returned by Siigo — from `POST`, `GET /{id}`, and
 * each entry of the `GET` list's `results[]`.
 */
final class CreditNote
{
    /**
     * @param  list<CreditNoteRetention>  $retentions
     * @param  list<CreditNoteItemDetails>  $items
     * @param  list<CreditNotePaymentDetails>  $payments
     */
    public function __construct(
        public readonly string $id,
        public readonly ?DocumentRef $document,
        public readonly ?int $number,
        public readonly ?string $name,
        public readonly ?string $date,
        public readonly ?CreditNoteInvoiceRef $invoice,
        public readonly ?CustomerSummary $customer,
        public readonly ?int $costCenter,
        public readonly ?Currency $currency,
        public readonly ?int $seller,
        public readonly array $retentions,
        public readonly ?float $advancePayment,
        public readonly ?float $total,
        public readonly ?string $observations,
        public readonly array $items,
        public readonly array $payments,
        public readonly ?CreditNoteStamp $stamp,
        public readonly ?CreditNoteMetadata $metadata,
    ) {}

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $document = $data['document'] ?? null;
        $invoice = $data['invoice'] ?? null;
        $customer = $data['customer'] ?? null;
        $currency = $data['currency'] ?? null;
        $stamp = $data['stamp'] ?? null;
        $metadata = $data['metadata'] ?? null;

        return new self(
            id: ArrayShape::string($data, 'id'),
            document: is_array($document) ? DocumentRef::fromArray($document) : null,
            number: ArrayShape::nullableInt($data, 'number'),
            name: ArrayShape::nullableString($data, 'name'),
            date: ArrayShape::nullableString($data, 'date'),
            invoice: is_array($invoice) ? CreditNoteInvoiceRef::fromArray($invoice) : null,
            customer: is_array($customer) ? CustomerSummary::fromArray($customer) : null,
            costCenter: ArrayShape::nullableInt($data, 'cost_center'),
            currency: is_array($currency) ? Currency::fromArray($currency) : null,
            seller: ArrayShape::nullableInt($data, 'seller'),
            retentions: self::mapList($data['retentions'] ?? null, CreditNoteRetention::fromArray(...)),
            advancePayment: ArrayShape::nullableFloat($data, 'advance_payment'),
            total: ArrayShape::nullableFloat($data, 'total'),
            observations: ArrayShape::nullableString($data, 'observations'),
            items: self::mapList($data['items'] ?? null, CreditNoteItemDetails::fromArray(...)),
            payments: self::mapList($data['payments'] ?? null, CreditNotePaymentDetails::fromArray(...)),
            stamp: is_array($stamp) ? CreditNoteStamp::fromArray($stamp) : null,
            metadata: is_array($metadata) ? CreditNoteMetadata::fromArray($metadata) : null,
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
