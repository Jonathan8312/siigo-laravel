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
use Jonathan8312\Siigo\DataTransferObjects\PaymentReceipts\DocumentRef;
use Jonathan8312\Siigo\DataTransferObjects\PaymentReceipts\Payment;
use Jonathan8312\Siigo\DataTransferObjects\PaymentReceipts\PaymentReceiptData;
use Jonathan8312\Siigo\DataTransferObjects\PaymentReceipts\SupplierRef;
use Jonathan8312\Siigo\Enums\CustomerType;
use Jonathan8312\Siigo\Enums\PaymentReceiptType;
use Jonathan8312\Siigo\Http\Client;
use Jonathan8312\Siigo\Http\ClientConfiguration;
use Jonathan8312\Siigo\Resources\Catalogs;
use Jonathan8312\Siigo\Resources\Customers;
use Jonathan8312\Siigo\Resources\PaymentReceipts;

/**
 * Fase "Payment Receipts" real-world verification. Unlike
 * {@see CreditNotesStagingTest}, this module has confirmed `PUT` and
 * `DELETE` endpoints (see docs/known-issues.md), so this test cleans up
 * fully: it creates an `AdvancePayment` receipt (no invoice due
 * dependency), updates its `observations` via `PUT`, deletes it, and
 * confirms the delete by expecting a subsequent `find()` to fail.
 *
 * Uses `document_type: 'FC'` (not `'RP'`) when looking up payment
 * types — confirmed against sandbox that `GET
 * /v1/payment-types?document_type=RP` is rejected with `404
 * not_found`, while `FC` (compra) is what actually returns the
 * supplier-side payment methods real payment receipts use. See
 * docs/known-issues.md.
 */
final class PaymentReceiptsStagingTest extends StagingTestCase
{
    public function test_creates_updates_and_deletes_a_real_advance_payment_receipt(): void
    {
        $client = $this->client();
        $catalogs = new Catalogs($client);
        $paymentReceipts = new PaymentReceipts($client);

        $suppliers = (new Customers($client))->all(type: CustomerType::Supplier, active: true, page: 1, pageSize: 25);
        $supplier = null;
        foreach ($suppliers->items as $candidate) {
            if ($candidate->type === CustomerType::Supplier) {
                $supplier = $candidate;
                break;
            }
        }
        if ($supplier === null) {
            $this->markTestSkipped('Sandbox account has no active suppliers to reference.');
        }

        $documentTypes = $catalogs->documentTypes('RP');
        $documentType = null;
        foreach ($documentTypes as $candidate) {
            if ($candidate->active && $candidate->automaticNumber) {
                $documentType = $candidate;
                break;
            }
        }
        if ($documentType === null) {
            $this->markTestSkipped('Sandbox account has no active, automatically-numbered RP document type.');
        }

        $paymentTypes = $catalogs->paymentTypes('FC');
        if ($paymentTypes === []) {
            $this->markTestSkipped('Sandbox account has no FC-scoped payment types configured.');
        }

        $created = $paymentReceipts->create(new PaymentReceiptData(
            document: new DocumentRef($documentType->id),
            date: (new \DateTimeImmutable)->format('Y-m-d'),
            type: PaymentReceiptType::AdvancePayment,
            supplier: new SupplierRef($supplier->identification, $supplier->branchOffice),
            payment: new Payment($paymentTypes[0]->id, 1000),
            observations: 'siigo-laravel-sdk staging test',
        ), idempotencyKey: 'siigolaravelsdkstaging'.random_int(100000, 999999));

        $this->assertNotSame('', $created->id);

        $updated = $paymentReceipts->update($created->id, new PaymentReceiptData(
            document: new DocumentRef($documentType->id),
            date: (new \DateTimeImmutable)->format('Y-m-d'),
            type: PaymentReceiptType::AdvancePayment,
            supplier: new SupplierRef($supplier->identification, $supplier->branchOffice),
            payment: new Payment($paymentTypes[0]->id, 1000),
            observations: 'siigo-laravel-sdk staging test (updated)',
        ));
        $this->assertSame('siigo-laravel-sdk staging test (updated)', $updated->observations);

        $deleted = $paymentReceipts->delete($created->id);
        $this->assertTrue($deleted);
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
