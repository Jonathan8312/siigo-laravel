<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\Resources;

use Jonathan8312\Siigo\Auth\AuthenticationManager;
use Jonathan8312\Siigo\DataTransferObjects\Catalogs\AccountGroup;
use Jonathan8312\Siigo\DataTransferObjects\Catalogs\CostCenter;
use Jonathan8312\Siigo\DataTransferObjects\Catalogs\DocumentType;
use Jonathan8312\Siigo\DataTransferObjects\Catalogs\PaymentType;
use Jonathan8312\Siigo\DataTransferObjects\Catalogs\PriceList;
use Jonathan8312\Siigo\DataTransferObjects\Catalogs\Tax;
use Jonathan8312\Siigo\DataTransferObjects\Catalogs\User;
use Jonathan8312\Siigo\DataTransferObjects\Catalogs\Warehouse;
use Jonathan8312\Siigo\Http\Client;
use Jonathan8312\Siigo\Http\PaginatedResponse;
use Jonathan8312\Siigo\Support\CatalogCache;

/**
 * Siigo's read-only master/reference data — the catalogs Customers,
 * Products, and Invoices reference by id. See
 * docs/research/siigo-api-co/01-catalogs.md.
 *
 * `fixed-assets`, `expenses`, and `misc-incomes` are documented but
 * deliberately not implemented here: they are only relevant to journals
 * and vouchers, not yet implemented (see the project roadmap).
 *
 * Every method here is cached through {@see CatalogCache} when resolved
 * via `$siigo->catalogs()` (see `SIIGO_CATALOG_CACHE_TTL_SECONDS` in
 * config/siigo.php) — this data changes rarely, and an application
 * resolving it on every invoice/payment receipt would burn through
 * Siigo's rate limit fast. `$cache` and `$cacheKeyPrefix` are both
 * optional and default to no caching, so constructing this class
 * directly (as the test suite does) keeps working unchanged.
 */
final class Catalogs
{
    /**
     * @param  (\Closure(): string)|null  $cacheKeyPrefix  Lazily resolved so constructing this class never eagerly requires credentials — only an actual cached call does. Typically the current credentials' fingerprint, so cached data never leaks across companies in a multi-tenant app — see {@see AuthenticationManager::credentialsFingerprint()}.
     */
    public function __construct(
        private readonly Client $client,
        private readonly ?CatalogCache $cache = null,
        private readonly ?\Closure $cacheKeyPrefix = null,
    ) {}

    /**
     * @return list<AccountGroup>
     */
    public function accountGroups(): array
    {
        return $this->remember('account-groups', fn (): array => $this->list('v1/account-groups', AccountGroup::fromArray(...)));
    }

    /**
     * @return list<Tax>
     */
    public function taxes(): array
    {
        return $this->remember('taxes', fn (): array => $this->list('v1/taxes', Tax::fromArray(...)));
    }

    /**
     * @return list<PriceList>
     */
    public function priceLists(): array
    {
        return $this->remember('price-lists', fn (): array => $this->list('v1/price-lists', PriceList::fromArray(...)));
    }

    /**
     * @return list<Warehouse>
     */
    public function warehouses(): array
    {
        return $this->remember('warehouses', fn (): array => $this->list('v1/warehouses', Warehouse::fromArray(...)));
    }

    /**
     * Sellers/vendedores. The only catalog confirmed paginated.
     *
     * @return PaginatedResponse<User>
     */
    public function users(int $page = 1, int $pageSize = 25): PaginatedResponse
    {
        return $this->remember("users:page={$page}:page_size={$pageSize}", function () use ($page, $pageSize): PaginatedResponse {
            $response = $this->client->get('v1/users', ['page' => $page, 'page_size' => $pageSize]);

            return PaginatedResponse::fromResponse($response, User::fromArray(...));
        });
    }

    /**
     * @param  string  $type  Required by Siigo despite its docs marking it optional (see docs/known-issues.md) — e.g. "FV", "FC", "NC", "RC". Not documented as a closed list.
     * @return list<DocumentType>
     */
    public function documentTypes(string $type): array
    {
        return $this->remember("document-types:type={$type}", fn (): array => $this->list('v1/document-types', DocumentType::fromArray(...), ['type' => $type]));
    }

    /**
     * @param  string  $documentType  Required by Siigo, e.g. "FV" for sales invoices — available payment types depend on it.
     * @return list<PaymentType>
     */
    public function paymentTypes(string $documentType): array
    {
        return $this->remember("payment-types:document_type={$documentType}", fn (): array => $this->list('v1/payment-types', PaymentType::fromArray(...), ['document_type' => $documentType]));
    }

    /**
     * @return list<CostCenter>
     */
    public function costCenters(): array
    {
        return $this->remember('cost-centers', fn (): array => $this->list('v1/cost-centers', CostCenter::fromArray(...)));
    }

    /**
     * Every catalog except `users` responds with a flat JSON array
     * (no pagination envelope) — this decodes and maps that shape.
     *
     * @template TItem
     *
     * @param  \Closure(array<array-key, mixed>): TItem  $mapItem
     * @param  array<string, mixed>  $query
     * @return list<TItem>
     */
    private function list(string $path, \Closure $mapItem, array $query = []): array
    {
        $json = $this->client->get($path, $query)->json();
        $rows = is_array($json) ? $json : [];

        $items = [];

        foreach ($rows as $row) {
            if (is_array($row)) {
                $items[] = $mapItem($row);
            }
        }

        return $items;
    }

    /**
     * @template TValue
     *
     * @param  \Closure(): TValue  $resolve
     * @return TValue
     */
    private function remember(string $key, \Closure $resolve): mixed
    {
        if ($this->cache === null || $this->cacheKeyPrefix === null) {
            return $resolve();
        }

        return $this->cache->remember('siigo:catalogs:'.($this->cacheKeyPrefix)().':'.$key, $resolve);
    }
}
