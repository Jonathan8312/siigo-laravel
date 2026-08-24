<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\DataTransferObjects\Customers;

use Jonathan8312\Siigo\DataTransferObjects\Support\ArrayShape;

/**
 * The location Siigo returns for a customer's address: the codes sent
 * (see {@see City}) enriched with the corresponding human-readable names.
 */
final class CityDetails
{
    public function __construct(
        public readonly string $countryCode,
        public readonly ?string $countryName,
        public readonly string $stateCode,
        public readonly ?string $stateName,
        public readonly string $cityCode,
        public readonly ?string $cityName,
    ) {}

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            countryCode: ArrayShape::string($data, 'country_code'),
            countryName: ArrayShape::nullableString($data, 'country_name'),
            stateCode: ArrayShape::string($data, 'state_code'),
            stateName: ArrayShape::nullableString($data, 'state_name'),
            cityCode: ArrayShape::string($data, 'city_code'),
            cityName: ArrayShape::nullableString($data, 'city_name'),
        );
    }
}
