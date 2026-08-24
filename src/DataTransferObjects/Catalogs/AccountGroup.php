<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\DataTransferObjects\Catalogs;

use Jonathan8312\Siigo\DataTransferObjects\Support\ArrayShape;

/**
 * `GET /v1/account-groups` — inventory classification, referenced by
 * `account_group` when creating a product.
 */
final class AccountGroup
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
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
            active: ArrayShape::bool($data, 'active'),
        );
    }
}
