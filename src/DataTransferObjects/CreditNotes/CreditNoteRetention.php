<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\DataTransferObjects\CreditNotes;

use Jonathan8312\Siigo\DataTransferObjects\Support\ArrayShape;

/**
 * A withholding tax entry on a credit note response. The request only
 * sends `retentions[]` as a plain list of ids ({@see CreditNoteData::$retentions}).
 */
final class CreditNoteRetention
{
    public function __construct(
        public readonly int $id,
        public readonly ?string $name,
        public readonly ?string $type,
        public readonly float $percentage,
        public readonly float $value,
    ) {}

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: ArrayShape::int($data, 'id'),
            name: ArrayShape::nullableString($data, 'name'),
            type: ArrayShape::nullableString($data, 'type'),
            percentage: ArrayShape::float($data, 'percentage'),
            value: ArrayShape::float($data, 'value'),
        );
    }
}
