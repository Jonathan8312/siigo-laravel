<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\Tests\Unit\DataTransferObjects\Invoices;

use Jonathan8312\Siigo\DataTransferObjects\Invoices\InvoiceBatchReceipt;
use PHPUnit\Framework\TestCase;

final class InvoiceBatchReceiptTest extends TestCase
{
    public function test_from_array_maps_the_acknowledgement_shape(): void
    {
        $receipt = InvoiceBatchReceipt::fromArray([
            'id' => 'ea6186c4-a11f-4694-90a4-c01b9785e9d2',
            'status' => 'Received',
            'received_at' => '2025-07-10T20:48:59.863Z',
        ]);

        $this->assertSame('ea6186c4-a11f-4694-90a4-c01b9785e9d2', $receipt->id);
        $this->assertSame('Received', $receipt->status);
        $this->assertSame('2025-07-10T20:48:59.863Z', $receipt->receivedAt);
    }

    public function test_from_array_tolerates_a_minimal_payload(): void
    {
        $receipt = InvoiceBatchReceipt::fromArray(['id' => 'abc']);

        $this->assertSame('abc', $receipt->id);
        $this->assertNull($receipt->status);
        $this->assertNull($receipt->receivedAt);
    }
}
