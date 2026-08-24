<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\DataTransferObjects\CreditNotes;

use Jonathan8312\Siigo\DataTransferObjects\Support\ArrayShape;

/**
 * The response of `GET /v1/credit-notes/{id}/pdf` — the file content
 * base64-encoded. Unlike `Invoices\InvoiceFile`, which carries `cufe`,
 * this carries `cude` (Código Único de Documento Electrónico), the
 * identifier used for credit/debit notes rather than invoices.
 */
final class CreditNoteFile
{
    public function __construct(
        public readonly string $id,
        public readonly ?string $cude,
        public readonly string $base64,
    ) {}

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: ArrayShape::string($data, 'id'),
            cude: ArrayShape::nullableString($data, 'cude'),
            base64: ArrayShape::string($data, 'base64'),
        );
    }
}
