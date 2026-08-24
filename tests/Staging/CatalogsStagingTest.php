<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\Tests\Staging;

use Illuminate\Cache\CacheManager;
use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Foundation\Application;
use Illuminate\Http\Client\Factory as HttpFactory;
use Jonathan8312\Siigo\Auth\AuthCredentials;
use Jonathan8312\Siigo\Auth\AuthenticationManager;
use Jonathan8312\Siigo\Auth\CacheTokenRepository;
use Jonathan8312\Siigo\DataTransferObjects\Catalogs\CostCenter;
use Jonathan8312\Siigo\Http\Client;
use Jonathan8312\Siigo\Http\ClientConfiguration;
use Jonathan8312\Siigo\Resources\Catalogs;

/**
 * Fase 2 real-world verification: catalog responses decode correctly
 * against the real Siigo sandbox, not just documented examples.
 */
final class CatalogsStagingTest extends StagingTestCase
{
    public function test_taxes_returns_real_decoded_entries(): void
    {
        $taxes = $this->catalogs()->taxes();

        $this->assertNotEmpty($taxes);
        $this->assertGreaterThan(0, $taxes[0]->id);
        $this->assertNotSame('', $taxes[0]->name);
    }

    public function test_cost_centers_returns_real_decoded_entries(): void
    {
        $costCenters = $this->catalogs()->costCenters();

        // A real runtime invariant PHPStan cannot prove statically from the
        // return type alone: no two decoded cost centers share an id.
        $ids = array_map(static fn (CostCenter $costCenter): int => $costCenter->id, $costCenters);
        $this->assertSame($ids, array_unique($ids));
    }

    public function test_document_types_returns_real_decoded_entries(): void
    {
        $documentTypes = $this->catalogs()->documentTypes('FV');

        $this->assertNotEmpty($documentTypes);
        $this->assertGreaterThan(0, $documentTypes[0]->id);
    }

    public function test_users_returns_a_real_paginated_response(): void
    {
        $users = $this->catalogs()->users(page: 1, pageSize: 5);

        $this->assertGreaterThanOrEqual(1, $users->page);
        $this->assertGreaterThanOrEqual(0, $users->totalResults);
    }

    private function catalogs(): Catalogs
    {
        $credentials = new AuthCredentials(self::env('SIIGO_USERNAME'), self::env('SIIGO_ACCESS_KEY'));

        $config = new ClientConfiguration(
            baseUrl: self::envOrDefault('SIIGO_BASE_URL', 'https://api.siigo.com'),
            partnerId: self::envOrDefault('SIIGO_PARTNER_ID', 'TREBOLDEV'),
            connectTimeout: 5.0,
            timeout: 30.0,
        );

        $app = new Application;
        $app['config'] = new ConfigRepository(['cache' => ['default' => 'array', 'stores' => ['array' => ['driver' => 'array']]]]);
        $tokens = new CacheTokenRepository(new CacheManager($app));

        $auth = new AuthenticationManager(new HttpFactory, $credentials, $config, $tokens);

        return new Catalogs(new Client(new HttpFactory, $auth, $config));
    }
}
