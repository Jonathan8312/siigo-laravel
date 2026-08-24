<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\DataTransferObjects\Products;

use Jonathan8312\Siigo\DataTransferObjects\Support\ArrayShape;

/**
 * `position` (1-12, from `Catalogs::priceLists()`, not the list's own
 * `id`) and `value`. `name` is only ever present on a response — Siigo
 * derives it from `position` and never needs it sent back.
 */
final class PriceListEntry
{
    public function __construct(
        public readonly int $position,
        public readonly float $value,
        public readonly ?string $name = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'position' => $this->position,
            'value' => $this->value,
        ];
    }

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            position: ArrayShape::int($data, 'position'),
            value: ArrayShape::float($data, 'value'),
            name: ArrayShape::nullableString($data, 'name'),
        );
    }
}
