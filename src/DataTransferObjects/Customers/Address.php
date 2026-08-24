<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\DataTransferObjects\Customers;

final class Address
{
    public function __construct(
        public readonly string $address,
        public readonly City $city,
        public readonly ?string $postalCode = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'address' => $this->address,
            'city' => $this->city->toArray(),
            'postal_code' => $this->postalCode,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
