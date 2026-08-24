<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\Tests\Unit\Resources;

use Illuminate\Cache\CacheManager;
use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Foundation\Application;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\Request;
use Jonathan8312\Siigo\Auth\AuthCredentials;
use Jonathan8312\Siigo\Auth\AuthenticationManager;
use Jonathan8312\Siigo\Auth\CacheTokenRepository;
use Jonathan8312\Siigo\DataTransferObjects\Products\ProductData;
use Jonathan8312\Siigo\Http\Client;
use Jonathan8312\Siigo\Http\ClientConfiguration;
use Jonathan8312\Siigo\Resources\Products;
use PHPUnit\Framework\TestCase;

final class ProductsTest extends TestCase
{
    public function test_create_sends_the_payload_and_decodes_the_response(): void
    {
        $http = $this->fakeHttp();
        $http->fake(['https://api.siigo.test/v1/products' => $http->response(['id' => 'abc-123', 'code' => 'Item-1'], 201)]);

        $product = $this->products($http)->create(new ProductData('Item-1', 'Cotton shirt', 1253));

        $this->assertSame('abc-123', $product->id);
        $http->assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && ($request['code'] ?? null) === 'Item-1');
    }

    public function test_find_requests_the_given_id(): void
    {
        $http = $this->fakeHttp();
        $http->fake(['https://api.siigo.test/v1/products/abc-123' => $http->response(['id' => 'abc-123'], 200)]);

        $product = $this->products($http)->find('abc-123');

        $this->assertSame('abc-123', $product->id);
    }

    public function test_update_sends_a_put_with_the_full_payload(): void
    {
        $http = $this->fakeHttp();
        $http->fake(['https://api.siigo.test/v1/products/abc-123' => $http->response(['id' => 'abc-123'], 200)]);

        $this->products($http)->update('abc-123', new ProductData('Item-1', 'Cotton shirt', 1253));

        $http->assertSent(fn (Request $request): bool => $request->method() === 'PUT'
            && $request->url() === 'https://api.siigo.test/v1/products/abc-123');
    }

    public function test_delete_returns_true_when_siigo_confirms_deletion(): void
    {
        $http = $this->fakeHttp();
        $http->fake(['https://api.siigo.test/v1/products/abc-123' => $http->response(['id' => 'abc-123', 'deleted' => true], 200)]);

        $this->assertTrue($this->products($http)->delete('abc-123'));
    }

    public function test_delete_returns_false_when_siigo_reports_deleted_false(): void
    {
        $http = $this->fakeHttp();
        $http->fake(['https://api.siigo.test/v1/products/abc-123' => $http->response(['id' => 'abc-123', 'deleted' => false], 200)]);

        $this->assertFalse($this->products($http)->delete('abc-123'));
    }

    public function test_all_joins_ids_with_a_comma(): void
    {
        $http = $this->fakeHttp();
        $http->fake(['https://api.siigo.test/v1/products*' => $http->response(['results' => []], 200)]);

        $this->products($http)->all(ids: ['id-1', 'id-2']);

        $http->assertSent(fn (Request $request): bool => str_contains($request->url(), 'ids=id-1%2Cid-2'));
    }

    public function test_all_sends_only_the_filters_that_were_given(): void
    {
        $http = $this->fakeHttp();
        $http->fake(['https://api.siigo.test/v1/products*' => $http->response(['results' => []], 200)]);

        $this->products($http)->all(code: 'Item-1', active: true);

        $http->assertSent(fn (Request $request): bool => $request->url() === 'https://api.siigo.test/v1/products?code=Item-1&active=true&page=1&page_size=25');
    }

    private function fakeHttp(): HttpFactory
    {
        $http = new HttpFactory;
        $http->fake(['https://api.siigo.test/auth' => $http->response(['access_token' => 'jwt-value', 'expires_in' => 86400], 200)]);

        return $http;
    }

    private function products(HttpFactory $http): Products
    {
        $config = new ClientConfiguration(
            baseUrl: 'https://api.siigo.test',
            partnerId: 'TestingPartner',
            connectTimeout: 5.0,
            timeout: 15.0,
        );

        $app = new Application;
        $app['config'] = new ConfigRepository(['cache' => ['default' => 'array', 'stores' => ['array' => ['driver' => 'array']]]]);
        $tokens = new CacheTokenRepository(new CacheManager($app));

        $auth = new AuthenticationManager($http, new AuthCredentials('user@example.com', 'secret-key'), $config, $tokens);
        $client = new Client($http, $auth, $config);

        return new Products($client);
    }
}
