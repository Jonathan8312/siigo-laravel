<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\DataTransferObjects\Invoices;

use Jonathan8312\Siigo\DataTransferObjects\Support\ArrayShape;

/**
 * A sales invoice as returned by Siigo — from `POST`, `GET /{id}`,
 * `PUT`, and each entry of the `GET` list's `results[]`.
 */
final class Invoice
{
    /**
     * @param  list<InvoiceRetention>  $retentions
     * @param  list<InvoiceItemDetails>  $items
     * @param  list<GlobalChargeDetails>  $globalCharges
     * @param  list<GlobalChargeDetails>  $globalDiscounts
     * @param  list<InvoicePaymentDetails>  $payments
     */
    public function __construct(
        public readonly string $id,
        public readonly ?DocumentRef $document,
        public readonly ?string $prefix,
        public readonly ?int $number,
        public readonly ?string $name,
        public readonly ?string $date,
        public readonly ?CustomerSummary $customer,
        public readonly ?int $costCenter,
        public readonly ?Currency $currency,
        public readonly ?int $seller,
        public readonly array $retentions,
        public readonly ?float $advancePayment,
        public readonly ?float $total,
        public readonly ?float $balance,
        public readonly ?string $observations,
        public readonly array $items,
        public readonly array $globalCharges,
        public readonly array $globalDiscounts,
        public readonly array $payments,
        public readonly ?AdditionalFields $additionalFields,
        public readonly ?Stamp $stamp,
        public readonly ?MailStatus $mail,
        public readonly ?InvoiceMetadata $metadata,
        public readonly bool $annulled,
    ) {}

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $document = $data['document'] ?? null;
        $customer = $data['customer'] ?? null;
        $currency = $data['currency'] ?? null;
        $additionalFields = $data['additional_fields'] ?? null;
        $stamp = $data['stamp'] ?? null;
        $mail = $data['mail'] ?? null;
        $metadata = $data['metadata'] ?? null;

        return new self(
            id: ArrayShape::string($data, 'id'),
            document: is_array($document) ? DocumentRef::fromArray($document) : null,
            prefix: ArrayShape::nullableString($data, 'prefix'),
            number: ArrayShape::nullableInt($data, 'number'),
            name: ArrayShape::nullableString($data, 'name'),
            date: ArrayShape::nullableString($data, 'date'),
            customer: is_array($customer) ? CustomerSummary::fromArray($customer) : null,
            costCenter: ArrayShape::nullableInt($data, 'cost_center'),
            currency: is_array($currency) ? Currency::fromArray($currency) : null,
            seller: ArrayShape::nullableInt($data, 'seller'),
            retentions: self::mapList($data['retentions'] ?? null, InvoiceRetention::fromArray(...)),
            advancePayment: ArrayShape::nullableFloat($data, 'advance_payment'),
            total: ArrayShape::nullableFloat($data, 'total'),
            balance: ArrayShape::nullableFloat($data, 'balance'),
            observations: ArrayShape::nullableString($data, 'observations'),
            items: self::mapList($data['items'] ?? null, InvoiceItemDetails::fromArray(...)),
            globalCharges: GlobalChargeDetails::manyFromArray($data['global_charges'] ?? null),
            globalDiscounts: GlobalChargeDetails::manyFromArray($data['global_discounts'] ?? null),
            payments: self::mapList($data['payments'] ?? null, InvoicePaymentDetails::fromArray(...)),
            additionalFields: is_array($additionalFields) ? AdditionalFields::fromArray($additionalFields) : null,
            stamp: is_array($stamp) ? Stamp::fromArray($stamp) : null,
            mail: is_array($mail) ? MailStatus::fromArray($mail) : null,
            metadata: is_array($metadata) ? InvoiceMetadata::fromArray($metadata) : null,
            annulled: ArrayShape::bool($data, 'annulled'),
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
