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
use Jonathan8312\Siigo\DataTransferObjects\Products\ProductData;
use Jonathan8312\Siigo\Exceptions\NotFoundException;
use Jonathan8312\Siigo\Http\Client;
use Jonathan8312\Siigo\Http\ClientConfiguration;
use Jonathan8312\Siigo\Resources\Catalogs;
use Jonathan8312\Siigo\Resources\Products;

/**
 * Fase 4 real-world verification. Like CustomersStagingTest, this
 * performs writes deliberately: it round-trips a product this test
 * itself creates (a random, never-reused code), using a real
 * account_group id fetched from the sandbox's own catalog rather than
 * a hardcoded guess, and cleans up after itself by deleting what it
 * created.
 */
final class ProductsStagingTest extends StagingTestCase
{
    public function test_creates_finds_updates_and_deletes_a_real_product(): void
    {
        $client = $this->client();
        $accountGroups = (new Catalogs($client))->accountGroups();

        if ($accountGroups === []) {
            $this->markTestSkipped('Sandbox account has no account groups configured; cannot run this test.');
        }

        $products = new Products($client);
        $code = 'SDKTEST'.random_int(100000, 999999);

        $created = $products->create(new ProductData(
            code: $code,
            name: 'Siigo Laravel SDK Test Product',
            accountGroup: $accountGroups[0]->id,
        ));

        $this->assertNotSame('', $created->id);
        $this->assertSame($code, $created->code);

        $found = $products->find($created->id);
        $this->assertSame($created->id, $found->id);

        $updated = $products->update($created->id, new ProductData(
            code: $code,
            name: 'Siigo Laravel SDK Test Product Updated',
            accountGroup: $accountGroups[0]->id,
        ));
        $this->assertSame('Siigo Laravel SDK Test Product Updated', $updated->name);

        $this->assertTrue($products->delete($created->id));

        $this->expectException(NotFoundException::class);
        $products->find($created->id);
    }

    private function client(): Client
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

        return new Client(new HttpFactory, $auth, $config);
    }
}
