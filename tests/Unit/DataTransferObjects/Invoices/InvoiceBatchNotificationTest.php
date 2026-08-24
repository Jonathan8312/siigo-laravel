<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\Tests\Unit\DataTransferObjects\Invoices;

use Jonathan8312\Siigo\DataTransferObjects\Invoices\InvoiceBatchNotification;
use PHPUnit\Framework\TestCase;

final class InvoiceBatchNotificationTest extends TestCase
{
    public function test_from_array_maps_a_successful_invoice_result(): void
    {
        $notification = InvoiceBatchNotification::fromArray([
            'id' => 'ea6186c4-a11f-4694-90a4-c01b9785e9d2',
            'status' => 'Processed',
            'status_at' => '2025-07-10T20:49:01.727Z',
            'notification_url' => 'https://webhook.site',
            'invoices' => [
                [
                    'status_code' => '201',
                    'idempotency_key' => '1032492954',
                    'id' => '166baecf-ae5c-402e-9c7a-1ce6a9c57b1d',
                    'document' => ['id' => 138531],
                    'prefix' => 'FT',
                    'number' => 7751,
                    'name' => 'FV-890-7751',
                    'public_url' => 'https://documentview.siigo.com',
                ],
            ],
        ]);

        $this->assertSame('ea6186c4-a11f-4694-90a4-c01b9785e9d2', $notification->id);
        $this->assertSame('Processed', $notification->status);
        $this->assertCount(1, $notification->results);

        $result = $notification->results[0];
        $this->assertTrue($result->successful());
        $this->assertSame('1032492954', $result->idempotencyKey);
        $this->assertSame('https://documentview.siigo.com', $result->publicUrl);
        $this->assertNotNull($result->invoice);
        $this->assertSame('166baecf-ae5c-402e-9c7a-1ce6a9c57b1d', $result->invoice->id);
        $this->assertSame('FV-890-7751', $result->invoice->name);
        $this->assertSame([], $result->errors);
    }

    public function test_from_array_maps_a_failed_invoice_result(): void
    {
        $notification = InvoiceBatchNotification::fromArray([
            'id' => 'ea6186c4-a11f-4694-90a4-c01b9785e9d2',
            'invoices' => [
                [
                    'status_code' => '400',
                    'idempotency_key' => '1032492955',
                    'error' => [
                        'status' => 400,
                        'errors' => [[
                            'code' => 'invalid_total_payments',
                            'message' => 'The total payments must be equal to the total invoice.',
                            'params' => ['payments'],
                            'detail' => 'Check the API documentation',
                        ]],
                    ],
                ],
            ],
        ]);

        $result = $notification->results[0];
        $this->assertFalse($result->successful());
        $this->assertNull($result->invoice);
        $this->assertCount(1, $result->errors);
        $this->assertSame('invalid_total_payments', $result->errors[0]->code);
        $this->assertSame(['payments'], $result->errors[0]->params);
    }

    public function test_from_array_tolerates_a_minimal_payload(): void
    {
        $notification = InvoiceBatchNotification::fromArray(['id' => 'abc']);

        $this->assertSame('abc', $notification->id);
        $this->assertSame([], $notification->results);
    }
}
