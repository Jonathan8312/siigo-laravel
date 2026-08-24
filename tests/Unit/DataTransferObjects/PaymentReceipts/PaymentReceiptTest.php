<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\Tests\Unit\DataTransferObjects\PaymentReceipts;

use Jonathan8312\Siigo\DataTransferObjects\PaymentReceipts\PaymentReceipt;
use Jonathan8312\Siigo\Enums\PaymentReceiptType;
use PHPUnit\Framework\TestCase;

final class PaymentReceiptTest extends TestCase
{
    public function test_from_array_maps_the_documented_full_response_shape(): void
    {
        $paymentReceipt = PaymentReceipt::fromArray([
            'id' => 'e59dd8d0-1647-4d65-87bd-2b9a9dd77dea',
            'document' => ['id' => 2376],
            'number' => 1051,
            'name' => 'RP-1-1051',
            'date' => '2026-08-22',
            'type' => 'DebtPayment',
            'supplier' => ['id' => '39aad863-42e3-4e1f-9c70-da9b116a6f4b', 'identification' => '1001560611', 'branch_office' => 0],
            'items' => [['due' => ['prefix' => 'FV', 'consecutive' => 2903, 'quote' => 2, 'date' => '2026-08-21'], 'value' => 50000000]],
            'payment' => ['id' => 8156, 'name' => 'Bancolombia', 'value' => 50000000],
            'metadata' => ['created' => '2026-08-22T08:41:40'],
        ]);

        $this->assertSame('e59dd8d0-1647-4d65-87bd-2b9a9dd77dea', $paymentReceipt->id);
        $this->assertNotNull($paymentReceipt->document);
        $this->assertSame(2376, $paymentReceipt->document->id);
        $this->assertSame('RP-1-1051', $paymentReceipt->name);
        $this->assertSame(PaymentReceiptType::DebtPayment, $paymentReceipt->type);

        $this->assertNotNull($paymentReceipt->supplier);
        $this->assertSame('1001560611', $paymentReceipt->supplier->identification);

        $this->assertCount(1, $paymentReceipt->items);
        $this->assertSame('FV', $paymentReceipt->items[0]->due->prefix);
        $this->assertSame(50000000.0, $paymentReceipt->items[0]->value);

        $this->assertNotNull($paymentReceipt->payment);
        $this->assertSame('Bancolombia', $paymentReceipt->payment->name);

        $this->assertNotNull($paymentReceipt->metadata);
        $this->assertSame('2026-08-22T08:41:40', $paymentReceipt->metadata->created);
    }

    public function test_from_array_tolerates_a_minimal_payload(): void
    {
        $paymentReceipt = PaymentReceipt::fromArray(['id' => 'abc']);

        $this->assertSame('abc', $paymentReceipt->id);
        $this->assertNull($paymentReceipt->document);
        $this->assertNull($paymentReceipt->supplier);
        $this->assertNull($paymentReceipt->payment);
        $this->assertSame([], $paymentReceipt->items);
        $this->assertNull($paymentReceipt->metadata);
    }

    public function test_from_array_tolerates_a_record_with_items_but_no_payment(): void
    {
        $paymentReceipt = PaymentReceipt::fromArray([
            'id' => 'abc',
            'items' => [],
            'observations' => 'Prueba de factura de compra DP 1',
        ]);

        $this->assertNull($paymentReceipt->payment);
        $this->assertSame([], $paymentReceipt->items);
        $this->assertSame('Prueba de factura de compra DP 1', $paymentReceipt->observations);
    }

    public function test_from_array_tolerates_an_unknown_future_type(): void
    {
        $paymentReceipt = PaymentReceipt::fromArray(['id' => 'abc', 'type' => 'SomeFutureType']);

        $this->assertNull($paymentReceipt->type);
    }
}
