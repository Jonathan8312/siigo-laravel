<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\Tests\Unit\DataTransferObjects\CreditNotes;

use Jonathan8312\Siigo\DataTransferObjects\CreditNotes\CreditNoteData;
use Jonathan8312\Siigo\DataTransferObjects\CreditNotes\CreditNoteInvoiceData;
use Jonathan8312\Siigo\DataTransferObjects\CreditNotes\CreditNoteItem;
use Jonathan8312\Siigo\DataTransferObjects\CreditNotes\CreditNotePayment;
use Jonathan8312\Siigo\DataTransferObjects\CreditNotes\Currency;
use Jonathan8312\Siigo\DataTransferObjects\CreditNotes\CustomerRef;
use Jonathan8312\Siigo\DataTransferObjects\CreditNotes\DocumentRef;
use Jonathan8312\Siigo\DataTransferObjects\CreditNotes\ItemTaxRef;
use Jonathan8312\Siigo\DataTransferObjects\CreditNotes\MailCommand;
use Jonathan8312\Siigo\DataTransferObjects\CreditNotes\StampCommand;
use Jonathan8312\Siigo\Enums\CreditNoteReason;
use Jonathan8312\Siigo\Enums\CreditNoteTaxpayer;
use PHPUnit\Framework\TestCase;

final class CreditNoteDataTest extends TestCase
{
    public function test_to_array_matches_the_documented_full_payload_shape_for_an_existing_invoice(): void
    {
        $creditNote = new CreditNoteData(
            document: new DocumentRef(22),
            date: '2021-10-15',
            reason: CreditNoteReason::PartialReturnOrRejection,
            items: [new CreditNoteItem(
                code: 'Item-1',
                quantity: 2,
                price: 50,
                description: 'This is a description',
                discount: 13,
                warehouse: 15,
                taxes: [new ItemTaxRef(13156)],
            )],
            payments: [new CreditNotePayment(5636, 1273.03, '2021-03-19')],
            invoice: '63f918c2-ca65-4edc-a7db-66bcdd5159fb',
            number: 25,
            name: 'NC-2-22',
            costCenter: 235,
            currency: new Currency('USD', 3825.03),
            retentions: [13156],
            advancePayment: 33.3,
            observations: 'This is an observation',
            stamp: new StampCommand(true),
            mail: new MailCommand(true),
        );

        $this->assertSame([
            'document' => ['id' => 22],
            'number' => 25,
            'name' => 'NC-2-22',
            'date' => '2021-10-15',
            'invoice' => '63f918c2-ca65-4edc-a7db-66bcdd5159fb',
            'reason' => 1,
            'cost_center' => 235,
            'currency' => ['code' => 'USD', 'exchange_rate' => 3825.03],
            'retentions' => [13156],
            'advance_payment' => 33.3,
            'observations' => 'This is an observation',
            'items' => [[
                'code' => 'Item-1',
                'description' => 'This is a description',
                'quantity' => 2.0,
                'price' => 50.0,
                'discount' => 13.0,
                'warehouse' => 15,
                'taxes' => [['id' => 13156]],
            ]],
            'payments' => [['id' => 5636, 'value' => 1273.03, 'due_date' => '2021-03-19']],
            'stamp' => ['send' => true],
            'mail' => ['send' => true],
        ], $creditNote->toArray());
    }

    public function test_to_array_omits_optional_fields_when_not_set(): void
    {
        $creditNote = new CreditNoteData(
            document: new DocumentRef(22),
            date: '2021-10-15',
            reason: 1,
            items: [new CreditNoteItem(code: 'Item-1', quantity: 1, price: 10)],
            payments: [new CreditNotePayment(5636, 10)],
            invoice: '63f918c2-ca65-4edc-a7db-66bcdd5159fb',
        );

        $array = $creditNote->toArray();

        $this->assertArrayNotHasKey('number', $array);
        $this->assertArrayNotHasKey('name', $array);
        $this->assertArrayNotHasKey('invoice_data', $array);
        $this->assertArrayNotHasKey('customer', $array);
        $this->assertArrayNotHasKey('seller', $array);
        $this->assertArrayNotHasKey('cost_center', $array);
        $this->assertArrayNotHasKey('currency', $array);
        $this->assertArrayNotHasKey('retentions', $array);
        $this->assertArrayNotHasKey('advance_payment', $array);
        $this->assertArrayNotHasKey('observations', $array);
        $this->assertArrayNotHasKey('stamp', $array);
        $this->assertArrayNotHasKey('mail', $array);
        $this->assertArrayNotHasKey('healthcare_company', $array);
    }

    public function test_reason_accepts_a_raw_int_for_a_code_not_covered_by_the_enum(): void
    {
        $creditNote = new CreditNoteData(
            document: new DocumentRef(22),
            date: '2021-10-15',
            reason: 7,
            items: [new CreditNoteItem(code: 'Item-1', quantity: 1, price: 10)],
            payments: [new CreditNotePayment(5636, 10)],
            invoice: '63f918c2-ca65-4edc-a7db-66bcdd5159fb',
        );

        $this->assertSame(7, $creditNote->toArray()['reason']);
    }

    public function test_to_array_sends_invoice_data_customer_and_seller_for_an_unregistered_invoice(): void
    {
        $creditNote = new CreditNoteData(
            document: new DocumentRef(2379),
            date: '2024-05-24',
            reason: CreditNoteReason::InvoiceAnnulment,
            items: [new CreditNoteItem(code: 'Code-1', quantity: 1, price: 2000, description: 'Producto de prueba')],
            payments: [new CreditNotePayment(542, 2000)],
            invoiceData: new CreditNoteInvoiceData(
                date: '2024-03-20',
                prefix: 'FV',
                number: '458',
                cufe: '302580df-838b-4531-b8bf-dd3c9hasdfu8e5',
            ),
            customer: new CustomerRef('28211179', 0),
            seller: 62,
        );

        $array = $creditNote->toArray();

        $this->assertArrayNotHasKey('invoice', $array);
        $this->assertSame([
            'date' => '2024-03-20',
            'prefix' => 'FV',
            'number' => '458',
            'cufe' => '302580df-838b-4531-b8bf-dd3c9hasdfu8e5',
        ], $array['invoice_data']);
        $this->assertSame(['identification' => '28211179', 'branch_office' => 0], $array['customer']);
        $this->assertSame(62, $array['seller']);
    }

    public function test_gift_items_send_tax_base_and_taxpayer(): void
    {
        $item = new CreditNoteItem(
            code: '1',
            description: 'Alquiler',
            quantity: 2,
            price: 0,
            taxBase: 1000,
            taxpayer: CreditNoteTaxpayer::Company,
            taxes: [new ItemTaxRef(31779)],
        );

        $this->assertSame([
            'code' => '1',
            'description' => 'Alquiler',
            'quantity' => 2.0,
            'price' => 0.0,
            'taxes' => [['id' => 31779]],
            'tax_base' => 1000.0,
            'taxpayer' => 'Company',
        ], $item->toArray());
    }
}
