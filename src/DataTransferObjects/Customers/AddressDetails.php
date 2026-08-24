<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\DataTransferObjects\Customers;

use Jonathan8312\Siigo\DataTransferObjects\Support\ArrayShape;

final class AddressDetails
{
    public function __construct(
        public readonly ?string $address,
        public readonly ?CityDetails $city,
        public readonly ?string $postalCode,
    ) {}

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $city = $data['city'] ?? null;

        return new self(
            address: ArrayShape::nullableString($data, 'address'),
            city: is_array($city) ? CityDetails::fromArray($city) : null,
            postalCode: ArrayShape::nullableString($data, 'postal_code'),
        );
    }
}
