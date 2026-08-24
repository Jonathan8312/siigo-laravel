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
use Jonathan8312\Siigo\DataTransferObjects\Invoices\CustomerRef;
use Jonathan8312\Siigo\DataTransferObjects\Invoices\DocumentRef;
use Jonathan8312\Siigo\DataTransferObjects\Invoices\InvoiceData;
use Jonathan8312\Siigo\DataTransferObjects\Invoices\InvoiceItem;
use Jonathan8312\Siigo\DataTransferObjects\Invoices\InvoicePayment;
use Jonathan8312\Siigo\Exceptions\NotFoundException;
use Jonathan8312\Siigo\Exceptions\RequestException;
use Jonathan8312\Siigo\Exceptions\ValidationException;
use Jonathan8312\Siigo\Http\Client;
use Jonathan8312\Siigo\Http\ClientConfiguration;
use Jonathan8312\Siigo\Resources\Catalogs;
use Jonathan8312\Siigo\Resources\Customers;
use Jonathan8312\Siigo\Resources\Invoices;
use Jonathan8312\Siigo\Resources\Products;

/**
 * Fase 5 real-world verification. Deliberately reuses existing sandbox
 * data (an active customer, an active product) instead of creating new
 * ones, to avoid piling up more orphaned test records — and never sends
 * `stamp.send: true`, so this never actually submits to the real DIAN.
 * Cleanup (delete) is attempted but, like CustomersStagingTest, treated
 * as a documented limitation rather than a failure if Siigo rejects it.
 */
final class InvoicesStagingTest extends StagingTestCase
{
    public function test_creates_finds_and_deletes_a_real_invoice(): void
    {
        $client = $this->client();
        $catalogs = new Catalogs($client);

        $documentTypes = $catalogs->documentTypes('FV');
        $activeDocumentType = null;
        foreach ($documentTypes as $documentType) {
            // A document type with automatic_number: false requires an
            // explicit `number` the SDK cannot safely guess (it would
            // need to know the account's next free consecutive) — skip
            // those and only use one that numbers itself.
            if ($documentType->active && $documentType->automaticNumber) {
                $activeDocumentType = $documentType;
                break;
            }
        }
        if ($activeDocumentType === null) {
            $this->markTestSkipped('Sandbox account has no active, automatically-numbered FV document type.');
        }

        $paymentTypes = $catalogs->paymentTypes('FV');
        if ($paymentTypes === []) {
            $this->markTestSkipped('Sandbox account has no FV payment types configured.');
        }

        $sellers = $catalogs->users(page: 1, pageSize: 1);
        if ($sellers->items === []) {
            $this->markTestSkipped('Sandbox account has no users/sellers configured.');
        }

        $existingCustomers = (new Customers($client))->all(active: true, page: 1, pageSize: 1);
        if ($existingCustomers->items === []) {
            $this->markTestSkipped('Sandbox account has no active customers to reference.');
        }
        $customer = $existingCustomers->items[0];

        $existingProducts = (new Products($client))->all(active: true, page: 1, pageSize: 1);
        if ($existingProducts->items === []) {
            $this->markTestSkipped('Sandbox account has no active products to reference.');
        }
        $product = $existingProducts->items[0];

        $invoices = new Invoices($client);
        $invoiceData = new InvoiceData(
            document: new DocumentRef($activeDocumentType->id),
            date: (new \DateTimeImmutable)->format('Y-m-d'),
            customer: new CustomerRef($customer->identification, $customer->branchOffice),
            seller: $sellers->items[0]->id,
            items: [new InvoiceItem(code: $product->code, quantity: 1, price: 100)],
            payments: [new InvoicePayment($paymentTypes[0]->id, 100)],
        );

        try {
            $created = $invoices->create($invoiceData, idempotencyKey: 'siigolaravelsdkstaging'.random_int(100000, 999999));
        } catch (ValidationException $exception) {
            if ($exception->errorCode() === 'document_settings') {
                $this->markTestIncomplete(
                    "Siigo rejected document type {$activeDocumentType->id} with document_settings ".
                    '("you must verify the document settings") — likely an account-configuration issue '.
                    '(e.g. numbering resolution) in Siigo Nube, not an SDK defect. See docs/known-issues.md.'
                );
            }

            throw $exception;
        }

        $this->assertNotSame('', $created->id);

        $found = $invoices->find($created->id);
        $this->assertSame($created->id, $found->id);

        try {
            $this->assertTrue($invoices->delete($created->id));
        } catch (RequestException $exception) {
            $this->markTestIncomplete(
                "DELETE /v1/invoices/{$created->id} was rejected by Siigo ({$exception->errorCode()}) — ".
                'the test invoice above was not cleaned up automatically.'
            );
        }

        $this->expectException(NotFoundException::class);
        $invoices->find($created->id);
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
