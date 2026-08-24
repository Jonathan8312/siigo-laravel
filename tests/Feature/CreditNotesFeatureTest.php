<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\Tests\Feature;

use Illuminate\Support\Facades\Http;
use Jonathan8312\Siigo\DataTransferObjects\CreditNotes\CreditNoteData;
use Jonathan8312\Siigo\DataTransferObjects\CreditNotes\CreditNoteItem;
use Jonathan8312\Siigo\DataTransferObjects\CreditNotes\CreditNotePayment;
use Jonathan8312\Siigo\DataTransferObjects\CreditNotes\DocumentRef;
use Jonathan8312\Siigo\Exceptions\ValidationException;
use Jonathan8312\Siigo\Siigo;
use Jonathan8312\Siigo\Tests\TestCase;

final class CreditNotesFeatureTest extends TestCase
{
    public function test_credit_notes_resolves_through_the_container_and_creates_a_credit_note(): void
    {
        Http::fake([
            'https://siigo.test/auth' => Http::response(['access_token' => 'jwt-value', 'expires_in' => 86400], 200),
            'https://siigo.test/v1/credit-notes' => Http::response(['id' => 'abc-123', 'name' => 'NC-2-22'], 201),
        ]);

        $siigo = $this->app()->make(Siigo::class);

        $creditNote = $siigo->creditNotes()->create(new CreditNoteData(
            document: new DocumentRef(22),
            date: '2021-10-15',
            reason: 1,
            items: [new CreditNoteItem(code: 'Item-1', quantity: 1, price: 100)],
            payments: [new CreditNotePayment(5636, 100)],
            invoice: '63f918c2-ca65-4edc-a7db-66bcdd5159fb',
        ));

        $this->assertSame('abc-123', $creditNote->id);
    }

    public function test_credit_notes_maps_a_validation_error(): void
    {
        Http::fake([
            'https://siigo.test/auth' => Http::response(['access_token' => 'jwt-value', 'expires_in' => 86400], 200),
            'https://siigo.test/v1/credit-notes' => Http::response([
                'Status' => 400,
                'Errors' => [['Code' => 'document_settings', 'Message' => 'The document.id cannot be used, you must verify the document settings', 'Params' => [], 'Detail' => null]],
            ], 400),
        ]);

        $siigo = $this->app()->make(Siigo::class);

        try {
            $siigo->creditNotes()->create(new CreditNoteData(
                document: new DocumentRef(0),
                date: '2021-10-15',
                reason: 1,
                items: [new CreditNoteItem(code: 'Item-1', quantity: 1, price: 100)],
                payments: [new CreditNotePayment(5636, 100)],
                invoice: '63f918c2-ca65-4edc-a7db-66bcdd5159fb',
            ));
            $this->fail('Expected a ValidationException.');
        } catch (ValidationException $exception) {
            $this->assertSame('document_settings', $exception->errorCode());
        }
    }
}
