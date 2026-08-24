<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\DataTransferObjects\Products;

use Jonathan8312\Siigo\DataTransferObjects\Support\ArrayShape;
use Jonathan8312\Siigo\Enums\ProductType;
use Jonathan8312\Siigo\Enums\TaxClassification;

/**
 * A product/service as returned by Siigo — from `POST`, `GET /{id}`,
 * `PUT`, and each entry of the `GET` list's `results[]`.
 */
final class Product
{
    /**
     * @param  list<ProductTaxDetails>  $taxes
     * @param  list<ProductPrice>  $prices
     * @param  list<ProductWarehouse>  $warehouses
     * @param  list<ComboComponentDetails>  $components
     */
    public function __construct(
        public readonly string $id,
        public readonly string $code,
        public readonly string $name,
        public readonly ?AccountGroupRef $accountGroup,
        public readonly ProductType $type,
        public readonly bool $stockControl,
        public readonly bool $active,
        public readonly ?TaxClassification $taxClassification,
        public readonly bool $taxIncluded,
        public readonly ?float $taxConsumptionValue,
        public readonly array $taxes,
        public readonly array $prices,
        public readonly ?Unit $unit,
        public readonly ?string $unitLabel,
        public readonly ?string $reference,
        public readonly ?string $description,
        public readonly ?AdditionalFields $additionalFields,
        public readonly ?float $availableQuantity,
        public readonly array $warehouses,
        public readonly ?ProductMetadata $metadata,
        public readonly array $components,
    ) {}

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $accountGroup = $data['account_group'] ?? null;
        $unit = $data['unit'] ?? null;
        $additionalFields = $data['additional_fields'] ?? null;
        $metadata = $data['metadata'] ?? null;

        return new self(
            id: ArrayShape::string($data, 'id'),
            code: ArrayShape::string($data, 'code'),
            name: ArrayShape::string($data, 'name'),
            accountGroup: is_array($accountGroup) ? AccountGroupRef::fromArray($accountGroup) : null,
            type: ProductType::tryFrom(ArrayShape::string($data, 'type')) ?? ProductType::Product,
            stockControl: ArrayShape::bool($data, 'stock_control'),
            active: ArrayShape::bool($data, 'active', true),
            taxClassification: TaxClassification::tryFrom(ArrayShape::string($data, 'tax_classification')),
            taxIncluded: ArrayShape::bool($data, 'tax_included'),
            taxConsumptionValue: ArrayShape::nullableFloat($data, 'tax_consumption_value'),
            taxes: self::mapList($data['taxes'] ?? null, ProductTaxDetails::fromArray(...)),
            prices: self::mapList($data['prices'] ?? null, ProductPrice::fromArray(...)),
            unit: is_array($unit) ? Unit::fromArray($unit) : null,
            unitLabel: ArrayShape::nullableString($data, 'unit_label'),
            reference: ArrayShape::nullableString($data, 'reference'),
            description: ArrayShape::nullableString($data, 'description'),
            additionalFields: is_array($additionalFields) ? AdditionalFields::fromArray($additionalFields) : null,
            availableQuantity: ArrayShape::nullableFloat($data, 'available_quantity'),
            warehouses: self::mapList($data['warehouses'] ?? null, ProductWarehouse::fromArray(...)),
            metadata: is_array($metadata) ? ProductMetadata::fromArray($metadata) : null,
            components: self::mapList($data['components'] ?? null, ComboComponentDetails::fromArray(...)),
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
