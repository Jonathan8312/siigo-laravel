<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\DataTransferObjects\Catalogs;

use Jonathan8312\Siigo\DataTransferObjects\Support\ArrayShape;

/**
 * One entry of a {@see DocumentType}'s `global_discounts` or
 * `global_charges` list — both share the same shape.
 */
final class DocumentTypeCharge
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly float $percentage,
        public readonly bool $active,
    ) {}

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: ArrayShape::int($data, 'id'),
            name: ArrayShape::string($data, 'name'),
            percentage: ArrayShape::float($data, 'percentage'),
            active: ArrayShape::bool($data, 'active'),
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
