<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\DataTransferObjects\Customers;

use Jonathan8312\Siigo\DataTransferObjects\Support\ArrayShape;

final class Contact
{
    public function __construct(
        public readonly string $firstName,
        public readonly ?string $lastName = null,
        public readonly ?string $email = null,
        public readonly ?Phone $phone = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'email' => $this->email,
            'phone' => $this->phone?->toArray(),
        ], static fn (mixed $value): bool => $value !== null && $value !== []);
    }

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $phone = $data['phone'] ?? null;

        return new self(
            firstName: ArrayShape::string($data, 'first_name'),
            lastName: ArrayShape::nullableString($data, 'last_name'),
            email: ArrayShape::nullableString($data, 'email'),
            phone: is_array($phone) ? Phone::fromArray($phone) : null,
        );
    }
}
