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
use Jonathan8312\Siigo\DataTransferObjects\Invoices\CustomerRef;
use Jonathan8312\Siigo\DataTransferObjects\Invoices\DocumentRef;
use Jonathan8312\Siigo\DataTransferObjects\Invoices\InvoiceData;
use Jonathan8312\Siigo\DataTransferObjects\Invoices\InvoiceItem;
use Jonathan8312\Siigo\DataTransferObjects\Invoices\InvoicePayment;
use Jonathan8312\Siigo\Http\Client;
use Jonathan8312\Siigo\Http\ClientConfiguration;
use Jonathan8312\Siigo\Resources\Invoices;
use PHPUnit\Framework\TestCase;

final class InvoicesTest extends TestCase
{
    public function test_create_sends_the_idempotency_key_and_decodes_the_response(): void
    {
        $http = $this->fakeHttp();
        $http->fake(['https://api.siigo.test/v1/invoices' => $http->response(['id' => 'abc-123', 'name' => 'FV-1-1'], 201)]);

        $invoice = $this->invoices($http)->create($this->minimalInvoiceData(), idempotencyKey: 'invoice1001');

        $this->assertSame('abc-123', $invoice->id);
        $http->assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && $request->hasHeader('Idempotency-Key', 'invoice1001'));
    }

    public function test_find_requests_the_given_id(): void
    {
        $http = $this->fakeHttp();
        $http->fake(['https://api.siigo.test/v1/invoices/abc-123' => $http->response(['id' => 'abc-123'], 200)]);

        $invoice = $this->invoices($http)->find('abc-123');

        $this->assertSame('abc-123', $invoice->id);
    }

    public function test_delete_and_annul_decode_the_deleted_flag(): void
    {
        $http = $this->fakeHttp();
        $http->fake([
            'https://api.siigo.test/v1/invoices/abc-123' => $http->response(['id' => 'abc-123', 'deleted' => true], 200),
            'https://api.siigo.test/v1/invoices/abc-123/annul' => $http->response(['id' => 'abc-123', 'deleted' => true], 200),
        ]);

        $invoices = $this->invoices($http);

        $this->assertTrue($invoices->delete('abc-123'));
        $this->assertTrue($invoices->annul('abc-123'));
    }

    public function test_annul_sends_no_body(): void
    {
        $http = $this->fakeHttp();
        $http->fake(['https://api.siigo.test/v1/invoices/abc-123/annul' => $http->response(['id' => 'abc-123', 'deleted' => true], 200)]);

        $this->invoices($http)->annul('abc-123');

        $http->assertSent(fn (Request $request): bool => $request->method() === 'POST' && $request->body() === '[]');
    }

    public function test_stamp_errors_extracts_the_message_list(): void
    {
        $http = $this->fakeHttp();
        $http->fake(['https://api.siigo.test/v1/invoices/abc-123/stamp/errors' => $http->response([
            'id' => 'abc-123',
            'errors' => [['message' => 'Missing customer tax id'], ['message' => 'Invalid item code']],
        ], 200)]);

        $errors = $this->invoices($http)->stampErrors('abc-123');

        $this->assertSame(['Missing customer tax id', 'Invalid item code'], $errors);
    }

    public function test_pdf_and_xml_decode_the_file_response(): void
    {
        $http = $this->fakeHttp();
        $http->fake([
            'https://api.siigo.test/v1/invoices/abc-123/pdf' => $http->response(['id' => 'abc-123', 'cufe' => 'cufe-1', 'base64' => 'AAA='], 200),
            'https://api.siigo.test/v1/invoices/abc-123/xml' => $http->response(['id' => 'abc-123', 'cufe' => 'cufe-1', 'base64' => 'BBB='], 200),
        ]);

        $invoices = $this->invoices($http);

        $this->assertSame('AAA=', $invoices->pdf('abc-123')->base64);
        $this->assertSame('BBB=', $invoices->xml('abc-123')->base64);
    }

    public function test_mail_sends_guid_and_recipients(): void
    {
        $http = $this->fakeHttp();
        $http->fake(['https://api.siigo.test/v1/invoices/abc-123/mail' => $http->response(['status' => 'Sent'], 200)]);

        $status = $this->invoices($http)->mail('abc-123', 'customer@example.com', 'cc@example.com');

        $this->assertSame('Sent', $status->status);
        $http->assertSent(fn (Request $request): bool => ($request['guid'] ?? null) === 'abc-123'
            && ($request['mail_to'] ?? null) === 'customer@example.com'
            && ($request['copy_to'] ?? null) === 'cc@example.com');
    }

    public function test_all_sends_only_the_filters_that_were_given(): void
    {
        $http = $this->fakeHttp();
        $http->fake(['https://api.siigo.test/v1/invoices*' => $http->response(['results' => []], 200)]);

        $this->invoices($http)->all(customerIdentification: '13832081');

        $http->assertSent(fn (Request $request): bool => $request->url() === 'https://api.siigo.test/v1/invoices?customer_identification=13832081&page=1&page_size=25');
    }

    private function minimalInvoiceData(): InvoiceData
    {
        return new InvoiceData(
            document: new DocumentRef(22),
            date: '2021-10-15',
            customer: new CustomerRef('13832081'),
            seller: 629,
            items: [new InvoiceItem(code: 'Item-1', quantity: 1, price: 10)],
            payments: [new InvoicePayment(5636, 10)],
        );
    }

    private function fakeHttp(): HttpFactory
    {
        $http = new HttpFactory;
        $http->fake(['https://api.siigo.test/auth' => $http->response(['access_token' => 'jwt-value', 'expires_in' => 86400], 200)]);

        return $http;
    }

    private function invoices(HttpFactory $http): Invoices
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

        return new Invoices($client);
    }
}
