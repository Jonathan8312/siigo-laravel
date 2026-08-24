<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\Resources;

use Jonathan8312\Siigo\DataTransferObjects\Products\Product;
use Jonathan8312\Siigo\DataTransferObjects\Products\ProductData;
use Jonathan8312\Siigo\Enums\ProductType;
use Jonathan8312\Siigo\Http\Client;
use Jonathan8312\Siigo\Http\PaginatedResponse;

/**
 * `/v1/products` — create, list, find, update, and delete a company's
 * products/services. See docs/research/siigo-api-co/02-products.md.
 */
final class Products
{
    public function __construct(private readonly Client $client) {}

    public function create(ProductData $product): Product
    {
        $response = $this->client->post('v1/products', $product->toArray());

        return Product::fromArray($this->decode($response->json()));
    }

    public function find(string $id): Product
    {
        $response = $this->client->get("v1/products/{$id}");

        return Product::fromArray($this->decode($response->json()));
    }

    /**
     * A full replace, not a partial patch, matching Siigo's documented
     * behavior for the equivalent Customers endpoint (unconfirmed for
     * Products specifically — see docs/known-issues.md). `accountGroup`
     * and a Combo's `components` are rejected by Siigo once the product
     * has movements on a document.
     */
    public function update(string $id, ProductData $product): Product
    {
        $response = $this->client->put("v1/products/{$id}", $product->toArray());

        return Product::fromArray($this->decode($response->json()));
    }

    /**
     * @param  list<string>|null  $ids  Up to 20 product GUIDs.
     * @return PaginatedResponse<Product>
     */
    public function all(
        ?string $code = null,
        ?int $accountGroup = null,
        ?ProductType $type = null,
        ?bool $stockControl = null,
        ?bool $active = null,
        ?array $ids = null,
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
            'code' => $code,
            'account_group' => $accountGroup,
            'type' => $type?->value,
            'stock_control' => $stockControl !== null ? ($stockControl ? 'true' : 'false') : null,
            'active' => $active !== null ? ($active ? 'true' : 'false') : null,
            'ids' => $ids !== null && $ids !== [] ? implode(',', $ids) : null,
            'created_start' => self::toRfc3339($createdStart),
            'created_end' => self::toRfc3339($createdEnd),
            'date_start' => self::toRfc3339($dateStart),
            'date_end' => self::toRfc3339($dateEnd),
            'updated_start' => self::toRfc3339($updatedStart),
            'updated_end' => self::toRfc3339($updatedEnd),
            'page' => $page,
            'page_size' => $pageSize,
        ], static fn (mixed $value): bool => $value !== null);

        $response = $this->client->get('v1/products', $query);

        return PaginatedResponse::fromResponse($response, Product::fromArray(...));
    }

    /**
     * Siigo's documented response is `{id, deleted}` — returns whether
     * deletion succeeded rather than void, since (unlike Customers'
     * DELETE) this shape is confirmed. See docs/known-issues.md if
     * real-world testing finds otherwise.
     */
    public function delete(string $id): bool
    {
        $response = $this->client->delete("v1/products/{$id}");
        $json = $response->json();

        return ! is_array($json) || ($json['deleted'] ?? true) !== false;
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
