<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\DataTransferObjects\Products;

use Jonathan8312\Siigo\DataTransferObjects\Support\ArrayShape;

/**
 * A `Combo` component as returned on a product response: `{id, code,
 * name}` — `quantity` (request-only, see {@see ComboComponent}) is not
 * echoed back.
 */
final class ComboComponentDetails
{
    public function __construct(
        public readonly string $id,
        public readonly string $code,
        public readonly ?string $name,
    ) {}

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: ArrayShape::string($data, 'id'),
            code: ArrayShape::string($data, 'code'),
            name: ArrayShape::nullableString($data, 'name'),
        );
    }
}
