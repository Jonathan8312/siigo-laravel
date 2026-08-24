<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\DataTransferObjects\Products;

use Jonathan8312\Siigo\DataTransferObjects\Support\ArrayShape;

/**
 * `ProductData::$accountGroup` sends a plain id; Siigo returns it
 * expanded into `{id, name}` (no `active` flag, unlike the full
 * `Catalogs\AccountGroup` entry) — response-only.
 */
final class AccountGroupRef
{
    public function __construct(
        public readonly int $id,
        public readonly ?string $name,
    ) {}

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: ArrayShape::int($data, 'id'),
            name: ArrayShape::nullableString($data, 'name'),
        );
    }
}
