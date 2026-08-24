<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\Tests\Unit\DataTransferObjects\PaymentReceipts;

use Jonathan8312\Siigo\DataTransferObjects\PaymentReceipts\AccountRef;
use Jonathan8312\Siigo\DataTransferObjects\PaymentReceipts\DetailedItem;
use Jonathan8312\Siigo\DataTransferObjects\PaymentReceipts\DetailedPaymentReceiptData;
use Jonathan8312\Siigo\DataTransferObjects\PaymentReceipts\DocumentRef;
use Jonathan8312\Siigo\DataTransferObjects\PaymentReceipts\Due;
use Jonathan8312\Siigo\DataTransferObjects\PaymentReceipts\SupplierRef;
use Jonathan8312\Siigo\DataTransferObjects\PaymentReceipts\TaxRef;
use Jonathan8312\Siigo\Enums\AccountMovement;
use PHPUnit\Framework\TestCase;

final class DetailedPaymentReceiptDataTest extends TestCase
{
    public function test_to_array_matches_the_documented_shape(): void
    {
        $paymentReceipt = new DetailedPaymentReceiptData(
            document: new DocumentRef(24445),
            date: '2015-01-15',
            supplier: new SupplierRef('8694251', 0),
            items: [
                new DetailedItem(
                    account: new AccountRef('11100501', AccountMovement::Credit),
                    description: 'FC-2 Base',
                    value: 50,
                ),
                new DetailedItem(
                    account: new AccountRef('13050501', AccountMovement::Debit),
                    description: 'FC-2 Base',
                    value: 50,
                    due: new Due('FC-1', 684, 1, '2020-02-15'),
                ),
                new DetailedItem(
                    account: new AccountRef('24081001', AccountMovement::Debit),
                    description: 'FC-2 Base',
                    value: 19,
                    tax: new TaxRef(13156),
                ),
            ],
            observations: 'observación de prueba',
        );

        $this->assertSame([
            'document' => ['id' => 24445],
            'date' => '2015-01-15',
            'type' => 'Detailed',
            'supplier' => ['identification' => '8694251', 'branch_office' => 0],
            'items' => [
                ['account' => ['code' => '11100501', 'movement' => 'Credit'], 'description' => 'FC-2 Base', 'value' => 50.0],
                ['account' => ['code' => '13050501', 'movement' => 'Debit'], 'due' => ['prefix' => 'FC-1', 'consecutive' => 684, 'quote' => 1, 'date' => '2020-02-15'], 'description' => 'FC-2 Base', 'value' => 50.0],
                ['account' => ['code' => '24081001', 'movement' => 'Debit'], 'description' => 'FC-2 Base', 'value' => 19.0, 'tax' => ['id' => 13156]],
            ],
            'observations' => 'observación de prueba',
        ], $paymentReceipt->toArray());
    }
}
