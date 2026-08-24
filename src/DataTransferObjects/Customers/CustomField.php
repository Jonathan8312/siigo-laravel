<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\DataTransferObjects\Customers;

use Jonathan8312\Siigo\DataTransferObjects\Support\ArrayShape;

final class CustomField
{
    public function __construct(
        public readonly string $key,
        public readonly string $value,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return ['key' => $this->key, 'value' => $this->value];
    }

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            key: ArrayShape::string($data, 'key'),
            value: ArrayShape::string($data, 'value'),
        );
    }
}
