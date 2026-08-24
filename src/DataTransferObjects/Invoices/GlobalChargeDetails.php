<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\DataTransferObjects\Invoices;

use Jonathan8312\Siigo\DataTransferObjects\Support\ArrayShape;

/**
 * One entry of an invoice's `global_charges` or `global_discounts` on
 * the response — both share this shape.
 */
final class GlobalChargeDetails
{
    public function __construct(
        public readonly int $id,
        public readonly ?string $name,
        public readonly float $percentage,
        public readonly float $value,
    ) {}

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: ArrayShape::int($data, 'id'),
            name: ArrayShape::nullableString($data, 'name'),
            percentage: ArrayShape::float($data, 'percentage'),
            value: ArrayShape::float($data, 'value'),
        );
    }

    /**
     * @return list<self>
     */
    public static function manyFromArray(mixed $json): array
    {
        if (! is_array($json)) {
            return [];
        }

        $items = [];

        foreach ($json as $entry) {
            if (is_array($entry)) {
                $items[] = self::fromArray($entry);
            }
        }

        return $items;
    }
}
