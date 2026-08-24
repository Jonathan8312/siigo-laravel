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
use Jonathan8312\Siigo\DataTransferObjects\PaymentReceipts\DocumentRef;
use Jonathan8312\Siigo\DataTransferObjects\PaymentReceipts\Payment;
use Jonathan8312\Siigo\DataTransferObjects\PaymentReceipts\PaymentReceiptData;
use Jonathan8312\Siigo\DataTransferObjects\PaymentReceipts\SupplierRef;
use Jonathan8312\Siigo\Enums\PaymentReceiptType;
use Jonathan8312\Siigo\Http\Client;
use Jonathan8312\Siigo\Http\ClientConfiguration;
use Jonathan8312\Siigo\Resources\PaymentReceipts;
use PHPUnit\Framework\TestCase;

final class PaymentReceiptsTest extends TestCase
{
    public function test_create_sends_the_idempotency_key_and_decodes_the_response(): void
    {
        $http = $this->fakeHttp();
        $http->fake(['https://api.siigo.test/v1/payment-receipts' => $http->response(['id' => 'abc-123', 'name' => 'RP-1-1051'], 201)]);

        $paymentReceipt = $this->paymentReceipts($http)->create($this->minimalPaymentReceiptData(), idempotencyKey: 'paymentreceipt1001');

        $this->assertSame('abc-123', $paymentReceipt->id);
        $http->assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && $request->hasHeader('Idempotency-Key', 'paymentreceipt1001'));
    }

    public function test_find_requests_the_given_id(): void
    {
        $http = $this->fakeHttp();
        $http->fake(['https://api.siigo.test/v1/payment-receipts/abc-123' => $http->response(['id' => 'abc-123'], 200)]);

        $paymentReceipt = $this->paymentReceipts($http)->find('abc-123');

        $this->assertSame('abc-123', $paymentReceipt->id);
    }

    public function test_update_sends_a_put_request(): void
    {
        $http = $this->fakeHttp();
        $http->fake(['https://api.siigo.test/v1/payment-receipts/abc-123' => $http->response(['id' => 'abc-123', 'observations' => 'updated'], 200)]);

        $paymentReceipt = $this->paymentReceipts($http)->update('abc-123', $this->minimalPaymentReceiptData());

        $this->assertSame('updated', $paymentReceipt->observations);
        $http->assertSent(fn (Request $request): bool => $request->method() === 'PUT');
    }

    public function test_delete_returns_true_when_siigo_confirms_deletion(): void
    {
        $http = $this->fakeHttp();
        $http->fake(['https://api.siigo.test/v1/payment-receipts/abc-123' => $http->response(['id' => 'abc-123', 'deleted' => true], 200)]);

        $this->assertTrue($this->paymentReceipts($http)->delete('abc-123'));
    }

    public function test_all_sends_only_the_filters_that_were_given(): void
    {
        $http = $this->fakeHttp();
        $http->fake(['https://api.siigo.test/v1/payment-receipts*' => $http->response(['results' => []], 200)]);

        $this->paymentReceipts($http)->all();

        $http->assertSent(fn (Request $request): bool => $request->url() === 'https://api.siigo.test/v1/payment-receipts?page=1&page_size=25');
    }

    private function minimalPaymentReceiptData(): PaymentReceiptData
    {
        return new PaymentReceiptData(
            document: new DocumentRef(28355),
            date: '2025-01-12',
            type: PaymentReceiptType::AdvancePayment,
            supplier: new SupplierRef('109048401'),
            payment: new Payment(5638, 10000),
        );
    }

    private function fakeHttp(): HttpFactory
    {
        $http = new HttpFactory;
        $http->fake(['https://api.siigo.test/auth' => $http->response(['access_token' => 'jwt-value', 'expires_in' => 86400], 200)]);

        return $http;
    }

    private function paymentReceipts(HttpFactory $http): PaymentReceipts
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

        return new PaymentReceipts($client);
    }
}
