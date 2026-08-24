<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\Tests\Feature;

use Illuminate\Support\Facades\Http;
use Jonathan8312\Siigo\DataTransferObjects\PaymentReceipts\DocumentRef;
use Jonathan8312\Siigo\DataTransferObjects\PaymentReceipts\Payment;
use Jonathan8312\Siigo\DataTransferObjects\PaymentReceipts\PaymentReceiptData;
use Jonathan8312\Siigo\DataTransferObjects\PaymentReceipts\SupplierRef;
use Jonathan8312\Siigo\Enums\PaymentReceiptType;
use Jonathan8312\Siigo\Exceptions\ValidationException;
use Jonathan8312\Siigo\Siigo;
use Jonathan8312\Siigo\Tests\TestCase;

final class PaymentReceiptsFeatureTest extends TestCase
{
    public function test_payment_receipts_resolves_through_the_container_and_creates_a_payment_receipt(): void
    {
        Http::fake([
            'https://siigo.test/auth' => Http::response(['access_token' => 'jwt-value', 'expires_in' => 86400], 200),
            'https://siigo.test/v1/payment-receipts' => Http::response(['id' => 'abc-123', 'name' => 'RP-1-1051'], 201),
        ]);

        $siigo = $this->app()->make(Siigo::class);

        $paymentReceipt = $siigo->paymentReceipts()->create(new PaymentReceiptData(
            document: new DocumentRef(28355),
            date: '2025-01-12',
            type: PaymentReceiptType::AdvancePayment,
            supplier: new SupplierRef('109048401'),
            payment: new Payment(5638, 10000),
        ));

        $this->assertSame('abc-123', $paymentReceipt->id);
    }

    public function test_payment_receipts_maps_a_validation_error(): void
    {
        Http::fake([
            'https://siigo.test/auth' => Http::response(['access_token' => 'jwt-value', 'expires_in' => 86400], 200),
            'https://siigo.test/v1/payment-receipts' => Http::response([
                'Status' => 400,
                'Errors' => [['Code' => 'parameter_required', 'Message' => 'The field Document is required', 'Params' => [], 'Detail' => null]],
            ], 400),
        ]);

        $siigo = $this->app()->make(Siigo::class);

        try {
            $siigo->paymentReceipts()->create(new PaymentReceiptData(
                document: new DocumentRef(0),
                date: '2025-01-12',
                type: PaymentReceiptType::AdvancePayment,
                supplier: new SupplierRef('109048401'),
                payment: new Payment(5638, 10000),
            ));
            $this->fail('Expected a ValidationException.');
        } catch (ValidationException $exception) {
            $this->assertSame('parameter_required', $exception->errorCode());
        }
    }
}
