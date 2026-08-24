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
use Jonathan8312\Siigo\DataTransferObjects\CreditNotes\CreditNoteData;
use Jonathan8312\Siigo\DataTransferObjects\CreditNotes\CreditNoteItem;
use Jonathan8312\Siigo\DataTransferObjects\CreditNotes\CreditNotePayment;
use Jonathan8312\Siigo\DataTransferObjects\CreditNotes\DocumentRef as CreditNoteDocumentRef;
use Jonathan8312\Siigo\DataTransferObjects\Invoices\CustomerRef as InvoiceCustomerRef;
use Jonathan8312\Siigo\DataTransferObjects\Invoices\DocumentRef as InvoiceDocumentRef;
use Jonathan8312\Siigo\DataTransferObjects\Invoices\Invoice;
use Jonathan8312\Siigo\DataTransferObjects\Invoices\InvoiceData;
use Jonathan8312\Siigo\DataTransferObjects\Invoices\InvoiceItem;
use Jonathan8312\Siigo\DataTransferObjects\Invoices\InvoicePayment;
use Jonathan8312\Siigo\Enums\CreditNoteReason;
use Jonathan8312\Siigo\Exceptions\ValidationException;
use Jonathan8312\Siigo\Http\Client;
use Jonathan8312\Siigo\Http\ClientConfiguration;
use Jonathan8312\Siigo\Resources\Catalogs;
use Jonathan8312\Siigo\Resources\CreditNotes;
use Jonathan8312\Siigo\Resources\Customers;
use Jonathan8312\Siigo\Resources\Invoices;
use Jonathan8312\Siigo\Resources\Products;

/**
 * Fase "Credit Notes" real-world verification. Unlike
 * {@see InvoicesStagingTest}, this test does NOT clean up after itself:
 * Siigo has no confirmed `DELETE`/annul endpoint for credit notes (see
 * docs/known-issues.md), and once a credit note references an invoice
 * that invoice can no longer be deleted either. This test therefore
 * permanently leaves one invoice and one credit note in the shared
 * sandbox account — harmless (both use trivial, clearly-labelled test
 * data, `stamp.send` is never sent so nothing reaches the real DIAN),
 * but deliberate and irreversible, unlike every other staging test in
 * this suite.
 *
 * Reuses the same `NoElectronic`-candidate-retry strategy as
 * {@see InvoicesStagingTest} for both the underlying invoice's document
 * type and the credit note's own document type, since this sandbox
 * account's `document_settings` inconsistency (missing DIAN
 * resolutions, unauthorized sellers, ...) applies to both.
 */
final class CreditNotesStagingTest extends StagingTestCase
{
    private const MAX_DOCUMENT_TYPE_ATTEMPTS = 5;

    public function test_creates_a_real_credit_note_against_a_real_invoice(): void
    {
        $client = $this->client();
        $catalogs = new Catalogs($client);

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

        $sellers = $catalogs->users(page: 1, pageSize: 1);
        if ($sellers->items === []) {
            $this->markTestSkipped('Sandbox account has no users/sellers configured.');
        }
        $seller = $sellers->items[0]->id;

        $today = (new \DateTimeImmutable)->format('Y-m-d');

        $invoice = $this->createInvoice($client, $catalogs, $customer->identification, $customer->branchOffice, $product->code, $seller, $today);
        if ($invoice === null) {
            $this->markTestIncomplete(
                'Could not create a real invoice to reference — every candidate FV document type '.
                'was rejected with document_settings. See docs/known-issues.md.'
            );
        }

        $creditNotePaymentTypes = $catalogs->paymentTypes('NC');
        if ($creditNotePaymentTypes === []) {
            $this->markTestSkipped('Sandbox account has no NC payment types configured.');
        }

        $documentTypes = $catalogs->documentTypes('NC');
        $candidates = array_values(array_filter(
            $documentTypes,
            static fn (DocumentType $documentType): bool => $documentType->active
                && $documentType->automaticNumber
                && $documentType->electronicType === 'NoElectronic',
        ));
        if ($candidates === []) {
            $this->markTestSkipped('Sandbox account has no active, automatically-numbered, non-electronic NC document type.');
        }

        $created = null;
        $lastException = null;

        foreach (array_slice($candidates, 0, self::MAX_DOCUMENT_TYPE_ATTEMPTS) as $documentType) {
            $creditNoteData = new CreditNoteData(
                document: new CreditNoteDocumentRef($documentType->id),
                date: $today,
                reason: CreditNoteReason::PartialReturnOrRejection,
                items: [new CreditNoteItem(code: $product->code, quantity: 1, price: 100)],
                payments: [new CreditNotePayment($creditNotePaymentTypes[0]->id, 100, $today)],
                invoice: $invoice->id,
            );

            try {
                $created = (new CreditNotes($client))->create($creditNoteData, idempotencyKey: 'siigolaravelsdkstaging'.random_int(100000, 999999));
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
                'Every candidate NC document type in this sandbox account was rejected with '.
                'document_settings — likely inconsistent per-document-type configuration across '.
                'this shared account. Last error: '.$lastException->getMessage().' See docs/known-issues.md. '.
                "The referenced invoice ({$invoice->id}) was left in the sandbox."
            );
        }

        $this->assertNotSame('', $created->id);
        $this->assertNotNull($created->invoice);
        $this->assertSame($invoice->id, $created->invoice->id);

        $found = (new CreditNotes($client))->find($created->id);
        $this->assertSame($created->id, $found->id);
    }

    private function createInvoice(Client $client, Catalogs $catalogs, string $customerIdentification, int $customerBranchOffice, string $productCode, int $seller, string $today): ?Invoice
    {
        $documentTypes = $catalogs->documentTypes('FV');
        $candidates = array_values(array_filter(
            $documentTypes,
            static fn (DocumentType $documentType): bool => $documentType->active
                && $documentType->automaticNumber
                && $documentType->electronicType === 'NoElectronic',
        ));
        if ($candidates === []) {
            return null;
        }

        $paymentTypes = $catalogs->paymentTypes('FV');
        if ($paymentTypes === []) {
            return null;
        }

        $invoices = new Invoices($client);

        foreach (array_slice($candidates, 0, self::MAX_DOCUMENT_TYPE_ATTEMPTS) as $documentType) {
            $invoiceData = new InvoiceData(
                document: new InvoiceDocumentRef($documentType->id),
                date: $today,
                customer: new InvoiceCustomerRef($customerIdentification, $customerBranchOffice),
                seller: $seller,
                items: [new InvoiceItem(code: $productCode, quantity: 1, price: 100)],
                payments: [new InvoicePayment($paymentTypes[0]->id, 100, $today)],
            );

            try {
                return $invoices->create($invoiceData, idempotencyKey: 'siigolaravelsdkstaging'.random_int(100000, 999999));
            } catch (ValidationException $exception) {
                if ($exception->errorCode() !== 'document_settings') {
                    throw $exception;
                }
            }
        }

        return null;
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
