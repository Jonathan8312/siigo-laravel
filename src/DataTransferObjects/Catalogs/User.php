<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\DataTransferObjects\Catalogs;

use Jonathan8312\Siigo\DataTransferObjects\Support\ArrayShape;

/**
 * `GET /v1/users` — sellers/vendedores, referenced by id in invoices
 * (`seller`) and in a customer's `related_users`. The only catalog
 * confirmed to return Siigo's standard paginated envelope.
 */
final class User
{
    public function __construct(
        public readonly int $id,
        public readonly string $username,
        public readonly string $firstName,
        public readonly string $lastName,
        public readonly string $email,
        public readonly bool $active,
        public readonly string $identification,
    ) {}

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: ArrayShape::int($data, 'id'),
            username: ArrayShape::string($data, 'username'),
            firstName: ArrayShape::string($data, 'first_name'),
            lastName: ArrayShape::string($data, 'last_name'),
            email: ArrayShape::string($data, 'email'),
            active: ArrayShape::bool($data, 'active'),
            identification: ArrayShape::string($data, 'identification'),
        );
    }
}
