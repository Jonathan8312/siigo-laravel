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
use Jonathan8312\Siigo\DataTransferObjects\CreditNotes\CreditNoteData;
use Jonathan8312\Siigo\DataTransferObjects\CreditNotes\CreditNoteItem;
use Jonathan8312\Siigo\DataTransferObjects\CreditNotes\CreditNotePayment;
use Jonathan8312\Siigo\DataTransferObjects\CreditNotes\DocumentRef;
use Jonathan8312\Siigo\Http\Client;
use Jonathan8312\Siigo\Http\ClientConfiguration;
use Jonathan8312\Siigo\Resources\CreditNotes;
use PHPUnit\Framework\TestCase;

final class CreditNotesTest extends TestCase
{
    public function test_create_sends_the_idempotency_key_and_decodes_the_response(): void
    {
        $http = $this->fakeHttp();
        $http->fake(['https://api.siigo.test/v1/credit-notes' => $http->response(['id' => 'abc-123', 'name' => 'NC-2-22'], 201)]);

        $creditNote = $this->creditNotes($http)->create($this->minimalCreditNoteData(), idempotencyKey: 'creditnote1001');

        $this->assertSame('abc-123', $creditNote->id);
        $http->assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && $request->hasHeader('Idempotency-Key', 'creditnote1001'));
    }

    public function test_find_requests_the_given_id(): void
    {
        $http = $this->fakeHttp();
        $http->fake(['https://api.siigo.test/v1/credit-notes/abc-123' => $http->response(['id' => 'abc-123'], 200)]);

        $creditNote = $this->creditNotes($http)->find('abc-123');

        $this->assertSame('abc-123', $creditNote->id);
    }

    public function test_pdf_decodes_the_file_response(): void
    {
        $http = $this->fakeHttp();
        $http->fake(['https://api.siigo.test/v1/credit-notes/abc-123/pdf' => $http->response(['id' => 'abc-123', 'cude' => 'cude-1', 'base64' => 'AAA='], 200)]);

        $file = $this->creditNotes($http)->pdf('abc-123');

        $this->assertSame('cude-1', $file->cude);
        $this->assertSame('AAA=', $file->base64);
    }

    public function test_all_sends_only_the_filters_that_were_given(): void
    {
        $http = $this->fakeHttp();
        $http->fake(['https://api.siigo.test/v1/credit-notes*' => $http->response(['results' => []], 200)]);

        $this->creditNotes($http)->all(name: 'NC-1-1516');

        $http->assertSent(fn (Request $request): bool => $request->url() === 'https://api.siigo.test/v1/credit-notes?name=NC-1-1516&page=1&page_size=25');
    }

    private function minimalCreditNoteData(): CreditNoteData
    {
        return new CreditNoteData(
            document: new DocumentRef(22),
            date: '2021-10-15',
            reason: 1,
            items: [new CreditNoteItem(code: 'Item-1', quantity: 1, price: 10)],
            payments: [new CreditNotePayment(5636, 10)],
            invoice: '63f918c2-ca65-4edc-a7db-66bcdd5159fb',
        );
    }

    private function fakeHttp(): HttpFactory
    {
        $http = new HttpFactory;
        $http->fake(['https://api.siigo.test/auth' => $http->response(['access_token' => 'jwt-value', 'expires_in' => 86400], 200)]);

        return $http;
    }

    private function creditNotes(HttpFactory $http): CreditNotes
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

        return new CreditNotes($client);
    }
}
