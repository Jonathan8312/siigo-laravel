<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\DataTransferObjects\Products;

use Jonathan8312\Siigo\DataTransferObjects\Support\ArrayShape;

final class AdditionalFields
{
    public function __construct(
        public readonly ?string $barcode = null,
        public readonly ?string $brand = null,
        public readonly ?string $tariff = null,
        public readonly ?string $model = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'barcode' => $this->barcode,
            'brand' => $this->brand,
            'tariff' => $this->tariff,
            'model' => $this->model,
        ], static fn (mixed $value): bool => $value !== null);
    }

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            barcode: ArrayShape::nullableString($data, 'barcode'),
            brand: ArrayShape::nullableString($data, 'brand'),
            tariff: ArrayShape::nullableString($data, 'tariff'),
            model: ArrayShape::nullableString($data, 'model'),
        );
    }
}
