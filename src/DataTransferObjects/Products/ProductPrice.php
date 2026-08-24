<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\DataTransferObjects\Products;

use Jonathan8312\Siigo\DataTransferObjects\Support\ArrayShape;

final class ProductPrice
{
    /**
     * @param  list<PriceListEntry>  $priceList
     */
    public function __construct(
        public readonly string $currencyCode,
        public readonly array $priceList,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'currency_code' => $this->currencyCode,
            'price_list' => array_map(static fn (PriceListEntry $entry): array => $entry->toArray(), $this->priceList),
        ];
    }

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $priceList = $data['price_list'] ?? null;
        $entries = [];

        if (is_array($priceList)) {
            foreach ($priceList as $entry) {
                if (is_array($entry)) {
                    $entries[] = PriceListEntry::fromArray($entry);
                }
            }
        }

        return new self(
            currencyCode: ArrayShape::string($data, 'currency_code'),
            priceList: $entries,
        );
    }
}
