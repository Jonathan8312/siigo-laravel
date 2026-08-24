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
use Jonathan8312\Siigo\DataTransferObjects\Catalogs\DocumentType;
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
 *
 * This sandbox account has 260+ FV document types (evidently a shared
 * account reused across many unrelated test companies over time), and
 * a large share of them reject invoice creation with `document_settings`
 * ("must verify the document settings") — confirmed to be per-document-
 * type account configuration (DIAN numbering resolution, authorized
 * sellers, ...), not an SDK defect: the same payload succeeds outright
 * against a properly-configured type. Rather than depending on any one
 * document type being usable, this test tries a bounded number of
 * `NoElectronic` candidates (electronic ones add the DIAN resolution
 * requirement on top) and treats it as a documented account limitation,
 * not a failure, only if every candidate is rejected the same way.
 */
final class InvoicesStagingTest extends StagingTestCase
{
    private const MAX_DOCUMENT_TYPE_ATTEMPTS = 5;

    public function test_creates_finds_and_deletes_a_real_invoice(): void
    {
        $client = $this->client();
        $catalogs = new Catalogs($client);

        $documentTypes = $catalogs->documentTypes('FV');
        $candidates = array_values(array_filter(
            $documentTypes,
            // automatic_number: false would require an explicit `number`
            // the SDK cannot safely guess; electronic_type other than
            // NoElectronic adds the DIAN resolution requirement above.
            static fn (DocumentType $documentType): bool => $documentType->active
                && $documentType->automaticNumber
                && $documentType->electronicType === 'NoElectronic',
        ));
        if ($candidates === []) {
            $this->markTestSkipped('Sandbox account has no active, automatically-numbered, non-electronic FV document type.');
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
        $today = (new \DateTimeImmutable)->format('Y-m-d');
        $created = null;
        $lastException = null;

        foreach (array_slice($candidates, 0, self::MAX_DOCUMENT_TYPE_ATTEMPTS) as $documentType) {
            $invoiceData = new InvoiceData(
                document: new DocumentRef($documentType->id),
                date: $today,
                customer: new CustomerRef($customer->identification, $customer->branchOffice),
                seller: $sellers->items[0]->id,
                items: [new InvoiceItem(code: $product->code, quantity: 1, price: 100)],
                // due_date is required by this sandbox account's payment
                // configuration even though the docs mark it optional.
                payments: [new InvoicePayment($paymentTypes[0]->id, 100, $today)],
            );

            try {
                $created = $invoices->create($invoiceData, idempotencyKey: 'siigolaravelsdkstaging'.random_int(100000, 999999));
                break;
            } catch (ValidationException $exception) {
                if ($exception->errorCode() !== 'document_settings') {
                    throw $exception;
                }

                $lastException = $exception;
            }
        }

        if ($created === null) {
            $this->markTestIncomplete(
                'Every candidate document type in this sandbox account was rejected with '.
                'document_settings — likely inconsistent per-document-type configuration '.
                '(DIAN resolution, authorized sellers, ...) across this shared account. Last error: '.
                $lastException->getMessage().' See docs/known-issues.md.'
            );
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
