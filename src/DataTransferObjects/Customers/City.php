<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\DataTransferObjects\Customers;

/**
 * The location codes sent when creating/updating a customer's address.
 * Siigo echoes these back enriched with human-readable names — see
 * {@see CityDetails} for that response-side shape.
 */
final class City
{
    public function __construct(
        public readonly string $countryCode,
        public readonly string $stateCode,
        public readonly string $cityCode,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'country_code' => $this->countryCode,
            'state_code' => $this->stateCode,
            'city_code' => $this->cityCode,
        ];
    }
}
