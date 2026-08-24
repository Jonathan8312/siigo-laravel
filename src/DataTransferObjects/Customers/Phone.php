<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\DataTransferObjects\Customers;

use Jonathan8312\Siigo\DataTransferObjects\Support\ArrayShape;

/**
 * Identical shape sent to and received from Siigo — used both as a
 * customer's own `phones[]` entry and as a `contacts[].phone`.
 */
final class Phone
{
    public function __construct(
        public readonly ?string $indicative = null,
        public readonly ?string $number = null,
        public readonly ?string $extension = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'indicative' => $this->indicative,
            'number' => $this->number,
            'extension' => $this->extension,
        ], static fn (mixed $value): bool => $value !== null);
    }

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            indicative: ArrayShape::nullableString($data, 'indicative'),
            number: ArrayShape::nullableString($data, 'number'),
            extension: ArrayShape::nullableString($data, 'extension'),
        );
    }
}
