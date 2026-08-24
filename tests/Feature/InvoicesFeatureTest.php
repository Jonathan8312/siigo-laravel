<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\Tests\Feature;

use Illuminate\Support\Facades\Http;
use Jonathan8312\Siigo\DataTransferObjects\Invoices\CustomerRef;
use Jonathan8312\Siigo\DataTransferObjects\Invoices\DocumentRef;
use Jonathan8312\Siigo\DataTransferObjects\Invoices\InvoiceData;
use Jonathan8312\Siigo\DataTransferObjects\Invoices\InvoiceItem;
use Jonathan8312\Siigo\DataTransferObjects\Invoices\InvoicePayment;
use Jonathan8312\Siigo\Exceptions\ValidationException;
use Jonathan8312\Siigo\Siigo;
use Jonathan8312\Siigo\Tests\TestCase;

final class InvoicesFeatureTest extends TestCase
{
    public function test_invoices_resolves_through_the_container_and_creates_an_invoice(): void
    {
        Http::fake([
            'https://siigo.test/auth' => Http::response(['access_token' => 'jwt-value', 'expires_in' => 86400], 200),
            'https://siigo.test/v1/invoices' => Http::response(['id' => 'abc-123', 'name' => 'FV-1-1'], 201),
        ]);

        $siigo = $this->app()->make(Siigo::class);

        $invoice = $siigo->invoices()->create(new InvoiceData(
            document: new DocumentRef(22),
            date: '2021-10-15',
            customer: new CustomerRef('13832081'),
            seller: 629,
            items: [new InvoiceItem(code: 'Item-1', quantity: 1, price: 100)],
            payments: [new InvoicePayment(5636, 100)],
        ));

        $this->assertSame('abc-123', $invoice->id);
    }

    public function test_invoices_maps_a_validation_error(): void
    {
        Http::fake([
            'https://siigo.test/auth' => Http::response(['access_token' => 'jwt-value', 'expires_in' => 86400], 200),
            'https://siigo.test/v1/invoices' => Http::response([
                'Status' => 400,
                'Errors' => [['Code' => 'parameter_required', 'Message' => 'seller is required', 'Params' => ['seller'], 'Detail' => null]],
            ], 400),
        ]);

        $siigo = $this->app()->make(Siigo::class);

        try {
            $siigo->invoices()->create(new InvoiceData(
                document: new DocumentRef(22),
                date: '2021-10-15',
                customer: new CustomerRef('13832081'),
                seller: 0,
                items: [new InvoiceItem(code: 'Item-1', quantity: 1, price: 100)],
                payments: [new InvoicePayment(5636, 100)],
            ));
            $this->fail('Expected a ValidationException.');
        } catch (ValidationException $exception) {
            $this->assertSame('parameter_required', $exception->errorCode());
        }
    }
}
