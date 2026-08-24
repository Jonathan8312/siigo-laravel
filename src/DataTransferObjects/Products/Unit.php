<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\DataTransferObjects\Products;

use Jonathan8312\Siigo\DataTransferObjects\Support\ArrayShape;

/**
 * Confirmed real asymmetry (not a documentation error): `unit` is sent
 * as a plain code string (e.g. `"94"`) on {@see ProductData}, but Siigo
 * returns it expanded into `{code, name}` — this class models only the
 * response side.
 */
final class Unit
{
    public function __construct(
        public readonly string $code,
        public readonly ?string $name,
    ) {}

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            code: ArrayShape::string($data, 'code'),
            name: ArrayShape::nullableString($data, 'name'),
        );
    }
}
