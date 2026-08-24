<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\Tests\Unit\DataTransferObjects\Invoices;

use Jonathan8312\Siigo\DataTransferObjects\Invoices\BatchInvoiceItem;
use Jonathan8312\Siigo\DataTransferObjects\Invoices\CustomerRef;
use Jonathan8312\Siigo\DataTransferObjects\Invoices\DocumentRef;
use Jonathan8312\Siigo\DataTransferObjects\Invoices\InvoiceData;
use Jonathan8312\Siigo\DataTransferObjects\Invoices\InvoiceItem;
use Jonathan8312\Siigo\DataTransferObjects\Invoices\InvoicePayment;
use PHPUnit\Framework\TestCase;

final class BatchInvoiceItemTest extends TestCase
{
    public function test_to_array_merges_the_idempotency_key_into_the_invoice_payload(): void
    {
        $item = new BatchInvoiceItem($this->minimalInvoiceData(), 'order1001');

        $array = $item->toArray();

        $this->assertSame('order1001', $array['idempotency_key']);
        $this->assertIsArray($array['document']);
        $this->assertSame(22, $array['document']['id']);
    }

    public function test_rejects_a_hyphenated_idempotency_key(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new BatchInvoiceItem($this->minimalInvoiceData(), 'order-1001');
    }

    private function minimalInvoiceData(): InvoiceData
    {
        return new InvoiceData(
            document: new DocumentRef(22),
            date: '2021-10-15',
            customer: new CustomerRef('13832081'),
            seller: 629,
            items: [new InvoiceItem(code: 'Item-1', quantity: 1, price: 10)],
            payments: [new InvoicePayment(5636, 10)],
        );
    }
}
