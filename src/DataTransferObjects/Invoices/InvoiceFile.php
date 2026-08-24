<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\DataTransferObjects\Invoices;

use Jonathan8312\Siigo\DataTransferObjects\Support\ArrayShape;

/**
 * The response of both `GET /v1/invoices/{id}/pdf` and
 * `GET /v1/invoices/{id}/xml` — identical shape, the file content
 * base64-encoded.
 */
final class InvoiceFile
{
    public function __construct(
        public readonly string $id,
        public readonly ?string $cufe,
        public readonly string $base64,
    ) {}

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: ArrayShape::string($data, 'id'),
            cufe: ArrayShape::nullableString($data, 'cufe'),
            base64: ArrayShape::string($data, 'base64'),
        );
    }
}
