<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\DataTransferObjects\Customers;

use Jonathan8312\Siigo\DataTransferObjects\Support\ArrayShape;

/**
 * DIAN tax-responsibility code, e.g. `R-99-PN` (not responsible). `name`
 * is documented in responses but not required when sending a request —
 * only `code` is validated by Siigo.
 */
final class FiscalResponsibility
{
    public function __construct(
        public readonly string $code,
        public readonly ?string $name = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'code' => $this->code,
            'name' => $this->name,
        ], static fn (mixed $value): bool => $value !== null);
    }

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
