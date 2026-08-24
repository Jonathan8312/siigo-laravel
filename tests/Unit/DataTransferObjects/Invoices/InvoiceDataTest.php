<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\Tests\Unit\DataTransferObjects\Invoices;

use Jonathan8312\Siigo\DataTransferObjects\Invoices\Currency;
use Jonathan8312\Siigo\DataTransferObjects\Invoices\CustomerRef;
use Jonathan8312\Siigo\DataTransferObjects\Invoices\DocumentRef;
use Jonathan8312\Siigo\DataTransferObjects\Invoices\GlobalCharge;
use Jonathan8312\Siigo\DataTransferObjects\Invoices\InvoiceData;
use Jonathan8312\Siigo\DataTransferObjects\Invoices\InvoiceItem;
use Jonathan8312\Siigo\DataTransferObjects\Invoices\InvoicePayment;
use Jonathan8312\Siigo\DataTransferObjects\Invoices\ItemTaxRef;
use Jonathan8312\Siigo\DataTransferObjects\Invoices\MailCommand;
use Jonathan8312\Siigo\DataTransferObjects\Invoices\StampCommand;
use PHPUnit\Framework\TestCase;

final class InvoiceDataTest extends TestCase
{
    public function test_to_array_matches_the_documented_full_payload_shape(): void
    {
        $invoice = new InvoiceData(
            document: new DocumentRef(22),
            date: '2021-10-15',
            customer: new CustomerRef('13832081', 0),
            seller: 629,
            items: [new InvoiceItem(
                code: 'Item-1',
                quantity: 2,
                price: 50,
                description: 'Product description',
                discount: 13,
                warehouse: 15,
                taxes: [new ItemTaxRef(13156)],
            )],
            payments: [new InvoicePayment(5636, 1273.03, '2021-03-19')],
            number: 25,
            costCenter: 235,
            currency: new Currency('USD', 3825.03),
            observations: 'Additional comments',
            advancePayment: 33.3,
            stamp: new StampCommand(true),
            mail: new MailCommand(true),
            globalDiscounts: [new GlobalCharge(13156, 5)],
        );

        $this->assertSame([
            'document' => ['id' => 22],
            'number' => 25,
            'date' => '2021-10-15',
            'customer' => ['identification' => '13832081', 'branch_office' => 0],
            'seller' => 629,
            'cost_center' => 235,
            'currency' => ['code' => 'USD', 'exchange_rate' => 3825.03],
            'observations' => 'Additional comments',
            'advance_payment' => 33.3,
            'items' => [[
                'code' => 'Item-1',
                'description' => 'Product description',
                'quantity' => 2.0,
                'price' => 50.0,
                'discount' => 13.0,
                'warehouse' => 15,
                'taxes' => [['id' => 13156]],
            ]],
            'payments' => [['id' => 5636, 'value' => 1273.03, 'due_date' => '2021-03-19']],
            'stamp' => ['send' => true],
            'mail' => ['send' => true],
            'global_discounts' => [['id' => 13156, 'percentage' => 5.0]],
        ], $invoice->toArray());
    }

    public function test_to_array_omits_optional_fields_when_not_set(): void
    {
        $invoice = new InvoiceData(
            document: new DocumentRef(22),
            date: '2021-10-15',
            customer: new CustomerRef('13832081'),
            seller: 629,
            items: [new InvoiceItem(code: 'Item-1', quantity: 1, price: 10)],
            payments: [new InvoicePayment(5636, 10)],
        );

        $array = $invoice->toArray();

        $this->assertArrayNotHasKey('number', $array);
        $this->assertArrayNotHasKey('cost_center', $array);
        $this->assertArrayNotHasKey('currency', $array);
        $this->assertArrayNotHasKey('observations', $array);
        $this->assertArrayNotHasKey('advance_payment', $array);
        $this->assertArrayNotHasKey('stamp', $array);
        $this->assertArrayNotHasKey('mail', $array);
        $this->assertArrayNotHasKey('global_discounts', $array);
        $this->assertArrayNotHasKey('retentions', $array);
        $this->assertArrayNotHasKey('healthcare_company', $array);
        $this->assertIsArray($array['items']);
        $this->assertIsArray($array['items'][0]);
        $this->assertArrayNotHasKey('discount', $array['items'][0]);
        $this->assertArrayNotHasKey('taxes', $array['items'][0]);
    }
}
