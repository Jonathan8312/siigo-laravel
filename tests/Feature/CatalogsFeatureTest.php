<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\Tests\Feature;

use Illuminate\Support\Facades\Http;
use Jonathan8312\Siigo\Siigo;
use Jonathan8312\Siigo\Tests\TestCase;

final class CatalogsFeatureTest extends TestCase
{
    public function test_catalogs_resolves_through_the_container_and_decodes_a_real_shaped_response(): void
    {
        Http::fake([
            'https://siigo.test/auth' => Http::response(['access_token' => 'jwt-value', 'expires_in' => 86400], 200),
            'https://siigo.test/v1/cost-centers*' => Http::response([
                ['id' => 13222, 'code' => '1112', 'name' => 'center', 'active' => true],
            ], 200),
        ]);

        $siigo = $this->app()->make(Siigo::class);
        $costCenters = $siigo->catalogs()->costCenters();

        $this->assertCount(1, $costCenters);
        $this->assertSame('center', $costCenters[0]->name);
    }

    public function test_catalogs_are_cached_by_default_through_the_container(): void
    {
        Http::fake([
            'https://siigo.test/auth' => Http::response(['access_token' => 'jwt-value', 'expires_in' => 86400], 200),
            'https://siigo.test/v1/taxes*' => Http::response([
                ['id' => 13156, 'name' => 'IVA 19%', 'type' => 'IVA', 'percentage' => 19, 'active' => true],
            ], 200),
        ]);

        $siigo = $this->app()->make(Siigo::class);
        $siigo->catalogs()->taxes();
        $siigo->catalogs()->taxes();

        Http::assertSentCount(2); // 1 auth + 1 taxes, not 2 taxes
    }

    public function test_catalog_caching_can_be_disabled_via_config(): void
    {
        $this->app()->make('config')->set('siigo.cache.catalog_ttl_seconds', 0);

        Http::fake([
            'https://siigo.test/auth' => Http::response(['access_token' => 'jwt-value', 'expires_in' => 86400], 200),
            'https://siigo.test/v1/taxes*' => Http::response([
                ['id' => 13156, 'name' => 'IVA 19%', 'type' => 'IVA', 'percentage' => 19, 'active' => true],
            ], 200),
        ]);

        $siigo = $this->app()->make(Siigo::class);
        $siigo->catalogs()->taxes();
        $siigo->catalogs()->taxes();

        Http::assertSentCount(3); // 1 auth + 2 taxes
    }
}
