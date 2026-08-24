<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\Resources;

use Jonathan8312\Siigo\DataTransferObjects\Customers\Customer;
use Jonathan8312\Siigo\DataTransferObjects\Customers\CustomerData;
use Jonathan8312\Siigo\Enums\CustomerType;
use Jonathan8312\Siigo\Enums\PersonType;
use Jonathan8312\Siigo\Http\Client;
use Jonathan8312\Siigo\Http\PaginatedResponse;

/**
 * `/v1/customers` — create, list, find, update, and delete a company's
 * customers/third parties. See docs/research/siigo-api-co/03-customers.md.
 */
final class Customers
{
    public function __construct(private readonly Client $client) {}

    public function create(CustomerData $customer): Customer
    {
        $response = $this->client->post('v1/customers', $customer->toArray());

        return Customer::fromArray($this->decode($response->json()));
    }

    /**
     * @return PaginatedResponse<Customer>
     */
    public function all(
        ?string $identification = null,
        ?int $branchOffice = null,
        ?bool $active = null,
        ?CustomerType $type = null,
        ?PersonType $personType = null,
        ?\DateTimeInterface $createdStart = null,
        ?\DateTimeInterface $createdEnd = null,
        ?\DateTimeInterface $dateStart = null,
        ?\DateTimeInterface $dateEnd = null,
        ?\DateTimeInterface $updatedStart = null,
        ?\DateTimeInterface $updatedEnd = null,
        int $page = 1,
        int $pageSize = 25,
    ): PaginatedResponse {
        $query = array_filter([
            'identification' => $identification,
            'branch_office' => $branchOffice,
            'active' => $active !== null ? ($active ? 'true' : 'false') : null,
            'type' => $type?->value,
            'person_type' => $personType?->value,
            'created_start' => self::toRfc3339($createdStart),
            'created_end' => self::toRfc3339($createdEnd),
            'date_start' => self::toRfc3339($dateStart),
            'date_end' => self::toRfc3339($dateEnd),
            'updated_start' => self::toRfc3339($updatedStart),
            'updated_end' => self::toRfc3339($updatedEnd),
            'page' => $page,
            'page_size' => $pageSize,
        ], static fn (mixed $value): bool => $value !== null);

        $response = $this->client->get('v1/customers', $query);

        return PaginatedResponse::fromResponse($response, Customer::fromArray(...));
    }

    public function find(string $id): Customer
    {
        $response = $this->client->get("v1/customers/{$id}");

        return Customer::fromArray($this->decode($response->json()));
    }

    /**
     * A full replace, not a partial patch — see {@see CustomerData}.
     */
    public function update(string $id, CustomerData $customer): Customer
    {
        $response = $this->client->put("v1/customers/{$id}", $customer->toArray());

        return Customer::fromArray($this->decode($response->json()));
    }

    public function delete(string $id): void
    {
        $this->client->delete("v1/customers/{$id}");
    }

    /**
     * @return array<array-key, mixed>
     */
    private function decode(mixed $json): array
    {
        return is_array($json) ? $json : [];
    }

    private static function toRfc3339(?\DateTimeInterface $date): ?string
    {
        if ($date === null) {
            return null;
        }

        return (new \DateTimeImmutable('@'.$date->getTimestamp()))->format('Y-m-d\TH:i:s\Z');
    }
}
