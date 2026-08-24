<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\Tests\Unit\DataTransferObjects\Invoices;

use Jonathan8312\Siigo\DataTransferObjects\Invoices\Invoice;
use Jonathan8312\Siigo\Enums\StampStatus;
use PHPUnit\Framework\TestCase;

final class InvoiceTest extends TestCase
{
    public function test_from_array_maps_the_documented_full_list_response_shape(): void
    {
        $invoice = Invoice::fromArray([
            'id' => '63f918c2-ca65-4edc-a7db-66bcdd5159fb',
            'document' => ['id' => 22],
            'prefix' => 'FV',
            'number' => 25,
            'name' => 'FV-2-22',
            'date' => '2021-10-10',
            'customer' => ['id' => '302580df-838b-4531-b8bf-dd3c98b34059', 'identification' => '13832081', 'branch_office' => 0],
            'cost_center' => 235,
            'currency' => ['code' => 'USD', 'exchange_rate' => 3825.03],
            'seller' => 629,
            'retentions' => [['id' => 13156, 'name' => 'VAT 19%', 'type' => 'Retefuente', 'percentage' => 19, 'value' => 5]],
            'advance_payment' => 33.3,
            'total' => 25.5,
            'balance' => 30302.24,
            'observations' => 'This is an observation',
            'items' => [[
                'id' => '63f918c2-ca65-4edc-a7db-66bcdd5159fb', 'code' => 'Item-1', 'quantity' => 2, 'price' => 50,
                'seller' => 629, 'description' => 'This is a description',
                'discount' => ['percentage' => 13, 'value' => 130],
                'taxes' => [['id' => 13156, 'name' => 'VAT 19%', 'type' => 'IVA', 'percentage' => 19, 'value' => 5, 'base_value' => 2000]],
                'warehouse' => ['id' => 15, 'name' => 'Main Warehouse'],
                'total' => 119000,
            ]],
            'global_charges' => [['id' => 0, 'name' => 'string', 'percentage' => 0, 'value' => 0]],
            'global_discounts' => [['id' => 0, 'name' => 'string', 'percentage' => 0, 'value' => 0]],
            'payments' => [['id' => 5636, 'name' => 'Credit', 'value' => 1273.03, 'due_date' => '2021-03-19']],
            'additional_fields' => [
                'purchase_order' => ['prefix' => 'OC', 'number' => '27'],
                'delivery_order' => ['prefix' => 'OE', 'number' => '27', 'date' => '2021-05-19'],
            ],
            'stamp' => ['status' => 'Accepted', 'cufe' => 'string', 'cude' => 'string', 'observations' => 'string', 'errors' => 'string'],
            'mail' => ['status' => 'string', 'observations' => 'string'],
            'metadata' => ['created' => 'string', 'last_updated' => 'string', 'stock_updated' => 'string'],
            'annulled' => true,
        ]);

        $this->assertSame('63f918c2-ca65-4edc-a7db-66bcdd5159fb', $invoice->id);
        $this->assertNotNull($invoice->document);
        $this->assertSame(22, $invoice->document->id);
        $this->assertSame('FV-2-22', $invoice->name);

        $this->assertNotNull($invoice->customer);
        $this->assertSame('302580df-838b-4531-b8bf-dd3c98b34059', $invoice->customer->id);

        $this->assertCount(1, $invoice->retentions);
        $this->assertSame('Retefuente', $invoice->retentions[0]->type);

        $this->assertCount(1, $invoice->items);
        $this->assertNotNull($invoice->items[0]->discount);
        $this->assertSame(130.0, $invoice->items[0]->discount->value);
        $this->assertNotNull($invoice->items[0]->warehouse);
        $this->assertSame('Main Warehouse', $invoice->items[0]->warehouse->name);
        $this->assertCount(1, $invoice->items[0]->taxes);
        $this->assertSame(2000.0, $invoice->items[0]->taxes[0]->baseValue);

        $this->assertCount(1, $invoice->globalCharges);
        $this->assertCount(1, $invoice->globalDiscounts);
        $this->assertCount(1, $invoice->payments);
        $this->assertSame('Credit', $invoice->payments[0]->name);

        $this->assertNotNull($invoice->additionalFields);
        $this->assertNotNull($invoice->additionalFields->purchaseOrder);
        $this->assertSame('OC', $invoice->additionalFields->purchaseOrder->prefix);
        $this->assertNotNull($invoice->additionalFields->deliveryOrder);
        $this->assertSame('2021-05-19', $invoice->additionalFields->deliveryOrder->date);

        $this->assertNotNull($invoice->stamp);
        $this->assertSame(StampStatus::Accepted, $invoice->stamp->status);

        $this->assertNotNull($invoice->mail);
        $this->assertNotNull($invoice->metadata);
        $this->assertTrue($invoice->annulled);
    }

    public function test_from_array_tolerates_a_minimal_payload(): void
    {
        $invoice = Invoice::fromArray(['id' => 'abc']);

        $this->assertSame('abc', $invoice->id);
        $this->assertNull($invoice->document);
        $this->assertNull($invoice->customer);
        $this->assertSame([], $invoice->items);
        $this->assertSame([], $invoice->retentions);
        $this->assertNull($invoice->stamp);
        $this->assertFalse($invoice->annulled);
    }

    public function test_from_array_tolerates_an_unknown_stamp_status(): void
    {
        $invoice = Invoice::fromArray(['id' => 'abc', 'stamp' => ['status' => 'SomethingSiigoAddsLater']]);

        $this->assertNotNull($invoice->stamp);
        $this->assertNull($invoice->stamp->status);
    }
}
