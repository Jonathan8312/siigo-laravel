<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\DataTransferObjects\CreditNotes;

use Jonathan8312\Siigo\DataTransferObjects\Support\ArrayShape;

/**
 * `document.id` — the credit note document type id from
 * `Catalogs::documentTypes('NC')`. Identical shape sent and received.
 */
final class DocumentRef
{
    public function __construct(
        public readonly int $id,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return ['id' => $this->id];
    }

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(id: ArrayShape::int($data, 'id'));
    }
}
