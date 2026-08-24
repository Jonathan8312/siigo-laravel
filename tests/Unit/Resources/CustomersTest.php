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
use Jonathan8312\Siigo\DataTransferObjects\Customers\Address;
use Jonathan8312\Siigo\DataTransferObjects\Customers\City;
use Jonathan8312\Siigo\DataTransferObjects\Customers\CustomerData;
use Jonathan8312\Siigo\DataTransferObjects\Customers\FiscalResponsibility;
use Jonathan8312\Siigo\Enums\PersonType;
use Jonathan8312\Siigo\Http\Client;
use Jonathan8312\Siigo\Http\ClientConfiguration;
use Jonathan8312\Siigo\Resources\Customers;
use PHPUnit\Framework\TestCase;

final class CustomersTest extends TestCase
{
    public function test_create_sends_the_payload_and_decodes_the_response(): void
    {
        $http = $this->fakeHttp();
        $http->fake(['https://api.siigo.test/v1/customers' => $http->response(['id' => 'abc-123', 'identification' => '900123456'], 201)]);

        $customer = $this->customers($http)->create($this->minimalCustomerData());

        $this->assertSame('abc-123', $customer->id);
        $http->assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && ($request['identification'] ?? null) === '900123456');
    }

    public function test_find_requests_the_given_id(): void
    {
        $http = $this->fakeHttp();
        $http->fake(['https://api.siigo.test/v1/customers/abc-123' => $http->response(['id' => 'abc-123'], 200)]);

        $customer = $this->customers($http)->find('abc-123');

        $this->assertSame('abc-123', $customer->id);
    }

    public function test_update_sends_a_put_with_the_full_payload(): void
    {
        $http = $this->fakeHttp();
        $http->fake(['https://api.siigo.test/v1/customers/abc-123' => $http->response(['id' => 'abc-123'], 200)]);

        $this->customers($http)->update('abc-123', $this->minimalCustomerData());

        $http->assertSent(fn (Request $request): bool => $request->method() === 'PUT'
            && $request->url() === 'https://api.siigo.test/v1/customers/abc-123');
    }

    public function test_delete_sends_a_delete_request(): void
    {
        $http = $this->fakeHttp();
        $http->fake(['https://api.siigo.test/v1/customers/abc-123' => $http->response([], 200)]);

        $this->customers($http)->delete('abc-123');

        $http->assertSent(fn (Request $request): bool => $request->method() === 'DELETE');
    }

    public function test_all_sends_only_the_filters_that_were_given(): void
    {
        $http = $this->fakeHttp();
        $http->fake(['https://api.siigo.test/v1/customers*' => $http->response(['pagination' => ['page' => 1, 'page_size' => 25, 'total_results' => 0], 'results' => []], 200)]);

        $this->customers($http)->all(identification: '900123456', active: true);

        $http->assertSent(fn (Request $request): bool => $request->method() === 'GET'
            && $request->url() === 'https://api.siigo.test/v1/customers?identification=900123456&active=true&page=1&page_size=25');
    }

    public function test_all_formats_date_filters_as_rfc3339(): void
    {
        $http = $this->fakeHttp();
        $http->fake(['https://api.siigo.test/v1/customers*' => $http->response(['results' => []], 200)]);

        $this->customers($http)->all(createdStart: new \DateTimeImmutable('2019-08-24T14:15:22+00:00'));

        $http->assertSent(fn (Request $request): bool => str_contains($request->url(), 'created_start=2019-08-24T14%3A15%3A22Z'));
    }

    private function minimalCustomerData(): CustomerData
    {
        return new CustomerData(
            personType: PersonType::Company,
            idType: '31',
            identification: '900123456',
            name: ['Acme SAS'],
            fiscalResponsibilities: [new FiscalResponsibility('O-13')],
            address: new Address('Cll. 1', new City('Co', '19', '19001')),
        );
    }

    private function fakeHttp(): HttpFactory
    {
        $http = new HttpFactory;
        $http->fake(['https://api.siigo.test/auth' => $http->response(['access_token' => 'jwt-value', 'expires_in' => 86400], 200)]);

        return $http;
    }

    private function customers(HttpFactory $http): Customers
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

        return new Customers($client);
    }
}
