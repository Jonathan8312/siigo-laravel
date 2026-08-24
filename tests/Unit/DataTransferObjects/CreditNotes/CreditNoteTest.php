<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\Tests\Unit\DataTransferObjects\CreditNotes;

use Jonathan8312\Siigo\DataTransferObjects\CreditNotes\CreditNote;
use Jonathan8312\Siigo\Enums\StampStatus;
use PHPUnit\Framework\TestCase;

final class CreditNoteTest extends TestCase
{
    public function test_from_array_maps_the_documented_full_response_shape(): void
    {
        $creditNote = CreditNote::fromArray([
            'id' => '63f918c2-ca65-4edc-a7db-66bcdd5159fb',
            'document' => ['id' => 22],
            'number' => 25,
            'name' => 'NC-2-22',
            'date' => '2021-10-15',
            'invoice' => ['id' => '302580df-838b-4531-b8bf-dd3c98b34059', 'name' => 'FV-2-20'],
            'customer' => ['id' => '302580df-838b-4531-b8bf-dd3c98b34059', 'identification' => '13832081', 'branch_office' => 0],
            'cost_center' => 235,
            'currency' => ['code' => 'USD', 'exchange_rate' => 3825.03],
            'seller' => 629,
            'retentions' => [['id' => 13156, 'name' => 'VAT 19%', 'type' => 'Retefuente', 'percentage' => 19, 'value' => 5]],
            'advance_payment' => 33.3,
            'total' => 25.5,
            'observations' => 'This is an observation',
            'items' => [[
                'id' => '63f918c2-ca65-4edc-a7db-66bcdd5159fb', 'code' => 'Item-1', 'quantity' => 2, 'price' => 50,
                'seller' => 629, 'description' => 'This is a description',
                'discount' => ['percentage' => 13, 'value' => 130],
                'taxes' => [['id' => 13156, 'name' => 'VAT 19%', 'type' => 'IVA', 'percentage' => 19, 'value' => 5, 'base_value' => 2000]],
                'warehouse' => ['id' => 15, 'name' => 'Main Warehouse'],
                'total' => 119000,
            ]],
            'payments' => [['id' => 5636, 'name' => 'Credit', 'value' => 1273.03, 'due_date' => '2021-03-19']],
            'stamp' => ['status' => 'Accepted', 'cufe' => 'string', 'cude' => 'string', 'observations' => 'string', 'errors' => 'string'],
            'metadata' => ['created' => 'string', 'last_updated' => 'string', 'stock_updated' => 'string'],
        ]);

        $this->assertSame('63f918c2-ca65-4edc-a7db-66bcdd5159fb', $creditNote->id);
        $this->assertNotNull($creditNote->document);
        $this->assertSame(22, $creditNote->document->id);
        $this->assertSame('NC-2-22', $creditNote->name);

        $this->assertNotNull($creditNote->invoice);
        $this->assertSame('302580df-838b-4531-b8bf-dd3c98b34059', $creditNote->invoice->id);
        $this->assertSame('FV-2-20', $creditNote->invoice->name);

        $this->assertNotNull($creditNote->customer);
        $this->assertSame('13832081', $creditNote->customer->identification);

        $this->assertCount(1, $creditNote->retentions);
        $this->assertSame('Retefuente', $creditNote->retentions[0]->type);

        $this->assertCount(1, $creditNote->items);
        $this->assertNotNull($creditNote->items[0]->discount);
        $this->assertSame(130.0, $creditNote->items[0]->discount->value);
        $this->assertNotNull($creditNote->items[0]->warehouse);
        $this->assertSame('Main Warehouse', $creditNote->items[0]->warehouse->name);
        $this->assertCount(1, $creditNote->items[0]->taxes);
        $this->assertSame(2000.0, $creditNote->items[0]->taxes[0]->baseValue);

        $this->assertCount(1, $creditNote->payments);
        $this->assertSame('Credit', $creditNote->payments[0]->name);

        $this->assertNotNull($creditNote->stamp);
        $this->assertSame(StampStatus::Accepted, $creditNote->stamp->status);
        $this->assertNotNull($creditNote->metadata);
    }

    public function test_from_array_tolerates_a_minimal_payload(): void
    {
        $creditNote = CreditNote::fromArray(['id' => 'abc']);

        $this->assertSame('abc', $creditNote->id);
        $this->assertNull($creditNote->document);
        $this->assertNull($creditNote->invoice);
        $this->assertNull($creditNote->customer);
        $this->assertSame([], $creditNote->items);
        $this->assertSame([], $creditNote->retentions);
        $this->assertNull($creditNote->stamp);
    }

    public function test_from_array_tolerates_a_credit_note_created_from_invoice_data(): void
    {
        $creditNote = CreditNote::fromArray([
            'id' => 'abc',
            'customer' => ['identification' => '28211179', 'branch_office' => 0],
        ]);

        $this->assertNull($creditNote->invoice);
        $this->assertNotNull($creditNote->customer);
        $this->assertNull($creditNote->customer->id);
        $this->assertSame('28211179', $creditNote->customer->identification);
    }
}
