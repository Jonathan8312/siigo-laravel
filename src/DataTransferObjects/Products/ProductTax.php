<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\DataTransferObjects\Products;

/**
 * A tax reference sent when creating/updating a product. `milliliters`
 * and `rate` are only required when the referenced tax is a sugary
 * drinks tax — `rate` is documented as one of 18, 35, 28, 55, 38, 65.
 * Siigo returns a differently-shaped object for the same field on a
 * product response — see {@see ProductTaxDetails}.
 */
final class ProductTax
{
    public function __construct(
        public readonly int $id,
        public readonly ?float $milliliters = null,
        public readonly ?int $rate = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'id' => $this->id,
            'milliliters' => $this->milliliters,
            'rate' => $this->rate,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
