<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\Tests\Unit\Resources;

use Illuminate\Cache\CacheManager;
use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Foundation\Application;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Jonathan8312\Siigo\Auth\AuthCredentials;
use Jonathan8312\Siigo\Auth\AuthenticationManager;
use Jonathan8312\Siigo\Auth\CacheTokenRepository;
use Jonathan8312\Siigo\DataTransferObjects\Catalogs\User;
use Jonathan8312\Siigo\Http\Client;
use Jonathan8312\Siigo\Http\ClientConfiguration;
use Jonathan8312\Siigo\Resources\Catalogs;
use Jonathan8312\Siigo\Support\CatalogCache;
use PHPUnit\Framework\TestCase;

final class CatalogsTest extends TestCase
{
    public function test_account_groups_maps_a_flat_array_response(): void
    {
        $http = $this->fakeHttp(['https://api.siigo.test/v1/account-groups*' => [
            ['id' => 1253, 'name' => 'Productos', 'active' => true],
        ]]);

        $groups = $this->catalogs($http)->accountGroups();

        $this->assertCount(1, $groups);
        $this->assertSame(1253, $groups[0]->id);
    }

    public function test_taxes_maps_a_flat_array_response(): void
    {
        $http = $this->fakeHttp(['https://api.siigo.test/v1/taxes*' => [
            ['id' => 13156, 'name' => 'IVA 19%', 'type' => 'IVA', 'percentage' => 19, 'active' => true],
        ]]);

        $taxes = $this->catalogs($http)->taxes();

        $this->assertCount(1, $taxes);
        $this->assertSame('IVA', $taxes[0]->type);
    }

    public function test_users_maps_the_paginated_envelope(): void
    {
        $http = $this->fakeHttp(['https://api.siigo.test/v1/users*' => [
            'pagination' => ['page' => 1, 'page_size' => 25, 'total_results' => 1],
            'results' => [['id' => 1, 'username' => 'a', 'first_name' => 'A', 'last_name' => 'B', 'email' => 'a@b.com', 'active' => true, 'identification' => '1']],
        ]]);

        $users = $this->catalogs($http)->users();

        $this->assertCount(1, $users->items);
        $this->assertInstanceOf(User::class, $users->items[0]);
        $this->assertSame(1, $users->totalResults);
    }

    public function test_users_sends_page_and_page_size_query_params(): void
    {
        $http = $this->fakeHttp(['https://api.siigo.test/v1/users*' => [
            'pagination' => ['page' => 2, 'page_size' => 5, 'total_results' => 0], 'results' => [],
        ]]);

        $this->catalogs($http)->users(page: 2, pageSize: 5);

        $http->assertSent(fn (Request $request): bool => $request->url() === 'https://api.siigo.test/v1/users?page=2&page_size=5');
    }

    public function test_document_types_sends_the_required_type_filter(): void
    {
        $http = $this->fakeHttp(['https://api.siigo.test/v1/document-types*' => []]);

        $this->catalogs($http)->documentTypes('FV');

        $http->assertSent(fn (Request $request): bool => $request->url() === 'https://api.siigo.test/v1/document-types?type=FV');
    }

    public function test_payment_types_sends_the_required_document_type(): void
    {
        $http = $this->fakeHttp(['https://api.siigo.test/v1/payment-types*' => []]);

        $this->catalogs($http)->paymentTypes('FV');

        $http->assertSent(fn (Request $request): bool => $request->url() === 'https://api.siigo.test/v1/payment-types?document_type=FV');
    }

    public function test_list_ignores_a_non_array_response_body(): void
    {
        $http = new HttpFactory;
        $http->fake(['https://api.siigo.test/auth' => $http->response(['access_token' => 'jwt', 'expires_in' => 86400], 200)]);
        $http->fake(['https://api.siigo.test/v1/cost-centers*' => $http->response('not-an-array-body', 200)]);

        $costCenters = $this->catalogs($http)->costCenters();

        $this->assertSame([], $costCenters);
    }

    public function test_a_second_call_is_served_from_cache_when_a_catalog_cache_is_configured(): void
    {
        $http = $this->fakeHttp(['https://api.siigo.test/v1/taxes*' => [
            ['id' => 13156, 'name' => 'IVA 19%', 'type' => 'IVA', 'percentage' => 19, 'active' => true],
        ]]);
        $cache = new CatalogCache(new CacheManager($this->cacheApp()), null, 3600);
        $catalogs = $this->catalogs($http, $cache, fn (): string => 'company-a');

        $catalogs->taxes();
        $catalogs->taxes();

        $this->assertCount(1, $this->taxesRequests($http));
    }

    public function test_different_credential_prefixes_do_not_share_the_cache(): void
    {
        $http = $this->fakeHttp(['https://api.siigo.test/v1/taxes*' => [
            ['id' => 13156, 'name' => 'IVA 19%', 'type' => 'IVA', 'percentage' => 19, 'active' => true],
        ]]);
        $cacheManager = new CacheManager($this->cacheApp());
        $cache = new CatalogCache($cacheManager, null, 3600);

        $this->catalogs($http, $cache, fn (): string => 'company-a')->taxes();
        $this->catalogs($http, $cache, fn (): string => 'company-b')->taxes();

        $this->assertCount(2, $this->taxesRequests($http));
    }

    /**
     * @return Collection<int, array{Request, Response|null}>
     */
    private function taxesRequests(HttpFactory $http): Collection
    {
        return $http->recorded(fn (Request $request): bool => str_contains($request->url(), 'v1/taxes'));
    }

    /**
     * @param  array<string, array<array-key, mixed>>  $urlToBody
     */
    private function fakeHttp(array $urlToBody): HttpFactory
    {
        $http = new HttpFactory;
        $http->fake(['https://api.siigo.test/auth' => $http->response(['access_token' => 'jwt-value', 'expires_in' => 86400], 200)]);

        foreach ($urlToBody as $url => $body) {
            $http->fake([$url => $http->response($body, 200)]);
        }

        return $http;
    }

    private function catalogs(HttpFactory $http, ?CatalogCache $cache = null, ?\Closure $cacheKeyPrefix = null): Catalogs
    {
        $config = new ClientConfiguration(
            baseUrl: 'https://api.siigo.test',
            partnerId: 'TestingPartner',
            connectTimeout: 5.0,
            timeout: 15.0,
        );

        $tokens = new CacheTokenRepository(new CacheManager($this->cacheApp()));

        $auth = new AuthenticationManager($http, new AuthCredentials('user@example.com', 'secret-key'), $config, $tokens);
        $client = new Client($http, $auth, $config);

        return new Catalogs($client, $cache, $cacheKeyPrefix);
    }

    private function cacheApp(): Application
    {
        $app = new Application;
        $app['config'] = new ConfigRepository(['cache' => ['default' => 'array', 'stores' => ['array' => ['driver' => 'array']]]]);

        return $app;
    }
}
