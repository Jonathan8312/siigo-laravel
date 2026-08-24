<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\DataTransferObjects\CreditNotes;

use Jonathan8312\Siigo\DataTransferObjects\Support\ArrayShape;
use Jonathan8312\Siigo\Enums\StampStatus;

/**
 * The electronic invoicing status Siigo returns on a credit note
 * response. `status` uses `tryFrom()` rather than `from()`, so an
 * undocumented future value never crashes the SDK. Unlike an invoice's
 * stamp, a credit note's is identified by `cude` (Código Único de
 * Documento Electrónico) rather than `cufe`, though Siigo's schema
 * documents both fields on this object — see docs/known-issues.md.
 */
final class CreditNoteStamp
{
    public function __construct(
        public readonly ?StampStatus $status,
        public readonly ?string $cufe,
        public readonly ?string $cude,
        public readonly ?string $observations,
        public readonly ?string $errors,
    ) {}

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $status = ArrayShape::nullableString($data, 'status');

        return new self(
            status: $status !== null ? StampStatus::tryFrom($status) : null,
            cufe: ArrayShape::nullableString($data, 'cufe'),
            cude: ArrayShape::nullableString($data, 'cude'),
            observations: ArrayShape::nullableString($data, 'observations'),
            errors: ArrayShape::nullableString($data, 'errors'),
        );
    }
}
