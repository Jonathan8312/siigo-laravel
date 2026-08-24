<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\DataTransferObjects\Customers;

use Jonathan8312\Siigo\DataTransferObjects\Support\ArrayShape;

/**
 * `seller_id`/`collector_id` must reference a real id from
 * `Catalogs::users()` — the SDK does not validate this itself.
 */
final class RelatedUsers
{
    public function __construct(
        public readonly ?int $sellerId = null,
        public readonly ?int $collectorId = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'seller_id' => $this->sellerId,
            'collector_id' => $this->collectorId,
        ], static fn (mixed $value): bool => $value !== null);
    }

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            sellerId: ArrayShape::nullableInt($data, 'seller_id'),
            collectorId: ArrayShape::nullableInt($data, 'collector_id'),
        );
    }
}
