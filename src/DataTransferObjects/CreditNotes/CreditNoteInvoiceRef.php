<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\DataTransferObjects\CreditNotes;

use Jonathan8312\Siigo\DataTransferObjects\Support\ArrayShape;

/**
 * The original invoice as returned on a credit note response — Siigo
 * expands the GUID sent on the request
 * ({@see CreditNoteData::$invoice}) into `{id, name}`. Absent when the
 * credit note was created via {@see CreditNoteInvoiceData} instead.
 */
final class CreditNoteInvoiceRef
{
    public function __construct(
        public readonly string $id,
        public readonly ?string $name,
    ) {}

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: ArrayShape::string($data, 'id'),
            name: ArrayShape::nullableString($data, 'name'),
        );
    }
}
