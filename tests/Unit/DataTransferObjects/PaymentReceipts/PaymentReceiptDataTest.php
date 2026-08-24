<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\Tests\Unit\DataTransferObjects\PaymentReceipts;

use Jonathan8312\Siigo\DataTransferObjects\PaymentReceipts\Currency;
use Jonathan8312\Siigo\DataTransferObjects\PaymentReceipts\DocumentRef;
use Jonathan8312\Siigo\DataTransferObjects\PaymentReceipts\Due;
use Jonathan8312\Siigo\DataTransferObjects\PaymentReceipts\Payment;
use Jonathan8312\Siigo\DataTransferObjects\PaymentReceipts\PaymentReceiptData;
use Jonathan8312\Siigo\DataTransferObjects\PaymentReceipts\PaymentReceiptItem;
use Jonathan8312\Siigo\DataTransferObjects\PaymentReceipts\SupplierRef;
use Jonathan8312\Siigo\Enums\PaymentReceiptType;
use PHPUnit\Framework\TestCase;

final class PaymentReceiptDataTest extends TestCase
{
    public function test_to_array_matches_the_documented_shape_for_a_debt_payment(): void
    {
        $paymentReceipt = new PaymentReceiptData(
            document: new DocumentRef(2376),
            date: '2025-01-12',
            type: PaymentReceiptType::DebtPayment,
            supplier: new SupplierRef('109048401', 0),
            payment: new Payment(5638, 10000),
            items: [new PaymentReceiptItem(new Due('FC-1', 684, 1, '2020-02-15'), 10000)],
            number: 1052,
            costCenter: 235,
            currency: new Currency('USD', 3825.03),
            observations: 'observación de prueba',
        );

        $this->assertSame([
            'document' => ['id' => 2376],
            'number' => 1052,
            'date' => '2025-01-12',
            'type' => 'DebtPayment',
            'supplier' => ['identification' => '109048401', 'branch_office' => 0],
            'cost_center' => 235,
            'currency' => ['code' => 'USD', 'exchange_rate' => 3825.03],
            'items' => [[
                'due' => ['prefix' => 'FC-1', 'consecutive' => 684, 'quote' => 1, 'date' => '2020-02-15'],
                'value' => 10000.0,
            ]],
            'payment' => ['id' => 5638, 'value' => 10000.0],
            'observations' => 'observación de prueba',
        ], $paymentReceipt->toArray());
    }

    public function test_to_array_omits_items_for_an_advance_payment(): void
    {
        $paymentReceipt = new PaymentReceiptData(
            document: new DocumentRef(28355),
            date: '2025-01-12',
            type: PaymentReceiptType::AdvancePayment,
            supplier: new SupplierRef('109048401', 0),
            payment: new Payment(5638, 10000),
            observations: 'observación de prueba',
        );

        $array = $paymentReceipt->toArray();

        $this->assertSame('AdvancePayment', $array['type']);
        $this->assertArrayNotHasKey('items', $array);
    }

    public function test_to_array_omits_optional_fields_when_not_set(): void
    {
        $paymentReceipt = new PaymentReceiptData(
            document: new DocumentRef(28355),
            date: '2025-01-12',
            type: PaymentReceiptType::AdvancePayment,
            supplier: new SupplierRef('109048401'),
            payment: new Payment(5638, 10000),
        );

        $array = $paymentReceipt->toArray();

        $this->assertArrayNotHasKey('number', $array);
        $this->assertArrayNotHasKey('cost_center', $array);
        $this->assertArrayNotHasKey('currency', $array);
        $this->assertArrayNotHasKey('observations', $array);
    }
}
