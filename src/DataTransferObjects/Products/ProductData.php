<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\DataTransferObjects\Products;

use Jonathan8312\Siigo\Enums\ProductType;
use Jonathan8312\Siigo\Enums\TaxClassification;

/**
 * The payload sent to `POST /v1/products` and `PUT /v1/products/{id}`.
 *
 * `unit`, `taxes`, `accountGroup`, and `components` are request-shaped
 * on purpose — Siigo returns each of these expanded/enriched
 * differently on a {@see Product} response, a confirmed real asymmetry
 * (not a documentation error). See
 * docs/research/siigo-api-co/02-products.md.
 *
 * Updating `accountGroup` or a `Combo`'s `components` is rejected by
 * Siigo once the product has movements on a document — not enforced
 * client-side, since the SDK has no way to know a product's movement
 * history.
 */
final class ProductData
{
    /**
     * @param  list<ProductTax>  $taxes
     * @param  list<ProductPrice>  $prices
     * @param  list<ComboComponent>  $components  only meaningful when `type` is `Combo`
     */
    public function __construct(
        public readonly string $code,
        public readonly string $name,
        public readonly int $accountGroup,
        public readonly ProductType $type = ProductType::Product,
        public readonly bool $stockControl = false,
        public readonly bool $active = true,
        public readonly TaxClassification $taxClassification = TaxClassification::Taxed,
        public readonly bool $taxIncluded = false,
        public readonly ?float $taxConsumptionValue = null,
        public readonly array $taxes = [],
        public readonly array $prices = [],
        public readonly ?string $unit = null,
        public readonly ?string $unitLabel = null,
        public readonly ?string $reference = null,
        public readonly ?string $description = null,
        public readonly ?AdditionalFields $additionalFields = null,
        public readonly array $components = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'code' => $this->code,
            'name' => $this->name,
            'account_group' => $this->accountGroup,
            'type' => $this->type->value,
            'stock_control' => $this->stockControl,
            'active' => $this->active,
            'tax_classification' => $this->taxClassification->value,
            'tax_included' => $this->taxIncluded,
            'tax_consumption_value' => $this->taxConsumptionValue,
            'taxes' => array_map(static fn (ProductTax $tax): array => $tax->toArray(), $this->taxes),
            'prices' => array_map(static fn (ProductPrice $price): array => $price->toArray(), $this->prices),
            'unit' => $this->unit,
            'unit_label' => $this->unitLabel,
            'reference' => $this->reference,
            'description' => $this->description,
            'additional_fields' => $this->additionalFields?->toArray(),
            'components' => array_map(static fn (ComboComponent $component): array => $component->toArray(), $this->components),
        ], static fn (mixed $value): bool => $value !== null && $value !== []);
    }
}
