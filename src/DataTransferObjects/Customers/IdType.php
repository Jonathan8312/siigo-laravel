<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\DataTransferObjects\Customers;

use Jonathan8312\Siigo\DataTransferObjects\Support\ArrayShape;

/**
 * Siigo echoes the `id_type` code sent on creation back enriched with
 * its name (e.g. `"13"` -> "Cédula de ciudadanía"). No catalog endpoint
 * for identification types was confirmed during research, so the SDK
 * cannot validate `code` client-side — see docs/known-issues.md.
 */
final class IdType
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
